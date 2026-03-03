

**User Requirements Document**


CareerForge

AI-Powered Experience Library & Job Application Platform


Version 1.0  |  March 2026  |  Open Source

Status: Draft for Technical Team Review

# **Table of Contents**




# **1. Executive Summary**

CareerForge is an open-source, AI-assisted platform that helps experienced professionals build, manage, and strategically deploy their professional experience across multiple job applications. Unlike conventional resume builders that start from a static document, CareerForge starts from the individual — constructing a comprehensive, structured library of skills, experiences, accomplishments, and professional identity that compounds in value over time.

The platform operates on a two-sided analysis model. On one side, it builds and maintains a deep experience library for the user. On the other, it analyzes target job postings and the companies behind them to construct an ideal candidate profile. A gap analysis between these two models drives intelligent resume generation, where modular, interchangeable sections give the user full editorial control over the final document.

A distinguishing feature is the AI Transparency Mode, which generates a companion page for each application that discloses exactly how AI assisted the resume creation process — positioning the tool itself as a demonstration of the user’s product and AI expertise.

**Target User: **Experienced professionals with diverse, deep career histories who need to strategically surface different facets of their background for different opportunities.

**Deployment: **Single-user, self-hosted, open-source application.

**Phasing: **Phase 1 covers the core experience library, job analysis, resume generation, and application tracking. Phase 2 introduces long-term career development coaching.


# **2. Product Vision & Principles**

## **2.1 Problem Statement**

Professionals with extensive and varied experience face a paradox: the more they have done, the harder it is to present themselves effectively for any single role. A one-page resume is a lossy compression of a rich career. Each application requires a manual process of deciding what to include, what to emphasize, and how to frame it — a process that is both time-consuming and inconsistent.

Existing tools optimize resumes by keyword-matching against job postings. They treat the resume as the starting point rather than as an output of a deeper model. They do not help the user understand what a company truly wants, nor do they maintain a persistent, evolving understanding of the user’s full professional identity.

## **2.2 Core Principles**

- The Experience Library is the product. Everything else consumes it. Job analysis, resume generation, and tracking are downstream features. The library is the asset that compounds.

- Two-sided intelligence. The system models both the user’s experience and the employer’s ideal candidate independently, then performs structured analysis between them.

- User agency over AI output. The system recommends; the user decides. Modular resume sections, editorial control, and full transparency ensure the user owns the final product.

- AI transparency as a feature, not a disclaimer. The companion page is a first-class deliverable, not a footnote. It demonstrates how AI was used as a tool, not a replacement.

- Progressive depth. The system delivers value immediately with minimal input and grows more powerful as the user invests in building their library.

- Open source and single-user. No multi-tenancy, no SaaS constraints. The user owns their data completely.


# **3. System Architecture Overview**

The system is composed of five primary modules that interact through a shared data layer. The Experience Library is the foundational module. All other modules either contribute to it or consume from it.

## **3.1 Module Map**

| **Module** | **Purpose** | **Relationship** |
| - | - | - |
| Experience Library | Store and manage the user’s full professional identity | Foundation — all modules depend on this |
| Job Analysis Engine | Analyze postings and companies to build ideal candidate profiles | Independent input; feeds Gap Analysis |
| Gap Analysis & Resume Engine | Compare user profile to ideal candidate; generate modular resumes | Consumes both Library and Job Analysis |
| Application Tracker | Track application status, deadlines, and communications | Consumes Resume Engine output |
| Career Development Advisor | Surface patterns and skill gaps across applications over time | Consumes Tracker + Library (Phase 2) |


## **3.2 Data Flow Summary**

1. User builds and enriches their Experience Library through multiple input methods (manual entry, document upload, voice interview, targeted questionnaires).

2. User submits a job posting (URL or pasted text). The Job Analysis Engine researches the company and role to construct an Ideal Candidate Profile.

3. The Gap Analysis compares the user’s library against the ideal profile, identifying strengths, gaps, and framing opportunities.

