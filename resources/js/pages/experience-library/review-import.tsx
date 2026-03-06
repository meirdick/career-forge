import { Head, router } from '@inertiajs/react';
import { Loader2, X } from 'lucide-react';
import { useEffect } from 'react';
import ExtractionReviewContent from '@/components/extraction-review/extraction-review-content';
import type { ExtractionData } from '@/components/extraction-review/types';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import ResumeUploadController from '@/actions/App/Http/Controllers/ExperienceLibrary/ResumeUploadController';
import type { BreadcrumbItem } from '@/types';

type ParseResult = {
    status: 'processing' | 'completed' | 'failed';
    data?: ExtractionData;
    error?: string;
};

type Document = { id: number; filename: string };

export default function ReviewImport({ document, parseResult }: { document: Document; parseResult: ParseResult }) {
    useEffect(() => {
        if (parseResult.status === 'processing') {
            const interval = setInterval(() => {
                router.reload({ only: ['parseResult'] });
            }, 3000);
            return () => clearInterval(interval);
        }
    }, [parseResult.status]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Experience Library', href: '/experience-library' },
        { title: 'Review Import', href: `/resume-upload/${document.id}/review` },
    ];

    function handleImport(payload: ExtractionData) {
        router.post(ResumeUploadController.commit(document.id).url, payload);
    }

    if (parseResult.status === 'processing') {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Parsing Resume..." />
                <div className="mx-auto max-w-2xl space-y-6 p-4">
                    <Heading title="Parsing Resume" description={document.filename} />
                    <Card>
                        <CardContent className="flex flex-col items-center gap-4 py-12">
                            <Loader2 className="text-primary h-10 w-10 animate-spin" />
                            <p className="text-muted-foreground">AI is analyzing your resume. This usually takes 15-30 seconds...</p>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    if (parseResult.status === 'failed') {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Parse Failed" />
                <div className="mx-auto max-w-2xl space-y-6 p-4">
                    <Heading title="Parse Failed" description={document.filename} />
                    <Card>
                        <CardContent className="py-8 text-center">
                            <X className="mx-auto mb-3 h-10 w-10 text-red-500" />
                            <p className="text-muted-foreground">{parseResult.error ?? 'An error occurred while parsing your resume.'}</p>
                            <Button variant="outline" className="mt-4" onClick={() => router.post(ResumeUploadController.retry(document.id).url)}>
                                Re-analyze
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    const data = parseResult.data!;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Review Import" />

            <div className="mx-auto max-w-3xl space-y-6 p-4">
                <Heading title="Review Import" description={`Parsed from ${document.filename}. Edit, enhance, or deselect items before importing.`} />
                <ExtractionReviewContent data={data} onImport={handleImport} />
            </div>
        </AppLayout>
    );
}
