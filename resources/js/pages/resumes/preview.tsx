import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Download } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Variant = { id: number; content: string };
type Section = { id: number; title: string; sort_order: number; selected_variant: Variant | null };
type ResumeData = {
    id: number;
    title: string;
    is_finalized: boolean;
    sections: Section[];
    job_posting: { title: string | null; company: string | null } | null;
};

export default function PreviewResume({ resume }: { resume: ResumeData }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Resumes', href: '/resumes' },
        { title: resume.title, href: `/resumes/${resume.id}` },
        { title: 'Preview', href: `/resumes/${resume.id}/preview` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Preview - ${resume.title}`} />

            <div className="mx-auto max-w-3xl space-y-6 p-4">
                <div className="flex items-start justify-between">
                    <Heading
                        title="Resume Preview"
                        description={resume.job_posting ? `${resume.job_posting.title ?? 'Untitled'} at ${resume.job_posting.company ?? 'Unknown'}` : undefined}
                    />
                    <div className="flex gap-2">
                        <Link href={`/resumes/${resume.id}`}>
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="mr-1 h-4 w-4" /> Back
                            </Button>
                        </Link>
                        <a href={`/resumes/${resume.id}/export/pdf`}>
                            <Button size="sm">
                                <Download className="mr-1 h-4 w-4" /> PDF
                            </Button>
                        </a>
                        <a href={`/resumes/${resume.id}/export/docx`}>
                            <Button variant="outline" size="sm">
                                <Download className="mr-1 h-4 w-4" /> DOCX
                            </Button>
                        </a>
                    </div>
                </div>

                <div className="rounded-lg border bg-white p-8 shadow-sm dark:bg-gray-950">
                    <h1 className="border-b-2 border-gray-800 pb-2 text-2xl font-bold dark:border-gray-200">
                        {resume.title}
                    </h1>
                    {resume.job_posting && (
                        <p className="mt-1 text-sm text-gray-500">
                            {resume.job_posting.title ?? 'Untitled'} at {resume.job_posting.company ?? 'Unknown'}
                        </p>
                    )}

                    {resume.sections
                        .sort((a, b) => a.sort_order - b.sort_order)
                        .map((section) => (
                            <div key={section.id} className="mt-6">
                                <Separator className="mb-3" />
                                <h2 className="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                                    {section.title}
                                </h2>
                                {section.selected_variant && (
                                    <div className="whitespace-pre-wrap text-sm leading-relaxed">
                                        {section.selected_variant.content}
                                    </div>
                                )}
                            </div>
                        ))}
                </div>
            </div>
        </AppLayout>
    );
}
