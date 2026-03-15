import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, ArrowDown, ArrowUp, Bot, Check, ChevronsDownUp, ChevronsUpDown, Eye, EyeOff, Loader2, Pencil, Trash2, X } from 'lucide-react';
import ReactMarkdown from 'react-markdown';
import { useEffect, useRef, useState } from 'react';
import Heading from '@/components/heading';
import PipelineAssistantPanel from '@/components/pipeline-assistant-panel';
import PipelineNextAction from '@/components/pipeline-next-action';
import PipelineSteps from '@/components/pipeline-steps';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Block = { key: string; label: string; content: string; is_hidden: boolean };
type Variant = { id: number; label: string; content: string; compact_content: string | null; formatted_content: string; blocks: Block[] | null; emphasis: string | null; is_ai_generated: boolean; is_user_edited: boolean };
type Section = { id: number; type: string; title: string; sort_order: number; selected_variant_id: number | null; is_hidden: boolean; display_mode: 'compact' | 'expanded'; variants: Variant[]; selected_variant: Variant | null };
type GenerationProgress = { total: number; completed: number; current_section: string | null; expected_sections: string[] };

type ResumeData = {
    id: number;
    title: string;
    template: string;
    is_finalized: boolean;
    is_generating: boolean;
    generation_status: string | null;
    generation_progress: GenerationProgress | null;
    header_config: Record<string, unknown> | null;
    sections: Section[];
    job_posting: { id: number; title: string | null; company: string | null } | null;
};

const BLOCK_SECTION_TYPES = ['experience', 'education', 'projects'];

