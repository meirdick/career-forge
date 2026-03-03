<?php

namespace App\Http\Controllers\ExperienceLibrary;

use App\Http\Controllers\Controller;
use App\Jobs\ParseResumeJob;
use App\Models\Accomplishment;
use App\Models\Document;
use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\Project;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ResumeUploadController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('experience-library/upload');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,docx,doc,txt|max:10240',
        ]);

        $file = $request->file('file');
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

        return to_route('resume-upload.review', $document)
            ->with('success', 'Resume uploaded. Parsing in progress...');
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

        foreach ($request->input('experiences', []) as $index => $expData) {
            $experience = $user->experiences()->create([
                'company' => $expData['company'],
                'title' => $expData['title'],
                'location' => $expData['location'] ?? null,
                'started_at' => $expData['started_at'],
                'ended_at' => $expData['ended_at'] ?? null,
                'is_current' => $expData['is_current'] ?? false,
                'description' => $expData['description'] ?? null,
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
                'impact' => $accData['impact'] ?? null,
                'sort_order' => 0,
            ]);
        }

        foreach ($request->input('education', []) as $eduData) {
            $user->educationEntries()->create([
                'type' => $eduData['type'],
                'institution' => $eduData['institution'],
                'title' => $eduData['title'],
                'field' => $eduData['field'] ?? null,
                'completed_at' => $eduData['completed_at'] ?? null,
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
                'role' => $projData['role'] ?? null,
                'outcome' => $projData['outcome'] ?? null,
                'sort_order' => 0,
            ]);
        }

        Cache::forget("resume-parse:{$document->id}");

        return to_route('experience-library.index')
            ->with('success', 'Resume data imported to your experience library.');
    }
}
