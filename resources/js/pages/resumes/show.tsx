import { Head, Link, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Bot, Check, ChevronDown, Download, Eye, Loader2, Pencil, Settings, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import Heading from '@/components/heading';
import PipelineAssistantPanel from '@/components/pipeline-assistant-panel';
import PipelineSteps from '@/components/pipeline-steps';
import TemplatePicker from '@/components/resume-templates/template-picker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Variant = { id: number; label: string; content: string; formatted_content: string; emphasis: string | null; is_ai_generated: boolean; is_user_edited: boolean };
type Section = { id: number; type: string; title: string; sort_order: number; selected_variant_id: number | null; variants: Variant[]; selected_variant: Variant | null };
type HeaderConfig = {
    name_preference: string;
    show_email: boolean;
    show_phone: boolean;
    show_location: boolean;
    show_linkedin: boolean;
    show_portfolio: boolean;
};

type ResumeData = {
    id: number;
    title: string;
    template: string;
    is_finalized: boolean;
    header_config: HeaderConfig | null;
    sections: Section[];
    job_posting: { id: number; title: string | null; company: string | null } | null;
};

const headerToggleFields = [
    { key: 'show_email' as const, label: 'Email' },
    { key: 'show_phone' as const, label: 'Phone' },
    { key: 'show_location' as const, label: 'Location' },
    { key: 'show_linkedin' as const, label: 'LinkedIn' },
    { key: 'show_portfolio' as const, label: 'Portfolio' },
];

export default function ShowResume({ resume, globalHeaderConfig }: { resume: ResumeData; globalHeaderConfig: HeaderConfig }) {
    const isGenerating = resume.sections.length === 0;
    const [editingVariant, setEditingVariant] = useState<number | null>(null);
    const [editContent, setEditContent] = useState('');
    const [headerOpen, setHeaderOpen] = useState(false);

    const effectiveConfig: HeaderConfig = { ...globalHeaderConfig, ...(resume.header_config ?? {}) };

    function updateHeaderConfig(key: string, value: boolean | string) {
        const updated = { ...effectiveConfig, [key]: value };
        router.put(`/resumes/${resume.id}`, { header_config: updated }, { preserveScroll: true });
    }

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

            <div className="mx-auto max-w-4xl space-y-6 p-4">
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

                {!isGenerating && !resume.is_finalized && (
                    <Collapsible open={headerOpen} onOpenChange={setHeaderOpen}>
                        <CollapsibleTrigger asChild>
                            <Button variant="ghost" size="sm" className="gap-2">
                                <Settings className="h-4 w-4" />
                                Header Settings
                                <ChevronDown className={`h-4 w-4 transition-transform ${headerOpen ? 'rotate-180' : ''}`} />
                            </Button>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <Card className="mt-2">
                                <CardContent className="space-y-4 pt-4">
                                    <div>
                                        <Label className="mb-2 block text-sm font-medium">Name Preference</Label>
                                        <div className="flex items-center gap-4">
                                            <label className="flex items-center gap-2 text-sm">
                                                <input
                                                    type="radio"
                                                    name="name_preference"
                                                    value="display_name"
                                                    checked={effectiveConfig.name_preference === 'display_name'}
                                                    onChange={() => updateHeaderConfig('name_preference', 'display_name')}
                                                    className="accent-primary"
                                                />
                                                Display Name
                                            </label>
                                            <label className="flex items-center gap-2 text-sm">
                                                <input
                                                    type="radio"
                                                    name="name_preference"
                                                    value="legal_name"
                                                    checked={effectiveConfig.name_preference === 'legal_name'}
                                                    onChange={() => updateHeaderConfig('name_preference', 'legal_name')}
                                                    className="accent-primary"
                                                />
                                                Legal Name
                                            </label>
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-sm font-medium">Show on Resume</Label>
                                        {headerToggleFields.map((field) => (
                                            <label key={field.key} className="flex items-center gap-2">
                                                <Checkbox
                                                    checked={effectiveConfig[field.key]}
                                                    onCheckedChange={(checked) => updateHeaderConfig(field.key, !!checked)}
                                                />
                                                <span className="text-sm">{field.label}</span>
                                            </label>
                                        ))}
                                    </div>
                                    <p className="text-muted-foreground text-xs">
                                        These override your global defaults from the Identity page.
                                    </p>
                                </CardContent>
                            </Card>
                        </CollapsibleContent>
                    </Collapsible>
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
                                <Card className={section.selected_variant.is_ai_generated && !section.selected_variant.is_user_edited ? 'border-l-4 border-l-info' : section.selected_variant.is_user_edited ? 'border-l-4 border-l-success' : ''}>
                                    <CardContent className="pt-4">
                                        {section.selected_variant.is_ai_generated && (
                                            <div className="mb-2 flex items-center gap-1.5">
                                                {section.selected_variant.is_user_edited ? (
                                                    <Badge variant="success" className="text-xs">
                                                        <Pencil className="mr-1 h-3 w-3" /> User Edited
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="info" className="text-xs">
                                                        <Bot className="mr-1 h-3 w-3" /> AI Generated
                                                    </Badge>
                                                )}
                                            </div>
                                        )}
                                        {editingVariant === section.selected_variant.id ? (
                                            <div className="space-y-2">
                                                <Textarea
                                                    value={editContent}
                                                    onChange={(e) => setEditContent(e.target.value)}
                                                    rows={8}
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
