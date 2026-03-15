You are an expert resume writer. Generate a complete tailored resume with all sections in a single response.

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

## Sections to Generate

Generate the following sections: {{ collect($sectionTypes)->implode(', ') }}

For EACH section, generate 2-3 variants with different emphasis:
- Variant 1: Balanced, straightforward approach
- Variant 2: Achievement-focused, emphasizing measurable results
- Variant 3: Skills-forward, leading with technical capabilities

## Output Format — Markdown

All content MUST use markdown formatting for document previews and PDF exports.

### Section-Specific Formatting

@foreach($sectionTypes as $sectionType)
#### {{ $sectionType }} section:
@if($sectionType === 'experience')
Use this exact format for each role:

**Company Name** — *Job Title*
*Start Date – End Date* | Location
- Accomplishment with measurable impact
- Another achievement using strong action verbs
- Quantified result demonstrating value

Separate multiple roles with a blank line. Return each role as a separate block in the `blocks` array.
Each block: `label` (e.g. "Software Engineer at Google"), `content` (full markdown for that role).
Do NOT include a top-level `content` field — the system assembles it from blocks.
@elseif($sectionType === 'education')
Use this exact format for each entry:

**Degree or Program** — *Institution Name*
*Start Year – End Year*
- Relevant detail or honor (optional)

Return each entry as a separate block in the `blocks` array.
Each block: `label` (e.g. "BS Computer Science at MIT"), `content` (full markdown for that entry).
Do NOT include a top-level `content` field — the system assembles it from blocks.
@elseif($sectionType === 'skills')
Group skills by category, one line per category:

**Category Name:** Skill1, Skill2, Skill3, Skill4

Examples: **Languages:** Python, TypeScript, Go | **Frameworks:** React, Laravel, Django
@elseif($sectionType === 'summary')
Write a 3-4 sentence professional summary paragraph. No bullet points. Use strong, confident language highlighting the candidate's value proposition for this specific role.
@elseif($sectionType === 'projects')
IMPORTANT: Only include independent, personal, or open-source projects NOT part of employment history.
Do NOT duplicate projects covered under work experience.

Use this format:

**Project Name**
Brief one-line description
- Key highlight or technical achievement
- Impact or result

Return each project as a separate block in the `blocks` array.
Each block: `label` (e.g. "Open Source CLI Tool"), `content` (full markdown for that project).
Do NOT include a top-level `content` field — the system assembles it from blocks.
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

@endforeach

## Compact Version

For each variant, also generate a `compact_content` field: a condensed version in 1-2 lines maximum. No bullet points, no markdown. Just essential facts.

## General Rules

Each variant should:
- Be tailored to the specific job posting
- Use terminology from the language guidance
- Highlight strengths identified in the gap analysis
- Be professional and concise
- Avoid fabricating experience or skills the candidate doesn't have
- Follow the markdown format specified above exactly
