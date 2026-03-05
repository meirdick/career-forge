import { Head, Link, router } from '@inertiajs/react';
import { FileText, Loader2, RefreshCw, Upload, X, CheckCircle2, Clock } from 'lucide-react';
import { useRef, useState, useCallback } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import ResumeUploadController from '@/actions/App/Http/Controllers/ExperienceLibrary/ResumeUploadController';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Experience Library', href: '/experience-library' },
    { title: 'Upload Resume', href: '/resume-upload' },
];

const ACCEPTED_TYPES = [
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/msword',
    'text/plain',
    'application/json',
];

const ACCEPTED_EXTENSIONS = ['.pdf', '.docx', '.doc', '.txt', '.json'];

type UploadedDocument = {
    id: number;
    filename: string;
    size: number;
    created_at: string;
    status: 'processing' | 'completed' | 'failed';
};

function formatFileSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function isValidFile(file: File): boolean {
    const ext = '.' + file.name.split('.').pop()?.toLowerCase();
    return ACCEPTED_EXTENSIONS.includes(ext) || ACCEPTED_TYPES.includes(file.type);
}

export default function UploadResume({ documents = [] }: { documents?: UploadedDocument[] }) {
    const [files, setFiles] = useState<File[]>([]);
    const [isDragging, setIsDragging] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const fileInputRef = useRef<HTMLInputElement>(null);

    const addFiles = useCallback((newFiles: FileList | File[]) => {
        const valid = Array.from(newFiles).filter(isValidFile);
        setFiles((prev) => {
            const existing = new Set(prev.map((f) => f.name + f.size));
            const unique = valid.filter((f) => !existing.has(f.name + f.size));
            return [...prev, ...unique];
        });
        setErrors({});
    }, []);

    function removeFile(index: number) {
        setFiles((prev) => prev.filter((_, i) => i !== index));
    }

    function handleDragOver(e: React.DragEvent) {
        e.preventDefault();
        setIsDragging(true);
    }

    function handleDragLeave(e: React.DragEvent) {
        e.preventDefault();
        setIsDragging(false);
    }

    function handleDrop(e: React.DragEvent) {
        e.preventDefault();
        setIsDragging(false);
        if (e.dataTransfer.files.length > 0) {
            addFiles(e.dataTransfer.files);
        }
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (files.length === 0) return;

        const formData = new FormData();
        files.forEach((file) => formData.append('files[]', file));

        setProcessing(true);
        router.post(ResumeUploadController.store().url, formData, {
            forceFormData: true,
            onError: (errs) => {
                setErrors(errs);
                setProcessing(false);
            },
            onFinish: () => setProcessing(false),
        });
    }

    const statusConfig = {
        processing: { icon: Clock, label: 'Processing', variant: 'secondary' as const },
        completed: { icon: CheckCircle2, label: 'Ready', variant: 'default' as const },
        failed: { icon: X, label: 'Failed', variant: 'destructive' as const },
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Upload Resume" />

            <div className="mx-auto max-w-2xl space-y-6 p-4">
                <Heading title="Upload Resume" description="Upload PDF, DOCX, TXT, or JSON (LinkedIn export) files to auto-populate your experience library." />

                <Card>
                    <CardContent className="pt-6">
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div
                                className={`cursor-pointer rounded-lg border-2 border-dashed p-8 text-center transition-colors ${
                                    isDragging
                                        ? 'border-primary bg-primary/5'
                                        : 'border-muted-foreground/25 hover:border-primary/50'
                                }`}
                                onClick={() => fileInputRef.current?.click()}
                                onDragOver={handleDragOver}
                                onDragLeave={handleDragLeave}
                                onDrop={handleDrop}
                            >
                                <Upload className={`mx-auto mb-3 h-10 w-10 ${isDragging ? 'text-primary' : 'text-muted-foreground'}`} />
                                <p className="font-medium">
                                    {isDragging ? 'Drop files here' : 'Click to select or drag & drop files'}
                                </p>
                                <p className="text-muted-foreground text-sm">PDF, DOCX, TXT, or JSON up to 10MB each</p>
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    multiple
                                    accept=".pdf,.docx,.doc,.txt,.json"
                                    className="hidden"
                                    onChange={(e) => {
                                        if (e.target.files) addFiles(e.target.files);
                                        e.target.value = '';
                                    }}
                                />
                            </div>

                            {Object.keys(errors).length > 0 && (
                                <div className="space-y-1">
                                    {Object.entries(errors).map(([key, msg]) => (
                                        <InputError key={key} message={msg} />
                                    ))}
                                </div>
                            )}

                            {files.length > 0 && (
                                <ul className="divide-y rounded-lg border">
                                    {files.map((file, i) => (
                                        <li key={file.name + file.size} className="flex items-center gap-3 px-3 py-2">
                                            <FileText className="text-muted-foreground h-4 w-4 shrink-0" />
                                            <span className="min-w-0 flex-1 truncate text-sm">{file.name}</span>
                                            <span className="text-muted-foreground shrink-0 text-xs">{formatFileSize(file.size)}</span>
                                            <button
                                                type="button"
                                                onClick={() => removeFile(i)}
                                                className="text-muted-foreground hover:text-foreground shrink-0"
                                            >
                                                <X className="h-4 w-4" />
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            )}

                            <div className="flex items-center justify-between">
                                <p className="text-muted-foreground text-sm">
                                    {files.length > 0 ? `${files.length} file${files.length > 1 ? 's' : ''} selected` : ''}
                                </p>
                                <Button type="submit" disabled={processing || files.length === 0}>
                                    {processing ? (
                                        <>
                                            <Loader2 className="mr-1 h-4 w-4 animate-spin" />
                                            Uploading...
                                        </>
                                    ) : (
                                        `Upload & Parse${files.length > 1 ? ` (${files.length})` : ''}`
                                    )}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {documents.length > 0 && (
                    <div className="space-y-3">
                        <h2 className="text-lg font-semibold">Previously Uploaded</h2>
                        <ul className="divide-y rounded-lg border">
                            {documents.map((doc) => {
                                const config = statusConfig[doc.status];
                                const StatusIcon = config.icon;
                                return (
                                    <li key={doc.id} className="flex items-center gap-3 px-3 py-2.5">
                                        <FileText className="text-muted-foreground h-4 w-4 shrink-0" />
                                        <div className="min-w-0 flex-1">
                                            <Link
                                                href={ResumeUploadController.review(doc.id).url}
                                                className="hover:text-primary truncate text-sm font-medium"
                                            >
                                                {doc.filename}
                                            </Link>
                                            <p className="text-muted-foreground text-xs">{doc.created_at}</p>
                                        </div>
                                        {doc.status === 'failed' ? (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => router.post(ResumeUploadController.retry(doc.id).url)}
                                            >
                                                <RefreshCw className="mr-1 h-3 w-3" />
                                                Retry
                                            </Button>
                                        ) : (
                                            <Badge variant={config.variant} className="shrink-0 gap-1 text-xs">
                                                <StatusIcon className="h-3 w-3" />
                                                {config.label}
                                            </Badge>
                                        )}
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
