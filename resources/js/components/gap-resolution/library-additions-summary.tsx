import axios from 'axios';
import { BookOpen, Check, Loader2 } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type LibraryAccomplishment = {
    id: number;
    title: string;
    description: string;
    experience_id: number | null;
    experience?: { id: number; title: string; company: string } | null;
};

type LibrarySkill = {
    id: number;
    name: string;
    category: string;
    proficiency: string | null;
    ai_inferred_proficiency: string | null;
};

type Experience = {
    id: number;
    title: string;
    company: string;
};

type Props = {
    gapAnalysisId: number;
    accomplishments: LibraryAccomplishment[];
    skills: LibrarySkill[];
    experiences: Experience[];
    onOrganized: () => void;
};

type Updates = Record<number, { type: 'accomplishment' | 'skill'; experience_id?: string; proficiency?: string }>;

const PROFICIENCY_OPTIONS = [
    { value: 'beginner', label: 'Beginner' },
    { value: 'intermediate', label: 'Intermediate' },
    { value: 'advanced', label: 'Advanced' },
    { value: 'expert', label: 'Expert' },
] as const;

const PROFICIENCY_VARIANTS: Record<string, 'default' | 'secondary' | 'info' | 'success' | 'warning'> = {
    beginner: 'secondary',
    intermediate: 'info',
    advanced: 'success',
    expert: 'warning',
};

export default function LibraryAdditionsSummary({ gapAnalysisId, accomplishments, skills, experiences, onOrganized }: Props) {
    const [updates, setUpdates] = useState<Updates>({});
    const [saving, setSaving] = useState(false);

    const hasUnlinkedAccomplishments = accomplishments.some((a) => !a.experience_id);
    const hasUnproficientSkills = skills.some((s) => !s.proficiency);
    const needsOrganizing = hasUnlinkedAccomplishments || hasUnproficientSkills;
    const hasChanges = Object.keys(updates).length > 0;

    if (accomplishments.length === 0 && skills.length === 0) {
        return null;
    }

    function updateAccomplishment(id: number, experienceId: string) {
        setUpdates((prev) => ({
            ...prev,
            [id]: { type: 'accomplishment', experience_id: experienceId },
        }));
    }

    function updateSkill(id: number, proficiency: string) {
        setUpdates((prev) => ({
            ...prev,
            [id]: { type: 'skill', proficiency },
        }));
    }

    async function handleSave() {
        const payload = Object.entries(updates).map(([id, data]) => ({
            type: data.type,
            id: parseInt(id),
            experience_id: data.experience_id ? parseInt(data.experience_id) : null,
            proficiency: data.proficiency ?? null,
        }));

        if (payload.length === 0) return;

        setSaving(true);
        try {
            await axios.post(`/gap-analyses/${gapAnalysisId}/organize`, { updates: payload });
            setUpdates({});
            onOrganized();
        } finally {
            setSaving(false);
        }
    }

    return (
        <Card className={needsOrganizing ? 'border-l-4 border-l-info' : ''}>
            <CardHeader>
                <div className="flex items-center gap-2">
                    <BookOpen className="h-4 w-4" />
                    <CardTitle className="text-base">
                        Library Additions ({accomplishments.length + skills.length})
                    </CardTitle>
                    {!needsOrganizing && (
                        <Badge variant="success">
                            <Check className="h-3 w-3" />
                            Organized
                        </Badge>
                    )}
                </div>
                <p className="text-muted-foreground text-sm">
                    {needsOrganizing
                        ? 'These items were added to your experience library during gap resolution. Link them to experiences and confirm proficiency to keep your library organized.'
                        : 'Items added to your experience library from this gap analysis.'}
                </p>
            </CardHeader>
            <CardContent className="space-y-4">
                {accomplishments.length > 0 && (
                    <div className="space-y-2">
                        <h3 className="text-sm font-medium">Accomplishments</h3>
                        {accomplishments.map((acc) => {
                            const currentExperienceId = updates[acc.id]?.experience_id ?? acc.experience_id?.toString() ?? '';
                            const isLinked = !!acc.experience_id;

                            return (
                                <div key={acc.id} className="bg-muted/50 space-y-2 rounded-md p-3">
                                    <div className="flex items-start justify-between gap-2">
                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm font-medium">{acc.title}</p>
                                            <p className="text-muted-foreground line-clamp-2 text-xs">{acc.description}</p>
                                        </div>
                                        {isLinked && acc.experience && (
                                            <Badge variant="outline" className="shrink-0 text-xs">
                                                {acc.experience.title} at {acc.experience.company}
                                            </Badge>
                                        )}
                                    </div>
                                    {!isLinked && (
                                        <Select value={currentExperienceId} onValueChange={(v) => updateAccomplishment(acc.id, v)}>
                                            <SelectTrigger className="h-8 text-xs">
                                                <SelectValue placeholder="Link to experience..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {experiences.map((exp) => (
                                                    <SelectItem key={exp.id} value={exp.id.toString()}>
                                                        {exp.title} at {exp.company}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}

                {skills.length > 0 && (
                    <div className="space-y-2">
                        <h3 className="text-sm font-medium">Skills</h3>
                        {skills.map((skill) => {
                            const effectiveProficiency = updates[skill.id]?.proficiency ?? skill.proficiency ?? skill.ai_inferred_proficiency;
                            const isConfirmed = !!skill.proficiency;

                            return (
                                <div key={skill.id} className="bg-muted/50 flex items-center gap-3 rounded-md p-3">
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2">
                                            <span className="text-sm font-medium">{skill.name}</span>
                                            <Badge variant="outline" className="text-xs">{skill.category}</Badge>
                                            {effectiveProficiency && (
                                                <Badge variant={PROFICIENCY_VARIANTS[effectiveProficiency] ?? 'secondary'} className="text-xs">
                                                    {effectiveProficiency}
                                                    {!isConfirmed && skill.ai_inferred_proficiency && ' (AI)'}
                                                </Badge>
                                            )}
                                        </div>
                                    </div>
                                    {!isConfirmed && (
                                        <Select
                                            value={updates[skill.id]?.proficiency ?? ''}
                                            onValueChange={(v) => updateSkill(skill.id, v)}
                                        >
                                            <SelectTrigger className="h-8 w-40 text-xs">
                                                <SelectValue placeholder="Set proficiency" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {PROFICIENCY_OPTIONS.map((opt) => (
                                                    <SelectItem key={opt.value} value={opt.value}>
                                                        {opt.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}

                {hasChanges && (
                    <div className="flex justify-end">
                        <Button size="sm" onClick={handleSave} disabled={saving}>
                            {saving ? <Loader2 className="mr-1 h-4 w-4 animate-spin" /> : <Check className="mr-1 h-4 w-4" />}
                            Save Organization
                        </Button>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
