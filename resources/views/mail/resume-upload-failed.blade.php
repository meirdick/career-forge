<x-mail::message>
# We Had Trouble Analyzing Your Resume

**{{ $filename }}**

We weren't able to fully extract data from your resume. This can happen with heavily formatted PDFs, image-based resumes, or unusual layouts.

## What You Can Try

- **Re-analyze** --- Sometimes a retry works, especially if it was a temporary issue
- **Try a different format** --- Plain text (.txt) or Word (.docx) files often parse more reliably than complex PDFs
- **Upload a simpler version** --- If your resume uses tables, columns, or graphics, a single-column version parses better

<x-mail::button :url="$url">
Try Again
</x-mail::button>

If you continue having trouble, just reply to this email and we'll help.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
