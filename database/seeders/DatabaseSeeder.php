<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\EducationType;
use App\Enums\ResumeSectionType;
use App\Enums\SkillCategory;
use App\Enums\SkillProficiency;
use App\Models\Accomplishment;
use App\Models\AiInteraction;
use App\Models\Application;
use App\Models\ApplicationNote;
use App\Models\ApplicationStatusChange;
use App\Models\EducationEntry;
use App\Models\EvidenceEntry;
use App\Models\Experience;
use App\Models\GapAnalysis;
use App\Models\IdealCandidateProfile;
use App\Models\JobPosting;
use App\Models\ProfessionalIdentity;
use App\Models\Project;
use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\ResumeSectionVariant;
use App\Models\Skill;
use App\Models\Tag;
use App\Models\TransparencyPage;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Experience Library
        $skills = collect([
            ['name' => 'PHP', 'category' => SkillCategory::Technical, 'proficiency' => SkillProficiency::Expert],
            ['name' => 'Laravel', 'category' => SkillCategory::Technical, 'proficiency' => SkillProficiency::Expert],
            ['name' => 'JavaScript', 'category' => SkillCategory::Technical, 'proficiency' => SkillProficiency::Advanced],
            ['name' => 'React', 'category' => SkillCategory::Technical, 'proficiency' => SkillProficiency::Intermediate],
            ['name' => 'SQL', 'category' => SkillCategory::Technical, 'proficiency' => SkillProficiency::Advanced],
            ['name' => 'Docker', 'category' => SkillCategory::Tool, 'proficiency' => SkillProficiency::Intermediate],
            ['name' => 'Git', 'category' => SkillCategory::Tool, 'proficiency' => SkillProficiency::Advanced],
            ['name' => 'Leadership', 'category' => SkillCategory::Soft, 'proficiency' => SkillProficiency::Advanced],
            ['name' => 'Agile', 'category' => SkillCategory::Methodology, 'proficiency' => SkillProficiency::Advanced],
        ])->map(fn (array $data) => Skill::create([...$data, 'user_id' => $user->id]));

        $tags = collect(['backend', 'frontend', 'devops', 'leadership', 'api-design'])
            ->map(fn (string $name) => Tag::create(['user_id' => $user->id, 'name' => $name]));

        $experience1 = Experience::factory()->create([
            'user_id' => $user->id,
            'company' => 'Acme Corp',
            'title' => 'Senior Software Engineer',
            'started_at' => '2021-03-01',
            'ended_at' => null,
            'is_current' => true,
            'sort_order' => 0,
        ]);

        $experience2 = Experience::factory()->create([
            'user_id' => $user->id,
            'company' => 'TechStart Inc',
            'title' => 'Software Engineer',
            'started_at' => '2018-06-01',
            'ended_at' => '2021-02-28',
            'is_current' => false,
            'sort_order' => 1,
        ]);

        $experience1->skills()->attach($skills->whereIn('name', ['PHP', 'Laravel', 'React', 'Docker'])->pluck('id'));
        $experience2->skills()->attach($skills->whereIn('name', ['PHP', 'JavaScript', 'SQL', 'Git'])->pluck('id'));

        $experience1->tags()->attach($tags->whereIn('name', ['backend', 'leadership'])->pluck('id'));

        $accomplishment1 = Accomplishment::factory()->create([
            'user_id' => $user->id,
            'experience_id' => $experience1->id,
            'title' => 'Led migration to microservices architecture',
            'description' => 'Designed and executed the migration of a monolithic application to microservices, reducing deployment times by 70%.',
            'impact' => 'Reduced deployment time from 45 minutes to 12 minutes.',
            'sort_order' => 0,
        ]);

        Accomplishment::factory()->create([
            'user_id' => $user->id,
            'experience_id' => $experience2->id,
            'title' => 'Built real-time notification system',
            'description' => 'Implemented WebSocket-based notification system serving 50k concurrent users.',
            'sort_order' => 0,
        ]);

        $accomplishment1->skills()->attach($skills->whereIn('name', ['PHP', 'Docker'])->pluck('id'));

        Project::factory()->create([
            'user_id' => $user->id,
            'experience_id' => $experience1->id,
            'name' => 'Customer Portal Redesign',
            'description' => 'Complete overhaul of customer-facing portal with React frontend and Laravel API.',
            'role' => 'Tech Lead',
            'sort_order' => 0,
        ]);

        ProfessionalIdentity::factory()->create([
            'user_id' => $user->id,
            'values' => 'Clean code, continuous improvement, mentoring junior developers.',
            'philosophy' => 'Build software that empowers users and stands the test of time.',
        ]);

        EducationEntry::factory()->create([
            'user_id' => $user->id,
            'type' => EducationType::Degree,
            'institution' => 'State University',
            'title' => 'Bachelor of Science',
            'field' => 'Computer Science',
            'completed_at' => '2018-05-15',
            'sort_order' => 0,
        ]);

        EvidenceEntry::factory()->create([
            'user_id' => $user->id,
            'type' => 'repository',
            'title' => 'Open Source Laravel Package',
            'url' => 'https://github.com/example/laravel-package',
            'description' => 'A popular Laravel package with 500+ stars.',
        ]);

        // Job Analysis
        $jobPosting = JobPosting::factory()->analyzed()->create([
            'user_id' => $user->id,
            'title' => 'Staff Software Engineer',
            'company' => 'BigTech Inc',
            'raw_text' => 'We are looking for a Staff Software Engineer with 8+ years experience in PHP/Laravel, React, and cloud infrastructure...',
        ]);

        $idealProfile = IdealCandidateProfile::factory()->create([
            'job_posting_id' => $jobPosting->id,
        ]);

        // Gap Analysis
        $gapAnalysis = GapAnalysis::factory()->create([
            'user_id' => $user->id,
            'ideal_candidate_profile_id' => $idealProfile->id,
            'overall_match_score' => 78,
        ]);

        // Resume
        $resume = Resume::factory()->create([
            'user_id' => $user->id,
            'gap_analysis_id' => $gapAnalysis->id,
            'job_posting_id' => $jobPosting->id,
            'title' => 'Resume for BigTech Inc - Staff Engineer',
        ]);

        $summarySection = ResumeSection::factory()->create([
            'resume_id' => $resume->id,
            'type' => ResumeSectionType::Summary,
            'title' => 'Professional Summary',
            'sort_order' => 0,
        ]);

        $variant = ResumeSectionVariant::factory()->create([
            'resume_section_id' => $summarySection->id,
            'label' => 'Balanced',
            'content' => 'Experienced software engineer with 8+ years building scalable web applications...',
            'sort_order' => 0,
        ]);

        $summarySection->update(['selected_variant_id' => $variant->id]);

        ResumeSectionVariant::factory()->create([
            'resume_section_id' => $summarySection->id,
            'label' => 'Technical Focus',
            'content' => 'Full-stack engineer specializing in PHP/Laravel and React with deep expertise in distributed systems...',
            'sort_order' => 1,
        ]);

        // Application
        $application = Application::factory()->create([
            'user_id' => $user->id,
            'job_posting_id' => $jobPosting->id,
            'resume_id' => $resume->id,
            'status' => ApplicationStatus::Applied,
            'applied_at' => now()->subDays(5),
            'company' => 'BigTech Inc',
            'role' => 'Staff Software Engineer',
        ]);

        ApplicationStatusChange::factory()->create([
            'application_id' => $application->id,
            'from_status' => null,
            'to_status' => ApplicationStatus::Draft,
        ]);

        ApplicationStatusChange::factory()->create([
            'application_id' => $application->id,
            'from_status' => ApplicationStatus::Draft,
            'to_status' => ApplicationStatus::Applied,
        ]);

        ApplicationNote::factory()->create([
            'application_id' => $application->id,
            'content' => 'Submitted application via company portal. Referral from John.',
        ]);

        // Transparency Page
        TransparencyPage::factory()->create([
            'user_id' => $user->id,
            'application_id' => $application->id,
        ]);

        // AI Interactions
        AiInteraction::factory()->create([
            'user_id' => $user->id,
            'interactable_id' => $jobPosting->id,
            'interactable_type' => JobPosting::class,
            'purpose' => 'job_analysis',
        ]);

        AiInteraction::factory()->create([
            'user_id' => $user->id,
            'interactable_id' => $gapAnalysis->id,
            'interactable_type' => GapAnalysis::class,
            'purpose' => 'gap_analysis',
        ]);
    }
}
