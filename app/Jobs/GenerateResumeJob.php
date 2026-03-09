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

        if ($user->projects()->exists()) {
            $sectionTypes[] = ResumeSectionType::Projects;
            $library['projects'] = $user->projects()->with('skills')->get()->toArray();
        }

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

        $sectionOrder = [];

        foreach ($sectionTypes as $index => $type) {
            $prompt = view('prompts.resume-section', [
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
            ])->render();

            $response = (new ResumeGenerator)->prompt($prompt);

            $section = $this->resume->sections()->create([
                'type' => $type,
                'title' => ucfirst($type->value),
                'sort_order' => $index,
            ]);

            $variants = $response['variants'] ?? [];
            $firstVariant = null;

            foreach ($variants as $vIndex => $variant) {
                $created = $section->variants()->create([
                    'label' => $variant['label'] ?? 'Variant '.($vIndex + 1),
                    'content' => $variant['content'] ?? '',
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
        }

        $this->resume->update(['section_order' => $sectionOrder]);

        $this->chargeAiUsage($user, AiPurpose::ResumeGeneration);
    }
}
