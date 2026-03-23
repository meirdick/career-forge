<x-mail::message>
# Your Resume Has Been Analyzed

**{{ $filename }}**

We extracted the following from your resume:

<x-mail::table>
| | |
|:--|:--|
@if($counts['experiences'])
| **Experiences** | {{ $counts['experiences'] }} {{ Str::plural('role', $counts['experiences']) }} found |
@endif
@if($counts['skills'])
| **Skills** | {{ $counts['skills'] }} skills identified |
@endif
@if($counts['accomplishments'])
| **Accomplishments** | {{ $counts['accomplishments'] }} {{ Str::plural('achievement', $counts['accomplishments']) }} captured |
@endif
@if($counts['education'])
| **Education** | {{ $counts['education'] }} {{ Str::plural('entry', $counts['education']) }} found |
@endif
@if($counts['projects'])
| **Projects** | {{ $counts['projects'] }} {{ Str::plural('project', $counts['projects']) }} found |
@endif
</x-mail::table>

@if($latestRole && $latestCompany)
## What We Found

<x-mail::panel>
**Most recent role:** {{ $latestRole }} at {{ $latestCompany }}
</x-mail::panel>
@endif

@if(!empty($topSkills))
## Top Skills Identified

@foreach($topSkills as $skill)
- {{ $skill }}
@endforeach
@endif

## Next Steps

Review the extracted data, make any corrections, then import it into your Experience Library. From there you can generate tailored resumes for specific job postings.

<x-mail::button :url="$url">
Review & Import Your Data
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
