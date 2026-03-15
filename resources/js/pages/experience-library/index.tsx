import { Head, Link, router } from '@inertiajs/react';
import { Library, Plus, Search, Upload, X } from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/components/empty-state';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { index as experienceLibraryIndex } from '@/routes/experience-library';
import { create as experienceCreate, show as experienceShow } from '@/routes/experiences';
import { create as resumeUploadCreate } from '@/routes/resume-upload';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Experience Library',
        href: experienceLibraryIndex(),
    },
];

type Skill = {
    id: number;
    name: string;
    category: string;
};

type Accomplishment = {
    id: number;
    title: string;
    impact: string | null;
};

type Project = {
    id: number;
    name: string;
    role: string | null;
};

type Experience = {
    id: number;
    company: string;
    title: string;
    location: string | null;
    started_at: string;
    ended_at: string | null;
    is_current: boolean;
    description: string | null;
    accomplishments: Accomplishment[];
    projects: Project[];
    skills: Skill[];
};

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
}

type FilterSkill = { id: number; name: string };
type FilterTag = { id: number; name: string };
type Filters = { search: string; skill_id: string; tag_id: string; from: string; to: string };

export default function ExperienceLibraryIndex({ experiences, skills = [], tags = [], filters = { search: '', skill_id: '', tag_id: '', from: '', to: '' } }: { experiences: Experience[]; skills?: FilterSkill[]; tags?: FilterTag[]; filters?: Filters }) {
    const [search, setSearch] = useState(filters.search);
    const [skillId, setSkillId] = useState(filters.skill_id);
    const [tagId, setTagId] = useState(filters.tag_id);
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);

    function applyFilters() {
        router.get(experienceLibraryIndex(), {
            search: search || undefined,
            skill_id: skillId || undefined,
            tag_id: tagId || undefined,
            from: from || undefined,
            to: to || undefined,
        }, { preserveState: true, replace: true });
    }

    function clearFilters() {
        setSearch('');
        setSkillId('');
        setTagId('');
        setFrom('');
        setTo('');
        router.get(experienceLibraryIndex(), {}, { preserveState: true, replace: true });
    }

    const hasFilters = search || skillId || tagId || from || to;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Experience Library" />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Experience Library" description="Your professional timeline" />
                    <Button asChild>
                        <Link href={experienceCreate()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Add Experience
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardContent className="pt-4">
                        <div className="flex flex-wrap items-end gap-3">
                            <div className="flex-1 min-w-[200px]">
                                <Input
                                    placeholder="Search experiences..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                />
                            </div>
                            <div>
                                <Select value={skillId || '_all'} onValueChange={(v) => setSkillId(v === '_all' ? '' : v)}>
                                    <SelectTrigger className="min-w-[150px]">
                                        <SelectValue placeholder="All Skills" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="_all">All Skills</SelectItem>
                                        {skills.map((s) => (
                                            <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Select value={tagId || '_all'} onValueChange={(v) => setTagId(v === '_all' ? '' : v)}>
                                    <SelectTrigger className="min-w-[150px]">
                                        <SelectValue placeholder="All Tags" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="_all">All Tags</SelectItem>
                                        {tags.map((t) => (
                                            <SelectItem key={t.id} value={String(t.id)}>#{t.name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} placeholder="From" className="w-[140px]" />
                            </div>
                            <div>
                                <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} placeholder="To" className="w-[140px]" />
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

                {experiences.length === 0 ? (
                    <EmptyState
                        icon={Library}
                        title="No experiences yet"
                        description="Add your first role to start building your professional timeline."
                        action={
                            <div className="flex flex-col items-center gap-2">
                                <Button asChild>
                                    <Link href={experienceCreate()}>
                                        <Plus className="mr-2 h-4 w-4" />Add Experience
                                    </Link>
                                </Button>
                                <Link href={resumeUploadCreate()} className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition-colors">
                                    <Upload className="size-3" /> Or upload a resume to auto-import
                                </Link>
                            </div>
                        }
                    />
                ) : (
                    <div className="relative space-y-6 border-l-2 border-muted pl-6">
                        {experiences.map((experience) => (
                            <div key={experience.id} className="relative">
                                <div className="bg-primary absolute -left-[31px] top-1 h-3 w-3 rounded-full" />
                                <Card
                                    className="cursor-pointer transition-shadow hover:shadow-md"
                                    onClick={() => router.visit(experienceShow(experience.id).url)}
                                >
                                    <CardHeader className="pb-3">
                                        <div className="flex items-start justify-between">
                                            <div>
                                                <CardTitle className="text-lg">{experience.title}</CardTitle>
                                                <p className="text-muted-foreground text-sm">
                                                    {experience.company}
                                                    {experience.location && ` · ${experience.location}`}
                                                </p>
                                            </div>
                                            <div className="text-right text-sm whitespace-nowrap">
                                                <span className="text-muted-foreground">
                                                    {formatDate(experience.started_at)} —{' '}
                                                    {experience.is_current ? (
                                                        <span className="text-primary font-medium">Present</span>
                                                    ) : (
                                                        experience.ended_at && formatDate(experience.ended_at)
                                                    )}
                                                </span>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="space-y-3 pt-0">
                                        {experience.description && (
                                            <p className="text-muted-foreground line-clamp-2 text-sm">{experience.description}</p>
                                        )}

                                        {experience.accomplishments.length > 0 && (
                                            <div>
                                                <p className="mb-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                                    Accomplishments ({experience.accomplishments.length})
                                                </p>
                                                <ul className="list-inside list-disc text-sm">
                                                    {experience.accomplishments.slice(0, 2).map((a) => (
                                                        <li key={a.id} className="truncate">{a.title}</li>
                                                    ))}
                                                    {experience.accomplishments.length > 2 && (
                                                        <li className="text-muted-foreground">
                                                            +{experience.accomplishments.length - 2} more
                                                        </li>
                                                    )}
                                                </ul>
                                            </div>
                                        )}

                                        {experience.skills.length > 0 && (
                                            <div className="flex flex-wrap gap-1">
                                                {experience.skills.map((skill) => (
                                                    <Badge key={skill.id} variant="secondary" className="text-xs">
                                                        {skill.name}
                                                    </Badge>
                                                ))}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
