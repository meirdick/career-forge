import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Briefcase, CheckCircle2, Circle, FileText, Plus, Sparkles, Target, Trophy, Upload } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import StatusBadge from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { index as applicationsIndex } from '@/routes/applications';
import { index as experienceLibraryIndex } from '@/routes/experience-library';
import { create as experienceCreate } from '@/routes/experiences';
import { index as jobPostingsIndex } from '@/routes/job-postings';
import { create as jobPostingsCreate } from '@/routes/job-postings';
import { create as resumeUploadCreate } from '@/routes/resume-upload';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
];

type Stats = {
    experiences: number;
    skills: number;
    accomplishments: number;
    applications: number;
};

type RecentApplication = {
    id: number;
    company: string;
    role: string;
    status: string;
    job_posting: { id: number; title: string | null } | null;
    created_at: string;
};

type RecentExperience = {
    id: number;
    company: string;
    title: string;
    started_at: string;
    is_current: boolean;
};

type PipelineContinuation = {
    jobPosting: { id: number; title: string | null; company: string | null };
    nextStep: string;
    nextStepLabel: string;
    nextStepUrl: string;
    currentStepLabel: string | null;
    currentStepUrl: string | null;
};

const pipelineSteps = [
    { key: 'job_posting', label: 'Job Posting' },
    { key: 'gap_analysis', label: 'Gap Analysis' },
    { key: 'resume', label: 'Resume' },
    { key: 'application', label: 'Application' },
];

