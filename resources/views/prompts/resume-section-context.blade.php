## Target Job
Title: {{ $jobTitle }}
Company: {{ $company }}

## Ideal Candidate Profile (key requirements)
{!! json_encode($requirements, JSON_PRETTY_PRINT) !!}

## Gap Analysis (strengths to highlight, gaps to address)
{!! json_encode($gapInsights, JSON_PRETTY_PRINT) !!}

## Candidate's Relevant Experience
{!! json_encode($experience, JSON_PRETTY_PRINT) !!}

## Language Guidance
{!! json_encode($languageGuidance, JSON_PRETTY_PRINT) !!}
