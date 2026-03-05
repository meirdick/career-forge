import { Form, Head, router } from '@inertiajs/react';
import { Plus, Search, Sparkles, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import EmptyState from '@/components/empty-state';
import AppLayout from '@/layouts/app-layout';
import SkillController from '@/actions/App/Http/Controllers/ExperienceLibrary/SkillController';
import { index as skillsIndex } from '@/routes/skills';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Experience Library', href: '/experience-library' },
    { title: 'Skills', href: skillsIndex() },
];

const categories = ['technical', 'domain', 'soft', 'tool', 'methodology'] as const;
const proficiencies = ['beginner', 'intermediate', 'advanced', 'expert'] as const;

const categoryLabels: Record<string, string> = {
    technical: 'Technical',
    domain: 'Domain',
    soft: 'Soft Skills',
    tool: 'Tools',
    methodology: 'Methodology',
};

const proficiencyColors: Record<string, string> = {
    beginner: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    intermediate: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    advanced: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
    expert: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
};

type SkillExperience = { id: number; company: string; title: string };
type SkillAccomplishment = { id: number; title: string };
type SkillProject = { id: number; name: string };

type Skill = {
    id: number;
    name: string;
    category: string;
    proficiency: string | null;
    notes: string | null;
    experiences: SkillExperience[];
    accomplishments: SkillAccomplishment[];
    projects: SkillProject[];
};

type Filters = { search: string; category: string };

