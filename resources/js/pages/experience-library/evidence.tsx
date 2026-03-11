import { Form, Head, router } from '@inertiajs/react';
import { CheckCircle, ExternalLink, Globe, Import, LinkIcon, Loader2, Plus, Sparkles, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import EmptyState from '@/components/empty-state';
import AppLayout from '@/layouts/app-layout';
import EvidenceEntryController from '@/actions/App/Http/Controllers/ExperienceLibrary/EvidenceEntryController';
import { index as evidenceIndex } from '@/routes/evidence';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Experience Library', href: '/experience-library' },
    { title: 'Links', href: evidenceIndex() },
];

const evidenceTypes = ['portfolio', 'repository', 'article', 'review', 'testimonial', 'other'] as const;

const typeLabels: Record<string, string> = {
    portfolio: 'Portfolio',
    repository: 'Repository',
    article: 'Article',
    review: 'Review',
    testimonial: 'Testimonial',
    other: 'Other',
};

type EvidenceEntry = {
    id: number;
    type: string;
    title: string;
    url: string | null;
    pages: string[] | null;
    description: string | null;
    content: string | null;
};

type IndexResult = {
    skills: { name: string; category: string }[];
    accomplishments: { title: string; description: string; impact?: string }[];
    projects: { name: string; description: string; role?: string }[];
};

type IndexStatus = {
    status: 'processing' | 'completed' | 'failed' | 'imported';
    data?: IndexResult;
    error?: string;
};

type DiscoverStatus = {
    status: 'processing' | 'completed' | 'failed';
    links?: { url: string }[];
    error?: string;
};

type Props = {
    entries: EvidenceEntry[];
    indexResults: Record<number, IndexStatus>;
    discoverResults: Record<number, DiscoverStatus>;
};

function IndexResultsDisplay({ data, imported, onImport }: { data: IndexResult; imported: boolean; onImport: () => void }) {
    return (
        <div className="space-y-2 rounded-md bg-muted/50 p-3">
            <div className="flex items-center justify-between">
                <p className="text-xs font-medium">Extracted from URL:</p>
                {imported && (
                    <Badge variant="secondary" className="text-xs">
                        <CheckCircle className="mr-1 h-3 w-3" />
                        Imported
                    </Badge>
                )}
            </div>
            {data.skills.length > 0 && (
                <div>
                    <p className="text-xs text-muted-foreground">Skills</p>
                    <div className="flex flex-wrap gap-1">
                        {data.skills.map((s, i) => (
                            <Badge key={i} variant="secondary" className="text-xs">{s.name}</Badge>
                        ))}
                    </div>
                </div>
            )}
            {data.accomplishments.length > 0 && (
                <div>
                    <p className="text-xs text-muted-foreground">Accomplishments</p>
                    {data.accomplishments.map((a, i) => (
                        <p key={i} className="text-xs">{a.title} — {a.description}</p>
                    ))}
                </div>
            )}
            {data.projects.length > 0 && (
                <div>
                    <p className="text-xs text-muted-foreground">Projects</p>
                    {data.projects.map((p, i) => (
                        <p key={i} className="text-xs">{p.name} — {p.description}</p>
                    ))}
                </div>
            )}
            {!imported && (
                <Button variant="outline" size="sm" className="mt-1" onClick={onImport}>
                    <Import className="mr-1 h-3 w-3" />
                    Import to Library
                </Button>
            )}
        </div>
    );
}

