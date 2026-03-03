import { Head, router } from '@inertiajs/react';
import { Check, Loader2, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import ResumeUploadController from '@/actions/App/Http/Controllers/ExperienceLibrary/ResumeUploadController';
import type { BreadcrumbItem } from '@/types';

type ParsedExperience = {
    company: string;
    title: string;
    location?: string;
    started_at: string;
    ended_at?: string;
    is_current: boolean;
    description?: string;
};

type ParsedAccomplishment = {
    title: string;
    description: string;
    impact?: string;
    experience_index?: number;
};

type ParsedSkill = { name: string; category: string };
type ParsedEducation = { type: string; institution: string; title: string; field?: string; completed_at?: string };
type ParsedProject = { name: string; description: string; role?: string; outcome?: string; experience_index?: number };

type ParseResult = {
    status: 'processing' | 'completed' | 'failed';
    data?: {
        experiences: ParsedExperience[];
        accomplishments: ParsedAccomplishment[];
        skills: ParsedSkill[];
        education: ParsedEducation[];
        projects: ParsedProject[];
    };
    error?: string;
};

type Document = { id: number; filename: string };

export default function ReviewImport({ document, parseResult }: { document: Document; parseResult: ParseResult }) {
    const [selected, setSelected] = useState({
        experiences: new Set<number>(),
        accomplishments: new Set<number>(),
        skills: new Set<number>(),
        education: new Set<number>(),
        projects: new Set<number>(),
    });

    useEffect(() => {
        if (parseResult.status === 'completed' && parseResult.data) {
            setSelected({
                experiences: new Set(parseResult.data.experiences.map((_, i) => i)),
                accomplishments: new Set(parseResult.data.accomplishments.map((_, i) => i)),
                skills: new Set(parseResult.data.skills.map((_, i) => i)),
                education: new Set(parseResult.data.education.map((_, i) => i)),
                projects: new Set(parseResult.data.projects.map((_, i) => i)),
            });
        }
    }, [parseResult]);

    useEffect(() => {
        if (parseResult.status === 'processing') {
            const interval = setInterval(() => {
                router.reload({ only: ['parseResult'] });
            }, 3000);
            return () => clearInterval(interval);
        }
    }, [parseResult.status]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Experience Library', href: '/experience-library' },
        { title: 'Review Import', href: `/resume-upload/${document.id}/review` },
    ];

    function toggle(section: keyof typeof selected, index: number) {
        setSelected((prev) => {
            const next = new Set(prev[section]);
            if (next.has(index)) {
                next.delete(index);
            } else {
                next.add(index);
            }
            return { ...prev, [section]: next };
        });
    }

    function handleCommit() {
        if (!parseResult.data) return;

        const data = {
            experiences: parseResult.data.experiences.filter((_, i) => selected.experiences.has(i)),
            accomplishments: parseResult.data.accomplishments.filter((_, i) => selected.accomplishments.has(i)),
            skills: parseResult.data.skills.filter((_, i) => selected.skills.has(i)),
            education: parseResult.data.education.filter((_, i) => selected.education.has(i)),
            projects: parseResult.data.projects.filter((_, i) => selected.projects.has(i)),
        };

        router.post(ResumeUploadController.commit(document.id).url, data);
    }

    if (parseResult.status === 'processing') {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Parsing Resume..." />
                <div className="mx-auto max-w-2xl space-y-6 p-4">
                    <Heading title="Parsing Resume" description={document.filename} />
                    <Card>
                        <CardContent className="flex flex-col items-center gap-4 py-12">
                            <Loader2 className="text-primary h-10 w-10 animate-spin" />
                            <p className="text-muted-foreground">AI is analyzing your resume. This usually takes 15-30 seconds...</p>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    if (parseResult.status === 'failed') {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Parse Failed" />
                <div className="mx-auto max-w-2xl space-y-6 p-4">
                    <Heading title="Parse Failed" description={document.filename} />
                    <Card>
                        <CardContent className="py-8 text-center">
                            <X className="mx-auto mb-3 h-10 w-10 text-red-500" />
                            <p className="text-muted-foreground">{parseResult.error ?? 'An error occurred while parsing your resume.'}</p>
                            <Button variant="outline" className="mt-4" onClick={() => router.visit('/resume-upload')}>
                                Try Again
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    const data = parseResult.data!;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Review Import" />

            <div className="mx-auto max-w-3xl space-y-6 p-4">
                <div className="flex items-start justify-between">
                    <Heading title="Review Import" description={`Parsed from ${document.filename}. Deselect any items you don't want to import.`} />
                    <Button onClick={handleCommit}>
                        <Check className="mr-1 h-4 w-4" /> Import Selected
                    </Button>
                </div>

                {data.experiences.length > 0 && (
                    <section className="space-y-3">
                        <h2 className="text-lg font-semibold">Experiences ({selected.experiences.size}/{data.experiences.length})</h2>
                        {data.experiences.map((exp, i) => (
                            <Card key={i} className={!selected.experiences.has(i) ? 'opacity-40' : ''}>
                                <CardHeader className="cursor-pointer pb-2" onClick={() => toggle('experiences', i)}>
                                    <CardTitle className="text-base">{exp.title} at {exp.company}</CardTitle>
                                    <p className="text-muted-foreground text-sm">
                                        {exp.started_at} — {exp.is_current ? 'Present' : exp.ended_at ?? 'N/A'}
                                        {exp.location && ` · ${exp.location}`}
                                    </p>
                                </CardHeader>
                                {exp.description && (
                                    <CardContent className="pt-0">
                                        <p className="text-muted-foreground text-sm">{exp.description}</p>
                                    </CardContent>
                                )}
                            </Card>
                        ))}
                    </section>
                )}

                {data.skills.length > 0 && (
                    <>
                        <Separator />
                        <section className="space-y-3">
                            <h2 className="text-lg font-semibold">Skills ({selected.skills.size}/{data.skills.length})</h2>
                            <div className="flex flex-wrap gap-2">
                                {data.skills.map((skill, i) => (
                                    <Badge
                                        key={i}
                                        variant={selected.skills.has(i) ? 'secondary' : 'outline'}
                                        className="cursor-pointer"
                                        onClick={() => toggle('skills', i)}
                                    >
                                        {skill.name}
                                    </Badge>
                                ))}
                            </div>
                        </section>
                    </>
                )}

                {data.accomplishments.length > 0 && (
                    <>
                        <Separator />
                        <section className="space-y-3">
                            <h2 className="text-lg font-semibold">Accomplishments ({selected.accomplishments.size}/{data.accomplishments.length})</h2>
                            {data.accomplishments.map((acc, i) => (
                                <Card key={i} className={`cursor-pointer ${!selected.accomplishments.has(i) ? 'opacity-40' : ''}`} onClick={() => toggle('accomplishments', i)}>
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-base">{acc.title}</CardTitle>
                                    </CardHeader>
                                    <CardContent className="pt-0">
                                        <p className="text-muted-foreground text-sm">{acc.description}</p>
                                        {acc.impact && <p className="mt-1 text-sm font-medium">{acc.impact}</p>}
                                    </CardContent>
                                </Card>
                            ))}
                        </section>
                    </>
                )}

                {data.education.length > 0 && (
                    <>
                        <Separator />
                        <section className="space-y-3">
                            <h2 className="text-lg font-semibold">Education ({selected.education.size}/{data.education.length})</h2>
                            {data.education.map((edu, i) => (
                                <Card key={i} className={`cursor-pointer ${!selected.education.has(i) ? 'opacity-40' : ''}`} onClick={() => toggle('education', i)}>
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-base">{edu.title}</CardTitle>
                                        <p className="text-muted-foreground text-sm">{edu.institution}{edu.field && ` · ${edu.field}`}</p>
                                    </CardHeader>
                                </Card>
                            ))}
                        </section>
                    </>
                )}

                {data.projects.length > 0 && (
                    <>
                        <Separator />
                        <section className="space-y-3">
                            <h2 className="text-lg font-semibold">Projects ({selected.projects.size}/{data.projects.length})</h2>
                            {data.projects.map((proj, i) => (
                                <Card key={i} className={`cursor-pointer ${!selected.projects.has(i) ? 'opacity-40' : ''}`} onClick={() => toggle('projects', i)}>
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-base">{proj.name}</CardTitle>
                                        {proj.role && <p className="text-muted-foreground text-sm">{proj.role}</p>}
                                    </CardHeader>
                                    <CardContent className="pt-0">
                                        <p className="text-muted-foreground text-sm">{proj.description}</p>
                                    </CardContent>
                                </Card>
                            ))}
                        </section>
                    </>
                )}

                <div className="flex justify-end pb-6">
                    <Button onClick={handleCommit}>
                        <Check className="mr-1 h-4 w-4" /> Import Selected
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
