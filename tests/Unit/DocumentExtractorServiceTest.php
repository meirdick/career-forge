<?php

use App\Services\DocumentExtractorService;

test('extracts text from json resume format', function () {
    $jsonContent = json_encode([
        'basics' => [
            'name' => 'John Doe',
            'label' => 'Software Engineer',
            'email' => 'john@example.com',
            'summary' => 'Experienced developer',
        ],
        'work' => [
            [
                'company' => 'Acme Corp',
                'title' => 'Senior Developer',
                'startDate' => '2020-01',
                'endDate' => '2023-06',
                'description' => 'Led a team of 5 engineers.',
            ],
        ],
        'education' => [
            [
                'institution' => 'MIT',
                'studyType' => 'BS',
                'area' => 'Computer Science',
            ],
        ],
        'skills' => [
            ['name' => 'PHP'],
            ['name' => 'JavaScript'],
        ],
    ]);

    $tmpFile = tempnam(sys_get_temp_dir(), 'test_').'.json';
    file_put_contents($tmpFile, $jsonContent);

    $service = new DocumentExtractorService;
    $result = $service->extract($tmpFile);

    expect($result)
        ->toContain('John Doe')
        ->toContain('Software Engineer')
        ->toContain('Senior Developer at Acme Corp')
        ->toContain('BS in Computer Science - MIT')
        ->toContain('PHP')
        ->toContain('JavaScript');

    unlink($tmpFile);
});

test('extracts text from linkedin export format', function () {
    $jsonContent = json_encode([
        'positions' => [
            [
                'companyName' => 'LinkedIn Corp',
                'title' => 'Product Manager',
                'startDate' => '2019-03',
                'endDate' => 'Present',
                'description' => 'Managed product roadmap.',
            ],
        ],
        'educations' => [
            [
                'schoolName' => 'Stanford',
                'degreeName' => 'MBA',
                'fieldOfStudy' => 'Business',
            ],
        ],
        'skills' => ['Python', 'SQL', 'Data Analysis'],
        'certifications' => [
            [
                'name' => 'PMP',
                'authority' => 'PMI',
            ],
        ],
    ]);

    $tmpFile = tempnam(sys_get_temp_dir(), 'test_').'.json';
    file_put_contents($tmpFile, $jsonContent);

    $service = new DocumentExtractorService;
    $result = $service->extract($tmpFile);

    expect($result)
        ->toContain('Product Manager at LinkedIn Corp')
        ->toContain('MBA in Business - Stanford')
        ->toContain('Python')
        ->toContain('PMP - PMI');

    unlink($tmpFile);
});

test('throws exception for invalid json', function () {
    $tmpFile = tempnam(sys_get_temp_dir(), 'test_').'.json';
    file_put_contents($tmpFile, 'not valid json{{{');

    $service = new DocumentExtractorService;
    $service->extract($tmpFile);

    unlink($tmpFile);
})->throws(\InvalidArgumentException::class);

test('throws exception for unsupported file type', function () {
    $service = new DocumentExtractorService;
    $service->extract('/tmp/test.xyz');
})->throws(\InvalidArgumentException::class);
