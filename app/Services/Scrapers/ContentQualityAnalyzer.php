<?php

namespace App\Services\Scrapers;

class ContentQualityAnalyzer
{
    private const int MIN_PASSING_SIGNALS = 4;

    private const int MIN_LENGTH = 500;

    private const int MIN_UNIQUE_WORDS = 50;

    private const int MIN_JOB_KEYWORDS = 2;

    private const float MAX_BOILERPLATE_RATIO = 0.4;

    private const int MIN_HEADINGS = 2;

    private const int MIN_SENTENCES = 3;

    private const int MIN_SENTENCE_LENGTH = 20;

    /** @var list<string> */
    private const array JOB_KEYWORDS = [
        'responsibilities',
        'requirements',
        'qualifications',
        'experience',
        'salary',
        'benefits',
        'apply',
        'team',
        'role',
        'position',
        'skills',
        'about us',
    ];

    /** @var list<string> */
    private const array BOILERPLATE_PATTERNS = [
        'skip to content',
        'skip to main',
        'sign in',
        'sign up',
        'log in',
        'cookie',
        'loading',
        'copyright',
        'privacy',
        'terms of use',
        'terms of service',
        'terms and conditions',
        'all rights reserved',
        'accept cookies',
        'cookie policy',
        'privacy policy',
    ];

    public static function analyze(string $content): ContentQualityResult
    {
        $signals = [
            'length' => self::checkLength($content),
            'unique_words' => self::checkUniqueWords($content),
            'job_keywords' => self::checkJobKeywords($content),
            'low_boilerplate' => self::checkLowBoilerplate($content),
            'has_headings' => self::checkHasHeadings($content),
            'has_sentences' => self::checkHasSentences($content),
        ];

        $score = count(array_filter($signals));
        $maxScore = count($signals);
        $isValid = $score >= self::MIN_PASSING_SIGNALS;

        return new ContentQualityResult($isValid, $score, $maxScore, $signals);
    }

    public static function isJobPostingContent(string $content): bool
    {
        return self::analyze($content)->isValid;
    }

    private static function checkLength(string $content): bool
    {
        return mb_strlen(trim($content)) >= self::MIN_LENGTH;
    }

    private static function checkUniqueWords(string $content): bool
    {
        $words = preg_split('/\s+/', mb_strtolower(strip_tags($content)));

        if ($words === false) {
            return false;
        }

        $uniqueWords = array_unique(array_filter($words, fn (string $word) => mb_strlen($word) > 1));

        return count($uniqueWords) >= self::MIN_UNIQUE_WORDS;
    }

    private static function checkJobKeywords(string $content): bool
    {
        $lowerContent = mb_strtolower($content);
        $found = 0;

        foreach (self::JOB_KEYWORDS as $keyword) {
            if (str_contains($lowerContent, $keyword)) {
                $found++;
            }
        }

        return $found >= self::MIN_JOB_KEYWORDS;
    }

    private static function checkLowBoilerplate(string $content): bool
    {
        $lines = array_filter(
            explode("\n", $content),
            fn (string $line) => trim($line) !== '',
        );

        if (count($lines) === 0) {
            return false;
        }

        $boilerplateCount = 0;

        foreach ($lines as $line) {
            $lowerLine = mb_strtolower(trim($line));

            foreach (self::BOILERPLATE_PATTERNS as $pattern) {
                if (str_contains($lowerLine, $pattern)) {
                    $boilerplateCount++;

                    break;
                }
            }
        }

        return ($boilerplateCount / count($lines)) < self::MAX_BOILERPLATE_RATIO;
    }

    private static function checkHasHeadings(string $content): bool
    {
        $headingCount = preg_match_all('/^#{1,6}\s+.+/m', $content);

        return $headingCount >= self::MIN_HEADINGS;
    }

    private static function checkHasSentences(string $content): bool
    {
        preg_match_all('/[^.!?]{'.self::MIN_SENTENCE_LENGTH.',}[.!?]/', $content, $matches);

        return count($matches[0]) >= self::MIN_SENTENCES;
    }
}
