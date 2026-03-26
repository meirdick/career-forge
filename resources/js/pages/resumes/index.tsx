import { Head, Link } from '@inertiajs/react';
import { Download, FileText, Target, Upload } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { index as jobPostingsIndex } from '@/routes/job-postings';
import { create as resumeUploadCreate } from '@/routes/resume-upload';
import type { BreadcrumbItem } from '@/types';

type Resume = {
    id: number;
    title: string;
    is_finalized: boolean;
    created_at: string;
    job_posting: { title: string | null; company: string | null } | null;
};

type UploadedDocument = {
    id: number;
    filename: string;
    size: number;
    mime_type: string;
    created_at: string;
    download_url: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Resumes', href: '/resumes' },
];

function formatFileSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function ResumesIndex({ resumes, uploadedDocuments }: { resumes: Resume[]; uploadedDocuments: UploadedDocument[] }) {
    const hasResumes = resumes.length > 0;
    const hasUploads = uploadedDocuments.length > 0;
    const isEmpty = !hasResumes && !hasUploads;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Resumes" />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                <Heading title="Resumes" description="AI-generated resumes tailored to job postings." />

                {isEmpty ? (
                    <EmptyState
                        icon={FileText}
                        title="No resumes yet"
                        description="Tailored resumes are generated after analyzing a job posting and running a gap analysis."
                        action={
                            <div className="flex flex-col items-center gap-2">
                                <Button asChild>
                                    <Link href={jobPostingsIndex()}>
                                        <Target className="mr-2 h-4 w-4" /> Go to Job Postings
                                    </Link>
                                </Button>
                                <Link href={resumeUploadCreate()} className="text-xs text-muted-foreground hover:text-foreground transition-colors">
                                    Or upload an existing resume
                                </Link>
                            </div>
                        }
                    />
                ) : (
                    <>
                        {hasResumes && (
                            <div className="space-y-3">
                                {resumes.map((resume) => (
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
                                ))}
                            </div>
                        )}

                        {hasUploads && (
                            <div className="space-y-3">
                                <h3 className="text-muted-foreground text-sm font-medium">Uploaded Resumes</h3>
                                {uploadedDocuments.map((doc) => (
                                    <Card key={doc.id}>
                                        <CardHeader className="pb-2">
                                            <div className="flex items-start justify-between">
                                                <div className="flex items-center gap-2">
                                                    <Upload className="text-muted-foreground h-5 w-5" />
                                                    <CardTitle className="text-base">{doc.filename}</CardTitle>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Badge variant="outline">Uploaded</Badge>
                                                    <a href={doc.download_url}>
                                                        <Button variant="ghost" size="icon" className="h-8 w-8" title="Download">
                                                            <Download className="h-4 w-4" />
                                                        </Button>
                                                    </a>
                                                </div>
                                            </div>
                                        </CardHeader>
                                        <CardContent className="pt-0">
                                            <p className="text-muted-foreground text-xs">
                                                {formatFileSize(doc.size)} · {new Date(doc.created_at).toLocaleDateString()}
                                            </p>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