4. The system collaborates with the user to address gaps — either by surfacing overlooked experience from the library or prompting the user for additional information.

5. The Resume Engine generates modular resume sections with multiple variants per section. The user assembles their preferred combination.

6. The AI Transparency Companion Page is generated alongside the resume, documenting exactly how AI assisted the process.

7. The user applies directly. The Application Tracker logs the submission and tracks status over time.

8. (Phase 2) The Career Development Advisor analyzes patterns across applications to surface long-term skill development insights.


# **4. Module 1: Experience Library**

The Experience Library is the core data asset of the platform. It is a structured, searchable, chronological repository of everything that defines the user professionally. It is not a resume — it is the source material from which targeted resumes are generated.

## **4.1 Data Model**

The library organizes the user’s professional identity across the following dimensions:

### **4.1.1 Professional Timeline**

- Roles and positions held, with dates, companies, reporting structure, and scope

- Key accomplishments per role, with quantified impact where available

- Projects led or contributed to, with context on scale, technology, outcomes

- Career transitions and the reasoning behind them

### **4.1.2 Skills Inventory**

- Technical skills with self-assessed and AI-inferred proficiency levels

- Domain expertise and industry knowledge

- Soft skills with supporting evidence drawn from accomplishments

- Tools, platforms, methodologies, and frameworks

- Skill provenance: which roles, projects, or experiences developed each skill

### **4.1.3 Professional Identity**

- Values and professional philosophy

- Passions and areas of intrinsic motivation

- Leadership style and collaboration approach

- Communication style and cultural preferences

### **4.1.4 Education & Credentials**

- Formal education, certifications, licenses

- Continuing education, courses, workshops

- Publications, patents, speaking engagements

### **4.1.5 Supplementary Evidence**

- Links to portfolios, repositories, published work

- Indexed documents (performance reviews, recommendation letters, project briefs)

- Testimonials or peer feedback

## **4.2 Input Methods**

The library supports multiple input methods to reduce friction and progressively deepen the user’s profile:

### **4.2.1 Document Ingestion**

- Upload existing resumes, CVs, LinkedIn exports (PDF, DOCX, JSON)

- The system parses and structures the content into the library data model

- The user reviews and corrects the parsed output before it is committed

### **4.2.2 Link Indexing**

- User provides URLs to portfolios, GitHub profiles, published articles, company bios

- The system extracts relevant professional information and suggests library entries

### **4.2.3 Voice Interview**

- Guided conversational interview where the AI asks open-ended questions about the user’s career

- Transcribed, parsed, and structured into library entries for user review

- Supports follow-up sessions that build on previous interviews

### **4.2.4 Targeted Questionnaires**

- Generated dynamically when the system identifies gaps in the library (especially during gap analysis for a specific job)

- Focused, specific questions designed to elicit particular types of experience or accomplishments

- Can be triggered by the system or initiated by the user

### **4.2.5 Manual Entry & Editing**

- Full CRUD interface for all library entries

- Rich text editing for accomplishment descriptions

- Tagging, categorization, and cross-referencing between entries

## **4.3 Minimum Viable Profile**

To deliver value on first use, the system defines a Minimum Viable Profile (MVP) — the least information needed to generate a useful first resume:

- One uploaded resume or CV (parsed automatically)

- One target job posting

From these two inputs, the system can perform an initial gap analysis, generate a first-pass resume, and immediately begin prompting the user to deepen their library with targeted questions. The experience must be: paste a job link, upload your resume, get a tailored output within minutes.

## **4.4 User Stories**

