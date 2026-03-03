import { Form, Head, Link, router } from '@inertiajs/react';
import { Edit, Plus, Tag, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import RichTextEditor from '@/components/rich-text-editor';
import AppLayout from '@/layouts/app-layout';
import AccomplishmentController from '@/actions/App/Http/Controllers/ExperienceLibrary/AccomplishmentController';
import ExperienceController from '@/actions/App/Http/Controllers/ExperienceLibrary/ExperienceController';
import ProjectController from '@/actions/App/Http/Controllers/ExperienceLibrary/ProjectController';
import TagController from '@/actions/App/Http/Controllers/ExperienceLibrary/TagController';
import { edit as experienceEdit, show as experienceShow } from '@/routes/experiences';
import type { BreadcrumbItem } from '@/types';

type Skill = { id: number; name: string; category: string };
type Tag = { id: number; name: string };
type Accomplishment = { id: number; title: string; description: string; impact: string | null; skills: Skill[] };
type Project = { id: number; name: string; description: string; role: string | null; url: string | null; skills: Skill[] };

type Experience = {
    id: number;
    company: string;
    title: string;
    location: string | null;
    started_at: string;
    ended_at: string | null;
    is_current: boolean;
    description: string | null;
    reporting_to: string | null;
    team_size: number | null;
    reason_for_leaving: string | null;
    accomplishments: Accomplishment[];
    projects: Project[];
    skills: Skill[];
    tags: Tag[];
};

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
}

type AvailableTag = { id: number; name: string };

