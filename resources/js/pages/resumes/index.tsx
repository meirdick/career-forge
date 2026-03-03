import { Head, Link } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Resume = {
    id: number;
    title: string;
    is_finalized: boolean;
    created_at: string;
    job_posting: { title: string | null; company: string | null } | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Resumes', href: '/resumes' },
];

export default function ResumesIndex({ resumes }: { resumes: Resume[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Resumes" />

            <div className="mx-auto max-w-3xl space-y-6 p-4">
                <Heading title="Resumes" description="AI-generated resumes tailored to job postings." />

                {resumes.length === 0 ? (
                    <Card>
                        <CardContent className="py-8 text-center">
                            <p className="text-muted-foreground">No resumes yet. Generate one from a gap analysis.</p>
                        </CardContent>
                    </Card>
                ) : (
                    resumes.map((resume) => (
                        <Link key={resume.id} href={`/resumes/${resume.id}`} className="block">
                            <Card className="transition-shadow hover:shadow-md">
                                <CardHeader className="pb-2">
                                    <div className="flex items-start justify-between">
                                        <div className="flex items-center gap-2">
                                            <FileText className="text-muted-foreground h-5 w-5" />
                                            <CardTitle className="text-base">{resume.title}</CardTitle>
                                        </div>
                                        {resume.is_finalized && <Badge variant="secondary">Finalized</Badge>}
                                    </div>
                                </CardHeader>
                                <CardContent className="pt-0">
                                    <p className="text-muted-foreground text-xs">
                                        {resume.job_posting && `${resume.job_posting.title ?? 'Untitled'} at ${resume.job_posting.company ?? 'Unknown'} · `}
                                        {new Date(resume.created_at).toLocaleDateString()}
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