function DiscoverLinksDisplay({ entryId, discoverStatus, savedPages }: { entryId: number; discoverStatus: DiscoverStatus; savedPages: string[] | null }) {
    const [selected, setSelected] = useState<Set<string>>(() => new Set(savedPages ?? []));
    const [saving, setSaving] = useState(false);

    if (discoverStatus.status === 'processing') {
        return (
            <div className="flex items-center gap-2 rounded-md bg-muted/50 p-3">
                <Loader2 className="h-3 w-3 animate-spin" />
                <p className="text-xs text-muted-foreground">Discovering pages...</p>
            </div>
        );
    }

    if (discoverStatus.status === 'failed') {
        return (
            <p className="text-xs text-destructive">{discoverStatus.error || 'Discovery failed.'}</p>
        );
    }

    if (!discoverStatus.links || discoverStatus.links.length === 0) {
        return (
            <p className="text-xs text-muted-foreground">No additional pages found.</p>
        );
    }

    function toggleUrl(url: string) {
        setSelected((prev) => {
            const next = new Set(prev);
            if (next.has(url)) {
                next.delete(url);
            } else {
                next.add(url);
            }
            return next;
        });
    }

    function selectAll() {
        setSelected(new Set(discoverStatus.links!.map((l) => l.url)));
    }

    function deselectAll() {
        setSelected(new Set());
    }

    const allSelected = discoverStatus.links.length > 0 && selected.size === discoverStatus.links.length;

    function saveSelected() {
        if (selected.size === 0) return;
        setSaving(true);
        router.post(
            EvidenceEntryController.saveSelectedPages(entryId).url,
            { urls: [...selected] },
            { preserveScroll: true, onFinish: () => setSaving(false) },
        );
    }

    const savedSet = new Set(savedPages ?? []);
    const hasChanges = selected.size !== savedSet.size || [...selected].some((u) => !savedSet.has(u));

    return (
        <div className="space-y-2 rounded-md border p-3">
            <div className="flex items-center justify-between">
                <p className="text-xs font-medium">Discovered Pages ({discoverStatus.links.length})</p>
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" className="h-6 text-xs" onClick={allSelected ? deselectAll : selectAll}>
                        {allSelected ? 'Deselect All' : 'Select All'}
                    </Button>
                </div>
            </div>
            <div className="max-h-48 space-y-1 overflow-y-auto">
                {discoverStatus.links.map((link) => (
                    <label key={link.url} className="flex items-center gap-2 rounded px-1 py-0.5 text-xs hover:bg-muted/50">
                        <Checkbox
                            checked={selected.has(link.url)}
                            onCheckedChange={() => toggleUrl(link.url)}
                        />
                        <span className="truncate">{link.url}</span>
                    </label>
                ))}
            </div>
            <div className="flex items-center gap-2">
                {selected.size > 0 && hasChanges && (
                    <Button size="sm" onClick={saveSelected} disabled={saving}>
                        {saving ? <Loader2 className="mr-1 h-3 w-3 animate-spin" /> : <CheckCircle className="mr-1 h-3 w-3" />}
                        Save {selected.size} Page{selected.size !== 1 ? 's' : ''} for Indexing
                    </Button>
                )}
                {savedPages && savedPages.length > 0 && !hasChanges && (
                    <p className="text-xs text-muted-foreground">{savedPages.length} page{savedPages.length !== 1 ? 's' : ''} selected for indexing</p>
                )}
            </div>
        </div>
    );
}

