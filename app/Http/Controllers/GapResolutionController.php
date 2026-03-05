<?php

namespace App\Http\Controllers;

use App\Ai\Agents\GapReframer;
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

        $user->accomplishments()->create([
            'title' => $gap['area'],
            'description' => $request->input('answer'),
            'sort_order' => 0,
        ]);

        $gapAnalysis->setResolutionFor($gapArea, [
            'status' => 'resolved',
            'answer' => $request->input('answer'),
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

    /**
     * @return array{area: string, description: string, classification: string, suggestion: string}|null
     */
    private function findGap(GapAnalysis $gapAnalysis, string $gapArea): ?array
    {
        return collect($gapAnalysis->gaps)->firstWhere('area', $gapArea);
    }
}
