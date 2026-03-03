<?php

namespace App\Http\Controllers\ExperienceLibrary;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExperienceLibrary\StoreTagRequest;
use App\Models\Accomplishment;
use App\Models\Experience;
use App\Models\Project;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function index(Request $request): Response
    {
        $tags = $request->user()->tags()
            ->withCount(['experiences', 'accomplishments', 'projects'])
            ->orderBy('name')
            ->get();

        return Inertia::render('experience-library/tags', [
            'tags' => $tags,
        ]);
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        $request->user()->tags()->create($request->validated());

        return back()->with('success', 'Tag created.');
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        abort_unless($tag->user_id === $request->user()->id, 403);

        $request->validate([
            'name' => ['required', 'string', 'max:50'],
        ]);

        $tag->update(['name' => $request->input('name')]);

        return back()->with('success', 'Tag updated.');
    }

    public function destroy(Request $request, Tag $tag): RedirectResponse
    {
        abort_unless($tag->user_id === $request->user()->id, 403);

        $tag->delete();

        return back()->with('success', 'Tag deleted.');
    }

    public function toggle(Request $request): RedirectResponse
    {
        $request->validate([
            'tag_id' => ['required', 'exists:tags,id'],
            'taggable_id' => ['required', 'integer'],
            'taggable_type' => ['required', 'string', 'in:experience,accomplishment,project'],
        ]);

        $tag = Tag::findOrFail($request->input('tag_id'));
        abort_unless($tag->user_id === $request->user()->id, 403);

        $model = match ($request->input('taggable_type')) {
            'experience' => Experience::findOrFail($request->input('taggable_id')),
            'accomplishment' => Accomplishment::findOrFail($request->input('taggable_id')),
            'project' => Project::findOrFail($request->input('taggable_id')),
        };

        $model->tags()->toggle($tag->id);

        return back()->with('success', 'Tag toggled.');
    }
}
