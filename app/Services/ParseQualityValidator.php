<?php

namespace App\Services;

use App\Support\ParseQualityResult;

class ParseQualityValidator
{
    private const RESUME_THRESHOLD = 0.6;

    private const JOB_ANALYSIS_THRESHOLD = 0.5;

    private const LINK_INDEX_THRESHOLD = 0.5;

    public function validateResumeParse(array $result, int $inputLength): ParseQualityResult
    {
        if ($inputLength < 100) {
            return new ParseQualityResult(
                score: 0.0,
                passed: false,
                failedRules: [],
                retryHint: '',
                inputTooShort: true,
            );
        }

        $rules = [];
        $failed = [];

        $experiences = $result['experiences'] ?? [];
        $skills = $result['skills'] ?? [];
        $accomplishments = $result['accomplishments'] ?? [];
        $projects = $result['projects'] ?? [];

        // Rule: at least 1 experience
        $rules[] = 'has_experiences';
        if (count($experiences) < 1) {
            $failed[] = 'has_experiences';
        }

        // Rule: at least 3 skills
        $rules[] = 'has_skills';
        if (count($skills) < 3) {
            $failed[] = 'has_skills';
        }

        // Rule: at least 1 accomplishment or project
        $rules[] = 'has_accomplishments_or_projects';
        if (count($accomplishments) + count($projects) < 1) {
            $failed[] = 'has_accomplishments_or_projects';
        }

        // Rule: each experience has company and title
        $rules[] = 'experiences_have_required_fields';
        foreach ($experiences as $exp) {
            if (empty($exp['company']) || empty($exp['title'])) {
                $failed[] = 'experiences_have_required_fields';
                break;
            }
        }

        // Rule: longer inputs should yield more experiences
        if ($inputLength > 500) {
            $rules[] = 'sufficient_experiences_for_input';
            if (count($experiences) < 2) {
                $failed[] = 'sufficient_experiences_for_input';
            }
        }

        return $this->buildResult($rules, $failed, self::RESUME_THRESHOLD, [
            'has_experiences' => 'work experiences (company, title, dates)',
            'has_skills' => 'technical and professional skills',
            'has_accomplishments_or_projects' => 'accomplishments or projects with descriptions',
            'experiences_have_required_fields' => 'company name and job title for each experience',
            'sufficient_experiences_for_input' => 'all work experiences (the source text is substantial)',
        ]);
    }

    public function validateJobAnalysis(array $result, int $inputLength): ParseQualityResult
    {
        if ($inputLength < 200) {
            return new ParseQualityResult(
                score: 0.0,
                passed: false,
                failedRules: [],
                retryHint: '',
                inputTooShort: true,
            );
        }

        $rules = [];
        $failed = [];

        // Rule: title present
        $rules[] = 'has_title';
        if (empty($result['title'])) {
            $failed[] = 'has_title';
        }

        // Rule: at least 1 required skill
        $rules[] = 'has_required_skills';
        if (count($result['required_skills'] ?? []) < 1) {
            $failed[] = 'has_required_skills';
        }

        // Rule: candidate summary is meaningful
        $rules[] = 'has_candidate_summary';
        if (mb_strlen($result['candidate_summary'] ?? '') < 50) {
            $failed[] = 'has_candidate_summary';
        }

        // Rule: experience profile has data
        $rules[] = 'has_experience_profile';
        $profile = $result['experience_profile'] ?? [];
        if (empty(array_filter($profile))) {
            $failed[] = 'has_experience_profile';
        }

        // Rule: language guidance key terms
        $rules[] = 'has_key_terms';
        $keyTerms = $result['language_guidance']['key_terms'] ?? [];
        if (count($keyTerms) < 1) {
            $failed[] = 'has_key_terms';
        }

        // Rule: longer inputs should yield more skills
        if ($inputLength > 300) {
            $rules[] = 'sufficient_skills_for_input';
            $totalSkills = count($result['required_skills'] ?? []) + count($result['preferred_skills'] ?? []);
            if ($totalSkills < 3) {
                $failed[] = 'sufficient_skills_for_input';
            }
        }

        return $this->buildResult($rules, $failed, self::JOB_ANALYSIS_THRESHOLD, [
            'has_title' => 'the job title',
            'has_required_skills' => 'required skills with importance levels',
            'has_candidate_summary' => 'a detailed candidate summary (at least 2-3 sentences)',
            'has_experience_profile' => 'experience requirements (years, industries, project types)',
            'has_key_terms' => 'key terms and phrases from the posting for language guidance',
            'sufficient_skills_for_input' => 'all required and preferred skills (the posting is detailed)',
        ]);
    }

    public function validateLinkIndex(array $result, int $inputLength): ParseQualityResult
    {
        $skills = $result['skills'] ?? [];
        $accomplishments = $result['accomplishments'] ?? [];
        $projects = $result['projects'] ?? [];
        $combinedCount = count($skills) + count($accomplishments) + count($projects);

        if ($inputLength < 200 && $combinedCount === 0) {
            return new ParseQualityResult(
                score: 0.0,
                passed: false,
                failedRules: [],
                retryHint: '',
                inputTooShort: true,
            );
        }

        $rules = [];
        $failed = [];

        // Rule: extracted something
        $rules[] = 'has_any_content';
        if ($combinedCount < 1) {
            $failed[] = 'has_any_content';
        }

        // Rule: at least one item with a meaningful description
        $rules[] = 'has_meaningful_description';
        $hasDescription = false;
        foreach ([...$accomplishments, ...$projects] as $item) {
            if (mb_strlen($item['description'] ?? '') >= 20) {
                $hasDescription = true;
                break;
            }
        }
        if (! $hasDescription) {
            $failed[] = 'has_meaningful_description';
        }

        return $this->buildResult($rules, $failed, self::LINK_INDEX_THRESHOLD, [
            'has_any_content' => 'skills, accomplishments, or projects from the page content',
            'has_meaningful_description' => 'detailed descriptions (not just titles) for accomplishments or projects',
        ]);
    }

    /**
     * @param  list<string>  $rules
     * @param  list<string>  $failed
     * @param  array<string, string>  $hintMap
     */
    private function buildResult(array $rules, array $failed, float $threshold, array $hintMap): ParseQualityResult
    {
        $score = count($rules) > 0
            ? (count($rules) - count($failed)) / count($rules)
            : 0.0;

        $retryHint = '';
        if (! empty($failed)) {
            $missing = array_map(fn (string $rule) => $hintMap[$rule] ?? $rule, $failed);
            $retryHint = 'The initial parse was incomplete. The following information was NOT extracted but should be present in the source text: '
                .implode('; ', $missing)
                .'. Please re-analyze the text carefully and extract ALL available information.';
        }

        return new ParseQualityResult(
            score: round($score, 2),
            passed: $score >= $threshold,
            failedRules: $failed,
            retryHint: $retryHint,
            inputTooShort: false,
        );
    }
}
