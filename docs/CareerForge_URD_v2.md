

**User Requirements Document**


CareerForge

AI-Powered Experience Library & Job Application Platform


Version 2.0  |  March 2026  |  Open Source

Status: Active Development — Phase 1 Implementation In Progress

# **Table of Contents**




# **1. Executive Summary**

CareerForge is an open-source, AI-assisted platform that helps experienced professionals build, manage, and strategically deploy their professional experience across multiple job applications. Unlike conventional resume builders that start from a static document, CareerForge starts from the individual — constructing a comprehensive, structured library of skills, experiences, accomplishments, and professional identity that compounds in value over time.

The platform operates on a two-sided analysis model. On one side, it builds and maintains a deep experience library for the user. On the other, it analyzes target job postings and the companies behind them to construct an ideal candidate profile. A gap analysis between these two models drives intelligent resume generation, where modular, interchangeable sections give the user full editorial control over the final document.

A distinguishing feature is the AI Transparency Mode, which generates a companion page for each application that discloses exactly how AI assisted the resume creation process — positioning the tool itself as a demonstration of the user’s product and AI expertise.

**Target User: **Experienced professionals with diverse, deep career histories who need to strategically surface different facets of their background for different opportunities.

**Deployment: **Single-user, self-hosted, open-source application.

**Phasing: **Phase 1 covers the core experience library, job analysis, resume generation, and application tracking. Phase 2 introduces long-term career development coaching.


# **2. Document Conventions**

## **2.1 Requirement Identification**

Each requirement is assigned a unique identifier using the format UR-\[MODULE\]-\[NUMBER\], where MODULE is a two-letter code identifying the system module and NUMBER is a sequential identifier. This enables traceability from requirements through design, implementation, and testing.

## **2.2 Module Codes**

| **Code** | **Module** | **Prefix** |
| - | - | - |
| EL | Experience Library | UR-EL-\#\#\# |
| JA | Job Analysis Engine | UR-JA-\#\#\# |
| GA | Gap Analysis & Resume Engine | UR-GA-\#\#\# |
| TC | AI Transparency Companion | UR-TC-\#\#\# |
| AT | Application Tracker | UR-AT-\#\#\# |
| CD | Career Development Advisor | UR-CD-\#\#\# |
| NF | Non-Functional Requirements | UR-NF-\#\#\# |


## **2.3 Priority Definitions**

| **Priority** | **Definition** |
| - | - |
| Must Have | Required for Phase 1 launch. The product does not function without it. Cannot be descoped. |
| Should Have | Important for a complete experience. Planned for Phase 1 but can ship in a fast-follow if necessary. |
| Nice to Have | Enhances the product but not critical for the core workflow. Can be deferred without impact. |


## **2.4 Requirement Language**

Requirements use the following keywords per RFC 2119 conventions:

- “Shall” indicates a mandatory requirement that must be implemented.

- “Should” indicates a recommended requirement that may be omitted with justification.

- “May” indicates an optional requirement at the implementer’s discretion.


# **3. Product Vision & Principles**

## **3.1 Problem Statement**

Professionals with extensive and varied experience face a paradox: the more they have done, the harder it is to present themselves effectively for any single role. A one-page resume is a lossy compression of a rich career. Each application requires a manual process of deciding what to include, what to emphasize, and how to frame it — a process that is both time-consuming and inconsistent.

Existing tools optimize resumes by keyword-matching against job postings. They treat the resume as the starting point rather than as an output of a deeper model. They do not help the user understand what a company truly wants, nor do they maintain a persistent, evolving understanding of the user’s full professional identity.

## **3.2 Core Principles**

- The Experience Library is the product. Everything else consumes it. The library is the asset that compounds in value.

- Two-sided intelligence. The system models both the user and the employer independently, then performs structured analysis between them.

- User agency over AI output. The system recommends; the user decides. Modular sections, editorial control, and full transparency ensure the user owns the final product.

- AI transparency as a feature, not a disclaimer. The companion page is a first-class deliverable that demonstrates how AI was used as a tool, not a replacement.

- Progressive depth. The system delivers value immediately with minimal input and grows more powerful as the user invests in building their library.

- Open source and single-user. No multi-tenancy, no SaaS constraints. The user owns their data completely.


# **4. System Architecture Overview**

The system is composed of five primary modules that interact through a shared data layer. The Experience Library is the foundational module. All other modules either contribute to it or consume from it.

## **4.1 Module Map**

| **Module** | **Purpose** | **Relationship** |
| - | - | - |
| Experience Library | Store and manage the user’s full professional identity | Foundation — all modules depend on this |
| Job Analysis Engine | Analyze postings and companies to build ideal candidate profiles | Independent input; feeds Gap Analysis |
| Gap Analysis & Resume Engine | Compare user to ideal candidate; generate modular resumes | Consumes both Library and Job Analysis |
| Application Tracker | Track application status and communications | Consumes Resume Engine output |
| Career Development Advisor | Surface patterns and skill gaps over time | Consumes Tracker + Library (Phase 2) |


