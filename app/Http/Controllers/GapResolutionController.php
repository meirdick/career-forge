<?php

namespace App\Http\Controllers;

use App\Ai\Agents\GapReframer;
use App\Enums\SkillProficiency;
use App\Models\Experience;
use App\Models\GapAnalysis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GapResolutionController extends Controller
{
    public function reframe(Request $request, GapAnalysis $gapAnalysis, string $gapArea): JsonResponse
    {
        abort_unless($gapAnalysis->user_id === $request->user()->id, 403);

        $request->validate([
            'experience_id' => 'required|exists:experiences,id',
        ]);

        $gap = $this->findGap($gapAnalysis, $gapArea);

        if (! $gap) {
            abort(404, 'Gap not found.');
        }

        $experience = Experience::where('id', $request->input('experience_id'))
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $gapAnalysis->load('idealCandidateProfile.jobPosting');
        $jobPosting = $gapAnalysis->idealCandidateProfile->jobPosting;

        $prompt = view('prompts.gap-reframe', [
            'gap' => $gap,
            'experience' => $experience,
            'jobTitle' => $jobPosting->title,
            'company' => $jobPosting->company,
        ])->render();

        $response = (new GapReframer)->prompt($prompt);

        $gapAnalysis->setResolutionFor($gapArea, [
            'status' => 'pending_review',
            'experience_id' => $experience->id,
            'reframe_original' => $experience->description,
            'reframe_suggestion' => $response['reframed_content'],
            'rationale' => $response['rationale'],
        ]);

        return response()->json([
            'reframed_content' => $response['reframed_content'],
            'rationale' => $response['rationale'],
        ]);
    }

    public function acceptReframe(Request $request, GapAnalysis $gapAnalysis, string $gapArea): JsonResponse
    {
        abort_unless($gapAnalysis->user_id === $request->user()->id, 403);

        $resolution = $gapAnalysis->getResolutionFor($gapArea);

        if (! $resolution || $resolution['status'] !== 'pending_review') {
            abort(422, 'No pending reframe to accept.');
        }

        $experience = Experience::where('id', $resolution['experience_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $currentDescription = $experience->description ?? '';
        $reframedContent = $resolution['reframe_suggestion'];

        $experience->update([
            'description' => $currentDescription
                ? $currentDescription."\n\n".$reframedContent
                : $reframedContent,
        ]);

        $gapAnalysis->setResolutionFor($gapArea, [
            ...$resolution,
            'status' => 'resolved',
        ]);

        return response()->json(['success' => true]);
    }

    public function rejectReframe(Request $request, GapAnalysis $gapAnalysis, string $gapArea): JsonResponse
    {
        abort_unless($gapAnalysis->user_id === $request->user()->id, 403);

        $resolution = $gapAnalysis->getResolutionFor($gapArea);

        if (! $resolution || $resolution['status'] !== 'pending_review') {
            abort(422, 'No pending reframe to reject.');
        }

        $gapAnalysis->setResolutionFor($gapArea, [
            ...$resolution,
            'status' => 'unresolved',
        ]);

        return response()->json(['success' => true]);
    }

    public function answer(Request $request, GapAnalysis $gapAnalysis, string $gapArea): JsonResponse
    {
        abort_unless($gapAnalysis->user_id === $request->user()->id, 403);

        $request->validate([
            'answer' => 'required|string|max:5000',
        ]);

        $gap = $this->findGap($gapAnalysis, $gapArea);

        if (! $gap) {
            abort(404, 'Gap not found.');
        }

        $user = $request->user();

        $accomplishment = $user->accomplishments()->create([
            'title' => $gap['area'],
            'description' => $request->input('answer'),
            'sort_order' => 0,
            'source_type' => 'gap_analysis',
            'source_id' => $gapAnalysis->id,
        ]);

        $gapAnalysis->setResolutionFor($gapArea, [
            'status' => 'resolved',
            'answer' => $request->input('answer'),
            'accomplishment_id' => $accomplishment->id,
        ]);

        return response()->json(['success' => true]);
    }

    public function acknowledge(Request $request, GapAnalysis $gapAnalysis, string $gapArea): JsonResponse
    {
        abort_unless($gapAnalysis->user_id === $request->user()->id, 403);

        $gap = $this->findGap($gapAnalysis, $gapArea);

        if (! $gap) {
            abort(404, 'Gap not found.');
        }

        $gapAnalysis->setResolutionFor($gapArea, [
            'status' => 'acknowledged',
            'note' => $request->input('note', ''),
        ]);

        return response()->json(['success' => true]);
    }

    public function organize(Request $request, GapAnalysis $gapAnalysis): JsonResponse
    {
        abort_unless($gapAnalysis->user_id === $request->user()->id, 403);

        $request->validate([
            'updates' => 'required|array|min:1',
            'updates.*.type' => 'required|in:accomplishment,skill',
            'updates.*.id' => 'required|integer',
            'updates.*.experience_id' => 'nullable|integer|exists:experiences,id',
            'updates.*.proficiency' => 'nullable|string|in:beginner,intermediate,advanced,expert',
        ]);

        $user = $request->user();
        $userExperienceIds = $user->experiences()->pluck('id');

        foreach ($request->input('updates') as $update) {
            if (isset($update['experience_id']) && ! $userExperienceIds->contains($update['experience_id'])) {
                abort(422, 'Experience does not belong to you.');
            }

            if ($update['type'] === 'accomplishment') {
                $user->accomplishments()
                    ->where('id', $update['id'])
                    ->where('source_type', 'gap_analysis')
                    ->where('source_id', $gapAnalysis->id)
                    ->update(['experience_id' => $update['experience_id'] ?? null]);
            }

            if ($update['type'] === 'skill' && isset($update['proficiency'])) {
                $user->skills()
                    ->where('id', $update['id'])
                    ->where('source_type', 'gap_analysis')
                    ->where('source_id', $gapAnalysis->id)
                    ->update(['proficiency' => SkillProficiency::from($update['proficiency'])]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * @return array{area: string, description: string, classification: string, suggestion: string}|null
     */
    private function findGap(GapAnalysis $gapAnalysis, string $gapArea): ?array
    {
        return collect($gapAnalysis->gaps)->firstWhere('area', $gapArea);
    }
}
