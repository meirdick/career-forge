import { Form, Head, router } from '@inertiajs/react';
import axios from 'axios';
import { ExternalLink, LinkIcon, Loader2, Plus, Sparkles, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import EvidenceEntryController from '@/actions/App/Http/Controllers/ExperienceLibrary/EvidenceEntryController';
import { index as evidenceIndex } from '@/routes/evidence';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Experience Library', href: '/experience-library' },
    { title: 'Evidence', href: evidenceIndex() },
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
    description: string | null;
    content: string | null;
};

type IndexResult = {
    skills: { name: string; category: string }[];
    accomplishments: { title: string; description: string; impact?: string }[];
    projects: { name: string; description: string; role?: string }[];
};

export default function Evidence({ entries }: { entries: EvidenceEntry[] }) {
    const [showForm, setShowForm] = useState(false);
    const [indexingId, setIndexingId] = useState<number | null>(null);
    const [indexResults, setIndexResults] = useState<Record<number, IndexResult>>({});

    async function indexLink(entryId: number) {
        setIndexingId(entryId);
        try {
            const { data } = await axios.post(EvidenceEntryController.indexLink(entryId).url);
            setIndexResults((prev) => ({ ...prev, [entryId]: data }));
        } catch {
            // error handled silently
        } finally {
            setIndexingId(null);
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Evidence" />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Evidence" description="Links, portfolios, and other supporting evidence" />
                    <Button onClick={() => setShowForm(!showForm)}>
                        <Plus className="mr-2 h-4 w-4" />
                        Add Evidence
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
                                            <select name="type" id="type" required className="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm">
                                                <option value="">Select...</option>
                                                {evidenceTypes.map((t) => (
                                                    <option key={t} value={t}>{typeLabels[t]}</option>
                                                ))}
                                            </select>
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
                                            <textarea
                                                id="description"
                                                name="description"
                                                rows={2}
                                                placeholder="Brief description..."
                                                className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm"
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
                    <Card>
                        <CardContent className="py-12 text-center">
                            <LinkIcon className="mx-auto mb-3 h-8 w-8 text-muted-foreground" />
                            <p className="text-muted-foreground">No evidence entries yet.</p>
                        </CardContent>
                    </Card>
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
                                        {entry.url && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => indexLink(entry.id)}
                                                disabled={indexingId === entry.id}
                                            >
                                                {indexingId === entry.id ? <Loader2 className="mr-1 h-3 w-3 animate-spin" /> : <Sparkles className="mr-1 h-3 w-3" />}
                                                Index
                                            </Button>
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
                                    {indexResults[entry.id] && (
                                        <div className="space-y-2 rounded-md bg-muted/50 p-3">
                                            <p className="text-xs font-medium">Extracted from URL:</p>
                                            {indexResults[entry.id].skills.length > 0 && (
                                                <div>
                                                    <p className="text-xs text-muted-foreground">Skills</p>
                                                    <div className="flex flex-wrap gap-1">
                                                        {indexResults[entry.id].skills.map((s, i) => (
                                                            <Badge key={i} variant="secondary" className="text-xs">{s.name}</Badge>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                            {indexResults[entry.id].accomplishments.length > 0 && (
                                                <div>
                                                    <p className="text-xs text-muted-foreground">Accomplishments</p>
                                                    {indexResults[entry.id].accomplishments.map((a, i) => (
                                                        <p key={i} className="text-xs">{a.title} — {a.description}</p>
                                                    ))}
                                                </div>
                                            )}
                                            {indexResults[entry.id].projects.length > 0 && (
                                                <div>
                                                    <p className="text-xs text-muted-foreground">Projects</p>
                                                    {indexResults[entry.id].projects.map((p, i) => (
                                                        <p key={i} className="text-xs">{p.name} — {p.description}</p>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
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
