import { Head, Link } from '@inertiajs/react';
import { Briefcase, FileText, Plus, Sparkles, Target, Trophy } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
    applied: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    interviewing: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    offer: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    withdrawn: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
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

            <div className="mx-auto max-w-5xl space-y-6 p-4">
                {/* Stats */}
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
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Quick Actions</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        <Button asChild variant="outline" size="sm">
                            <Link href={experienceCreate()}>
                                <Plus className="mr-1 h-4 w-4" /> Add Experience
                            </Link>
                        </Button>
                        <Button asChild variant="outline" size="sm">
                            <Link href={jobPostingsIndex()}>
                                <Target className="mr-1 h-4 w-4" /> Analyze Job Posting
                            </Link>
                        </Button>
                        <Button asChild variant="outline" size="sm">
                            <Link href={experienceLibraryIndex()}>
                                <FileText className="mr-1 h-4 w-4" /> View Timeline
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

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
                                <p className="text-sm text-muted-foreground">No applications yet.</p>
                            ) : (
                                <div className="space-y-3">
                                    {recentApplications.map((app) => (
                                        <Link key={app.id} href={`/applications/${app.id}`} className="flex items-center justify-between rounded-md p-2 hover:bg-muted/50">
                                            <div>
                                                <p className="text-sm font-medium">{app.role}</p>
                                                <p className="text-xs text-muted-foreground">{app.company}</p>
                                            </div>
                                            <Badge className={statusColors[app.status] ?? ''}>{app.status}</Badge>
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
                                <p className="text-sm text-muted-foreground">No experiences yet. Add your first role to get started.</p>
                            ) : (
                                <div className="space-y-3">
                                    {recentExperiences.map((exp) => (
                                        <Link key={exp.id} href={`/experiences/${exp.id}`} className="flex items-center justify-between rounded-md p-2 hover:bg-muted/50">
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