| **ID** | **User Story** | **Priority** | **Phase** |
| - | - | - | - |
| EL-01 | As a user, I want to upload my existing resume so the system can bootstrap my experience library | Must Have | Phase 1 |
| EL-02 | As a user, I want to manually add, edit, and delete entries in my experience library | Must Have | Phase 1 |
| EL-03 | As a user, I want to do a voice interview so I can describe my experience conversationally | Should Have | Phase 1 |
| EL-04 | As a user, I want the system to generate targeted questions when it detects gaps in my library | Must Have | Phase 1 |
| EL-05 | As a user, I want to provide links to my online profiles so the system can extract relevant experience | Should Have | Phase 1 |
| EL-06 | As a user, I want to browse my library chronologically and by skill category | Must Have | Phase 1 |
| EL-07 | As a user, I want to tag and cross-reference entries so related experiences are connected | Should Have | Phase 1 |
| EL-08 | As a user, I want to see how complete my profile is with a visual progress indicator | Nice to Have | Phase 1 |


# **5. Module 2: Job Analysis Engine**

The Job Analysis Engine takes a job posting as input and produces a structured Ideal Candidate Profile. This profile represents what the perfect applicant looks like from the employer’s perspective, informed by both the specific posting and broader research on the company and role.

## **5.1 Input**

- Job posting URL (the system fetches and parses the page)

- Pasted job posting text (for postings behind authentication or in non-standard formats)

## **5.2 Analysis Pipeline**

### **5.2.1 Job Posting Analysis**

- Extract structured data: title, required qualifications, preferred qualifications, responsibilities, seniority level, compensation (if listed), location/remote policy

- Identify explicit and implicit requirements (e.g., “fast-paced environment” implies adaptability and comfort with ambiguity)

- Classify skills into required vs. preferred vs. inferred

### **5.2.2 Company Research**

Lightweight research focused on understanding the company’s hiring identity:

- Company mission, vision, and values (from careers page, about page)

- Language and tone used in public communications (blog posts, press releases, social media)

- Cultural signals: what type of candidates they appear to attract and celebrate

- Recent news or strategic direction that may inform what they’re looking for

The goal is not competitive intelligence. It is to understand the voice in which the resume should speak to this particular company.

### **5.2.3 Role Industry Standards**

- What are the standard qualifications, tools, and expectations for this type of role across the industry?

- What differentiates a strong candidate from an adequate one at this level?

- What emerging skills or trends are relevant to this role?

## **5.3 Output: Ideal Candidate Profile**

A structured document that includes:

- Prioritized skill requirements (must-have, strong-preference, differentiator)

- Experience profile: years, types of companies, scope of responsibility

- Cultural fit indicators: values alignment, communication style, work approach

- Language and framing guidance: specific words, phrases, and framing styles that will resonate with this employer

- Red flags and dealbreakers identified from the posting

## **5.4 User Stories**

| **ID** | **User Story** | **Priority** | **Phase** |
| - | - | - | - |
| JA-01 | As a user, I want to paste a job posting URL and have the system automatically analyze it | Must Have | Phase 1 |
| JA-02 | As a user, I want to paste job posting text directly for postings I can’t link to | Must Have | Phase 1 |
| JA-03 | As a user, I want to see the Ideal Candidate Profile so I understand what the employer is looking for | Must Have | Phase 1 |
| JA-04 | As a user, I want company research to surface tone and values so my resume can mirror it | Should Have | Phase 1 |
| JA-05 | As a user, I want to edit the Ideal Candidate Profile to adjust the system’s interpretation | Should Have | Phase 1 |


# **6. Module 3: Gap Analysis & Resume Engine**

This module sits at the intersection of the Experience Library and the Job Analysis Engine. It compares the user’s actual experience against the Ideal Candidate Profile, identifies gaps and strengths, collaborates with the user to close gaps, and generates modular resume sections.

## **6.1 Gap Analysis**

### **6.1.1 Comparison Framework**

The system evaluates the user against the Ideal Candidate Profile across multiple dimensions:

- Skills match: which required and preferred skills the user has, which are missing, and which are adjacent or transferable

- Experience match: does the user’s career trajectory, scope, and seniority align with expectations?

- Cultural and values alignment: does the user’s professional identity resonate with the company’s signals?