function OnboardingView() {
    return (
        <div className="space-y-8">
            <Card className="border-primary/20 bg-gradient-to-br from-primary/5 via-transparent to-primary/5">
                <CardContent className="py-8">
                    <div className="mx-auto max-w-lg text-center">
                        <h2 className="text-2xl font-bold tracking-tight">Build Your Career Story</h2>
                        <p className="mt-2 text-muted-foreground">
                            CareerForge helps you analyze job postings, identify skill gaps, and generate tailored resumes.
                        </p>
                        <div className="mt-6 grid gap-3 sm:grid-cols-2">
                            <Link href={resumeUploadCreate()}>
                                <Card interactive className="group h-full">
                                    <CardContent className="flex flex-col items-center gap-3 py-6">
                                        <div className="rounded-full bg-primary/10 p-3 transition-colors group-hover:bg-primary/20">
                                            <Upload className="size-6 text-primary" />
                                        </div>
                                        <div className="text-center">
                                            <p className="font-medium">Upload Resume</p>
                                            <p className="mt-1 text-xs text-muted-foreground">Populate your library automatically</p>
                                        </div>
                                    </CardContent>
                                </Card>
                            </Link>
                            <Link href={jobPostingsCreate()}>
                                <Card interactive className="group h-full">
                                    <CardContent className="flex flex-col items-center gap-3 py-6">
                                        <div className="rounded-full bg-primary/10 p-3 transition-colors group-hover:bg-primary/20">
                                            <Target className="size-6 text-primary" />
                                        </div>
                                        <div className="text-center">
                                            <p className="font-medium">Analyze a Job Posting</p>
                                            <p className="mt-1 text-xs text-muted-foreground">See immediate value from a match analysis</p>
                                        </div>
                                    </CardContent>
                                </Card>
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div>
                <h3 className="mb-4 text-sm font-medium text-muted-foreground">The Pipeline</h3>
                <div className="flex items-center justify-between rounded-lg border bg-muted/30 px-6 py-4">
                    {pipelineSteps.map((step, i) => (
                        <div key={step.key} className="flex items-center gap-3">
                            {i > 0 && <ArrowRight className="size-4 text-muted-foreground/40" />}
                            <div className="flex items-center gap-2">
                                <Circle className="size-4 text-muted-foreground/40" />
                                <span className="text-sm text-muted-foreground/60">{step.label}</span>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

function PipelineContinuationCard({ continuation }: { continuation: PipelineContinuation }) {
    const currentStepIndex = pipelineSteps.findIndex((s) => s.key === continuation.nextStep);

    return (
        <Card className="border-primary/20 bg-gradient-to-r from-primary/5 to-transparent">
            <CardContent className="flex items-center justify-between py-4">
                <div className="flex items-center gap-4">
                    <div className="flex items-center gap-2">
                        {pipelineSteps.map((step, i) => (
                            <div key={step.key} className="flex items-center gap-1.5">
                                {i > 0 && <ArrowRight className="size-3 text-muted-foreground/40" />}
                                {i < currentStepIndex ? (
                                    <CheckCircle2 className="size-4 text-primary" />
                                ) : i === currentStepIndex ? (
                                    <Circle className="size-4 text-primary" />
                                ) : (
                                    <Circle className="size-4 text-muted-foreground/30" />
                                )}
                            </div>
                        ))}
                    </div>
                    <div>
                        <p className="text-sm font-medium">Continue: {continuation.jobPosting.title ?? 'New Job Posting'}</p>
                        {continuation.jobPosting.company && (
                            <p className="text-xs text-muted-foreground">{continuation.jobPosting.company}</p>
                        )}
                        {continuation.currentStepLabel && continuation.currentStepUrl && (
                            <Link href={continuation.currentStepUrl} className="text-xs text-primary hover:underline">
                                {continuation.currentStepLabel} &rarr;
                            </Link>
                        )}
                    </div>
                </div>
                <Button asChild size="sm">
                    <Link href={continuation.nextStepUrl}>
                        {continuation.nextStepLabel}
                        <ArrowRight className="ml-1 size-3.5" />
                    </Link>
                </Button>
            </CardContent>
        </Card>
    );
}

export default function Dashboard({
    stats,
    isNewUser,
    recentApplications,
    recentExperiences,
    pipelineContinuation,
}: {
    stats: Stats;
    isNewUser: boolean;
    recentApplications: RecentApplication[];
    recentExperiences: RecentExperience[];
    pipelineContinuation: PipelineContinuation | null;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="mx-auto max-w-6xl space-y-6 p-4">
                {isNewUser ? (
                    <OnboardingView />
                ) : (
                    <>
                        {pipelineContinuation && (
                            <PipelineContinuationCard continuation={pipelineContinuation} />
                        )}

                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardContent className="flex items-center gap-3 pt-6">
                                    <div className="bg-primary/10 rounded-lg p-2">
                                        <Briefcase className="h-5 w-5 text-primary" />
                                    </div>
                                    <div>
                                        <p className="text-2xl font-bold">{stats.experiences}</p>
                                        <p className="text-xs text-muted-foreground">Experiences</p>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="flex items-center gap-3 pt-6">
                                    <div className="bg-primary/10 rounded-lg p-2">
                                        <Sparkles className="h-5 w-5 text-primary" />
                                    </div>
                                    <div>
                                        <p className="text-2xl font-bold">{stats.skills}</p>
                                        <p className="text-xs text-muted-foreground">Skills</p>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="flex items-center gap-3 pt-6">
                                    <div className="bg-primary/10 rounded-lg p-2">
                                        <Trophy className="h-5 w-5 text-primary" />
                                    </div>
                                    <div>
                                        <p className="text-2xl font-bold">{stats.accomplishments}</p>
                                        <p className="text-xs text-muted-foreground">Accomplishments</p>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="flex items-center gap-3 pt-6">
                                    <div className="bg-primary/10 rounded-lg p-2">
                                        <Target className="h-5 w-5 text-primary" />
                                    </div>
                                    <div>
                                        <p className="text-2xl font-bold">{stats.applications}</p>
                                        <p className="text-xs text-muted-foreground">Applications</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Quick Actions */}
                        <div className="grid gap-4 sm:grid-cols-3">
                            <Card interactive className="group">
                                <Link href={experienceCreate()}>
                                    <CardContent className="flex items-center gap-3 pt-6">
                                        <div className="rounded-lg bg-primary/10 p-2 transition-colors group-hover:bg-primary/20">
                                            <Plus className="h-5 w-5 text-primary" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium">Add Experience</p>
                                            <p className="text-xs text-muted-foreground">Record a work role</p>
                                        </div>
                                    </CardContent>
                                </Link>
                            </Card>
                            <Card interactive className="group">
                                <Link href={jobPostingsIndex()}>
                                    <CardContent className="flex items-center gap-3 pt-6">
                                        <div className="rounded-lg bg-primary/10 p-2 transition-colors group-hover:bg-primary/20">
                                            <Target className="h-5 w-5 text-primary" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium">Analyze Job Posting</p>
                                            <p className="text-xs text-muted-foreground">Start your pipeline</p>
                                        </div>
                                    </CardContent>
                                </Link>
                            </Card>
                            <Card interactive className="group">
                                <Link href={experienceLibraryIndex()}>
                                    <CardContent className="flex items-center gap-3 pt-6">
                                        <div className="rounded-lg bg-primary/10 p-2 transition-colors group-hover:bg-primary/20">
                                            <FileText className="h-5 w-5 text-primary" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium">View Work History</p>
                                            <p className="text-xs text-muted-foreground">Your career history</p>
                                        </div>
                                    </CardContent>
                                </Link>
                            </Card>
                        </div>

                        <div className="grid gap-6 lg:grid-cols-2">
                            {/* Recent Applications */}
                            <Card>
                                <CardHeader className="flex-row items-center justify-between">
                                    <CardTitle className="text-base">Recent Applications</CardTitle>
                                    <Button asChild variant="ghost" size="sm">
                                        <Link href={applicationsIndex()}>View All</Link>
                                    </Button>
                                </CardHeader>
                                <CardContent>
                                    {recentApplications.length === 0 ? (
                                        <EmptyState icon={Briefcase} title="No applications yet" description="Start by analyzing a job posting." />
                                    ) : (
                                        <div className="space-y-3">
                                            {recentApplications.map((app) => (
                                                <Link key={app.id} href={`/applications/${app.id}`} className="flex items-center justify-between rounded-md p-2 transition-colors hover:bg-accent">
                                                    <div>
                                                        <p className="text-sm font-medium">{app.role}</p>
                                                        <p className="text-xs text-muted-foreground">{app.company}</p>
                                                    </div>
                                                    <StatusBadge status={app.status} />
                                                </Link>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Recent Experiences */}
                            <Card>
                                <CardHeader className="flex-row items-center justify-between">
                                    <CardTitle className="text-base">Recent Experiences</CardTitle>
                                    <Button asChild variant="ghost" size="sm">
                                        <Link href={experienceLibraryIndex()}>View All</Link>
                                    </Button>
                                </CardHeader>
                                <CardContent>
                                    {recentExperiences.length === 0 ? (
                                        <EmptyState icon={Briefcase} title="No experiences yet" description="Add your first role to get started." />
                                    ) : (
                                        <div className="space-y-3">
                                            {recentExperiences.map((exp) => (
                                                <Link key={exp.id} href={`/experiences/${exp.id}`} className="flex items-center justify-between rounded-md p-2 transition-colors hover:bg-accent">
                                                    <div>
                                                        <p className="text-sm font-medium">{exp.title}</p>
                                                        <p className="text-xs text-muted-foreground">{exp.company}</p>
                                                    </div>
                                                    {exp.is_current && <Badge variant="outline">Current</Badge>}
                                                </Link>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
