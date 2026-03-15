import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Download } from 'lucide-react';
import Heading from '@/components/heading';
import ResumeDocument from '@/components/resume-templates/resume-document';
import TemplatePicker from '@/components/resume-templates/template-picker';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Variant = { id: number; content: string; formatted_content: string };
type Section = { id: number; title: string; sort_order: number; is_hidden: boolean; selected_variant: Variant | null };
type Contact = {
    name?: string;
    email?: string;
    phone?: string;
    location?: string;
    linkedin_url?: string;
    portfolio_links?: { url: string; label: string }[];
};
type ResumeData = {
    id: number;
    title: string;
    template: string;
    is_finalized: boolean;
    sections: Section[];
    job_posting: { title: string | null; company: string | null } | null;
};

export default function PreviewResume({ resume, contact }: { resume: ResumeData; contact: Contact }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Resumes', href: '/resumes' },
        { title: resume.title, href: `/resumes/${resume.id}` },
        { title: 'Preview', href: `/resumes/${resume.id}/preview` },
    ];

    function changeTemplate(key: string) {
        router.put(`/resumes/${resume.id}`, { template: key }, { preserveScroll: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Preview - ${resume.title}`} />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                <div className="flex items-start justify-between">
                    <Heading
                        title="Resume Preview"
                        description={resume.job_posting ? `${resume.job_posting.title ?? 'Untitled'} at ${resume.job_posting.company ?? 'Unknown'}` : undefined}
                    />
                    <div className="flex gap-2">
                        <Link href={`/resumes/${resume.id}`}>
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="mr-1 h-4 w-4" /> Back to Editor
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Template Picker */}
                <div>
                    <h3 className="mb-2 text-sm font-medium">Template</h3>
                    <TemplatePicker selected={resume.template ?? 'classic'} onChange={changeTemplate} disabled={resume.is_finalized} />
                </div>

                {/* Export Actions */}
                <div className="flex gap-3">
                    <a href={`/resumes/${resume.id}/export/pdf`}>
                        <Button size="sm">
                            <Download className="mr-1 h-4 w-4" /> Download PDF
                        </Button>
                    </a>
                    <a href={`/resumes/${resume.id}/export/docx`}>
                        <Button variant="outline" size="sm">
                            <Download className="mr-1 h-4 w-4" /> Download DOCX
                        </Button>
                    </a>
                </div>

                {/* Resume Document Preview */}
                <ResumeDocument template={resume.template ?? 'classic'} contact={contact} sections={resume.sections.filter((s) => !s.is_hidden)} />
            </div>
        </AppLayout>
    );
}
