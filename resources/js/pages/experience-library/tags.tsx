import { Form, Head, router } from '@inertiajs/react';
import { Edit, Plus, Tag as TagIcon, Trash2 } from 'lucide-react';
import { useState } from 'react';
import TagController from '@/actions/App/Http/Controllers/ExperienceLibrary/TagController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index as tagsIndex } from '@/routes/tags';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Experience Library', href: '/experience-library' },
    { title: 'Tags', href: tagsIndex() },
];

type TagItem = {
    id: number;
    name: string;
    experiences_count: number;
    accomplishments_count: number;
    projects_count: number;
};

export default function Tags({ tags }: { tags: TagItem[] }) {
    const [showForm, setShowForm] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [editName, setEditName] = useState('');

    function startEdit(tag: TagItem) {
        setEditingId(tag.id);
        setEditName(tag.name);
    }

    function saveEdit(tag: TagItem) {
        router.put(TagController.update(tag.id).url, { name: editName }, { preserveScroll: true, onSuccess: () => setEditingId(null) });
    }

    const totalUsages = (tag: TagItem) => tag.experiences_count + tag.accomplishments_count + tag.projects_count;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tags" />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Tags" description="Organize your experiences, accomplishments, and projects with tags" />
                    <Button size="sm" onClick={() => setShowForm(!showForm)}>
                        <Plus className="mr-1 h-4 w-4" /> New Tag
                    </Button>
                </div>

                {showForm && (
                    <Card>
                        <CardContent className="pt-6">
                            <Form {...TagController.store.form()} options={{ preserveScroll: true, onSuccess: () => setShowForm(false) }} className="flex items-end gap-3">
                                {({ processing, errors }) => (
                                    <>
                                        <div className="flex-1">
                                            <Label htmlFor="tag-name">Tag Name</Label>
                                            <Input id="tag-name" name="name" required placeholder="e.g., leadership, frontend, cloud" />
                                            <InputError message={errors.name} />
                                        </div>
                                        <div className="flex gap-2">
                                            <Button type="button" variant="ghost" onClick={() => setShowForm(false)}>Cancel</Button>
                                            <Button type="submit" disabled={processing}>Create</Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                {tags.length === 0 && !showForm ? (
                    <Card>
                        <CardContent className="py-12 text-center">
                            <TagIcon className="mx-auto mb-3 h-10 w-10 text-muted-foreground" />
                            <p className="text-muted-foreground">No tags yet. Create your first tag to start organizing.</p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-2">
                        {tags.map((tag) => (
                            <Card key={tag.id}>
                                <CardContent className="flex items-center justify-between py-3">
                                    {editingId === tag.id ? (
                                        <div className="flex flex-1 items-center gap-2">
                                            <Input
                                                value={editName}
                                                onChange={(e) => setEditName(e.target.value)}
                                                className="max-w-xs"
                                                onKeyDown={(e) => e.key === 'Enter' && saveEdit(tag)}
                                            />
                                            <Button size="sm" onClick={() => saveEdit(tag)}>Save</Button>
                                            <Button size="sm" variant="ghost" onClick={() => setEditingId(null)}>Cancel</Button>
                                        </div>
                                    ) : (
                                        <div className="flex items-center gap-3">
                                            <Badge variant="outline" className="text-sm">#{tag.name}</Badge>
                                            <span className="text-xs text-muted-foreground">
                                                {totalUsages(tag)} {totalUsages(tag) === 1 ? 'item' : 'items'}
                                                {tag.experiences_count > 0 && ` (${tag.experiences_count} exp)`}
                                                {tag.accomplishments_count > 0 && ` (${tag.accomplishments_count} acc)`}
                                                {tag.projects_count > 0 && ` (${tag.projects_count} proj)`}
                                            </span>
                                        </div>
                                    )}

                                    {editingId !== tag.id && (
                                        <div className="flex gap-1">
                                            <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => startEdit(tag)}>
                                                <Edit className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8"
                                                onClick={() => {
                                                    if (confirm('Delete this tag? It will be removed from all items.')) {
                                                        router.delete(TagController.destroy(tag.id).url, { preserveScroll: true });
                                                    }
                                                }}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
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