- Language and framing: what experiences can be reframed to better match the employer’s language?

### **6.1.2 Gap Classification**

Each gap is classified into one of three categories:

- Closable by reframing: The user has relevant experience but it is not described in matching terms. The system can reframe existing library entries.

- Closable by prompting: The user may have relevant experience not yet in the library. The system generates targeted questions to surface it.

- Genuine gap: The user does not have this skill or experience. Noted honestly and flagged for long-term development (Phase 2).

## **6.2 Collaborative Gap Closure**

The system does not silently fill gaps. It engages the user in a structured conversation:

9. Present the gap analysis results clearly, showing strengths, reframing opportunities, and genuine gaps.

10. For each closable gap, propose a specific action: a reframed description, a targeted question, or a suggestion to elaborate on a library entry.

11. The user provides additional input or approves reframing suggestions.

12. The system updates the working resume draft and, where appropriate, adds new information back to the Experience Library for future use.

## **6.3 Modular Resume Generation**

### **6.3.1 Architecture**

The resume is not generated as a monolithic document. Instead, the system produces modular sections, each with multiple variant options:

- Professional Summary: 2–3 variants with different emphasis (technical depth vs. leadership vs. strategic vision)

- Experience entries: multiple framings of the same role (emphasizing different accomplishments, quantifying impact differently)

- Skills section: different groupings and orderings based on what the posting prioritizes

- Additional sections (projects, publications, certifications): included or excluded based on relevance

### **6.3.2 User Assembly**

The user is presented with the modular sections and can:

- Preview each variant for each section

- Select their preferred variant for each section

- Edit any variant directly

- Reorder sections

- Preview the assembled resume as a complete document

- Export to PDF or DOCX

### **6.3.3 Cover Letter Generation (Optional)**

If enabled, the system generates a draft cover letter tailored to the company’s tone and values, highlighting the strongest points of alignment. A draft submission email is also generated. Both are editable before use.

## **6.4 User Stories**

| **ID** | **User Story** | **Priority** | **Phase** |
| - | - | - | - |
| GA-01 | As a user, I want a clear gap analysis comparing my profile to the ideal candidate for a specific job | Must Have | Phase 1 |
| GA-02 | As a user, I want gaps classified as reframable, promptable, or genuine so I know where I stand | Must Have | Phase 1 |
| GA-03 | As a user, I want the system to ask targeted questions to close gaps before generating my resume | Must Have | Phase 1 |
| GA-04 | As a user, I want multiple variant options for each resume section so I can pick the best framing | Must Have | Phase 1 |
| GA-05 | As a user, I want to assemble my resume by selecting and reordering modular sections | Must Have | Phase 1 |
| GA-06 | As a user, I want to edit any AI-generated section directly before finalizing | Must Have | Phase 1 |
| GA-07 | As a user, I want to export my assembled resume as PDF or DOCX | Must Have | Phase 1 |
| GA-08 | As a user, I want an optional cover letter and submission email drafted alongside my resume | Should Have | Phase 1 |
| GA-09 | As a user, I want new information from gap closure saved back to my Experience Library | Must Have | Phase 1 |


# **7. Module 4: AI Transparency Companion**

The AI Transparency Companion is a first-class feature, not an afterthought. For each job application, it generates a companion page documenting exactly how AI was used in the resume creation process. This serves dual purposes: ethical transparency and professional demonstration.

## **7.1 Purpose**

The companion page communicates to the hiring manager: this resume was not generated by AI — it was crafted by a human using AI as a collaborative tool. The page documents the human decisions, editorial choices, and domain expertise that went into the final product, while openly showing the AI-assisted workflow.

For the user specifically, who works in product management and AI, the companion page functions as a live portfolio piece. It demonstrates the ability to design AI-assisted workflows, make thoughtful use of LLM capabilities, and maintain intellectual honesty about the role of AI in professional work.

## **7.2 Content**

Each companion page includes:

- A statement that the resume was human-authored with AI assistance, not AI-generated

