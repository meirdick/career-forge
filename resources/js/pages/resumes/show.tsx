import { Head, Link, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Bot, Check, Download, Eye, Loader2, Pencil, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import Heading from '@/components/heading';
import PipelineAssistantPanel from '@/components/pipeline-assistant-panel';
import PipelineSteps from '@/components/pipeline-steps';
import TemplatePicker from '@/components/resume-templates/template-picker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Variant = { id: number; label: string; content: string; formatted_content: string; emphasis: string | null; is_ai_generated: boolean; is_user_edited: boolean };
type Section = { id: number; type: string; title: string; sort_order: number; selected_variant_id: number | null; variants: Variant[]; selected_variant: Variant | null };
type ResumeData = {
    id: number;
    title: string;
    template: string;
    is_finalized: boolean;
    sections: Section[];
    job_posting: { id: number; title: string | null; company: string | null } | null;
};

export default function ShowResume({ resume }: { resume: ResumeData }) {
    const isGenerating = resume.sections.length === 0;
    const [editingVariant, setEditingVariant] = useState<number | null>(null);
    const [editContent, setEditContent] = useState('');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Resumes', href: '/resumes' },
        { title: resume.title, href: `/resumes/${resume.id}` },
    ];

    useEffect(() => {
        if (isGenerating) {
            const interval = setInterval(() => {
                router.reload({ only: ['resume'] });
            }, 5000);
            return () => clearInterval(interval);
        }
    }, [isGenerating]);

    const sortedSections = [...resume.sections].sort((a, b) => a.sort_order - b.sort_order);

    function moveSection(fromIndex: number, toIndex: number) {
        const reordered = [...sortedSections];
        const [moved] = reordered.splice(fromIndex, 1);
        reordered.splice(toIndex, 0, moved);
        const newOrder = reordered.map((s) => s.id);
        router.put(`/resumes/${resume.id}`, { section_order: newOrder }, { preserveScroll: true });
    }

    function selectVariant(sectionId: number, variantId: number) {
        router.put(`/resumes/${resume.id}/sections/${sectionId}`, { variant_id: variantId }, { preserveScroll: true });
    }

    function saveVariant(variantId: number) {
        router.put(`/resumes/${resume.id}/variants/${variantId}`, { content: editContent }, { preserveScroll: true, onSuccess: () => setEditingVariant(null) });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={resume.title} />

            <div className="mx-auto max-w-3xl space-y-6 p-4">
                {resume.job_posting && (
                    <PipelineSteps
                        steps={[
                            { label: 'Job Posting', href: `/job-postings/${resume.job_posting.id}`, status: 'completed' },
                            { label: 'Ideal Candidate', status: 'completed' },
                            { label: 'Gap Analysis', status: 'completed' },
                            { label: 'Resume', href: `/resumes/${resume.id}`, status: 'active' },
                            { label: 'Application', status: 'upcoming' },
                        ]}
                    />
                )}
                <div className="flex items-start justify-between">
                    <Heading title={resume.title} description={resume.job_posting ? `For ${resume.job_posting.title ?? 'Untitled'} at ${resume.job_posting.company ?? 'Unknown'}` : undefined} />
                    <div className="flex gap-2">
                        {!isGenerating && (
                            <>
                                <Link href={`/resumes/${resume.id}/preview`}>
                                    <Button variant="outline" size="sm">
                                        <Eye className="mr-1 h-4 w-4" /> Preview
                                    </Button>
                                </Link>
                                <a href={`/resumes/${resume.id}/export/pdf`}>
                                    <Button variant="outline" size="sm">
                                        <Download className="mr-1 h-4 w-4" /> PDF
                                    </Button>
                                </a>
                            </>
                        )}
                        {!resume.is_finalized && !isGenerating && (
                            <Button onClick={() => router.post(`/resumes/${resume.id}/finalize`)}>
                                <Check className="mr-1 h-4 w-4" /> Finalize
                            </Button>
                        )}
                        <Button variant="destructive" size="sm" onClick={() => { if (confirm('Delete this resume?')) router.delete(`/resumes/${resume.id}`); }}>
                            <Trash2 className="mr-1 h-4 w-4" />
                        </Button>
                    </div>
                </div>

                {resume.is_finalized && <Badge variant="secondary">Finalized</Badge>}

                {!isGenerating && !resume.is_finalized && (
                    <div>
                        <h3 className="mb-2 text-sm font-medium">Template</h3>
                        <TemplatePicker
                            selected={resume.template ?? 'classic'}
                            onChange={(key) => router.put(`/resumes/${resume.id}`, { template: key }, { preserveScroll: true })}
                        />
                    </div>
                )}

                {isGenerating && (
                    <Card>
                        <CardContent className="flex items-center gap-3 py-8">
                            <Loader2 className="text-primary h-6 w-6 animate-spin" />
                            <p className="text-muted-foreground">Generating resume sections... This may take a minute.</p>
                        </CardContent>
                    </Card>
                )}

                {sortedSections.map((section, index) => (
                        <div key={section.id} className="space-y-3">
                            <Separator />
                            <div className="flex items-center justify-between">
                                <h2 className="text-lg font-semibold">{section.title}</h2>
                                {!resume.is_finalized && sortedSections.length > 1 && (
                                    <div className="flex gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            disabled={index === 0}
                                            onClick={() => moveSection(index, index - 1)}
                                            title="Move up"
                                        >
                                            <ArrowUp className="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            disabled={index === sortedSections.length - 1}
                                            onClick={() => moveSection(index, index + 1)}
                                            title="Move down"
                                        >
                                            <ArrowDown className="h-4 w-4" />
                                        </Button>
                                    </div>
                                )}
                            </div>

                            {section.selected_variant && (
                                <Card className={section.selected_variant.is_ai_generated && !section.selected_variant.is_user_edited ? 'border-l-4 border-l-blue-400' : section.selected_variant.is_user_edited ? 'border-l-4 border-l-green-400' : ''}>
                                    <CardContent className="pt-4">
                                        {section.selected_variant.is_ai_generated && (
                                            <div className="mb-2 flex items-center gap-1.5">
                                                {section.selected_variant.is_user_edited ? (
                                                    <Badge variant="outline" className="text-xs text-green-700 dark:text-green-300">
                                                        <Pencil className="mr-1 h-3 w-3" /> User Edited
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline" className="text-xs text-blue-700 dark:text-blue-300">
                                                        <Bot className="mr-1 h-3 w-3" /> AI Generated
                                                    </Badge>
                                                )}
                                            </div>
                                        )}
                                        {editingVariant === section.selected_variant.id ? (
                                            <div className="space-y-2">
                                                <textarea
                                                    value={editContent}
                                                    onChange={(e) => setEditContent(e.target.value)}
                                                    rows={8}
                                                    className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm"
                                                />
                                                <div className="flex justify-end gap-2">
                                                    <Button variant="ghost" size="sm" onClick={() => setEditingVariant(null)}>Cancel</Button>
                                                    <Button size="sm" onClick={() => saveVariant(section.selected_variant!.id)}>Save</Button>
                                                </div>
                                            </div>
                                        ) : (
                                            <div
                                                className="prose prose-sm dark:prose-invert max-w-none cursor-pointer"
                                                onClick={() => {
                                                    if (!resume.is_finalized) {
                                                        setEditingVariant(section.selected_variant!.id);
                                                        setEditContent(section.selected_variant!.content);
                                                    }
                                                }}
                                                dangerouslySetInnerHTML={{ __html: section.selected_variant.formatted_content }}
                                            />
                                        )}
                                    </CardContent>
                                </Card>
                            )}

                            {section.variants.length > 1 && (
                                <div className="flex flex-wrap gap-2">
                                    {section.variants.map((variant) => (
                                        <Button
                                            key={variant.id}
                                            variant={variant.id === section.selected_variant_id ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => selectVariant(section.id, variant.id)}
                                            disabled={resume.is_finalized}
                                        >
                                            {variant.label}
                                            {variant.is_user_edited && ' (edited)'}
                                        </Button>
                                    ))}
                                </div>
                            )}
                        </div>
                    ))}
            </div>

            {resume.job_posting && !isGenerating && (
                <PipelineAssistantPanel context={{ step: 'resume_builder', pipelineKey: `job_posting:${resume.job_posting.id}` }} />
            )}
        </AppLayout>
    );
}
