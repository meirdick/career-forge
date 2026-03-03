import { Head, Link } from '@inertiajs/react';
import { Loader2, Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type JobPosting = {
    id: number;
    title: string | null;
    company: string | null;
    location: string | null;
    seniority_level: string | null;
    analyzed_at: string | null;
    created_at: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Job Postings', href: '/job-postings' },
];

export default function JobPostingsIndex({ postings }: { postings: JobPosting[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Job Postings" />

            <div className="mx-auto max-w-3xl space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Job Postings" description="Analyze job postings to build ideal candidate profiles." />
                    <Button asChild>
                        <Link href="/job-postings/create"><Plus className="mr-1 h-4 w-4" /> New Posting</Link>
                    </Button>
                </div>

                {postings.length === 0 ? (
                    <Card>
                        <CardContent className="py-8 text-center">
                            <p className="text-muted-foreground">No job postings yet. Add one to get started.</p>
                        </CardContent>
                    </Card>
                ) : (
                    postings.map((posting) => (
                        <Link key={posting.id} href={`/job-postings/${posting.id}`} className="block">
                            <Card className="transition-shadow hover:shadow-md">
                                <CardHeader className="pb-2">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <CardTitle className="text-base">{posting.title ?? 'Untitled Posting'}</CardTitle>
                                            {posting.company && <p className="text-muted-foreground text-sm">{posting.company}{posting.location && ` · ${posting.location}`}</p>}
                                        </div>
                                        {posting.analyzed_at ? (
                                            <Badge variant="secondary">Analyzed</Badge>
                                        ) : (
                                            <Badge variant="outline"><Loader2 className="mr-1 h-3 w-3 animate-spin" /> Analyzing</Badge>
                                        )}
                                    </div>
                                </CardHeader>
                                <CardContent className="pt-0">
                                    <p className="text-muted-foreground text-xs">
                                        Added {new Date(posting.created_at).toLocaleDateString()}
                                        {posting.seniority_level && ` · ${posting.seniority_level}`}
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
