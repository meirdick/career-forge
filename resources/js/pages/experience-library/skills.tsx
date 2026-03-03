import { Form, Head, router } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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

type Skill = {
    id: number;
    name: string;
    category: string;
    proficiency: string | null;
    notes: string | null;
};

export default function Skills({ skillsByCategory }: { skillsByCategory: Record<string, Skill[]> }) {
    const [showForm, setShowForm] = useState(false);

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
                                            <select name="category" id="category" required className="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm">
                                                <option value="">Select...</option>
                                                {categories.map((c) => (
                                                    <option key={c} value={c}>{categoryLabels[c]}</option>
                                                ))}
                                            </select>
                                            <InputError message={errors.category} />
                                        </div>
                                        <div>
                                            <Label htmlFor="proficiency">Proficiency</Label>
                                            <select name="proficiency" id="proficiency" className="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm">
                                                <option value="">Select...</option>
                                                {proficiencies.map((p) => (
                                                    <option key={p} value={p}>{p.charAt(0).toUpperCase() + p.slice(1)}</option>
                                                ))}
                                            </select>
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
                    <Card>
                        <CardContent className="py-12 text-center">
                            <p className="text-muted-foreground">No skills yet. Add your first skill above.</p>
                        </CardContent>
                    </Card>
                ) : (
                    Object.entries(skillsByCategory).map(([category, skills]) => (
                        <Card key={category}>
                            <CardHeader>
                                <CardTitle className="text-base">{categoryLabels[category] ?? category}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex flex-wrap gap-2">
                                    {skills.map((skill) => (
                                        <div key={skill.id} className="group relative">
                                            <Badge
                                                variant="outline"
                                                className={`pr-7 ${skill.proficiency ? proficiencyColors[skill.proficiency] : ''}`}
                                            >
                                                {skill.name}
                                                {skill.proficiency && (
                                                    <span className="ml-1 opacity-70">· {skill.proficiency}</span>
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
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    ))
                )}
            </div>
        </AppLayout>
    );
}
