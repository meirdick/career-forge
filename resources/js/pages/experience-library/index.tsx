import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { index as experienceLibraryIndex } from '@/routes/experience-library';
import { create as experienceCreate, show as experienceShow } from '@/routes/experiences';
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

export default function ExperienceLibraryIndex({ experiences }: { experiences: Experience[] }) {
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

                {experiences.length === 0 ? (
                    <Card>
                        <CardContent className="py-12 text-center">
                            <p className="text-muted-foreground">No experiences yet. Add your first role to get started.</p>
                        </CardContent>
                    </Card>
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
