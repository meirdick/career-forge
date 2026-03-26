import { Head, Link } from '@inertiajs/react';
import { Briefcase, Plus, Target } from 'lucide-react';
import { useMemo, useState } from 'react';
import EmptyState from '@/components/empty-state';
import Heading from '@/components/heading';
import StatusBadge from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { index as jobPostingsIndex } from '@/routes/job-postings';
import type { BreadcrumbItem } from '@/types';

type ApplicationData = {
    id: number;
    company: string;
    role: string;
    status: string;
    applied_at: string | null;
    job_posting: { title: string | null } | null;
};


const allStatuses = ['draft', 'applied', 'interviewing', 'offer', 'rejected', 'withdrawn'];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Applications', href: '/applications' },
];

export default function ApplicationsIndex({ applications }: { applications: ApplicationData[] }) {
    const [filter, setFilter] = useState<string | null>(null);

    const statusCounts = useMemo(() => {
        const counts: Record<string, number> = {};
        for (const status of allStatuses) {
            counts[status] = applications.filter((a) => a.status === status).length;
        }
        return counts;
    }, [applications]);

    const filtered = filter ? applications.filter((a) => a.status === filter) : applications;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Applications" />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                <div className="flex items-start justify-between">
                    <Heading title="Applications" description="Track your job applications." />
                    <Button size="sm" asChild>
                        <Link href="/applications/create">
                            <Plus className="mr-1 h-4 w-4" /> New Application
                        </Link>
                    </Button>
                </div>

                {/* Pipeline Summary */}
                {applications.length > 0 && (
                    <div className="grid grid-cols-3 gap-3 sm:grid-cols-6">
                        {allStatuses.map((status) => (
                            <button
                                key={status}
                                onClick={() => setFilter(filter === status ? null : status)}
                                className={`rounded-lg border p-2 text-center transition-colors ${
                                    filter === status ? 'border-primary bg-primary/5' : 'hover:bg-muted'
                                }`}
                            >
                                <p className="text-lg font-bold">{statusCounts[status]}</p>
                                <p className="text-muted-foreground text-xs capitalize">{status}</p>
                            </button>
                        ))}
                    </div>
                )}

                {applications.length === 0 ? (
                    <EmptyState
                        icon={Briefcase}
                        title="No applications yet"
                        description="Applications track your full pipeline — start by analyzing a job posting, then generate a resume and apply."
                        action={
                            <div className="flex flex-col items-center gap-2">
                                <Button asChild>
                                    <Link href={jobPostingsIndex()}>
                                        <Target className="mr-1 h-4 w-4" /> Go to Job Postings
                                    </Link>
                                </Button>
                                <Link href="/applications/create" className="text-xs text-muted-foreground hover:text-foreground transition-colors">
                                    Or create an application manually
                                </Link>
                            </div>
                        }
                    />
                ) : (
                    <>
                        {filter && (
                            <div className="flex items-center gap-2">
                                <span className="text-muted-foreground text-sm">
                                    Showing {filtered.length} {filter} application{filtered.length !== 1 ? 's' : ''}
                                </span>
                                <Button variant="ghost" size="sm" onClick={() => setFilter(null)}>
                                    Clear filter
                                </Button>
                            </div>
                        )}
                        {filtered.map((app) => (
                            <Link key={app.id} href={`/applications/${app.id}`} className="block">
                                <Card className="transition-shadow hover:shadow-md">
                                    <CardHeader className="pb-2">
                                        <div className="flex items-start justify-between">
                                            <div className="flex items-center gap-2">
                                                <Briefcase className="text-muted-foreground h-5 w-5" />
                                                <CardTitle className="text-base">{app.role}</CardTitle>
                                            </div>
                                            <StatusBadge status={app.status} />
                                        </div>
                                    </CardHeader>
                                    <CardContent className="pt-0">
                                        <p className="text-muted-foreground text-xs">
                                            {app.company}
                                            {app.applied_at && ` · Applied ${new Date(app.applied_at).toLocaleDateString()}`}
                                        </p>
                                    </CardContent>
                                </Card>
                            </Link>
                        ))}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
