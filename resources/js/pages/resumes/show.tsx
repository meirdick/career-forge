import Placeholder from '@tiptap/extension-placeholder';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, ArrowDown, ArrowUp, Bold, Bot, Check, ChevronsDownUp, ChevronsUpDown, Eye, EyeOff, Heading2, Italic, List, ListOrdered, Loader2, Pencil, RefreshCw, Trash2, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import ReactMarkdown from 'react-markdown';
import { htmlToMarkdown, markdownToHtml } from '@/lib/markdown-html';
import Heading from '@/components/heading';
import PipelineAssistantPanel from '@/components/pipeline-assistant-panel';
import PipelineNextAction from '@/components/pipeline-next-action';
import PipelineSteps from '@/components/pipeline-steps';
import ResumeDocument from '@/components/resume-templates/resume-document';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Block = { key: string; label: string; content: string; is_hidden: boolean };
type Variant = { id: number; label: string; content: string; compact_content: string | null; formatted_content: string; blocks: Block[] | null; emphasis: string | null; is_ai_generated: boolean; is_user_edited: boolean };
type Section = { id: number; type: string; title: string; sort_order: number; selected_variant_id: number | null; is_hidden: boolean; display_mode: 'compact' | 'expanded'; variants: Variant[]; selected_variant: Variant | null };
type GenerationProgress = { total: number; completed: number; current_section: string | null; expected_sections: string[] };

type Contact = {
    name?: string;
    email?: string;
    phone?: string;
    location?: string;
    linkedin_url?: string;
    portfolio_links?: { url: string; label: string }[];
};

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
    is_generating: boolean;
    generation_status: string | null;
    generation_progress: GenerationProgress | null;
    header_config: HeaderConfig | null;
    show_transparency: boolean;
    transparency_text: string | null;
    sections: Section[];
    job_posting: { id: number; title: string | null; company: string | null } | null;
};

const BLOCK_SECTION_TYPES = ['experience', 'education', 'projects'];

const HEADER_TOGGLE_FIELDS = [
    { key: 'show_email' as const, label: 'Email' },
    { key: 'show_phone' as const, label: 'Phone' },
    { key: 'show_location' as const, label: 'Location' },
    { key: 'show_linkedin' as const, label: 'LinkedIn' },
    { key: 'show_portfolio' as const, label: 'Portfolio Links' },
];

const DEFAULT_TRANSPARENCY_TEXT = 'This resume was created with AI assistance. View details at [your-link-here]';

function VariantTiptapEditor({ content, onChange, placeholder }: { content: string; onChange: (html: string) => void; placeholder?: string }) {
    const editor = useEditor({
        extensions: [
            StarterKit,
            Placeholder.configure({ placeholder: placeholder ?? 'Start writing...' }),
        ],
        content,
        editorProps: {
            attributes: {
                class: 'prose prose-sm dark:prose-invert max-w-none min-h-[120px] px-3 py-2 focus:outline-none [&_ul]:list-disc [&_ul]:pl-4 [&_ol]:list-decimal [&_ol]:pl-4 [&_h2]:text-base [&_h2]:font-semibold [&_h2]:mt-2 [&_p]:my-1',
                'aria-label': 'Resume section editor',
            },
        },
        onUpdate({ editor: e }) {
            onChange(e.getHTML());
        },
    });

    if (!editor) {
        return <div className="h-[120px] animate-pulse rounded-md bg-muted" />;
    }

    return (
        <div className="border-input bg-background rounded-md border text-sm">
            <div className="border-input flex gap-1 border-b px-2 py-1">
                <button type="button" onClick={() => editor.chain().focus().toggleBold().run()} className={`min-h-[44px] min-w-[44px] rounded p-1 hover:bg-accent ${editor.isActive('bold') ? 'bg-accent' : ''}`} title="Bold">
                    <Bold className="mx-auto h-4 w-4" />
                </button>
                <button type="button" onClick={() => editor.chain().focus().toggleItalic().run()} className={`min-h-[44px] min-w-[44px] rounded p-1 hover:bg-accent ${editor.isActive('italic') ? 'bg-accent' : ''}`} title="Italic">
                    <Italic className="mx-auto h-4 w-4" />
                </button>
                <button type="button" onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()} className={`min-h-[44px] min-w-[44px] rounded p-1 hover:bg-accent ${editor.isActive('heading', { level: 2 }) ? 'bg-accent' : ''}`} title="Heading">
                    <Heading2 className="mx-auto h-4 w-4" />
                </button>
                <button type="button" onClick={() => editor.chain().focus().toggleBulletList().run()} className={`min-h-[44px] min-w-[44px] rounded p-1 hover:bg-accent ${editor.isActive('bulletList') ? 'bg-accent' : ''}`} title="Bullet List">
                    <List className="mx-auto h-4 w-4" />
                </button>
                <button type="button" onClick={() => editor.chain().focus().toggleOrderedList().run()} className={`min-h-[44px] min-w-[44px] rounded p-1 hover:bg-accent ${editor.isActive('orderedList') ? 'bg-accent' : ''}`} title="Numbered List">
                    <ListOrdered className="mx-auto h-4 w-4" />
                </button>
            </div>
            <EditorContent editor={editor} />
        </div>
    );
}

