import { Head, Link } from '@inertiajs/react';
import { Briefcase, FileText, LayoutGrid, Plus, Sparkles, Target, Trophy } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import StatusBadge from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { index as applicationsIndex } from '@/routes/applications';
import { index as experienceLibraryIndex } from '@/routes/experience-library';
import { create as experienceCreate } from '@/routes/experiences';
import { index as jobPostingsIndex } from '@/routes/job-postings';
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


export default function Dashboard({
    stats,
    recentApplications,
    recentExperiences,
}: {
    stats: Stats;
    recentApplications: RecentApplication[];
    recentExperiences: RecentExperience[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="mx-auto max-w-6xl space-y-6 p-4">
                {/* Welcome / Stats */}
                {stats.experiences === 0 && stats.skills === 0 && stats.accomplishments === 0 && stats.applications === 0 && (
                    <Card className="border-primary/20 bg-gradient-to-r from-primary/5 to-transparent">
                        <CardContent className="flex items-center gap-4 py-6">
                            <div className="rounded-xl bg-primary/10 p-3">
                                <LayoutGrid className="h-6 w-6 text-primary" />
                            </div>
                            <div>
                                <h2 className="text-lg font-semibold">Welcome to CareerForge</h2>
                                <p className="text-sm text-muted-foreground">Start by adding your work experiences, then analyze job postings to generate tailored resumes.</p>
                            </div>
                        </CardContent>
                    </Card>
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
                                    <p className="text-sm font-medium">View Timeline</p>
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
            </div>
        </AppLayout>
    );
}
