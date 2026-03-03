<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $stats = [
            'experiences' => $user->experiences()->count(),
            'skills' => $user->skills()->count(),
            'accomplishments' => $user->experiences()->withCount('accomplishments')->get()->sum('accomplishments_count'),
            'applications' => $user->applications()->count(),
        ];

        $recentApplications = $user->applications()
            ->with('jobPosting:id,title')
            ->latest()
            ->limit(5)
            ->get(['id', 'company', 'role', 'status', 'job_posting_id', 'created_at']);

        $recentExperiences = $user->experiences()
            ->latest('started_at')
            ->limit(3)
            ->get(['id', 'company', 'title', 'started_at', 'is_current']);

        return Inertia::render('dashboard', [
            'stats' => $stats,
            'recentApplications' => $recentApplications,
            'recentExperiences' => $recentExperiences,
        ]);
    }
}
