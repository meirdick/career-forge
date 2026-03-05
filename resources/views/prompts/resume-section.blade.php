You are an expert resume writer. Generate content for the "{{ $sectionType }}" section of a resume, tailored to the target job.

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

## Output Format — Markdown

You MUST format each variant's content using markdown so it renders well in both document previews and PDF exports.

@if($sectionType === 'experience')
Use this exact format for each role:

**Company Name** — *Job Title*
*Start Date – End Date* | Location
- Accomplishment with measurable impact
- Another achievement using strong action verbs
- Quantified result demonstrating value

Separate multiple roles with a blank line between them.
@elseif($sectionType === 'education')
Use this exact format for each entry:

**Degree or Program** — *Institution Name*
*Start Year – End Year*
- Relevant detail or honor (optional)

@elseif($sectionType === 'skills')
Group skills by category, one line per category:

**Category Name:** Skill1, Skill2, Skill3, Skill4

Examples: **Languages:** Python, TypeScript, Go | **Frameworks:** React, Laravel, Django

@elseif($sectionType === 'summary')
Write a 3-4 sentence professional summary paragraph. No bullet points. Use strong, confident language highlighting the candidate's value proposition for this specific role.

@elseif($sectionType === 'projects')
Use this format for each project:

**Project Name**
Brief one-line description of the project
- Key highlight or technical achievement
- Impact or result

@elseif($sectionType === 'certifications')
Use this format:

**Certification Name** — *Issuing Organization*
*Year Obtained*

@elseif($sectionType === 'publications')
Use this format:

**Title** — *Publication Venue or Conference*
*Year*
- Brief description of contribution

@endif

## Instructions

Generate 2-3 VARIANTS of this section, each with a different emphasis or approach:
- Variant 1: Balanced, straightforward approach
- Variant 2: Achievement-focused, emphasizing measurable results
- Variant 3: Skills-forward, leading with technical capabilities

Each variant should:
- Be tailored to the specific job posting
- Use terminology from the language guidance
- Highlight strengths identified in the gap analysis
- Be professional and concise
- Avoid fabricating experience or skills the candidate doesn't have
- Follow the markdown format specified above exactly
