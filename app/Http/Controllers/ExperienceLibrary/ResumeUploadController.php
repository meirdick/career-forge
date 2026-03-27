<?php

namespace App\Http\Controllers\ExperienceLibrary;

use App\Http\Controllers\Controller;
use App\Jobs\ParseResumeJob;
use App\Models\Document;
use App\Services\ExperienceImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResumeUploadController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('experience-library/upload', [
            'documents' => $request->user()->documents()
                ->where('metadata->purpose', 'resume_import')
                ->latest()
                ->get()
                ->map(fn (Document $doc) => [
                    'id' => $doc->id,
                    'filename' => $doc->filename,
                    'size' => $doc->size,
                    'created_at' => $doc->created_at->diffForHumans(),
                    'status' => Cache::get("resume-parse:{$doc->id}", ['status' => 'completed'])['status'],
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|mimes:pdf,docx,doc,txt,json|max:20480',
        ]);

        foreach ($request->file('files') as $file) {
            $path = $file->store('resume-uploads');

            $document = $request->user()->documents()->create([
                'filename' => Str::ascii($file->getClientOriginalName()),
                'disk' => config('filesystems.default'),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'metadata' => ['purpose' => 'resume_import'],
            ]);

            Cache::put("resume-parse:{$document->id}", ['status' => 'processing'], now()->addHour());

            ParseResumeJob::dispatch($request->user(), $document);
        }

        return to_route('resume-upload.create')
            ->with('success', count($request->file('files')) > 1
                ? 'Resumes uploaded. Parsing in progress...'
                : 'Resume uploaded. Parsing in progress...');
    }

    public function review(Request $request, Document $document, ExperienceImportService $importService): Response
    {
        abort_unless($document->user_id === $request->user()->id, 403);

        $cacheKey = "resume-parse:{$document->id}";
        $parseResult = Cache::get($cacheKey);

        if (! $parseResult && $document->parsed_data) {
            $parseResult = [
                'status' => 'completed',
                'data' => $document->parsed_data,
            ];
        }

        $parseResult ??= ['status' => 'processing'];

        $matchAnalysis = null;
        if (($parseResult['status'] ?? null) === 'completed' && isset($parseResult['data'])) {
            $matchAnalysis = $importService->analyze($request->user(), $parseResult['data']);
        }

        return Inertia::render('experience-library/review-import', [
            'document' => $document,
            'parseResult' => $parseResult,
            'matchAnalysis' => $matchAnalysis,
        ]);
    }

    public function retry(Request $request, Document $document): RedirectResponse
    {
        abort_unless($document->user_id === $request->user()->id, 403);

        Cache::put("resume-parse:{$document->id}", ['status' => 'processing'], now()->addHour());

        ParseResumeJob::dispatch($request->user(), $document);

        return to_route('resume-upload.review', $document)
            ->with('success', 'Re-parsing in progress...');
    }

    public function download(Request $request, Document $document): StreamedResponse
    {
        abort_unless($document->user_id === $request->user()->id, 403);

        return Storage::disk($document->disk)->download($document->path, $document->filename);
    }

    public function commit(Request $request, Document $document, ExperienceImportService $importService): RedirectResponse
    {
        abort_unless($document->user_id === $request->user()->id, 403);

        $request->validate([
            'experiences' => 'array',
            'experiences.*.company' => 'required|string',
            'experiences.*.title' => 'required|string',
            'experiences.*.started_at' => 'nullable|date',
            'accomplishments' => 'array',
            'accomplishments.*.title' => 'required|string',
            'accomplishments.*.description' => 'required|string',
            'skills' => 'array',
            'skills.*.name' => 'required|string',
            'skills.*.category' => 'required|string',
            'education' => 'array',
            'education.*.type' => 'required|string',
            'education.*.institution' => 'required|string',
            'education.*.title' => 'required|string',
            'projects' => 'array',
            'projects.*.name' => 'required|string',
            'projects.*.description' => 'required|string',
            'urls' => 'array',
            'urls.*.url' => 'required|string',
            'urls.*.type' => 'required|string',
            'urls.*.label' => 'nullable|string',
        ]);

        $stats = $importService->import($request->user(), $request->only([
            'experiences', 'accomplishments', 'skills', 'education', 'projects', 'urls',
        ]));

        Cache::put("resume-parse:{$document->id}", ['status' => 'imported'], now()->addYear());

        return to_route('experience-library.index')
            ->with('success', ExperienceImportService::buildImportMessage($stats));
    }
}
