import { Form, Head, router } from '@inertiajs/react';
import { GraduationCap, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import EducationEntryController from '@/actions/App/Http/Controllers/ExperienceLibrary/EducationEntryController';
import EmptyState from '@/components/empty-state';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { index as educationIndex } from '@/routes/education';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Experience Library', href: '/experience-library' },
    { title: 'Education', href: educationIndex() },
];

const educationTypes = [
    'degree', 'certification', 'license', 'course', 'workshop', 'publication', 'patent', 'speaking_engagement',
] as const;

const typeLabels: Record<string, string> = {
    degree: 'Degree',
    certification: 'Certification',
    license: 'License',
    course: 'Course',
    workshop: 'Workshop',
    publication: 'Publication',
    patent: 'Patent',
    speaking_engagement: 'Speaking Engagement',
};

type EducationEntry = {
    id: number;
    type: string;
    institution: string;
    title: string;
    field: string | null;
    url: string | null;
    description: string | null;
    started_at: string | null;
    completed_at: string | null;
};

export default function Education({ entries }: { entries: EducationEntry[] }) {
    const [showForm, setShowForm] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Education" />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Education & Credentials" description="Your degrees, certifications, and other credentials" />
                    <Button onClick={() => setShowForm(!showForm)}>
                        <Plus className="mr-2 h-4 w-4" />
                        Add Entry
                    </Button>
                </div>

                {showForm && (
                    <Card>
                        <CardContent className="pt-6">
                            <Form {...EducationEntryController.store.form()} options={{ preserveScroll: true, onSuccess: () => setShowForm(false) }} className="grid gap-4 sm:grid-cols-2">
                                {({ processing, errors }) => (
                                    <>
                                        <div>
                                            <Label htmlFor="type">Type</Label>
                                            <Select name="type" required>
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Select..." />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {educationTypes.map((t) => (
                                                        <SelectItem key={t} value={t}>{typeLabels[t]}</SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.type} />
                                        </div>
                                        <div>
                                            <Label htmlFor="institution">Institution</Label>
                                            <Input id="institution" name="institution" required placeholder="e.g. MIT" />
                                            <InputError message={errors.institution} />
                                        </div>
                                        <div>
                                            <Label htmlFor="title">Title</Label>
                                            <Input id="title" name="title" required placeholder="e.g. Bachelor of Science" />
                                            <InputError message={errors.title} />
                                        </div>
                                        <div>
                                            <Label htmlFor="field">Field</Label>
                                            <Input id="field" name="field" placeholder="e.g. Computer Science" />
                                            <InputError message={errors.field} />
                                        </div>
                                        <div>
                                            <Label htmlFor="started_at">Start Date</Label>
                                            <Input id="started_at" name="started_at" type="date" />
                                        </div>
                                        <div>
                                            <Label htmlFor="completed_at">Completion Date</Label>
                                            <Input id="completed_at" name="completed_at" type="date" />
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
                        icon={GraduationCap}
                        title="No education entries yet"
                        description="Add your degrees, certifications, and other credentials."
                        action={<Button onClick={() => setShowForm(true)}><Plus className="mr-2 h-4 w-4" />Add Entry</Button>}
                    />
                ) : (
                    <div className="space-y-3">
                        {entries.map((entry) =>
                            editingId === entry.id ? (
                                <Card key={entry.id}>
                                    <CardContent className="pt-6">
                                        <Form
                                            {...EducationEntryController.update.form(entry.id)}
                                            options={{ preserveScroll: true, onSuccess: () => setEditingId(null) }}
                                            className="grid gap-4 sm:grid-cols-2"
                                        >
                                            {({ processing, errors }) => (
                                                <>
                                                    <div>
                                                        <Label htmlFor={`type-${entry.id}`}>Type</Label>
                                                        <Select name="type" defaultValue={entry.type} required>
                                                            <SelectTrigger className="w-full">
                                                                <SelectValue placeholder="Select..." />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {educationTypes.map((t) => (
                                                                    <SelectItem key={t} value={t}>{typeLabels[t]}</SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                        <InputError message={errors.type} />
                                                    </div>
                                                    <div>
                                                        <Label htmlFor={`institution-${entry.id}`}>Institution</Label>
                                                        <Input id={`institution-${entry.id}`} name="institution" required defaultValue={entry.institution} />
                                                        <InputError message={errors.institution} />
                                                    </div>
                                                    <div>
                                                        <Label htmlFor={`title-${entry.id}`}>Title</Label>
                                                        <Input id={`title-${entry.id}`} name="title" required defaultValue={entry.title} />
                                                        <InputError message={errors.title} />
                                                    </div>
                                                    <div>
                                                        <Label htmlFor={`field-${entry.id}`}>Field</Label>
                                                        <Input id={`field-${entry.id}`} name="field" defaultValue={entry.field ?? ''} />
                                                        <InputError message={errors.field} />
                                                    </div>
                                                    <div>
                                                        <Label htmlFor={`started_at-${entry.id}`}>Start Date</Label>
                                                        <Input id={`started_at-${entry.id}`} name="started_at" type="date" defaultValue={entry.started_at ?? ''} />
                                                    </div>
                                                    <div>
                                                        <Label htmlFor={`completed_at-${entry.id}`}>Completion Date</Label>
                                                        <Input id={`completed_at-${entry.id}`} name="completed_at" type="date" defaultValue={entry.completed_at ?? ''} />
                                                    </div>
                                                    <div className="flex justify-between sm:col-span-2">
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            className="text-destructive hover:text-destructive"
                                                            onClick={() => router.delete(EducationEntryController.destroy(entry.id).url, { preserveScroll: true })}
                                                        >
                                                            <Trash2 className="mr-2 h-4 w-4" />
                                                            Delete
                                                        </Button>
                                                        <div className="flex gap-2">
                                                            <Button type="button" variant="ghost" onClick={() => setEditingId(null)}>
                                                                Cancel
                                                            </Button>
                                                            <Button type="submit" disabled={processing}>Save</Button>
                                                        </div>
                                                    </div>
                                                </>
                                            )}
                                        </Form>
                                    </CardContent>
                                </Card>
                            ) : (
                                <Card key={entry.id}>
                                    <CardHeader className="flex-row items-center justify-between pb-2">
                                        <div>
                                            <CardTitle className="text-base">{entry.title}</CardTitle>
                                            <p className="text-muted-foreground text-sm">{entry.institution}</p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge variant="outline">{typeLabels[entry.type] ?? entry.type}</Badge>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8"
                                                onClick={() => setEditingId(entry.id)}
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </CardHeader>
                                    {(entry.field || entry.completed_at) && (
                                        <CardContent className="pt-0">
                                            <div className="flex gap-3 text-sm text-muted-foreground">
                                                {entry.field && <span>{entry.field}</span>}
                                                {entry.completed_at && (
                                                    <span>Completed {new Date(entry.completed_at).toLocaleDateString('en-US', { month: 'short', year: 'numeric' })}</span>
                                                )}
                                            </div>
                                        </CardContent>
                                    )}
                                </Card>
                            ),
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