## **4.2 Data Flow**

1. User builds and enriches their Experience Library through multiple input methods.

2. User submits a job posting. The Job Analysis Engine researches the company and role to construct an Ideal Candidate Profile.

3. The Gap Analysis compares the user’s library against the ideal profile, identifying strengths, gaps, and framing opportunities.

4. The system collaborates with the user to address gaps — surfacing overlooked experience or prompting for additional information.

5. The Resume Engine generates modular sections with multiple variants. The user assembles their preferred combination.

6. The AI Transparency Companion Page is generated alongside the resume.

7. The user applies directly. The Application Tracker logs the submission and tracks status.

8. (Phase 2) The Career Development Advisor analyzes patterns across applications for long-term insights.


# **5. Module 1: Experience Library**

The Experience Library is the core data asset of the platform. It is a structured, searchable, chronological repository of everything that defines the user professionally. It is not a resume — it is the source material from which targeted resumes are generated.

## **5.1 Data Model**

The library organizes the user’s professional identity across the following dimensions:

### **5.1.1 Professional Timeline**

- Roles and positions held, with dates, companies, reporting structure, and scope

- Key accomplishments per role, with quantified impact where available

- Projects led or contributed to, with context on scale, technology, and outcomes

- Career transitions and the reasoning behind them

### **5.1.2 Skills Inventory**

- Technical skills with self-assessed and AI-inferred proficiency levels

- Domain expertise and industry knowledge

- Soft skills with supporting evidence drawn from accomplishments

- Tools, platforms, methodologies, and frameworks

- Skill provenance: which roles, projects, or experiences developed each skill

### **5.1.3 Professional Identity**

- Values and professional philosophy

- Passions and areas of intrinsic motivation

- Leadership style and collaboration approach

### **5.1.4 Education & Credentials**

- Formal education, certifications, licenses, continuing education

- Publications, patents, speaking engagements

### **5.1.5 Supplementary Evidence**

- Links to portfolios, repositories, published work

- Indexed documents (performance reviews, recommendation letters, project briefs)

- Testimonials or peer feedback

## **5.2 Input Methods**

### **5.2.1 Document Ingestion**

- Upload existing resumes, CVs, LinkedIn exports (PDF, DOCX, JSON)

- System parses and structures content into the library data model

- User reviews and corrects parsed output before it is committed

### **5.2.2 Link Indexing**

- User provides URLs to portfolios, GitHub profiles, published articles, company bios

- System extracts relevant professional information and suggests library entries

### **5.2.3 Voice Interview**

- Guided conversational interview where the AI asks open-ended questions about the user’s career

- Transcribed, parsed, and structured into library entries for user review

- Supports follow-up sessions that build on previous interviews

### **5.2.4 Targeted Questionnaires**

- Generated dynamically when the system identifies gaps in the library

- Focused, specific questions designed to elicit particular types of experience

### **5.2.5 Manual Entry & Editing**

- Full CRUD interface for all library entries

- Rich text editing, tagging, categorization, and cross-referencing

## **5.3 Minimum Viable Profile**

The system defines a Minimum Viable Profile — the least information needed to generate a useful first resume: one uploaded resume or CV (parsed automatically) and one target job posting. From these two inputs, the system performs an initial gap analysis and generates a first-pass resume within minutes.

## **5.4 User Requirements Specifications**

