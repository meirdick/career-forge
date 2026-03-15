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

        // Skip Projects section if all user projects belong to an experience
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
                'current_section' => $expectedSections[0] ?? null,
                'expected_sections' => $expectedSections,
            ],
        ]);

        $sectionOrder = [];
        $blockTypes = [ResumeSectionType::Experience, ResumeSectionType::Education, ResumeSectionType::Projects];
        $generatedExperienceContent = null;

        try {
            foreach ($sectionTypes as $index => $type) {
                $promptData = [
                    'sectionType' => $type->value,
                    'jobTitle' => $jobPosting->title ?? 'Target Role',
                    'company' => $jobPosting->company ?? 'Target Company',
                    'requirements' => $profile->required_skills ?? [],
                    'gapInsights' => [
                        'strengths' => $gapAnalysis->strengths ?? [],
                        'gaps' => $gapAnalysis->gaps ?? [],
                    ],
                    'experience' => $library,
                    'languageGuidance' => $profile->language_guidance ?? [],
                ];

                if ($type === ResumeSectionType::Projects && $generatedExperienceContent) {
                    $promptData['experienceContent'] = $generatedExperienceContent;
                }

                $prompt = view('prompts.resume-section', $promptData)->render();

                $response = (new ResumeGenerator)->prompt($prompt);

                Log::info('ResumeGenerator response debug', [
                    'section' => $type->value,
                    'response_class' => get_class($response),
                    'response_text' => substr((string) $response, 0, 500),
                    'variants_type' => gettype($response['variants'] ?? null),
                    'variants_preview' => is_string($response['variants'] ?? null)
                        ? substr($response['variants'], 0, 300)
                        : json_encode(array_slice((array) ($response['variants'] ?? []), 0, 1), JSON_PRETTY_PRINT),
                ]);

                $section = $this->resume->sections()->create([
                    'type' => $type,
                    'title' => ucfirst($type->value),
                    'sort_order' => $index,
                ]);

                $variants = $response['variants'] ?? [];

                if (is_string($variants)) {
                    $decoded = json_decode($variants, true);
                    $variants = is_array($decoded) ? $decoded : [];
                }

                if (! is_array($variants) || empty($variants)) {
                    throw new \RuntimeException(
                        "Resume section [{$type->value}] returned invalid variants: expected array, got ".gettype($response['variants'] ?? null)
                    );
                }

                $firstVariant = null;

                foreach ($variants as $vIndex => $variant) {
                    $blocks = in_array($type, $blockTypes) ? ($variant['blocks'] ?? null) : null;
                    $content = $blocks
                        ? collect($blocks)->pluck('content')->implode("\n\n")
                        : ($variant['content'] ?? '');

                    // Add key and is_hidden to each block
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

                if ($type === ResumeSectionType::Experience && $firstVariant) {
                    $generatedExperienceContent = $firstVariant->content;
                }

                $sectionOrder[] = $section->id;

                // Brief pause between sections to avoid provider rate limits
                if (isset($sectionTypes[$index + 1])) {
                    sleep(3);
                }

                $this->resume->update([
                    'generation_progress' => [
                        'total' => count($sectionTypes),
                        'completed' => $index + 1,
                        'current_section' => isset($sectionTypes[$index + 1]) ? ucfirst($sectionTypes[$index + 1]->value) : null,
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
                'section_order' => $sectionOrder,
            ]);

            throw $e;
        }
    }
}