- The company and role-specific research the system conducted, and how it informed framing decisions

- The ideal candidate profile that was generated, and how the gap analysis shaped content selection

- Which resume sections had AI-suggested variants, and which variant the user selected (including manual edits)

- A brief description of CareerForge, positioning it as a product the user built to demonstrate AI and PM expertise

- A link to the open-source repository (optional, user-configurable)

## **7.3 Format**

- Web page (shareable URL or static HTML file) linkable from the resume or cover letter

- Styled consistently with the resume for professional cohesion

- Specific to each application — not generic, but showing the exact process for this company and role

## **7.4 User Stories**

| **ID** | **User Story** | **Priority** | **Phase** |
| - | - | - | - |
| TC-01 | As a user, I want a companion page generated for each application showing how AI assisted me | Must Have | Phase 1 |
| TC-02 | As a user, I want the companion page to include specific research, analysis, and decisions for this application | Must Have | Phase 1 |
| TC-03 | As a user, I want to include a link to the companion page in my resume or cover letter | Must Have | Phase 1 |
| TC-04 | As a user, I want the companion page to position this tool as a demo of my product and AI skills | Should Have | Phase 1 |
| TC-05 | As a user, I want to review and edit the companion page before making it available | Must Have | Phase 1 |


# **8. Module 5: Application Tracker**

The Application Tracker is a lightweight CRM for the user’s job search. It logs each application, tracks its status, and stores all associated artifacts.

## **8.1 Features**

- Application log: company, role, date applied, status (draft, applied, interviewing, offer, rejected, withdrawn)

- Associated artifacts per application: resume version, cover letter, companion page, job posting snapshot, ideal candidate profile, gap analysis

- Status tracking with timestamped updates

- Draft email with cover letter for submission (generated by Resume Engine, editable, ready to copy-paste)

- Notes field for recording interview feedback, follow-up actions, contacts

- Dashboard view showing pipeline summary across all active applications

## **8.2 Design Notes**

The user applies directly to jobs themselves. The tracker does not submit applications on the user’s behalf. Its role is to organize the application process, keep artifacts accessible, and provide a single view of the job search.

The draft email feature packages the resume and cover letter into a ready-to-send format, but the user copies and sends it from their own email client.

## **8.3 User Stories**

| **ID** | **User Story** | **Priority** | **Phase** |
| - | - | - | - |
| AT-01 | As a user, I want each application logged automatically when I finalize a resume for a specific job | Must Have | Phase 1 |
| AT-02 | As a user, I want to update the status of each application as it progresses | Must Have | Phase 1 |
| AT-03 | As a user, I want to see all artifacts for an application in one place | Must Have | Phase 1 |
| AT-04 | As a user, I want a draft submission email I can copy and send from my email client | Should Have | Phase 1 |
| AT-05 | As a user, I want a dashboard showing the status of all active applications | Should Have | Phase 1 |
| AT-06 | As a user, I want to add notes to each application for interview feedback and follow-ups | Should Have | Phase 1 |


# **9. Module 6: Career Development Advisor (Phase 2)**

This module is scoped for Phase 2 implementation after the core platform is stable and has accumulated application data. It provides passive, insight-driven career development guidance by analyzing patterns across the user’s applications over time.

## **9.1 Scope**

This is an analytical lens on the Experience Library and Application Tracker, not an active coaching engine. It surfaces patterns and insights; it does not prescribe specific actions.

## **9.2 Planned Capabilities**

- Recurring gap detection: identify skills or experience types that appear as gaps across multiple target roles

- Trending requirements: surface skills increasingly common in the user’s target roles

- Application success correlation: identify which resume framings or skill emphases correlate with positive outcomes

- Library health assessment: identify thin areas relative to the user’s target roles

- Periodic summary reports with actionable observations (not prescriptions)

## **9.3 User Stories**