| **Req ID** | **Requirement** | **Priority** | **Phase** | **Relates To** |
| - | - | - | - | - |
| UR-EL-001 | The system shall allow the user to upload professional documents in PDF, DOCX, and JSON formats for automated parsing into the Experience Library. | Must Have | 1 | — |
| UR-EL-002 | The system shall automatically parse uploaded documents to extract roles, dates, skills, accomplishments, and education into structured library entries. | Must Have | 1 | UR-EL-001 |
| UR-EL-003 | The system shall present all parsed entries to the user for review, correction, and approval before committing them to the library. | Must Have | 1 | UR-EL-002 |
| UR-EL-004 | The system shall provide a CRUD interface for manually creating, reading, updating, and deleting all library entry types. | Must Have | 1 | — |
| UR-EL-005 | The system shall support rich text editing for accomplishment and project descriptions within library entries. | Must Have | 1 | UR-EL-004 |
| UR-EL-006 | The system shall maintain a chronological timeline view of all professional roles, projects, and transitions. | Must Have | 1 | — |
| UR-EL-007 | The system shall allow the user to browse and filter library entries by skill category, role type, date range, and tags. | Must Have | 1 | — |
| UR-EL-008 | The system shall support tagging and cross-referencing between library entries so that related experiences, skills, and accomplishments are linked. | Should Have | 1 | — |
| UR-EL-009 | The system shall maintain a skills inventory with proficiency levels (self-assessed and AI-inferred) and provenance linking each skill to the experience that developed it. | Must Have | 1 | — |
| UR-EL-010 | The system shall accept URLs to external professional profiles (GitHub, portfolio sites, published articles) and extract relevant information as suggested library entries. | Should Have | 1 | — |
| UR-EL-011 | The system shall provide a guided voice interview mode that asks open-ended career questions, transcribes responses, and structures them into library entries for user review. | Should Have | 1 | — |
| UR-EL-012 | The system shall generate targeted questionnaires dynamically when it detects gaps or thin areas in the library, particularly during gap analysis for a specific job. | Must Have | 1 | UR-GA-003 |
| UR-EL-013 | The system shall store professional identity data including values, passions, leadership style, and collaboration preferences as structured library entries. | Should Have | 1 | — |
| UR-EL-014 | The system shall display a profile completeness indicator showing the depth and coverage of the user’s library across all data model dimensions. | Nice to Have | 1 | — |
| UR-EL-015 | The system shall allow the user to export the full Experience Library in a portable, machine-readable format. | Should Have | 1 | UR-NF-002 |


## **5.5 User Stories**

| **ID** | **User Story** | **Priority** | **Phase** |
| - | - | - | - |
| US-EL-01 | As a user, I want to upload my existing resume so the system can bootstrap my experience library | Must Have | Phase 1 |
| US-EL-02 | As a user, I want to manually add, edit, and delete entries in my experience library | Must Have | Phase 1 |
| US-EL-03 | As a user, I want to do a voice interview so I can describe my experience conversationally | Should Have | Phase 1 |
| US-EL-04 | As a user, I want the system to generate targeted questions when it detects gaps in my library | Must Have | Phase 1 |
| US-EL-05 | As a user, I want to provide links to my online profiles so the system can extract relevant experience | Should Have | Phase 1 |
| US-EL-06 | As a user, I want to browse my library chronologically and by skill category | Must Have | Phase 1 |
| US-EL-07 | As a user, I want to tag and cross-reference entries so related experiences are connected | Should Have | Phase 1 |


# **6. Module 2: Job Analysis Engine**

The Job Analysis Engine takes a job posting as input and produces a structured Ideal Candidate Profile representing what the perfect applicant looks like from the employer’s perspective.

## **6.1 Analysis Pipeline**

### **6.1.1 Job Posting Analysis**

- Extract structured data: title, qualifications, responsibilities, seniority, compensation, location

- Identify explicit and implicit requirements (e.g., “fast-paced environment” implies adaptability)

- Classify skills into required vs. preferred vs. inferred

### **6.1.2 Company Research**

Lightweight research focused on understanding the company’s hiring identity:

- Company mission, vision, and values from public pages

- Language and tone in public communications

- Cultural signals: what type of candidates they attract and celebrate

- Recent news or strategic direction relevant to hiring needs

### **6.1.3 Role Industry Standards**

- Standard qualifications, tools, and expectations for this role type across the industry

- Differentiators between strong and adequate candidates at this level

- Emerging skills or trends relevant to the role

## **6.2 Output: Ideal Candidate Profile**

- Prioritized skill requirements (must-have, strong-preference, differentiator)

- Experience profile: years, company types, scope of responsibility

- Cultural fit indicators: values alignment, communication style, work approach

- Language and framing guidance: words and styles that will resonate with this employer

- Red flags and dealbreakers identified from the posting

## **6.3 User Requirements Specifications**

| **Req ID** | **Requirement** | **Priority** | **Phase** | **Relates To** |
| - | - | - | - | - |
| UR-JA-001 | The system shall accept a job posting via URL and automatically fetch, parse, and extract structured job data from the page. | Must Have | 1 | — |
| UR-JA-002 | The system shall accept pasted plain text of a job posting as an alternative to URL input. | Must Have | 1 | — |
| UR-JA-003 | The system shall extract and classify requirements from the posting into required, preferred, and inferred skill categories. | Must Have | 1 | — |
| UR-JA-004 | The system shall conduct lightweight company research by analyzing the company’s public web presence (careers page, about page, blog, press releases) to extract mission, values, tone, and cultural signals. | Should Have | 1 | — |
| UR-JA-005 | The system shall research industry standards for the target role type to identify typical qualifications, expectations, and differentiators. | Should Have | 1 | — |
| UR-JA-006 | The system shall generate a structured Ideal Candidate Profile document that includes prioritized skills, experience expectations, cultural fit indicators, and language/framing guidance. | Must Have | 1 | UR-JA-003 |
| UR-JA-007 | The system shall present the Ideal Candidate Profile to the user for review and allow manual edits to correct or adjust the system’s interpretation. | Should Have | 1 | UR-JA-006 |
| UR-JA-008 | The system shall store each Ideal Candidate Profile as an artifact associated with the job application for later reference. | Must Have | 1 | UR-AT-003 |


