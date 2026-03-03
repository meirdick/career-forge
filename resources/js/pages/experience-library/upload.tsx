import { Form, Head } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import { useRef, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import ResumeUploadController from '@/actions/App/Http/Controllers/ExperienceLibrary/ResumeUploadController';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Experience Library', href: '/experience-library' },
    { title: 'Upload Resume', href: '/resume-upload' },
];

export default function UploadResume() {
    const [fileName, setFileName] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Upload Resume" />

            <div className="mx-auto max-w-2xl space-y-6 p-4">
                <Heading title="Upload Resume" description="Upload a PDF, DOCX, TXT, or JSON (LinkedIn export) file to auto-populate your experience library." />

                <Card>
                    <CardContent className="pt-6">
                        <Form {...ResumeUploadController.store.form()} encType="multipart/form-data" className="space-y-4">
                            {({ processing, errors }) => (
                                <>
                                    <div
                                        className="border-muted-foreground/25 hover:border-primary/50 cursor-pointer rounded-lg border-2 border-dashed p-8 text-center transition-colors"
                                        onClick={() => fileInputRef.current?.click()}
                                    >
                                        <Upload className="text-muted-foreground mx-auto mb-3 h-10 w-10" />
                                        {fileName ? (
                                            <p className="font-medium">{fileName}</p>
                                        ) : (
                                            <>
                                                <p className="font-medium">Click to select a file</p>
                                                <p className="text-muted-foreground text-sm">PDF, DOCX, TXT, or JSON up to 10MB</p>
                                            </>
                                        )}
                                        <input
                                            ref={fileInputRef}
                                            type="file"
                                            name="file"
                                            accept=".pdf,.docx,.doc,.txt,.json"
                                            className="hidden"
                                            onChange={(e) => setFileName(e.target.files?.[0]?.name ?? null)}
                                        />
                                    </div>
                                    <InputError message={errors.file} />

                                    <div className="flex justify-end">
                                        <Button type="submit" disabled={processing || !fileName}>
                                            {processing ? 'Uploading...' : 'Upload & Parse'}
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
