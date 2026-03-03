import { Form, Head, router } from '@inertiajs/react';
import { ExternalLink, LinkIcon, Plus, Trash2 } from 'lucide-react';
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

export default function Evidence({ entries }: { entries: EvidenceEntry[] }) {
    const [showForm, setShowForm] = useState(false);

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
                                {entry.description && (
                                    <CardContent className="pt-0">
                                        <p className="text-sm text-muted-foreground">{entry.description}</p>
                                    </CardContent>
                                )}
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
