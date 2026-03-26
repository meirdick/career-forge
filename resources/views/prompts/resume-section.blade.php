Generate content for the "{{ $sectionType }}" section of the resume.

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
IMPORTANT: Only include independent, personal, or open-source projects that are NOT part of the candidate's employment history.
Do NOT duplicate projects already covered under work experience entries.
If no independent projects remain, return variants with minimal/empty content.

@isset($experienceContent)
## Already covered in Experience section (DO NOT repeat these):
{!! $experienceContent !!}
@endisset

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

@if(in_array($sectionType, ['experience', 'education', 'projects']))
## Block Structure

For this section, return each entry (role, degree, or project) as a separate block in a `blocks` array within each variant.
Each block should have:
- `label`: A short identifier (e.g., "Software Engineer at Google", "BS Computer Science at MIT", "Open Source CLI Tool")
- `content`: The full markdown content for just that entry

Do NOT include a top-level `content` field for this section — the system will assemble it from blocks automatically.
@endif

## Page Budget

This resume targets {{ $pageLimit ?? 1 }} page(s). Keep this section concise to fit the overall budget.

@if(($pageLimit ?? 1) === 1)
@if($sectionType === 'summary')
Budget: 2-3 sentences, ~40-60 words.
@elseif($sectionType === 'experience')
Budget: Most recent 2-3 roles only. 3-5 bullet points per role. ~200-250 words total.
@elseif($sectionType === 'skills')
Budget: 3-5 category lines. ~50-70 words total.
@elseif($sectionType === 'education')
Budget: 1-2 entries, minimal detail. ~30-40 words total.
@else
Budget: 2-4 entries, very brief. ~30-50 words total.
@endif
@else
@if($sectionType === 'summary')
Budget: 3-5 sentences, ~60-100 words.
@elseif($sectionType === 'experience')
Budget: Up to 4-5 roles. 4-7 bullet points per role. ~400-500 words total.
@elseif($sectionType === 'skills')
Budget: 4-7 category lines. ~70-100 words total.
@elseif($sectionType === 'education')
Budget: 1-3 entries with relevant details. ~50-70 words total.
@else
Budget: 3-6 entries. ~50-80 words total.
@endif
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

## Compact Version

For each variant, also generate a `compact_content` field: a condensed version of the entire section in 1-2 lines maximum. No bullet points, no markdown formatting. Just the essential facts.

@if($sectionType === 'experience')
Example compact: "Head of Product at Payquad (2021–Present) | Co-Founder/CEO at Bridge7 Oncology (2017–2021) | VP Operations at META (2012–2015)"
@elseif($sectionType === 'education')
Example compact: "MBA, Rotman School of Management (2017) | BASc Materials Engineering, University of Toronto (2010)"
@elseif($sectionType === 'skills')
Example compact: "Product Strategy, AI/ML, Python, TypeScript, Agile/Scrum, GTM, SaaS Metrics, Figma, PostHog"
@elseif($sectionType === 'summary')
Example compact: "Senior PM and AI founder with 10+ years shipping products across healthcare AI, B2C, and B2B SaaS."
@elseif($sectionType === 'projects')
Example compact: "Bridge7 AI Medical Device (12 hospital deployments) | Payquad SaaS Platform ($500M+ payments) | VISR Wellness App (20K users)"
@else
Condense all entries to a single line of key facts separated by pipes.
@endif
