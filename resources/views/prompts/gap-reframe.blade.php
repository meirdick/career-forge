Reframe the candidate's experience to address a gap identified in their job application analysis.

## Gap to Address
- **Area**: {{ $gap['area'] }}
- **Description**: {{ $gap['description'] }}
- **Suggestion**: {{ $gap['suggestion'] }}

## Target Role
{{ $jobTitle }} at {{ $company }}

## Candidate's Experience Entry
- **Title**: {{ $experience->title }}
- **Company**: {{ $experience->company }}
@if($experience->description)
- **Current Description**: {{ $experience->description }}
@endif
@if($experience->accomplishments->isNotEmpty())
- **Accomplishments**:
@foreach($experience->accomplishments as $accomplishment)
  - {{ $accomplishment->title }}@if($accomplishment->impact) (Impact: {{ $accomplishment->impact }})@endif

@endforeach
@endif
@if($experience->skills->isNotEmpty())
- **Skills**: {{ $experience->skills->pluck('name')->join(', ') }}
@endif

Suggest a reframed description that repositions this experience to address the identified gap. Also explain your rationale for the reframe.