## **6.4 User Stories**

| **ID** | **User Story** | **Priority** | **Phase** |
| - | - | - | - |
| US-JA-01 | As a user, I want to paste a job posting URL and have the system automatically analyze it | Must Have | Phase 1 |
| US-JA-02 | As a user, I want to paste job posting text directly for postings I can’t link to | Must Have | Phase 1 |
| US-JA-03 | As a user, I want to see the Ideal Candidate Profile so I understand what the employer wants | Must Have | Phase 1 |
| US-JA-04 | As a user, I want company research to surface tone and values so my resume can mirror it | Should Have | Phase 1 |
| US-JA-05 | As a user, I want to edit the Ideal Candidate Profile to adjust the system’s interpretation | Should Have | Phase 1 |


# **7. Module 3: Gap Analysis & Resume Engine**

This module sits at the intersection of the Experience Library and the Job Analysis Engine. It compares the user’s actual experience against the Ideal Candidate Profile, collaborates with the user to close gaps, and generates modular resume sections.

## **7.1 Gap Analysis**

### **7.1.1 Comparison Framework**

- Skills match: which required and preferred skills the user has, which are missing, which are transferable

- Experience match: career trajectory, scope, and seniority alignment

- Cultural and values alignment with the company’s signals

- Language and framing: experiences that can be reframed to match employer language

### **7.1.2 Gap Classification**

- Closable by reframing: user has relevant experience described in non-matching terms. System proposes reframing.

- Closable by prompting: user may have experience not yet in library. System generates targeted questions.

- Genuine gap: user lacks this skill or experience. Noted honestly, flagged for Phase 2 development tracking.

## **7.2 Collaborative Gap Closure**

9. Present gap analysis results showing strengths, reframing opportunities, and genuine gaps.

10. For each closable gap, propose a specific action: reframed description, targeted question, or elaboration prompt.

11. User provides additional input or approves reframing suggestions.

12. System updates the resume draft and adds new information back to the Experience Library.

## **7.3 Modular Resume Generation**

### **7.3.1 Section Architecture**

- Professional Summary: 2–3 variants with different emphasis (technical, leadership, strategic)

- Experience entries: multiple framings per role (different accomplishments, different quantification)

- Skills section: different groupings and orderings based on posting priorities

- Additional sections (projects, publications, certifications): included or excluded by relevance

### **7.3.2 User Assembly**

- Preview each variant for each section; select preferred variant; edit directly; reorder sections

- Preview assembled resume as complete document; export to PDF or DOCX

### **7.3.3 Cover Letter & Email (Optional)**

System generates a draft cover letter tailored to the company’s tone and a draft submission email. Both are editable.

## **7.4 User Requirements Specifications**

| **Req ID** | **Requirement** | **Priority** | **Phase** | **Relates To** |
| - | - | - | - | - |
| UR-GA-001 | The system shall compare the user’s Experience Library against the Ideal Candidate Profile and produce a structured gap analysis across skills, experience, cultural fit, and framing dimensions. | Must Have | 1 | UR-JA-006 |
| UR-GA-002 | The system shall classify each identified gap as “closable by reframing,” “closable by prompting,” or “genuine gap.” | Must Have | 1 | UR-GA-001 |
| UR-GA-003 | The system shall present the gap analysis to the user and, for each closable gap, propose a specific closure action (reframed text, targeted question, or elaboration prompt). | Must Have | 1 | UR-GA-002 |
| UR-GA-004 | The system shall allow the user to respond to gap closure prompts and shall incorporate the responses into the working resume draft. | Must Have | 1 | UR-GA-003 |
| UR-GA-005 | The system shall save new information provided during gap closure back to the Experience Library for reuse in future applications. | Must Have | 1 | UR-EL-004 |
| UR-GA-006 | The system shall generate modular resume sections, each with at least two variant options differing in emphasis, framing, or content selection. | Must Have | 1 | — |
| UR-GA-007 | The system shall allow the user to preview, select, and switch between variant options for each resume section independently. | Must Have | 1 | UR-GA-006 |
| UR-GA-008 | The system shall allow the user to directly edit the text of any AI-generated resume section or variant. | Must Have | 1 | UR-GA-007 |
| UR-GA-009 | The system shall allow the user to reorder resume sections via drag-and-drop or equivalent interface. | Must Have | 1 | — |
| UR-GA-010 | The system shall provide a live preview of the fully assembled resume as the user selects variants and reorders sections. | Must Have | 1 | — |
| UR-GA-011 | The system shall export the finalized resume in both PDF and DOCX formats. | Must Have | 1 | — |
| UR-GA-012 | The system should generate a draft cover letter tailored to the target company’s tone, values, and role requirements when the user enables this option. | Should Have | 1 | UR-JA-004 |
| UR-GA-013 | The system should generate a draft submission email incorporating the cover letter, ready for the user to copy into their email client. | Should Have | 1 | UR-GA-012 |


