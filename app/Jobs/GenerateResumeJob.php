<?php

namespace App\Jobs;

use App\Ai\Agents\ResumeGenerator;
use App\Concerns\ConfiguresAiForUser;
use App\Enums\AiPurpose;
use App\Enums\EducationType;
use App\Enums\ResumeSectionType;
use App\Models\Resume;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateResumeJob implements ShouldQueue
{
    use ConfiguresAiForUser, Queueable;

    public int $timeout = 300;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public Resume $resume,
    ) {}

    public function handle(): void
    {
        $gapAnalysis = $this->resume->gapAnalysis;
        $profile = $gapAnalysis->idealCandidateProfile;
        $jobPosting = $profile->jobPosting;
        $user = $this->resume->user;

        $this->configureAiForUser($user, AiPurpose::ResumeGeneration);

        $library = [
            'experiences' => $user->experiences()->with(['accomplishments', 'projects', 'skills'])->get()->toArray(),
            'skills' => $user->skills()->get()->toArray(),
            'education' => $user->educationEntries()->get()->toArray(),
            'identity' => $user->professionalIdentity?->toArray(),
        ];

        $sectionTypes = [
            ResumeSectionType::Summary,
            ResumeSectionType::Experience,
            ResumeSectionType::Skills,
            ResumeSectionType::Education,
        ];

        $certTypes = [EducationType::Certification, EducationType::License];
        if ($user->educationEntries()->whereIn('type', $certTypes)->exists()) {
            $sectionTypes[] = ResumeSectionType::Certifications;
            $library['certifications'] = $user->educationEntries()->whereIn('type', $certTypes)->get()->toArray();
        }

        $pubTypes = [EducationType::Publication, EducationType::Patent, EducationType::SpeakingEngagement];
        if ($user->educationEntries()->whereIn('type', $pubTypes)->exists()) {
            $sectionTypes[] = ResumeSectionType::Publications;
            $library['publications'] = $user->educationEntries()->whereIn('type', $pubTypes)->get()->toArray();
        }

        if ($user->projects()->exists()) {
            $totalProjects = $user->projects()->count();
            $linkedProjects = $user->projects()->whereNotNull('experience_id')->count();

            if ($totalProjects > $linkedProjects) {
                $sectionTypes[] = ResumeSectionType::Projects;
                $library['projects'] = $user->projects()->with('skills')->get()->toArray();
            }
        }

        $expectedSections = array_map(fn ($type) => ucfirst($type->value), $sectionTypes);
        $this->resume->update([
            'generation_status' => 'generating',
            'generation_progress' => [
                'total' => count($sectionTypes),
                'completed' => 0,
                'current_section' => 'All sections',
                'expected_sections' => $expectedSections,
            ],
        ]);

        $blockTypes = [ResumeSectionType::Experience, ResumeSectionType::Education, ResumeSectionType::Projects];

        try {
            $prompt = view('prompts.resume-full', [
                'jobTitle' => $jobPosting->title ?? 'Target Role',
                'company' => $jobPosting->company ?? 'Target Company',
                'requirements' => $profile->required_skills ?? [],
                'gapInsights' => [
                    'strengths' => $gapAnalysis->strengths ?? [],
                    'gaps' => $gapAnalysis->gaps ?? [],
                ],
                'experience' => $library,
                'languageGuidance' => $profile->language_guidance ?? [],
                'sectionTypes' => array_map(fn ($type) => $type->value, $sectionTypes),
            ])->render();

            $response = (new ResumeGenerator)->prompt($prompt);

            $sections = $response['sections'] ?? [];

            if (is_string($sections)) {
                $decoded = json_decode($sections, true);
                $sections = is_array($decoded) ? $decoded : [];
            }

            if (! is_array($sections) || empty($sections)) {
                Log::error('ResumeGenerator returned invalid sections', [
                    'response_class' => get_class($response),
                    'response_preview' => substr((string) $response, 0, 500),
                    'sections_type' => gettype($response['sections'] ?? null),
                ]);

                throw new \RuntimeException(
                    'Resume generation returned invalid sections: expected array, got '.gettype($response['sections'] ?? null)
                );
            }

            $sectionOrder = [];
            $sectionTypeMap = collect($sectionTypes)->keyBy(fn ($type) => $type->value);

            foreach ($sections as $index => $sectionData) {
                $typeValue = $sectionData['type'] ?? null;
                $sectionType = $sectionTypeMap->get($typeValue);

                if (! $sectionType) {
                    Log::warning("Skipping unknown section type: {$typeValue}");

                    continue;
                }

                $section = $this->resume->sections()->create([
                    'type' => $sectionType,
                    'title' => ucfirst($sectionType->value),
                    'sort_order' => $index,
                ]);

                $variants = $sectionData['variants'] ?? [];
                if (is_string($variants)) {
                    $decoded = json_decode($variants, true);
                    $variants = is_array($decoded) ? $decoded : [];
                }

                $firstVariant = null;

                foreach ($variants as $vIndex => $variant) {
                    $blocks = in_array($sectionType, $blockTypes) ? ($variant['blocks'] ?? null) : null;
                    $content = $blocks
                        ? collect($blocks)->pluck('content')->implode("\n\n")
                        : ($variant['content'] ?? '');

                    if ($blocks) {
                        $blocks = array_map(function ($block, $i) {
                            return [
                                'key' => str($block['label'] ?? 'block-'.$i)->slug()->toString(),
                                'label' => $block['label'] ?? 'Block '.($i + 1),
                                'content' => $block['content'] ?? '',
                                'is_hidden' => false,
                            ];
                        }, $blocks, array_keys($blocks));
                    }

                    $created = $section->variants()->create([
                        'label' => $variant['label'] ?? 'Variant '.($vIndex + 1),
                        'content' => $content,
                        'compact_content' => $variant['compact_content'] ?? null,
                        'blocks' => $blocks,
                        'emphasis' => $variant['emphasis'] ?? null,
                        'is_ai_generated' => true,
                        'is_user_edited' => false,
                        'sort_order' => $vIndex,
                    ]);

                    if ($firstVariant === null) {
                        $firstVariant = $created;
                    }
                }

                if ($firstVariant) {
                    $section->update(['selected_variant_id' => $firstVariant->id]);
                }

                $sectionOrder[] = $section->id;

                $this->resume->update([
                    'generation_progress' => [
                        'total' => count($sectionTypes),
                        'completed' => $index + 1,
                        'current_section' => isset($sections[$index + 1])
                            ? ucfirst($sections[$index + 1]['type'] ?? 'unknown')
                            : null,
                        'expected_sections' => $expectedSections,
                    ],
                ]);
            }

            $this->resume->update([
                'section_order' => $sectionOrder,
                'generation_status' => 'completed',
                'generation_progress' => null,
            ]);

            $this->chargeAiUsage($user, AiPurpose::ResumeGeneration);
        } catch (\Throwable $e) {
            $this->resume->update([
                'generation_status' => 'failed',
                'section_order' => [],
            ]);

            throw $e;
        }
    }
}