| **ID** | **User Story** | **Priority** | **Phase** |
| - | - | - | - |
| CD-01 | As a user, I want to see which skills appear as gaps across multiple applications to prioritize development | Should Have | Phase 2 |
| CD-02 | As a user, I want to know which resume approaches correlated with better outcomes | Should Have | Phase 2 |
| CD-03 | As a user, I want periodic insight reports on my job search patterns and progress | Nice to Have | Phase 2 |


# **10. Non-Functional Requirements**

## **10.1 Data Privacy & Ownership**

- All data stored locally. No cloud telemetry, no third-party data sharing.

- The user owns all data and can export or delete it at any time.

- AI model interactions should use API calls that do not retain training data.

## **10.2 Performance**

- Job analysis pipeline should complete within 60 seconds for a typical posting.

- Resume generation should complete within 30 seconds after gap closure.

- Experience Library search should be near-instantaneous for libraries up to 500 entries.

## **10.3 Extensibility**

- Plugin architecture for additional input methods (new document types, new indexing sources).

- Configurable AI model backend (support swapping between different LLM providers).

- Resume template system allowing custom visual designs and formats.

## **10.4 User Experience**

- Progressive disclosure: the interface should not overwhelm new users. Advanced features surface as the library grows.

- The first-run experience should require fewer than 5 minutes to reach a first resume draft.

- All AI-generated content is clearly marked and editable before any use.

## **10.5 Technology Constraints**

As an open-source, single-user application, the technical team has latitude in technology selection. The following are guidelines, not mandates:

- Web-based interface preferred for portability

- Local-first data storage (SQLite, flat files, or similar)

- LLM integration via API (Anthropic Claude recommended, with abstraction layer for model swapping)

- Standard document generation libraries for PDF/DOCX export


# **11. Phasing Summary**

## **11.1 Phase 1: Core Platform**

Phase 1 delivers the complete end-to-end workflow from experience library to submitted application:

- Experience Library with all input methods (upload, manual, voice, questionnaire, link indexing)

- Job Analysis Engine with posting analysis and lightweight company research

- Gap Analysis with collaborative gap closure workflow

- Modular Resume Generation with variant selection and user assembly

- AI Transparency Companion Page generation

- Application Tracker with status management and artifact storage

- Optional cover letter and draft submission email generation

## **11.2 Phase 2: Career Development**

Phase 2 is implemented after Phase 1 is stable and has accumulated sufficient application data:

- Career Development Advisor with passive pattern detection and insight reporting

- Expanded analytics on application outcomes and resume effectiveness

## **11.3 Priority Framework**

| **Priority** | **Definition** | **Examples** |
| - | - | - |
| Must Have | Required for Phase 1 launch. The product does not function without it. | Experience Library CRUD, job analysis, gap analysis, resume generation, transparency page |
| Should Have | Important for complete experience. Planned for Phase 1 but can ship in a fast-follow. | Voice interview, link indexing, cover letter, draft email, dashboard view |
| Nice to Have | Enhances the product but not critical for core workflow. | Profile completeness indicator, advanced tagging, resume templates |


# **12. Glossary**

| **Term** | **Definition** |
| - | - |
| Experience Library | The structured repository of all user professional experience, skills, values, and credentials. The core data asset. |
| Ideal Candidate Profile | A structured model of the perfect applicant for a specific job, generated by analyzing the posting, company, and industry. |
| Gap Analysis | The comparison between the user’s Experience Library and the Ideal Candidate Profile, identifying strengths and gaps. |
| Modular Resume | A resume composed of interchangeable sections, each with multiple AI-generated variants, assembled by the user. |
| AI Transparency Companion | A web page for each application documenting how AI assisted the resume creation process. |
| Minimum Viable Profile | The minimum user input (one resume + one job posting) required to generate a first useful output. |
| Gap Closure | The collaborative process of addressing gaps through reframing, prompting for new information, or acknowledging genuine gaps. |
| Progressive Depth | The design principle that the system delivers value immediately and grows more powerful over time. |


*End of Document*