## **7.5 User Stories**

| **ID** | **User Story** | **Priority** | **Phase** |
| - | - | - | - |
| US-GA-01 | As a user, I want a clear gap analysis comparing my profile to the ideal candidate | Must Have | Phase 1 |
| US-GA-02 | As a user, I want gaps classified so I know which I can address and which are genuine | Must Have | Phase 1 |
| US-GA-03 | As a user, I want targeted questions to close gaps before resume generation | Must Have | Phase 1 |
| US-GA-04 | As a user, I want multiple variants for each resume section | Must Have | Phase 1 |
| US-GA-05 | As a user, I want to assemble my resume by selecting and reordering modular sections | Must Have | Phase 1 |
| US-GA-06 | As a user, I want to edit any AI-generated section before finalizing | Must Have | Phase 1 |
| US-GA-07 | As a user, I want to export my resume as PDF or DOCX | Must Have | Phase 1 |
| US-GA-08 | As a user, I want an optional cover letter and email drafted alongside my resume | Should Have | Phase 1 |


# **8. Module 4: AI Transparency Companion**

The AI Transparency Companion is a first-class feature. For each job application, it generates a companion page documenting exactly how AI was used in the resume creation process. It serves dual purposes: ethical transparency and professional demonstration.

## **8.1 Purpose**

The companion page communicates: this resume was crafted by a human using AI as a collaborative tool. It documents the human decisions, editorial choices, and domain expertise that shaped the final product, while openly showing the AI-assisted workflow.

The companion page also functions as a portfolio piece demonstrating the ability to design AI-assisted workflows and maintain intellectual honesty about AI’s role in professional work.

## **8.2 Content**

- Statement that the resume was human-authored with AI assistance, not AI-generated

- Company and role-specific research conducted, and how it informed framing decisions

- Ideal candidate profile generated, and how gap analysis shaped content selection

- Which sections had AI-suggested variants, which variant was selected, and any manual edits made

- Description of CareerForge as a product the user built, demonstrating AI and PM expertise

- Link to the open-source repository (optional, user-configurable)

## **8.3 Format**

- Web page (shareable URL or static HTML) linkable from the resume or cover letter

- Styled consistently with the resume for professional cohesion

- Specific to each application — documenting the exact process for this company and role

## **8.4 User Requirements Specifications**

| **Req ID** | **Requirement** | **Priority** | **Phase** | **Relates To** |
| - | - | - | - | - |
| UR-TC-001 | The system shall generate a unique AI Transparency Companion page for each job application. | Must Have | 1 | — |
| UR-TC-002 | The companion page shall include a statement that the resume was human-authored with AI assistance. | Must Have | 1 | — |
| UR-TC-003 | The companion page shall document the specific company research, ideal candidate profile, and gap analysis performed for this application. | Must Have | 1 | UR-JA-006 |
| UR-TC-004 | The companion page shall identify which resume sections had AI-generated variants, which variant the user selected, and where manual edits were made. | Must Have | 1 | UR-GA-007 |
| UR-TC-005 | The system shall render the companion page as a shareable web page (URL or static HTML file) that can be linked from the resume or cover letter. | Must Have | 1 | — |
| UR-TC-006 | The companion page should include a configurable section describing CareerForge and linking to the open-source repository. | Should Have | 1 | — |
| UR-TC-007 | The system shall allow the user to review and edit the companion page content before making it available. | Must Have | 1 | — |
| UR-TC-008 | The companion page shall be styled consistently with the user’s resume for visual cohesion. | Should Have | 1 | — |


## **8.5 User Stories**

| **ID** | **User Story** | **Priority** | **Phase** |
| - | - | - | - |
| US-TC-01 | As a user, I want a companion page for each application showing how AI assisted me | Must Have | Phase 1 |
| US-TC-02 | As a user, I want the companion page to include specific research and decisions for this application | Must Have | Phase 1 |
| US-TC-03 | As a user, I want to link to the companion page from my resume or cover letter | Must Have | Phase 1 |
| US-TC-04 | As a user, I want to review and edit the companion page before sharing | Must Have | Phase 1 |


# **9. Module 5: Application Tracker**

The Application Tracker is a lightweight CRM for the user’s job search. It logs each application, tracks status, and stores all associated artifacts. The user applies directly to jobs; the tracker does not submit applications on the user’s behalf.

## **9.1 Features**

- Application log: company, role, date applied, status (draft, applied, interviewing, offer, rejected, withdrawn)