export default function ShowExperience({ experience, tags: availableTags }: { experience: Experience; tags: AvailableTag[] }) {
    const [showAccomplishmentForm, setShowAccomplishmentForm] = useState(false);
    const [showProjectForm, setShowProjectForm] = useState(false);
    const [showTagPicker, setShowTagPicker] = useState(false);

    function toggleTag(tagId: number, taggableId: number, taggableType: 'experience' | 'accomplishment' | 'project') {
        router.post(TagController.toggle().url, { tag_id: tagId, taggable_id: taggableId, taggable_type: taggableType }, { preserveScroll: true });
    }

    const experienceTagIds = experience.tags.map((t) => t.id);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Experience Library', href: '/experience-library' },
        { title: experience.company, href: experienceShow(experience.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${experience.title} at ${experience.company}`} />

            <div className="mx-auto max-w-3xl space-y-6 p-4">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">{experience.title}</h1>
                        <p className="text-muted-foreground text-lg">
                            {experience.company}
                            {experience.location && ` · ${experience.location}`}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {formatDate(experience.started_at)} — {experience.is_current ? 'Present' : experience.ended_at && formatDate(experience.ended_at)}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={experienceEdit(experience.id)}>
                                <Edit className="mr-1 h-4 w-4" /> Edit
                            </Link>
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            onClick={() => {
                                if (confirm('Delete this experience?')) {
                                    router.delete(ExperienceController.destroy(experience.id).url);
                                }
                            }}
                        >
                            <Trash2 className="mr-1 h-4 w-4" /> Delete
                        </Button>
                    </div>
                </div>

                {experience.description && (
                    <div className="prose prose-sm text-muted-foreground max-w-none [&_ul]:list-disc [&_ul]:pl-4 [&_ol]:list-decimal [&_ol]:pl-4" dangerouslySetInnerHTML={{ __html: experience.description }} />
                )}

                <div className="flex flex-wrap gap-4 text-sm text-muted-foreground">
                    {experience.reporting_to && <span>Reports to: {experience.reporting_to}</span>}
                    {experience.team_size && <span>Team size: {experience.team_size}</span>}
                    {experience.reason_for_leaving && <span>Left because: {experience.reason_for_leaving}</span>}
                </div>

                {experience.skills.length > 0 && (
                    <div className="flex flex-wrap gap-1">
                        {experience.skills.map((skill) => (
                            <Badge key={skill.id} variant="secondary">{skill.name}</Badge>
                        ))}
                    </div>
                )}

                <div className="space-y-2">
                    <div className="flex flex-wrap items-center gap-1">
                        {experience.tags.map((tag) => (
                            <Badge key={tag.id} variant="outline" className="cursor-pointer" onClick={() => toggleTag(tag.id, experience.id, 'experience')}>
                                #{tag.name} &times;
                            </Badge>
                        ))}
                        <Button variant="ghost" size="sm" className="h-6 px-2 text-xs" onClick={() => setShowTagPicker(!showTagPicker)}>
                            <Tag className="mr-1 h-3 w-3" /> {showTagPicker ? 'Hide' : 'Tag'}
                        </Button>
                    </div>
                    {showTagPicker && availableTags.length > 0 && (
                        <div className="flex flex-wrap gap-1">
                            {availableTags.filter((t) => !experienceTagIds.includes(t.id)).map((tag) => (
                                <Badge
                                    key={tag.id}
                                    variant="secondary"
                                    className="cursor-pointer opacity-60 hover:opacity-100"
                                    onClick={() => toggleTag(tag.id, experience.id, 'experience')}
                                >
                                    + #{tag.name}
                                </Badge>
                            ))}
                            {availableTags.filter((t) => !experienceTagIds.includes(t.id)).length === 0 && (
                                <span className="text-xs text-muted-foreground">All tags applied</span>
                            )}
                        </div>
                    )}
                </div>

                <Separator />

                {/* Accomplishments */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Accomplishments</h2>
                        <Button size="sm" variant="outline" onClick={() => setShowAccomplishmentForm(!showAccomplishmentForm)}>
                            <Plus className="mr-1 h-4 w-4" /> Add
                        </Button>
                    </div>

                    {showAccomplishmentForm && (
                        <Card>
                            <CardContent className="pt-6">
                                <Form {...AccomplishmentController.store.form()} options={{ preserveScroll: true, onSuccess: () => setShowAccomplishmentForm(false) }} className="space-y-3">
                                    {({ processing, errors }) => (
                                        <>
                                            <input type="hidden" name="experience_id" value={experience.id} />
                                            <div>
                                                <Label htmlFor="acc-title">Title</Label>
                                                <Input id="acc-title" name="title" required placeholder="Led migration to microservices" />
                                                <InputError message={errors.title} />
                                            </div>
                                            <div>
                                                <Label htmlFor="acc-description">Description</Label>
                                                <RichTextEditor name="description" required placeholder="What did you accomplish?" />
                                                <InputError message={errors.description} />
                                            </div>
                                            <div>
                                                <Label htmlFor="acc-impact">Impact</Label>
                                                <Input id="acc-impact" name="impact" placeholder="Reduced deploy time by 70%" />
                                            </div>
                                            <input type="hidden" name="sort_order" value="0" />
                                            <div className="flex justify-end gap-2">
                                                <Button type="button" variant="ghost" onClick={() => setShowAccomplishmentForm(false)}>Cancel</Button>
                                                <Button type="submit" disabled={processing}>Save</Button>
                                            </div>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    )}

                    {experience.accomplishments.length === 0 && !showAccomplishmentForm ? (
                        <p className="text-sm text-muted-foreground">No accomplishments yet.</p>
                    ) : (
                        experience.accomplishments.map((acc) => (
                            <Card key={acc.id}>
                                <CardHeader className="flex-row items-start justify-between pb-2">
                                    <CardTitle className="text-base">{acc.title}</CardTitle>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-8 w-8"
                                        onClick={() => router.delete(AccomplishmentController.destroy(acc.id).url, { preserveScroll: true })}
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </CardHeader>
                                <CardContent className="space-y-2 pt-0">
                                    <div className="prose prose-sm text-muted-foreground max-w-none [&_ul]:list-disc [&_ul]:pl-4 [&_ol]:list-decimal [&_ol]:pl-4" dangerouslySetInnerHTML={{ __html: acc.description }} />
                                    {acc.impact && <p className="text-sm font-medium">{acc.impact}</p>}
                                    {acc.skills.length > 0 && (
                                        <div className="flex flex-wrap gap-1">
                                            {acc.skills.map((s) => <Badge key={s.id} variant="secondary" className="text-xs">{s.name}</Badge>)}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        ))
                    )}
                </div>

                <Separator />

                {/* Projects */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Projects</h2>
                        <Button size="sm" variant="outline" onClick={() => setShowProjectForm(!showProjectForm)}>
                            <Plus className="mr-1 h-4 w-4" /> Add
                        </Button>
                    </div>

                    {showProjectForm && (
                        <Card>
                            <CardContent className="pt-6">
                                <Form {...ProjectController.store.form()} options={{ preserveScroll: true, onSuccess: () => setShowProjectForm(false) }} className="space-y-3">
                                    {({ processing, errors }) => (
                                        <>
                                            <input type="hidden" name="experience_id" value={experience.id} />
                                            <div className="grid gap-4 sm:grid-cols-2">
                                                <div>
                                                    <Label htmlFor="proj-name">Name</Label>
                                                    <Input id="proj-name" name="name" required placeholder="Customer Portal Redesign" />
                                                    <InputError message={errors.name} />
                                                </div>
                                                <div>
                                                    <Label htmlFor="proj-role">Your Role</Label>
                                                    <Input id="proj-role" name="role" placeholder="Tech Lead" />
                                                </div>
                                            </div>
                                            <div>
                                                <Label htmlFor="proj-description">Description</Label>
                                                <RichTextEditor name="description" required placeholder="What was the project about?" />
                                                <InputError message={errors.description} />
                                            </div>
                                            <div>
                                                <Label htmlFor="proj-outcome">Outcome</Label>
                                                <Input id="proj-outcome" name="outcome" placeholder="Increased user engagement by 40%" />
                                            </div>
                                            <input type="hidden" name="sort_order" value="0" />
                                            <div className="flex justify-end gap-2">
                                                <Button type="button" variant="ghost" onClick={() => setShowProjectForm(false)}>Cancel</Button>
                                                <Button type="submit" disabled={processing}>Save</Button>
                                            </div>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    )}

                    {experience.projects.length === 0 && !showProjectForm ? (
                        <p className="text-sm text-muted-foreground">No projects yet.</p>
                    ) : (
                        experience.projects.map((proj) => (
                            <Card key={proj.id}>
                                <CardHeader className="flex-row items-start justify-between pb-2">
                                    <div>
                                        <CardTitle className="text-base">{proj.name}</CardTitle>
                                        {proj.role && <p className="text-sm text-muted-foreground">{proj.role}</p>}
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-8 w-8"
                                        onClick={() => router.delete(ProjectController.destroy(proj.id).url, { preserveScroll: true })}
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </CardHeader>
                                <CardContent className="space-y-2 pt-0">
                                    <div className="prose prose-sm text-muted-foreground max-w-none [&_ul]:list-disc [&_ul]:pl-4 [&_ol]:list-decimal [&_ol]:pl-4" dangerouslySetInnerHTML={{ __html: proj.description }} />
                                    {proj.skills.length > 0 && (
                                        <div className="flex flex-wrap gap-1">
                                            {proj.skills.map((s) => <Badge key={s.id} variant="secondary" className="text-xs">{s.name}</Badge>)}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        ))
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
