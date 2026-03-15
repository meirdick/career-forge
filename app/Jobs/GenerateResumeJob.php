<?php

namespace App\Jobs;

use App\Ai\Agents\ResumeGenerator;
use App\Ai\Agents\SectionResumeGenerator;
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
        $context = $this->prepareContext();

        $this->resume->update([
            'generation_status' => 'generating',
            'generation_progress' => [
                'total' => count($context['sectionTypes']),
                'completed' => 0,
                'current_section' => $context['expectedSections'][0] ?? null,
                'expected_sections' => $context['expectedSections'],
            ],
        ]);

        try {
            $sectionOrder = match (config('ai.resume_strategy')) {
                'per_section' => $this->generatePerSection($context),
                default => $this->generateFull($context),
            };

            $this->resume->update([
                'section_order' => $sectionOrder,
                'generation_status' => 'completed',
                'generation_progress' => null,
            ]);

            $this->chargeAiUsage($context['user'], AiPurpose::ResumeGeneration);
        } catch (\Throwable $e) {
            $this->resume->update([
                'generation_status' => 'failed',
                'section_order' => [],
            ]);

            throw $e;
        }
    }

    /**
     * Gather all data needed for resume generation.
     *
     * @return array{user: \App\Models\User, library: array, sectionTypes: list<ResumeSectionType>, expectedSections: list<string>, blockTypes: list<ResumeSectionType>, jobPosting: \App\Models\JobPosting, profile: \App\Models\IdealCandidateProfile, gapAnalysis: \App\Models\GapAnalysis}
     */
    private function prepareContext(): array
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

        return [
            'user' => $user,
            'library' => $this->trimLibraryData($library),
            'sectionTypes' => $sectionTypes,
            'expectedSections' => array_map(fn ($type) => ucfirst($type->value), $sectionTypes),
            'blockTypes' => [ResumeSectionType::Experience, ResumeSectionType::Education, ResumeSectionType::Projects],
            'jobPosting' => $jobPosting,
            'profile' => $profile,
            'gapAnalysis' => $gapAnalysis,
        ];
    }

    /**
     * Generate all sections in a single AI call.
     *
     * @return list<int>
     */
    private function generateFull(array $context): array
    {
        $prompt = view('prompts.resume-full', [
            'jobTitle' => $context['jobPosting']->title ?? 'Target Role',
            'company' => $context['jobPosting']->company ?? 'Target Company',
            'requirements' => $context['profile']->required_skills ?? [],
            'gapInsights' => [
                'strengths' => $context['gapAnalysis']->strengths ?? [],
                'gaps' => $context['gapAnalysis']->gaps ?? [],
            ],
            'experience' => $context['library'],
            'languageGuidance' => $context['profile']->language_guidance ?? [],
            'sectionTypes' => array_map(fn ($type) => $type->value, $context['sectionTypes']),
        ])->render();

        $response = (new ResumeGenerator)->prompt($prompt);

        $sections = $response['sections'] ?? [];

        if (is_string($sections)) {
            $decoded = json_decode($sections, true);
            $sections = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($sections) || empty($sections)) {
            Log::error('ResumeGenerator full strategy returned invalid sections', [
                'response_class' => get_class($response),
                'response_preview' => substr((string) $response, 0, 500),
                'sections_type' => gettype($response['sections'] ?? null),
            ]);

            throw new \RuntimeException(
                'Resume generation returned invalid sections: expected array, got '.gettype($response['sections'] ?? null)
            );
        }

        $sectionOrder = [];
        $sectionTypeMap = collect($context['sectionTypes'])->keyBy(fn ($type) => $type->value);

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

            $this->createVariants($section, $sectionType, $sectionData['variants'] ?? [], $context['blockTypes']);

            $sectionOrder[] = $section->id;

            $this->resume->update([
                'generation_progress' => [
                    'total' => count($context['sectionTypes']),
                    'completed' => $index + 1,
                    'current_section' => isset($sections[$index + 1])
                        ? ucfirst($sections[$index + 1]['type'] ?? 'unknown')
                        : null,
                    'expected_sections' => $context['expectedSections'],
                ],
            ]);
        }

        return $sectionOrder;
    }

    /**
     * Generate each section with its own AI call.
     *
     * @return list<int>
     */
    private function generatePerSection(array $context): array
    {
        $sectionOrder = [];
        $generatedExperienceContent = null;

        // Render shared context once — goes into the system prompt (cached by Anthropic)
        $sharedContext = view('prompts.resume-section-context', [
            'jobTitle' => $context['jobPosting']->title ?? 'Target Role',
            'company' => $context['jobPosting']->company ?? 'Target Company',
            'requirements' => $context['profile']->required_skills ?? [],
            'gapInsights' => [
                'strengths' => $context['gapAnalysis']->strengths ?? [],
                'gaps' => $context['gapAnalysis']->gaps ?? [],
            ],
            'experience' => $context['library'],
            'languageGuidance' => $context['profile']->language_guidance ?? [],
        ])->render();

        $agent = (new SectionResumeGenerator)->withSharedContext($sharedContext);

        foreach ($context['sectionTypes'] as $index => $type) {
            $promptData = [
                'sectionType' => $type->value,
            ];

            if ($type === ResumeSectionType::Projects && $generatedExperienceContent) {
                $promptData['experienceContent'] = $generatedExperienceContent;
            }

            $prompt = view('prompts.resume-section', $promptData)->render();

            $response = $agent->prompt($prompt);

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

            $firstVariant = $this->createVariants($section, $type, $variants, $context['blockTypes']);

            if ($type === ResumeSectionType::Experience && $firstVariant) {
                $generatedExperienceContent = $firstVariant->content;
            }

            $sectionOrder[] = $section->id;

            // Brief pause between sections to avoid provider rate limits
            if (isset($context['sectionTypes'][$index + 1])) {
                sleep(3);
            }

            $this->resume->update([
                'generation_progress' => [
                    'total' => count($context['sectionTypes']),
                    'completed' => $index + 1,
                    'current_section' => isset($context['sectionTypes'][$index + 1])
                        ? ucfirst($context['sectionTypes'][$index + 1]->value)
                        : null,
                    'expected_sections' => $context['expectedSections'],
                ],
            ]);
        }

        return $sectionOrder;
    }

    /**
     * Strip DB metadata fields from library data to reduce prompt token usage.
     */
    private function trimLibraryData(array $library): array
    {
        $stripKeys = ['id', 'user_id', 'experience_id', 'skill_id', 'project_id', 'created_at', 'updated_at', 'pivot', 'formatted_description'];

        $stripRecursive = function (array $data) use (&$stripRecursive, $stripKeys): array {
            $result = [];
            foreach ($data as $key => $value) {
                if (in_array($key, $stripKeys, true)) {
                    continue;
                }
                $result[$key] = is_array($value) ? $stripRecursive($value) : $value;
            }

            return $result;
        };

        return $stripRecursive($library);
    }

    /**
     * Return only the library data relevant to a given section type.
     */
    private function libraryForSection(ResumeSectionType $type, array $library): array
    {
        return match ($type) {
            ResumeSectionType::Summary => $library,
            ResumeSectionType::Experience => array_intersect_key($library, array_flip(['experiences', 'identity'])),
            ResumeSectionType::Skills => [
                'skills' => $library['skills'] ?? [],
                'experiences' => array_map(fn ($exp) => [
                    'company' => $exp['company'] ?? null,
                    'title' => $exp['title'] ?? null,
                    'skills' => $exp['skills'] ?? [],
                ], $library['experiences'] ?? []),
            ],
            ResumeSectionType::Education => array_intersect_key($library, array_flip(['education'])),
            ResumeSectionType::Certifications => array_intersect_key($library, array_flip(['certifications'])),
            ResumeSectionType::Publications => array_intersect_key($library, array_flip(['publications'])),
            ResumeSectionType::Projects => array_intersect_key($library, array_flip(['projects', 'experiences'])),
        };
    }

    /**
     * Create variant records for a section from the AI response data.
     *
     * @return \App\Models\ResumeSectionVariant|null The first variant created.
     */
    private function createVariants(
        \App\Models\ResumeSection $section,
        ResumeSectionType $sectionType,
        array $variants,
        array $blockTypes,
    ): ?\App\Models\ResumeSectionVariant {
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

        return $firstVariant;
    }
}