- Associated artifacts per application: resume version, cover letter, companion page, job posting snapshot, ideal candidate profile, gap analysis

- Status tracking with timestamped updates

- Draft email with cover letter for submission (editable, copy-paste to email client)

- Notes field for interview feedback, follow-up actions, contacts

- Dashboard view showing pipeline summary across all active applications

## **9.2 User Requirements Specifications**

| **Req ID** | **Requirement** | **Priority** | **Phase** | **Relates To** |
| - | - | - | - | - |
| UR-AT-001 | The system shall automatically create an application record when the user finalizes a resume for a specific job posting. | Must Have | 1 | UR-GA-011 |
| UR-AT-002 | The system shall allow the user to update the status of each application through defined stages: draft, applied, interviewing, offer, rejected, withdrawn. | Must Have | 1 | — |
| UR-AT-003 | The system shall store and provide access to all artifacts associated with each application: resume version, cover letter, companion page, job posting snapshot, ideal candidate profile, and gap analysis. | Must Have | 1 | — |
| UR-AT-004 | The system shall maintain a timestamped log of all status changes for each application. | Must Have | 1 | UR-AT-002 |
| UR-AT-005 | The system should generate a draft submission email incorporating the cover letter, ready for the user to copy and send from their own email client. | Should Have | 1 | UR-GA-013 |
| UR-AT-006 | The system should provide a notes field per application for recording interview feedback, follow-up actions, and contact information. | Should Have | 1 | — |
| UR-AT-007 | The system should provide a dashboard view displaying pipeline summary and status distribution across all active applications. | Should Have | 1 | — |


## **9.3 User Stories**

| **ID** | **User Story** | **Priority** | **Phase** |
| - | - | - | - |
| US-AT-01 | As a user, I want applications logged automatically when I finalize a resume | Must Have | Phase 1 |
| US-AT-02 | As a user, I want to update application status as it progresses | Must Have | Phase 1 |
| US-AT-03 | As a user, I want all artifacts for an application accessible in one place | Must Have | Phase 1 |
| US-AT-04 | As a user, I want a draft submission email I can copy and send | Should Have | Phase 1 |
| US-AT-05 | As a user, I want a dashboard of all active applications | Should Have | Phase 1 |


# **10. Module 6: Career Development Advisor (Phase 2)**

This module is scoped for Phase 2 after the core platform is stable and has accumulated application data. It provides passive, insight-driven career development guidance by analyzing patterns across applications.

## **10.1 Scope**

This is an analytical lens on the Experience Library and Application Tracker, not an active coaching engine. It surfaces patterns and insights; it does not prescribe specific actions.

## **10.2 User Requirements Specifications**

| **Req ID** | **Requirement** | **Priority** | **Phase** | **Relates To** |
| - | - | - | - | - |
| UR-CD-001 | The system shall analyze gap analysis results across multiple applications to identify skills or experience types that recur as gaps. | Should Have | 2 | UR-GA-001 |
| UR-CD-002 | The system shall identify trending skill requirements across the user’s target roles over time. | Should Have | 2 | UR-JA-003 |
| UR-CD-003 | The system shall correlate resume variants and framing choices with application outcomes (interview, offer, rejection) where status data is available. | Should Have | 2 | UR-AT-002 |
| UR-CD-004 | The system shall assess library coverage relative to the user’s target roles and flag thin areas. | Should Have | 2 | UR-EL-014 |
| UR-CD-005 | The system may generate periodic summary reports presenting observations and patterns without prescribing specific actions. | Nice to Have | 2 | — |


# **11. Non-Functional Requirements**

## **11.1 User Requirements Specifications**

| **Req ID** | **Requirement** | **Priority** | **Phase** | **Relates To** |
| - | - | - | - | - |
| UR-NF-001 | The system shall store all user data locally. No data shall be transmitted to third parties or cloud services beyond LLM API calls required for AI features. | Must Have | 1 | — |
| UR-NF-002 | The system shall allow the user to export all personal data in a portable, machine-readable format and to delete all data permanently. | Must Have | 1 | — |
| UR-NF-003 | The system shall use LLM API configurations that do not retain user data for model training purposes. | Must Have | 1 | — |
| UR-NF-004 | The Job Analysis pipeline (posting parsing, company research, ideal candidate profile generation) shall complete within 60 seconds for a typical posting. | Should Have | 1 | — |
| UR-NF-005 | Resume generation (all variants for all sections) shall complete within 30 seconds after gap closure is finalized. | Should Have | 1 | — |
| UR-NF-006 | Experience Library search and retrieval shall return results within 2 seconds for libraries containing up to 500 entries. | Must Have | 1 | — |
| UR-NF-007 | The system shall support configurable LLM backend, allowing the user to swap between different model providers (e.g., Anthropic, OpenAI) via an abstraction layer. | Should Have | 1 | — |
| UR-NF-008 | The system shall support a plugin architecture for adding new document input types and indexing sources. | Nice to Have | 1 | — |
| UR-NF-009 | The system shall support a resume template system allowing custom visual designs and export formats. | Nice to Have | 1 | — |
| UR-NF-010 | The first-run experience (upload one resume + paste one job posting) shall produce a first resume draft within 5 minutes of user interaction time. | Must Have | 1 | — |
| UR-NF-011 | All AI-generated content shall be visually distinguished from user-authored content and shall be fully editable before any use or export. | Must Have | 1 | — |
| UR-NF-012 | The system shall provide a web-based interface accessible via modern browsers without requiring native application installation. | Should Have | 1 | — |
| UR-NF-013 | The system shall use local-first data storage (e.g., SQLite, flat files) with no dependency on external database services. | Must Have | 1 | UR-NF-001 |