export default function ShowResume({ resume }: { resume: ResumeData }) {
    const isGenerating = resume.is_generating;
    const isFailed = resume.generation_status === 'failed';
    const [editingVariant, setEditingVariant] = useState<number | null>(null);
    const [editContent, setEditContent] = useState('');
    const [editingBlock, setEditingBlock] = useState<{ variantId: number; blockKey: string } | null>(null);
    const [editBlockContent, setEditBlockContent] = useState('');
    const [editingTitle, setEditingTitle] = useState<number | null>(null);
    const [editTitleValue, setEditTitleValue] = useState('');
    const seenSectionIds = useRef(new Set<number>());

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Resumes', href: '/resumes' },
        { title: resume.title, href: `/resumes/${resume.id}` },
    ];

    useEffect(() => {
        if (isGenerating) {
            const interval = setInterval(() => {
                router.reload({ only: ['resume'] });
            }, 3000);
            return () => clearInterval(interval);
        }
    }, [isGenerating]);

    // Track which sections are new (for fade-in animation)
    const newSectionIds = new Set<number>();
    resume.sections.forEach((s) => {
        if (!seenSectionIds.current.has(s.id)) {
            newSectionIds.add(s.id);
            seenSectionIds.current.add(s.id);
        }
    });

    const sortedSections = [...resume.sections].sort((a, b) => a.sort_order - b.sort_order);
    const progress = resume.generation_progress;
    const completedSectionNames = progress?.expected_sections?.slice(0, progress.completed) ?? [];
    const pendingSectionNames = progress?.expected_sections?.slice(progress.completed) ?? [];

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

    function toggleSection(sectionId: number) {
        router.put(`/resumes/${resume.id}/sections/${sectionId}/toggle`, {}, { preserveScroll: true });
    }

    function saveSectionTitle(sectionId: number) {
        router.patch(`/resumes/${resume.id}/sections/${sectionId}`, { title: editTitleValue }, { preserveScroll: true, onSuccess: () => setEditingTitle(null) });
    }

    function deleteSection(sectionId: number) {
        if (confirm('Delete this section? This cannot be undone.')) {
            router.delete(`/resumes/${resume.id}/sections/${sectionId}`, { preserveScroll: true });
        }
    }

    function toggleDisplayMode(sectionId: number, currentMode: string) {
        const newMode = currentMode === 'compact' ? 'expanded' : 'compact';
        router.patch(`/resumes/${resume.id}/sections/${sectionId}`, { display_mode: newMode }, { preserveScroll: true });
    }

    function updateBlocks(variant: Variant, updatedBlocks: Block[]) {
        router.patch(`/resumes/${resume.id}/variants/${variant.id}/blocks`, { blocks: updatedBlocks }, { preserveScroll: true });
    }

    function toggleBlock(variant: Variant, blockKey: string) {
        const blocks = (variant.blocks ?? []).map((b) =>
            b.key === blockKey ? { ...b, is_hidden: !b.is_hidden } : b,
        );
        updateBlocks(variant, blocks);
    }

    function moveBlock(variant: Variant, fromIdx: number, toIdx: number) {
        const blocks = [...(variant.blocks ?? [])];
        const [moved] = blocks.splice(fromIdx, 1);
        blocks.splice(toIdx, 0, moved);
        updateBlocks(variant, blocks);
    }

    function saveBlockEdit(variant: Variant, blockKey: string) {
        const blocks = (variant.blocks ?? []).map((b) =>
            b.key === blockKey ? { ...b, content: editBlockContent } : b,
        );
        updateBlocks(variant, blocks);
        setEditingBlock(null);
    }

    function renderBlockEditor(variant: Variant) {
        const blocks = variant.blocks ?? [];
        return (
            <div className="space-y-3">
                {blocks.map((block, idx) => {
                    const isEditing = editingBlock?.variantId === variant.id && editingBlock?.blockKey === block.key;
                    return (
                        <div key={block.key} className={`rounded-lg border p-3 ${block.is_hidden ? 'opacity-50' : ''}`}>
                            <div className="mb-2 flex items-center justify-between">
                                <span className="text-sm font-medium">{block.label}</span>
                                {!resume.is_finalized && (
                                    <div className="flex gap-1">
                                        {!isEditing && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => {
                                                    setEditingBlock({ variantId: variant.id, blockKey: block.key });
                                                    setEditBlockContent(block.content);
                                                }}
                                                title="Edit block"
                                            >
                                                <Pencil className="h-3 w-3" />
                                            </Button>
                                        )}
                                        <Button variant="ghost" size="sm" onClick={() => toggleBlock(variant, block.key)} title={block.is_hidden ? 'Show' : 'Hide'}>
                                            {block.is_hidden ? <Eye className="h-3 w-3" /> : <EyeOff className="h-3 w-3" />}
                                        </Button>
                                        {block.is_hidden && <Badge variant="secondary" className="text-xs">Hidden</Badge>}
                                        {idx > 0 && (
                                            <Button variant="ghost" size="sm" onClick={() => moveBlock(variant, idx, idx - 1)} title="Move up">
                                                <ArrowUp className="h-3 w-3" />
                                            </Button>
                                        )}
                                        {idx < blocks.length - 1 && (
                                            <Button variant="ghost" size="sm" onClick={() => moveBlock(variant, idx, idx + 1)} title="Move down">
                                                <ArrowDown className="h-3 w-3" />
                                            </Button>
                                        )}
                                    </div>
                                )}
                            </div>
                            {isEditing ? (
                                <div className="space-y-2">
                                    <div className="grid grid-cols-2 gap-3">
                                        <Textarea
                                            value={editBlockContent}
                                            onChange={(e) => setEditBlockContent(e.target.value)}
                                            rows={6}
                                            className="font-mono text-sm"
                                        />
                                        <div className="prose prose-sm dark:prose-invert max-w-none overflow-auto rounded-md border p-3">
                                            <ReactMarkdown>{editBlockContent}</ReactMarkdown>
                                        </div>
                                    </div>
                                    <div className="flex justify-end gap-2">
                                        <Button variant="ghost" size="sm" onClick={() => setEditingBlock(null)}>Cancel</Button>
                                        <Button size="sm" onClick={() => saveBlockEdit(variant, block.key)}>Save</Button>
                                    </div>
                                </div>
                            ) : (
                                !block.is_hidden && (
                                    <div className="prose prose-sm dark:prose-invert max-w-none">
                                        <ReactMarkdown>{block.content}</ReactMarkdown>
                                    </div>
                                )
                            )}
                        </div>
                    );
                })}
            </div>
        );
    }

    function renderVariantContent(section: Section, variant: Variant) {
        // Compact mode — show compact_content as plain text
        if (section.display_mode === 'compact' && variant.compact_content) {
            return (
                <div className="text-muted-foreground text-sm italic">
                    {variant.compact_content}
                </div>
            );
        }

        const hasBlocks = BLOCK_SECTION_TYPES.includes(section.type) && variant.blocks && variant.blocks.length > 0;

        if (hasBlocks) {
            return renderBlockEditor(variant);
        }

        // Single-content editing (Summary, Skills, etc.)
        if (editingVariant === variant.id) {
            return (
                <div className="space-y-2">
                    <div className="grid grid-cols-2 gap-3">
                        <Textarea
                            value={editContent}
                            onChange={(e) => setEditContent(e.target.value)}
                            rows={8}
                            className="font-mono text-sm"
                        />
                        <div className="prose prose-sm dark:prose-invert max-w-none overflow-auto rounded-md border p-3">
                            <ReactMarkdown>{editContent}</ReactMarkdown>
                        </div>
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button variant="ghost" size="sm" onClick={() => setEditingVariant(null)}>Cancel</Button>
                        <Button size="sm" onClick={() => saveVariant(variant.id)}>Save</Button>
                    </div>
                </div>
            );
        }

        return (
            <div className="group relative">
                <div className="prose prose-sm dark:prose-invert max-w-none">
                    <ReactMarkdown>{variant.content}</ReactMarkdown>
                </div>
                {!resume.is_finalized && (
                    <Button
                        variant="ghost"
                        size="sm"
                        className="absolute top-0 right-0 opacity-0 group-hover:opacity-100"
                        onClick={() => {
                            setEditingVariant(variant.id);
                            setEditContent(variant.content);
                        }}
                        title="Edit content"
                    >
                        <Pencil className="h-3 w-3" />
                    </Button>
                )}
            </div>
        );
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
                            { label: 'Application', href: `/applications/create?job_posting_id=${resume.job_posting.id}`, status: 'upcoming' },
                        ]}
                    />
                )}
                <div className="flex items-start justify-between">
                    <Heading title={resume.title} description={resume.job_posting ? `For ${resume.job_posting.title ?? 'Untitled'} at ${resume.job_posting.company ?? 'Unknown'}` : undefined} />
                    <div className="flex gap-2">
                        {!isGenerating && !isFailed && (
                            <Link href={`/resumes/${resume.id}/preview`}>
                                <Button variant="outline" size="sm">
                                    <Eye className="mr-1 h-4 w-4" /> Preview & Export
                                </Button>
                            </Link>
                        )}
                        {!resume.is_finalized && !isGenerating && !isFailed && (
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

                {/* Generation progress card */}
                {isGenerating && progress && (
                    <Card>
                        <CardContent className="space-y-4 py-6">
                            <div className="flex items-center gap-3">
                                <Loader2 className="text-primary h-5 w-5 animate-spin" />
                                <div>
                                    <p className="font-medium">
                                        {progress.current_section
                                            ? `Writing ${progress.current_section}...`
                                            : 'Starting generation...'}
                                    </p>
                                    <p className="text-muted-foreground text-sm">
                                        Section {progress.completed} of {progress.total}
                                    </p>
                                </div>
                            </div>
                            <div className="bg-secondary h-2 w-full overflow-hidden rounded-full">
                                <div
                                    className="bg-primary h-full rounded-full transition-all duration-500 ease-out"
                                    style={{ width: `${progress.total > 0 ? (progress.completed / progress.total) * 100 : 0}%` }}
                                />
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Pending state without progress data yet */}
                {isGenerating && !progress && (
                    <Card>
                        <CardContent className="flex items-center gap-3 py-8">
                            <Loader2 className="text-primary h-6 w-6 animate-spin" />
                            <p className="text-muted-foreground">Preparing resume generation...</p>
                        </CardContent>
                    </Card>
                )}

                {/* Failed state */}
                {isFailed && (
                    <Card className="border-destructive">
                        <CardContent className="flex items-center gap-3 py-6">
                            <AlertTriangle className="text-destructive h-6 w-6" />
                            <div>
                                <p className="font-medium">Generation failed</p>
                                <p className="text-muted-foreground text-sm">Something went wrong while generating your resume. You can delete this resume and try again.</p>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Completed sections */}
                {sortedSections.map((section, index) => (
                    <div key={section.id} className={`space-y-3 ${section.is_hidden ? 'opacity-50' : ''} ${newSectionIds.has(section.id) ? 'animate-fade-in-up' : ''}`}>
                        <Separator />
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                {editingTitle === section.id ? (
                                    <div className="flex items-center gap-1">
                                        <Input
                                            value={editTitleValue}
                                            onChange={(e) => setEditTitleValue(e.target.value)}
                                            className="h-8 w-48"
                                            onKeyDown={(e) => { if (e.key === 'Enter') saveSectionTitle(section.id); if (e.key === 'Escape') setEditingTitle(null); }}
                                            autoFocus
                                        />
                                        <Button variant="ghost" size="sm" onClick={() => saveSectionTitle(section.id)}>
                                            <Check className="h-3 w-3" />
                                        </Button>
                                        <Button variant="ghost" size="sm" onClick={() => setEditingTitle(null)}>
                                            <X className="h-3 w-3" />
                                        </Button>
                                    </div>
                                ) : (
                                    <>
                                        <h2 className={`text-lg font-semibold ${section.is_hidden ? 'line-through' : ''}`}>{section.title}</h2>
                                        {section.is_hidden && <Badge variant="secondary" className="text-xs">Hidden</Badge>}
                                        {!section.is_hidden && section.display_mode === 'compact' && <Badge variant="outline" className="text-xs">Compact</Badge>}
                                    </>
                                )}
                            </div>
                            {!resume.is_finalized && (
                                <div className="flex gap-1">
                                    {editingTitle !== section.id && (
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => { setEditingTitle(section.id); setEditTitleValue(section.title); }}
                                            title="Rename section"
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </Button>
                                    )}
                                    {section.selected_variant?.compact_content && (
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => toggleDisplayMode(section.id, section.display_mode)}
                                            title={section.display_mode === 'compact' ? 'Expand section' : 'Compact section'}
                                        >
                                            {section.display_mode === 'compact' ? <ChevronsUpDown className="h-4 w-4" /> : <ChevronsDownUp className="h-4 w-4" />}
                                        </Button>
                                    )}
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => toggleSection(section.id)}
                                        title={section.is_hidden ? 'Show section' : 'Hide section'}
                                    >
                                        {section.is_hidden ? <Eye className="h-4 w-4" /> : <EyeOff className="h-4 w-4" />}
                                    </Button>
                                    {sortedSections.length > 1 && (
                                        <>
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
                                        </>
                                    )}
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => deleteSection(section.id)}
                                        title="Delete section"
                                        className="text-destructive hover:text-destructive"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            )}
                        </div>

                        {!section.is_hidden && section.selected_variant && (
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
                                    {renderVariantContent(section, section.selected_variant)}
                                </CardContent>
                            </Card>
                        )}

                        {!section.is_hidden && section.variants.length > 1 && (
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

                {/* Skeleton cards for sections still generating */}
                {isGenerating && progress && pendingSectionNames.map((name) => {
                    const isCurrent = name === progress.current_section;
                    return (
                        <div key={name} className="space-y-3">
                            <Separator />
                            <div className="flex items-center gap-2">
                                <Skeleton className={`h-6 w-32 ${isCurrent ? 'animate-pulse' : ''}`} />
                                {isCurrent && (
                                    <Loader2 className="text-muted-foreground h-4 w-4 animate-spin" />
                                )}
                            </div>
                            <Card>
                                <CardContent className={`space-y-3 pt-4 ${isCurrent ? 'animate-pulse' : ''}`}>
                                    <p className="text-muted-foreground text-xs">{name}</p>
                                    <Skeleton className="h-4 w-full" />
                                    <Skeleton className="h-4 w-5/6" />
                                    <Skeleton className="h-4 w-4/6" />
                                </CardContent>
                            </Card>
                        </div>
                    );
                })}

                {resume.job_posting && !isGenerating && !isFailed && (
                    <PipelineNextAction
                        label="Create Application"
                        description="Finalize your resume and create an application"
                        href={`/applications/create?job_posting_id=${resume.job_posting.id}`}
                    />
                )}
            </div>

            {resume.job_posting && !isGenerating && !isFailed && (
                <PipelineAssistantPanel context={{ step: 'resume_builder', pipelineKey: `job_posting:${resume.job_posting.id}` }} />
            )}
        </AppLayout>
    );
}