export default function Skills({ skillsByCategory, filters = { search: '', category: '' } }: { skillsByCategory: Record<string, Skill[]>; filters?: Filters }) {
    const [showForm, setShowForm] = useState(false);
    const [expandedSkill, setExpandedSkill] = useState<number | null>(null);
    const [search, setSearch] = useState(filters.search);
    const [category, setCategory] = useState(filters.category);

    function applyFilters() {
        router.get(skillsIndex(), {
            search: search || undefined,
            category: category || undefined,
        }, { preserveState: true, replace: true });
    }

    function clearFilters() {
        setSearch('');
        setCategory('');
        router.get(skillsIndex(), {}, { preserveState: true, replace: true });
    }

    const hasFilters = search || category;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Skills" />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Skills Inventory" description="Your skills grouped by category" />
                    <Button onClick={() => setShowForm(!showForm)}>
                        <Plus className="mr-2 h-4 w-4" />
                        Add Skill
                    </Button>
                </div>

                <Card>
                    <CardContent className="pt-4">
                        <div className="flex flex-wrap items-end gap-3">
                            <div className="flex-1 min-w-[200px]">
                                <Input
                                    placeholder="Search skills..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                />
                            </div>
                            <div>
                                <Select value={category || '_all'} onValueChange={(v) => setCategory(v === '_all' ? '' : v)}>
                                    <SelectTrigger className="min-w-[150px]">
                                        <SelectValue placeholder="All Categories" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="_all">All Categories</SelectItem>
                                        {categories.map((c) => (
                                            <SelectItem key={c} value={c}>{categoryLabels[c]}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button size="sm" onClick={applyFilters}>
                                <Search className="mr-1 h-4 w-4" /> Filter
                            </Button>
                            {hasFilters && (
                                <Button size="sm" variant="ghost" onClick={clearFilters}>
                                    <X className="mr-1 h-4 w-4" /> Clear
                                </Button>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {showForm && (
                    <Card>
                        <CardContent className="pt-6">
                            <Form {...SkillController.store.form()} options={{ preserveScroll: true, onSuccess: () => setShowForm(false) }} className="grid gap-4 sm:grid-cols-4">
                                {({ processing, errors }) => (
                                    <>
                                        <div className="sm:col-span-2">
                                            <Label htmlFor="name">Skill Name</Label>
                                            <Input id="name" name="name" required placeholder="e.g. React" />
                                            <InputError message={errors.name} />
                                        </div>
                                        <div>
                                            <Label htmlFor="category">Category</Label>
                                            <Select name="category" required>
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Select..." />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {categories.map((c) => (
                                                        <SelectItem key={c} value={c}>{categoryLabels[c]}</SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.category} />
                                        </div>
                                        <div>
                                            <Label htmlFor="proficiency">Proficiency</Label>
                                            <Select name="proficiency">
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Select..." />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {proficiencies.map((p) => (
                                                        <SelectItem key={p} value={p}>{p.charAt(0).toUpperCase() + p.slice(1)}</SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.proficiency} />
                                        </div>
                                        <div className="sm:col-span-4 flex justify-end gap-2">
                                            <Button type="button" variant="ghost" onClick={() => setShowForm(false)}>Cancel</Button>
                                            <Button type="submit" disabled={processing}>Save Skill</Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                {Object.keys(skillsByCategory).length === 0 ? (
                    <EmptyState
                        icon={Sparkles}
                        title="No skills yet"
                        description="Add your first skill to start building your skills inventory."
                        action={<Button onClick={() => setShowForm(true)}><Plus className="mr-2 h-4 w-4" />Add Skill</Button>}
                    />
                ) : (
                    Object.entries(skillsByCategory).map(([category, skills]) => (
                        <Card key={category}>
                            <CardHeader>
                                <CardTitle className="text-base">{categoryLabels[category] ?? category}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex flex-wrap gap-2">
                                    {skills.map((skill) => {
                                        const provenanceCount = skill.experiences.length + skill.accomplishments.length + skill.projects.length;
                                        return (
                                            <div key={skill.id} className="group relative">
                                                <Badge
                                                    variant="outline"
                                                    className={`cursor-pointer pr-7 ${skill.proficiency ? proficiencyColors[skill.proficiency] : ''}`}
                                                    onClick={() => setExpandedSkill(expandedSkill === skill.id ? null : skill.id)}
                                                >
                                                    {skill.name}
                                                    {skill.proficiency && (
                                                        <span className="ml-1 opacity-70">· {skill.proficiency}</span>
                                                    )}
                                                    {provenanceCount > 0 && (
                                                        <span className="ml-1 rounded-full bg-muted px-1.5 text-xs">{provenanceCount}</span>
                                                    )}
                                                </Badge>
                                                <button
                                                    onClick={() => router.delete(SkillController.destroy(skill.id).url, { preserveScroll: true })}
                                                    className="absolute right-1 top-1/2 -translate-y-1/2 opacity-0 transition-opacity group-hover:opacity-100"
                                                    title="Delete skill"
                                                >
                                                    <Trash2 className="h-3 w-3 text-destructive" />
                                                </button>
                                            </div>
                                        );
                                    })}
                                </div>
                                {skills.filter(s => expandedSkill === s.id).map((skill) => (
                                    <div key={skill.id} className="rounded-md border bg-muted/50 p-3 text-sm">
                                        <p className="mb-1 font-medium">{skill.name} — linked to:</p>
                                        {skill.experiences.length > 0 && (
                                            <div className="mb-1">
                                                <span className="text-muted-foreground">Experiences:</span>
                                                {skill.experiences.map((e) => (
                                                    <span key={e.id} className="ml-2">{e.title} at {e.company}</span>
                                                ))}
                                            </div>
                                        )}
                                        {skill.accomplishments.length > 0 && (
                                            <div className="mb-1">
                                                <span className="text-muted-foreground">Accomplishments:</span>
                                                {skill.accomplishments.map((a) => (
                                                    <span key={a.id} className="ml-2">{a.title}</span>
                                                ))}
                                            </div>
                                        )}
                                        {skill.projects.length > 0 && (
                                            <div>
                                                <span className="text-muted-foreground">Projects:</span>
                                                {skill.projects.map((p) => (
                                                    <span key={p.id} className="ml-2">{p.name}</span>
                                                ))}
                                            </div>
                                        )}
                                        {skill.experiences.length === 0 && skill.accomplishments.length === 0 && skill.projects.length === 0 && (
                                            <p className="text-muted-foreground italic">Not yet linked to any experiences</p>
                                        )}
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    ))
                )}
            </div>
        </AppLayout>
    );
}
