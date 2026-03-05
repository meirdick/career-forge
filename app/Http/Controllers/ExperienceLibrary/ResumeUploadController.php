<?php

namespace App\Http\Controllers\ExperienceLibrary;

use App\Http\Controllers\Controller;
use App\Jobs\ParseResumeJob;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

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
            'files.*' => 'required|file|mimes:pdf,docx,doc,txt,json|max:10240',
        ]);

        $firstDocument = null;

        foreach ($request->file('files') as $file) {
            $path = $file->store('resume-uploads', 'local');

            $document = $request->user()->documents()->create([
                'filename' => $file->getClientOriginalName(),
                'disk' => 'local',
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'metadata' => ['purpose' => 'resume_import'],
            ]);

            Cache::put("resume-parse:{$document->id}", ['status' => 'processing'], now()->addHour());

            ParseResumeJob::dispatch($request->user(), $document);

            $firstDocument ??= $document;
        }

        return to_route('resume-upload.review', $firstDocument)
            ->with('success', count($request->file('files')) > 1
                ? 'Resumes uploaded. Parsing in progress...'
                : 'Resume uploaded. Parsing in progress...');
    }

    public function review(Request $request, Document $document): Response
    {
        abort_unless($document->user_id === $request->user()->id, 403);

        $cacheKey = "resume-parse:{$document->id}";
        $parseResult = Cache::get($cacheKey, ['status' => 'processing']);

        return Inertia::render('experience-library/review-import', [
            'document' => $document,
            'parseResult' => $parseResult,
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

    public function commit(Request $request, Document $document): RedirectResponse
    {
        abort_unless($document->user_id === $request->user()->id, 403);

        $request->validate([
            'experiences' => 'array',
            'experiences.*.company' => 'required|string',
            'experiences.*.title' => 'required|string',
            'experiences.*.started_at' => 'required|date',
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
        ]);

        $user = $request->user();
        $experienceMap = [];
        $nullish = static fn ($value) => in_array($value, [null, 'null', ''], true) ? null : $value;

        foreach ($request->input('experiences', []) as $index => $expData) {
            $experience = $user->experiences()->create([
                'company' => $expData['company'],
                'title' => $expData['title'],
                'location' => $nullish($expData['location'] ?? null),
                'started_at' => $expData['started_at'],
                'ended_at' => $nullish($expData['ended_at'] ?? null),
                'is_current' => $expData['is_current'] ?? false,
                'description' => $nullish($expData['description'] ?? null),
                'sort_order' => $index,
            ]);
            $experienceMap[$index] = $experience;
        }

        foreach ($request->input('skills', []) as $skillData) {
            $user->skills()->firstOrCreate(
                ['name' => $skillData['name']],
                ['category' => $skillData['category']],
            );
        }

        foreach ($request->input('accomplishments', []) as $accData) {
            $experienceId = isset($accData['experience_index'], $experienceMap[$accData['experience_index']])
                ? $experienceMap[$accData['experience_index']]->id
                : null;

            $user->accomplishments()->create([
                'experience_id' => $experienceId,
                'title' => $accData['title'],
                'description' => $accData['description'],
                'impact' => $nullish($accData['impact'] ?? null),
                'sort_order' => 0,
            ]);
        }

        foreach ($request->input('education', []) as $eduData) {
            $user->educationEntries()->create([
                'type' => $eduData['type'],
                'institution' => $eduData['institution'],
                'title' => $eduData['title'],
                'field' => $nullish($eduData['field'] ?? null),
                'completed_at' => $nullish($eduData['completed_at'] ?? null),
                'sort_order' => 0,
            ]);
        }

        foreach ($request->input('projects', []) as $projData) {
            $experienceId = isset($projData['experience_index'], $experienceMap[$projData['experience_index']])
                ? $experienceMap[$projData['experience_index']]->id
                : null;

            $user->projects()->create([
                'experience_id' => $experienceId,
                'name' => $projData['name'],
                'description' => $projData['description'],
                'role' => $nullish($projData['role'] ?? null),
                'outcome' => $nullish($projData['outcome'] ?? null),
                'sort_order' => 0,
            ]);
        }

        Cache::forget("resume-parse:{$document->id}");

        return to_route('experience-library.index')
            ->with('success', 'Resume data imported to your experience library.');
    }
}