# **12. Requirements Traceability Summary**

The following table provides a summary count of all user requirements by module, priority, and phase for project planning and tracking purposes.


| **Module** | **Must Have** | **Should Have** | **Nice to Have** | **Total** |
| - | - | - | - | - |
| Experience Library | 9 | 5 | 1 | 15 |
| Job Analysis Engine | 4 | 3 | 0 | 7 |
| Gap Analysis & Resume | 10 | 2 | 0 | 12 |
| AI Transparency | 5 | 2 | 0 | 7 |
| Application Tracker | 3 | 3 | 0 | 6 |
| Career Dev (Phase 2) | 0 | 4 | 1 | 5 |
| Non-Functional | 6 | 4 | 2 | 12 |


Total Phase 1 requirements: 59. Total Phase 2 requirements: 5. All requirement IDs follow the UR-\[MODULE\]-\[NUMBER\] convention and are designed for direct traceability into technical design documents, test plans, and acceptance criteria.


# **13. Phasing Summary**

## **13.1 Phase 1: Core Platform**

Phase 1 delivers the complete end-to-end workflow:

- Experience Library with all input methods (upload, manual, voice, questionnaire, link indexing)

- Job Analysis Engine with posting analysis and lightweight company research

- Gap Analysis with collaborative gap closure workflow

- Modular Resume Generation with variant selection and user assembly

- AI Transparency Companion Page generation

- Application Tracker with status management and artifact storage

- Optional cover letter and draft submission email generation

## **13.2 Phase 2: Career Development**

- Career Development Advisor with passive pattern detection and insight reporting

- Expanded analytics on application outcomes and resume effectiveness


# **14. Glossary**

| **Term** | **Definition** |
| - | - |
| Experience Library | The structured repository of all user professional experience, skills, values, and credentials. The core data asset. |
| Ideal Candidate Profile | A structured model of the perfect applicant for a specific job, generated by analyzing the posting, company, and industry. |
| Gap Analysis | The comparison between the user’s Experience Library and the Ideal Candidate Profile, classifying gaps by closability. |
| Modular Resume | A resume composed of interchangeable sections, each with multiple AI-generated variants, assembled by the user. |
| AI Transparency Companion | A per-application web page documenting how AI assisted the resume creation process. |
| Minimum Viable Profile | The minimum user input (one resume + one job posting) required to generate a first useful output. |
| Gap Closure | The collaborative process of addressing gaps through reframing, prompting, or acknowledging genuine gaps. |
| Progressive Depth | The design principle that the system delivers value immediately and grows more powerful over time. |


---

# **Appendix A: Implementation Status**

The following summarizes the current state of Phase 1 implementation as of March 2026.

## **A.1 Completed Features**

### Experience Library
- Professional timeline with CRUD (roles, accomplishments, projects, transitions)
- Skills inventory with proficiency levels and AI-inferred categorization
- Professional identity (values, leadership style, passions)
- Education & credentials management
- Evidence/links library with URL indexing
- Tagging system with cross-referencing between entries
- Profile completeness indicator
- Document upload with AI-powered extraction (resume parsing)
- Context-aware extraction with intelligent merge (deduplication of existing entries)
- Career Chat — conversational AI interface for building the experience library through natural dialogue

### Job Analysis Engine
- URL-based job posting fetch and analysis (via Firecrawl)
- Pasted text analysis
- Ideal Candidate Profile generation with must-have / nice-to-have / culture signals
- LinkedIn URL auto-detection from job posting text

### Gap Analysis & Resume Engine
- Structured gap analysis with skill/experience/culture dimensions
- Gap classification (reframable, promptable, genuine)
- Collaborative gap closure with targeted questions and action cards
- Modular resume generation with multiple variants per section
- Variant selection, editing, and section reordering
- PDF export via RenderCV integration (with DomPDF fallback)
- DOCX export
- Resume template system with multiple professional templates (Classic, sb2nov, ModernCV, Engineering Resumes)

