<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataExportController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = [
            'exported_at' => now()->toIso8601String(),
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'professional_identity' => $user->professionalIdentity,
            'experiences' => $user->experiences()
                ->with(['accomplishments.skills', 'projects.skills', 'skills', 'tags'])
                ->orderByDesc('started_at')
                ->get(),
            'skills' => $user->skills()
                ->orderBy('category')
                ->orderBy('name')
                ->get(),
            'education' => $user->educationEntries()
                ->orderByDesc('completed_at')
                ->get(),
            'evidence' => $user->evidenceEntries()->get(),
            'job_postings' => $user->jobPostings()
                ->with('idealCandidateProfile')
                ->orderByDesc('created_at')
                ->get(),
            'applications' => $user->applications()
                ->with(['statusChanges', 'applicationNotes'])
                ->orderByDesc('created_at')
                ->get(),
        ];

        $filename = 'careerforge-export-'.now()->format('Y-m-d').'.json';

        return response()->json($data, headers: [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
