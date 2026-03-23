<x-mail::message>
# {{ $title }}

**{{ $company }}**@if($location) --- {{ $location }}@endif

@if($seniority || $remote || $compensation)
<x-mail::table>
| | |
|:--|:--|
@if($seniority)
| **Level** | {{ $seniority }} |
@endif
@if($remote)
| **Work Style** | {{ $remote }} |
@endif
@if($compensation)
| **Compensation** | {{ $compensation }} |
@endif
</x-mail::table>
@endif

@if($summary)
## Ideal Candidate

{{ $summary }}
@endif

@if($experience)
## Experience Profile

<x-mail::panel>
@if(!empty($experience['years_minimum']))
**{{ $experience['years_minimum'] }}+ years required**{{ !empty($experience['years_preferred']) ? ' ('.$experience['years_preferred'].'+ preferred)' : '' }}

@endif
@if(!empty($experience['industries']))
**Industries:** {{ implode(', ', $experience['industries']) }}

@endif
@if(!empty($experience['leadership_expectations']))
**Leadership:** {{ $experience['leadership_expectations'] }}
@endif
</x-mail::panel>
@endif

@if($culturalFit)
## Cultural Fit

@if(!empty($culturalFit['values']))
@foreach($culturalFit['values'] as $value)
- {{ $value }}
@endforeach
@endif

@if(!empty($culturalFit['work_style']))
**Work Style:** {{ $culturalFit['work_style'] }}
@endif

@if(!empty($culturalFit['team_dynamics']))
**Team:** {{ $culturalFit['team_dynamics'] }}
@endif
@endif

@if(!empty($redFlags))
## Watch For

@foreach($redFlags as $flag)
- {{ $flag }}
@endforeach
@endif

<x-mail::button :url="$url">
View Full Analysis
</x-mail::button>

Generate a gap analysis and tailored resume to see how you stack up.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