export default function Evidence({ entries, indexResults, discoverResults }: Props) {
    const [showForm, setShowForm] = useState(false);
    const pollingRef = useRef<ReturnType<typeof setInterval> | null>(null);

    const hasProcessing = Object.values(indexResults).some((r) => r.status === 'processing')
        || Object.values(discoverResults).some((r) => r.status === 'processing');

    useEffect(() => {
        if (hasProcessing && !pollingRef.current) {
            pollingRef.current = setInterval(() => {
                router.reload({ only: ['indexResults', 'discoverResults'] });
            }, 3000);
        }

        if (!hasProcessing && pollingRef.current) {
            clearInterval(pollingRef.current);
            pollingRef.current = null;
        }

        return () => {
            if (pollingRef.current) {
                clearInterval(pollingRef.current);
                pollingRef.current = null;
            }
        };
    }, [hasProcessing]);

    function indexLink(entryId: number) {
        router.post(EvidenceEntryController.indexLink(entryId).url, {}, { preserveScroll: true });
    }

    function importResults(entryId: number) {
        router.post(EvidenceEntryController.importResults(entryId).url, {}, { preserveScroll: true });
    }

    function discoverLinks(entryId: number) {
        router.post(EvidenceEntryController.discoverLinks(entryId).url, {}, { preserveScroll: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Links" />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Links" description="Links, portfolios, and other supporting evidence" />
                    <Button onClick={() => setShowForm(!showForm)}>
                        <Plus className="mr-2 h-4 w-4" />
                        Add Link
                    </Button>
                </div>

                {showForm && (
                    <Card>
                        <CardContent className="pt-6">
                            <Form {...EvidenceEntryController.store.form()} options={{ preserveScroll: true, onSuccess: () => setShowForm(false) }} className="grid gap-4 sm:grid-cols-2">
                                {({ processing, errors }) => (
                                    <>
                                        <div>
                                            <Label htmlFor="type">Type</Label>
                                            <Select name="type" required>
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Select..." />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {evidenceTypes.map((t) => (
                                                        <SelectItem key={t} value={t}>{typeLabels[t]}</SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.type} />
                                        </div>
                                        <div>
                                            <Label htmlFor="title">Title</Label>
                                            <Input id="title" name="title" required placeholder="e.g. My Open Source Project" />
                                            <InputError message={errors.title} />
                                        </div>
                                        <div className="sm:col-span-2">
                                            <Label htmlFor="url">URL</Label>
                                            <Input id="url" name="url" type="url" placeholder="https://..." />
                                            <InputError message={errors.url} />
                                        </div>
                                        <div className="sm:col-span-2">
                                            <Label htmlFor="description">Description</Label>
                                            <Textarea
                                                id="description"
                                                name="description"
                                                rows={2}
                                                placeholder="Brief description..."
                                            />
                                            <InputError message={errors.description} />
                                        </div>
                                        <div className="sm:col-span-2 flex justify-end gap-2">
                                            <Button type="button" variant="ghost" onClick={() => setShowForm(false)}>Cancel</Button>
                                            <Button type="submit" disabled={processing}>Save</Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                {entries.length === 0 ? (
                    <EmptyState
                        icon={LinkIcon}
                        title="No links yet"
                        description="Add links, portfolios, and other supporting evidence to strengthen your profile."
                        action={<Button onClick={() => setShowForm(true)}><Plus className="mr-2 h-4 w-4" />Add Link</Button>}
                    />
                ) : (
                    <div className="space-y-3">
                        {entries.map((entry) => (
                            <Card key={entry.id}>
                                <CardHeader className="flex-row items-center justify-between pb-2">
                                    <div>
                                        <CardTitle className="text-base">{entry.title}</CardTitle>
                                        {entry.url && (
                                            <a href={entry.url} target="_blank" rel="noopener noreferrer" className="text-sm text-primary hover:underline flex items-center gap-1">
                                                {entry.url} <ExternalLink className="h-3 w-3" />
                                            </a>
                                        )}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Badge variant="outline">{typeLabels[entry.type] ?? entry.type}</Badge>
                                        {entry.pages && entry.pages.length > 0 && (
                                            <Badge variant="secondary" className="text-xs">{entry.pages.length + 1} pages</Badge>
                                        )}
                                        {entry.url && (
                                            <>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => discoverLinks(entry.id)}
                                                    disabled={discoverResults[entry.id]?.status === 'processing'}
                                                    title="Discover child pages"
                                                >
                                                    {discoverResults[entry.id]?.status === 'processing' ? <Loader2 className="mr-1 h-3 w-3 animate-spin" /> : <Globe className="mr-1 h-3 w-3" />}
                                                    Discover
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => indexLink(entry.id)}
                                                    disabled={indexResults[entry.id]?.status === 'processing'}
                                                >
                                                    {indexResults[entry.id]?.status === 'processing' ? <Loader2 className="mr-1 h-3 w-3 animate-spin" /> : <Sparkles className="mr-1 h-3 w-3" />}
                                                    {indexResults[entry.id]?.status === 'processing' ? 'Indexing...' : 'Index'}
                                                </Button>
                                            </>
                                        )}
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-8 w-8"
                                            onClick={() => router.delete(EvidenceEntryController.destroy(entry.id).url, { preserveScroll: true })}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-2 pt-0">
                                    {entry.description && (
                                        <p className="text-sm text-muted-foreground">{entry.description}</p>
                                    )}
                                    {discoverResults[entry.id] && discoverResults[entry.id].status !== 'processing' && (
                                        <DiscoverLinksDisplay entryId={entry.id} discoverStatus={discoverResults[entry.id]} savedPages={entry.pages} />
                                    )}
                                    {indexResults[entry.id]?.status === 'failed' && (
                                        <p className="text-sm text-destructive">{indexResults[entry.id].error || 'Indexing failed.'}</p>
                                    )}
                                    {(indexResults[entry.id]?.status === 'completed' || indexResults[entry.id]?.status === 'imported') && indexResults[entry.id].data && (
                                        <IndexResultsDisplay
                                            data={indexResults[entry.id].data!}
                                            imported={indexResults[entry.id].status === 'imported'}
                                            onImport={() => importResults(entry.id)}
                                        />
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
