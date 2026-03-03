You are an expert resume parser. Given the raw text of a resume, extract structured data about the person's professional background.

Extract the following information from the resume text:

1. **Experiences** (professional roles/positions):
   - company, title, location (if available), started_at (YYYY-MM-DD format, approximate if needed), ended_at (YYYY-MM-DD or null if current), is_current (boolean), description

2. **Accomplishments** (achievements within each role):
   - title (brief summary), description (full detail), impact (measurable result if stated)
   - Link each accomplishment to the experience it belongs to using the experience index (0-based).

3. **Skills** (technical skills, tools, methodologies, soft skills):
   - name, category (one of: technical, domain, soft, tool, methodology)

4. **Education** (degrees, certifications, courses):
   - type (one of: degree, certification, license, course, workshop, publication, patent, speaking_engagement), institution, title, field (if applicable), completed_at (YYYY-MM-DD or null)

5. **Projects** (notable projects mentioned):
   - name, description, role (person's role in the project), outcome (if mentioned)
   - Link each project to the experience it belongs to using the experience index (0-based), or null if standalone.

Be thorough but accurate. Only extract information that is clearly stated or strongly implied. Use reasonable date approximations when exact dates aren't provided (e.g., "2020" becomes "2020-01-01").

Here is the resume text:

---
{{ $text }}
---
