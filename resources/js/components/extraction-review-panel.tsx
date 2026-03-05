import { router } from '@inertiajs/react';
import { Check, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { commit } from '@/routes/career-chat';

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

export type ExtractionData = {
    experiences: ParsedExperience[];
    accomplishments: ParsedAccomplishment[];
    skills: ParsedSkill[];
    education: ParsedEducation[];
    projects: ParsedProject[];
};

interface ExtractionReviewPanelProps {
    data: ExtractionData;
    chatSessionId: number;
    open: boolean;
    onClose: () => void;
}

export default function ExtractionReviewPanel({ data, chatSessionId, open, onClose }: ExtractionReviewPanelProps) {
    const [selected, setSelected] = useState({
        experiences: new Set<number>(),
        accomplishments: new Set<number>(),
        skills: new Set<number>(),
        education: new Set<number>(),
        projects: new Set<number>(),
    });
    const [importing, setImporting] = useState(false);

    useEffect(() => {
        setSelected({
            experiences: new Set(data.experiences.map((_, i) => i)),
            accomplishments: new Set(data.accomplishments.map((_, i) => i)),
            skills: new Set(data.skills.map((_, i) => i)),
            education: new Set(data.education.map((_, i) => i)),
            projects: new Set(data.projects.map((_, i) => i)),
        });
    }, [data]);

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

    function handleImport() {
        setImporting(true);

        const payload = {
            experiences: data.experiences.filter((_, i) => selected.experiences.has(i)),
            accomplishments: data.accomplishments.filter((_, i) => selected.accomplishments.has(i)),
            skills: data.skills.filter((_, i) => selected.skills.has(i)),
            education: data.education.filter((_, i) => selected.education.has(i)),
            projects: data.projects.filter((_, i) => selected.projects.has(i)),
        };

        router.post(commit(chatSessionId).url, payload, {
            onSuccess: () => {
                setImporting(false);
                onClose();
            },
            onError: () => {
                setImporting(false);
            },
        });
    }

    const totalSelected =
        selected.experiences.size + selected.accomplishments.size + selected.skills.size + selected.education.size + selected.projects.size;

    return (
        <Sheet open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <SheetContent className="w-full overflow-y-auto sm:max-w-lg">
                <SheetHeader>
                    <SheetTitle>Extracted Experiences</SheetTitle>
                </SheetHeader>

                <div className="space-y-6 py-4">
                    {data.experiences.length > 0 && (
                        <section className="space-y-3">
                            <h3 className="text-sm font-semibold">
                                Experiences ({selected.experiences.size}/{data.experiences.length})
                            </h3>
                            {data.experiences.map((exp, i) => (
                                <Card
                                    key={i}
                                    className={`cursor-pointer ${!selected.experiences.has(i) ? 'opacity-40' : ''}`}
                                    onClick={() => toggle('experiences', i)}
                                >
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-sm">
                                            {exp.title} at {exp.company}
                                        </CardTitle>
                                        <p className="text-muted-foreground text-xs">
                                            {exp.started_at} — {exp.is_current ? 'Present' : (exp.ended_at ?? 'N/A')}
                                            {exp.location && ` · ${exp.location}`}
                                        </p>
                                    </CardHeader>
                                    {exp.description && (
                                        <CardContent className="pt-0">
                                            <p className="text-muted-foreground text-xs">{exp.description}</p>
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
                                <h3 className="text-sm font-semibold">
                                    Skills ({selected.skills.size}/{data.skills.length})
                                </h3>
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
                                <h3 className="text-sm font-semibold">
                                    Accomplishments ({selected.accomplishments.size}/{data.accomplishments.length})
                                </h3>
                                {data.accomplishments.map((acc, i) => (
                                    <Card
                                        key={i}
                                        className={`cursor-pointer ${!selected.accomplishments.has(i) ? 'opacity-40' : ''}`}
                                        onClick={() => toggle('accomplishments', i)}
                                    >
                                        <CardHeader className="pb-2">
                                            <CardTitle className="text-sm">{acc.title}</CardTitle>
                                        </CardHeader>
                                        <CardContent className="pt-0">
                                            <p className="text-muted-foreground text-xs">{acc.description}</p>
                                            {acc.impact && <p className="mt-1 text-xs font-medium">{acc.impact}</p>}
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
                                <h3 className="text-sm font-semibold">
                                    Education ({selected.education.size}/{data.education.length})
                                </h3>
                                {data.education.map((edu, i) => (
                                    <Card
                                        key={i}
                                        className={`cursor-pointer ${!selected.education.has(i) ? 'opacity-40' : ''}`}
                                        onClick={() => toggle('education', i)}
                                    >
                                        <CardHeader className="pb-2">
                                            <CardTitle className="text-sm">{edu.title}</CardTitle>
                                            <p className="text-muted-foreground text-xs">
                                                {edu.institution}
                                                {edu.field && ` · ${edu.field}`}
                                            </p>
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
                                <h3 className="text-sm font-semibold">
                                    Projects ({selected.projects.size}/{data.projects.length})
                                </h3>
                                {data.projects.map((proj, i) => (
                                    <Card
                                        key={i}
                                        className={`cursor-pointer ${!selected.projects.has(i) ? 'opacity-40' : ''}`}
                                        onClick={() => toggle('projects', i)}
                                    >
                                        <CardHeader className="pb-2">
                                            <CardTitle className="text-sm">{proj.name}</CardTitle>
                                            {proj.role && <p className="text-muted-foreground text-xs">{proj.role}</p>}
                                        </CardHeader>
                                        <CardContent className="pt-0">
                                            <p className="text-muted-foreground text-xs">{proj.description}</p>
                                        </CardContent>
                                    </Card>
                                ))}
                            </section>
                        </>
                    )}

                    <div className="flex items-center justify-between pt-4">
                        <Button variant="outline" onClick={onClose}>
                            <X className="mr-1 h-4 w-4" /> Close
                        </Button>
                        <Button onClick={handleImport} disabled={totalSelected === 0 || importing}>
                            <Check className="mr-1 h-4 w-4" /> Import Selected ({totalSelected})
                        </Button>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}
