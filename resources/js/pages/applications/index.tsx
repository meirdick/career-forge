import { Head, Link } from '@inertiajs/react';
import { Briefcase, Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type ApplicationData = {
    id: number;
    company: string;
    role: string;
    status: string;
    applied_at: string | null;
    job_posting: { title: string | null } | null;
};

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
    applied: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    interviewing: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    offer: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    withdrawn: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Applications', href: '/applications' },
];

export default function ApplicationsIndex({ applications }: { applications: ApplicationData[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Applications" />

            <div className="mx-auto max-w-3xl space-y-6 p-4">
                <div className="flex items-start justify-between">
                    <Heading title="Applications" description="Track your job applications." />
                    <Link href="/applications/create">
                        <Button size="sm">
                            <Plus className="mr-1 h-4 w-4" /> New Application
                        </Button>
                    </Link>
                </div>

                {applications.length === 0 ? (
                    <Card>
                        <CardContent className="py-8 text-center">
                            <p className="text-muted-foreground">No applications yet. Create one to start tracking.</p>
                        </CardContent>
                    </Card>
                ) : (
                    applications.map((app) => (
                        <Link key={app.id} href={`/applications/${app.id}`} className="block">
                            <Card className="transition-shadow hover:shadow-md">
                                <CardHeader className="pb-2">
                                    <div className="flex items-start justify-between">
                                        <div className="flex items-center gap-2">
                                            <Briefcase className="text-muted-foreground h-5 w-5" />
                                            <CardTitle className="text-base">{app.role}</CardTitle>
                                        </div>
                                        <Badge className={statusColors[app.status] ?? ''}>{app.status}</Badge>
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
                    ))
                )}
            </div>
        </AppLayout>
    );
}