export default function ShowResume({ resume, contact, globalHeaderConfig }: { resume: ResumeData; contact: Contact; globalHeaderConfig: HeaderConfig }) {
    const isGenerating = resume.is_generating;
    const isFailed = resume.generation_status === 'failed';
    const [editingVariant, setEditingVariant] = useState<number | null>(null);
    const [editContent, setEditContent] = useState('');
    const [editingBlock, setEditingBlock] = useState<{ variantId: number; blockKey: string } | null>(null);
    const [editBlockContent, setEditBlockContent] = useState('');
    const [editingTitle, setEditingTitle] = useState<number | null>(null);
    const [editTitleValue, setEditTitleValue] = useState('');
    const [transparencyText, setTransparencyText] = useState(resume.transparency_text ?? DEFAULT_TRANSPARENCY_TEXT);
    const effectiveConfig: HeaderConfig = { ...globalHeaderConfig, ...(resume.header_config ?? {}) };
    // Track which sections are new (for fade-in animation) — sections
    // present at mount are "seen"; anything arriving later animates in.
    const [initialSectionIds] = useState(() => new Set(resume.sections.map((s) => s.id)));
    const newSectionIds = useMemo(
        () => new Set(resume.sections.filter((s) => !initialSectionIds.has(s.id)).map((s) => s.id)),
        [resume.sections, initialSectionIds],
    );

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

    const sortedSections = [...resume.sections].sort((a, b) => a.sort_order - b.sort_order);
    const progress = resume.generation_progress;
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
        const markdown = htmlToMarkdown(editContent);
        router.put(`/resumes/${resume.id}/variants/${variantId}`, { content: markdown }, { preserveScroll: true, onSuccess: () => setEditingVariant(null) });
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

    function updateHeaderConfig(key: string, value: boolean | string) {
        const updated = { ...effectiveConfig, [key]: value };
        router.put(`/resumes/${resume.id}`, { header_config: updated }, { preserveScroll: true });
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
                    <VariantTiptapEditor
                        content={editContent}
                        onChange={setEditContent}
                        placeholder="Start writing or generate content with AI..."
                    />
                    <div className="flex items-center justify-end gap-2">
                        {variant.is_user_edited && (
                            <span className="text-muted-foreground mr-auto text-xs">Edited by you</span>
                        )}
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
                            setEditContent(markdownToHtml(variant.content));
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
                            <Button variant="outline" size="sm" asChild>
                                <Link href={`/resumes/${resume.id}/preview`}>
                                    <Eye className="mr-1 h-4 w-4" /> Preview & Export
                                </Link>
                            </Button>
                        )}
                        {!resume.is_finalized && !isGenerating && !isFailed && (
                            <Button size="sm" onClick={() => router.post(`/resumes/${resume.id}/finalize`)}>
                                <Check className="mr-1 h-4 w-4" /> Finalize
                            </Button>
                        )}
                        <Button variant="destructive" size="sm" onClick={() => { if (confirm('Delete this resume?')) router.delete(`/resumes/${resume.id}`); }}>
                            <Trash2 className="mr-1 h-4 w-4" />
                        </Button>
                    </div>
                </div>

                {resume.is_finalized && <Badge variant="secondary">Finalized</Badge>}

                {/* Header settings */}
                {!resume.is_finalized && !isGenerating && !isFailed && (
                    <div className="space-y-3">
                        <Separator />
                        <h2 className="text-lg font-semibold">Contact Header</h2>
                        <Card>
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
                                    {HEADER_TOGGLE_FIELDS.map((field) => (
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
                    </div>
                )}

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
                        <CardContent className="flex items-center justify-between py-6">
                            <div className="flex items-center gap-3">
                                <AlertTriangle className="text-destructive h-6 w-6 shrink-0" />
                                <div>
                                    <p className="font-medium">Generation failed</p>
                                    <p className="text-muted-foreground text-sm">Something went wrong while generating your resume.</p>
                                </div>
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => router.post(`/resumes/${resume.id}/regenerate`)}
                            >
                                <RefreshCw className="mr-1.5 h-4 w-4" /> Retry
                            </Button>
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

                {/* Transparency statement */}
                {!resume.is_finalized && !isGenerating && !isFailed && sortedSections.length > 0 && (
                    <div className="space-y-3">
                        <Separator />
                        <h2 className="text-lg font-semibold">AI Transparency</h2>
                        <Card>
                            <CardContent className="space-y-3 pt-4">
                                <label className="flex items-center gap-2">
                                    <Checkbox
                                        checked={resume.show_transparency}
                                        onCheckedChange={(checked) =>
                                            router.put(`/resumes/${resume.id}`, { show_transparency: !!checked, transparency_text: transparencyText }, { preserveScroll: true })
                                        }
                                    />
                                    <span className="text-sm font-medium">Include AI transparency statement</span>
                                </label>
                                {resume.show_transparency && (
                                    <div className="space-y-2">
                                        <Input
                                            value={transparencyText}
                                            onChange={(e) => setTransparencyText(e.target.value)}
                                            onBlur={() =>
                                                router.put(`/resumes/${resume.id}`, { transparency_text: transparencyText, show_transparency: true }, { preserveScroll: true })
                                            }
                                            placeholder="e.g. This resume was created with AI assistance."
                                            className="text-sm"
                                        />
                                        <p className="text-muted-foreground text-xs">This text will appear at the bottom of your exported resume.</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* Document preview */}
                {!isGenerating && !isFailed && sortedSections.length > 0 && (
                    <div className="space-y-3">
                        <Separator />
                        <h2 className="text-lg font-semibold">Preview</h2>
                        <ResumeDocument
                            template={resume.template ?? 'classic'}
                            contact={contact}
                            sections={resume.sections.filter((s) => !s.is_hidden)}
                        />
                    </div>
                )}

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