### AI Transparency Companion
- Per-application transparency page showing AI decisions, research, and editorial choices
- Shareable public URL for each transparency page
- Styled consistently with resume output

### Application Tracker
- Automatic application creation on resume finalization
- Status tracking with timestamped status change log (draft → applied → interviewing → offer → rejected → withdrawn)
- Dashboard with pipeline summary and application statistics
- All artifacts accessible per application

### Pipeline Assistants
- Context-aware AI assistant panels at each pipeline step (job analysis, gap analysis, resume builder)
- Tool-use capability allowing assistants to directly modify data (update gap actions, adjust resume sections)
- Conversational interface with step-specific guidance

### Platform & Branding
- Production branding with custom CareerForge icon and favicons
- Premium landing page with animated sections, feature showcase, and responsive design
- Dark mode support throughout all pages
- Two-factor authentication (TOTP)
- Accessibility: reduced-motion media queries, semantic HTML, keyboard navigation

## **A.2 In Progress / Partially Implemented**

| Feature | Status | Notes |
| - | - | - |
| Voice interview mode (UR-EL-011) | Not started | Requires speech-to-text integration |
| Cover letter generation (UR-GA-012) | Not started | Planned for fast-follow |
| Draft submission email (UR-GA-013) | Not started | Planned for fast-follow |
| Company research depth (UR-JA-004) | Partial | Basic analysis done; deeper crawling and tone analysis planned |
| Resume live preview (UR-GA-010) | Partial | Preview via export; inline WYSIWYG preview planned |

## **A.3 Phase 2 (Not Started)**

- Career Development Advisor (UR-CD-001 through UR-CD-005)
- Application outcome analytics
- Recurring gap pattern detection


# **Appendix B: New Requirements (Post-URD v1)**

The following requirements emerged during implementation and were not in the original URD. They are documented here for traceability.

## **B.1 Resume Templates**

| **Req ID** | **Requirement** | **Priority** | **Phase** | **Status** |
| - | - | - | - | - |
| UR-GA-014 | The system shall support multiple professional resume templates that control the visual layout and styling of exported resumes. | Should Have | 1 | Implemented |
| UR-GA-015 | The system shall integrate with RenderCV for high-fidelity PDF generation using LaTeX-quality templates. | Should Have | 1 | Implemented |
| UR-GA-016 | The system shall fall back to DomPDF for PDF generation when RenderCV is not available. | Must Have | 1 | Implemented |

## **B.2 Pipeline Assistants**

| **Req ID** | **Requirement** | **Priority** | **Phase** | **Status** |
| - | - | - | - | - |
| UR-PA-001 | The system shall provide a context-aware AI assistant panel at each pipeline step (job analysis, gap analysis, resume building). | Should Have | 1 | Implemented |
| UR-PA-002 | Pipeline assistants shall have tool-use capability to directly modify pipeline data (e.g., update gap actions, adjust resume sections) with user confirmation. | Should Have | 1 | Implemented |
| UR-PA-003 | Pipeline assistants shall maintain conversational context within a pipeline step. | Should Have | 1 | Implemented |

## **B.3 Career Chat**

| **Req ID** | **Requirement** | **Priority** | **Phase** | **Status** |
| - | - | - | - | - |
| UR-EL-016 | The system shall provide a conversational AI chat interface for building the experience library through natural dialogue. | Should Have | 1 | Implemented |
| UR-EL-017 | The career chat shall support markdown rendering for structured AI responses. | Should Have | 1 | Implemented |

## **B.4 Context-Aware Extraction**

| **Req ID** | **Requirement** | **Priority** | **Phase** | **Status** |
| - | - | - | - | - |
| UR-EL-018 | The system shall perform intelligent merge during document extraction, detecting and deduplicating entries that overlap with existing library content. | Must Have | 1 | Implemented |
| UR-EL-019 | The system shall present extraction results in a review interface allowing the user to accept, modify, or reject each extracted entry before committing to the library. | Must Have | 1 | Implemented |

## **B.5 Landing Page & Public Presence**

| **Req ID** | **Requirement** | **Priority** | **Phase** | **Status** |
| - | - | - | - | - |
| UR-NF-014 | The system shall present a production-quality landing page with feature overview, trust signals, and calls-to-action for unauthenticated visitors. | Should Have | 1 | Implemented |
| UR-NF-015 | The landing page shall support dark mode, responsive layouts (mobile/tablet/desktop), and respect prefers-reduced-motion. | Should Have | 1 | Implemented |
| UR-NF-016 | All template/starter-kit branding remnants shall be replaced with CareerForge branding (favicon, app icon, name fallbacks, email placeholders). | Must Have | 1 | Implemented |


*End of Document*
