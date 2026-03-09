import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, Check, Edit, Loader2, Pencil, Plus, RefreshCw, Target, Trash2, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import Heading from '@/components/heading';
import PipelineAssistantPanel from '@/components/pipeline-assistant-panel';
import PipelineNextAction from '@/components/pipeline-next-action';
import PipelineSteps from '@/components/pipeline-steps';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import JobPostingController from '@/actions/App/Http/Controllers/JobPostingController';
import type { BreadcrumbItem } from '@/types';

type SkillItem = { name: string; importance: string; category: string };
type IdealCandidateProfile = {
    id: number;
    required_skills: SkillItem[];
    preferred_skills: SkillItem[];
    experience_profile: { years_minimum?: number; years_preferred?: number; industries?: string[]; project_types?: string[]; leadership_expectations?: string };
    cultural_fit: { values?: string[]; team_dynamics?: string; work_style?: string };
    language_guidance: { key_terms?: string[]; tone?: string; phrases_to_mirror?: string[] };
    red_flags: string[];
    company_research: { industry?: string; size_indicators?: string; growth_stage?: string; notable_details?: string[] } | null;
    candidate_summary: string | null;
    is_user_edited: boolean;
};

type JobPosting = {
    id: number;
    title: string | null;
    company: string | null;
    location: string | null;
    seniority_level: string | null;
    compensation: string | null;
    remote_policy: string | null;
    raw_text: string;
    analyzed_at: string | null;
    ideal_candidate_profile: IdealCandidateProfile | null;
};

type SectionName = 'required_skills' | 'preferred_skills' | 'experience_profile' | 'cultural_fit' | 'language_guidance' | 'red_flags';

function SectionHeader({
    title,
    section,
    editingSection,
    saving,
    onEdit,
    onSave,
    onCancel,
}: {
    title: string;
    section: SectionName;
    editingSection: SectionName | null;
    saving: boolean;
    onEdit: () => void;
    onSave: () => void;
    onCancel: () => void;
}) {
    const isEditing = editingSection === section;

    return (
        <CardHeader className="flex-row items-center justify-between">
            <CardTitle className="text-base">{title}</CardTitle>
            {isEditing ? (
                <div className="flex gap-2">
                    <Button size="sm" onClick={onSave} disabled={saving}>
                        <Check className="mr-1 h-4 w-4" /> Save
                    </Button>
                    <Button variant="ghost" size="sm" onClick={onCancel} disabled={saving}>
                        <X className="mr-1 h-4 w-4" /> Cancel
                    </Button>
                </div>
            ) : (
                <Button
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8"
                    onClick={onEdit}
                    disabled={editingSection !== null}
                >
                    <Pencil className="h-4 w-4" />
                </Button>
            )}
        </CardHeader>
    );
}

type LatestGapAnalysis = {
    id: number;
    overall_match_score: number | null;
    created_at: string;
};

export default function ShowJobPosting({ posting, latestGapAnalysis }: { posting: JobPosting; latestGapAnalysis?: LatestGapAnalysis | null }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Job Postings', href: '/job-postings' },
        { title: posting.title ?? 'Posting', href: `/job-postings/${posting.id}` },
    ];

    const profile = posting.ideal_candidate_profile;
    const [editingSection, setEditingSection] = useState<SectionName | null>(null);
    const [saving, setSaving] = useState(false);

    // Draft states for each section
    const [draftRequiredSkills, setDraftRequiredSkills] = useState<SkillItem[]>([]);
    const [draftPreferredSkills, setDraftPreferredSkills] = useState<SkillItem[]>([]);
    const [draftExperience, setDraftExperience] = useState({
        years_minimum: 0,
        years_preferred: 0,
        industries: '',
        project_types: '',
        leadership_expectations: '',
    });
    const [draftCulturalFit, setDraftCulturalFit] = useState({
        values: '',
        team_dynamics: '',
        work_style: '',
    });
    const [draftLanguageGuidance, setDraftLanguageGuidance] = useState({
        tone: '',
        key_terms: '',
        phrases_to_mirror: '',
    });
    const [draftRedFlags, setDraftRedFlags] = useState('');

    function startEditing(section: SectionName) {
        if (!profile) return;

        switch (section) {
            case 'required_skills':
                setDraftRequiredSkills([...profile.required_skills]);
                break;
            case 'preferred_skills':
                setDraftPreferredSkills([...profile.preferred_skills]);
                break;
            case 'experience_profile':
                setDraftExperience({
                    years_minimum: profile.experience_profile?.years_minimum ?? 0,
                    years_preferred: profile.experience_profile?.years_preferred ?? 0,
                    industries: (profile.experience_profile?.industries ?? []).join(', '),
                    project_types: (profile.experience_profile?.project_types ?? []).join(', '),
                    leadership_expectations: profile.experience_profile?.leadership_expectations ?? '',
                });
                break;
            case 'cultural_fit':
                setDraftCulturalFit({
                    values: (profile.cultural_fit?.values ?? []).join(', '),
                    team_dynamics: profile.cultural_fit?.team_dynamics ?? '',
                    work_style: profile.cultural_fit?.work_style ?? '',
                });
                break;
            case 'language_guidance':
                setDraftLanguageGuidance({
                    tone: profile.language_guidance?.tone ?? '',
                    key_terms: (profile.language_guidance?.key_terms ?? []).join(', '),
                    phrases_to_mirror: (profile.language_guidance?.phrases_to_mirror ?? []).join(', '),
                });
                break;
            case 'red_flags':
                setDraftRedFlags(profile.red_flags.join('\n'));
                break;
        }

        setEditingSection(section);
    }

    function cancelEditing() {
        setEditingSection(null);
    }

    function saveSection(section: SectionName) {
        if (!profile) return;

        let data: Record<string, unknown> = {};

        switch (section) {
            case 'required_skills':
                data = { required_skills: draftRequiredSkills };
                break;
            case 'preferred_skills':
                data = { preferred_skills: draftPreferredSkills };
                break;
            case 'experience_profile':
                data = {
                    experience_profile: {
                        years_minimum: draftExperience.years_minimum || null,
                        years_preferred: draftExperience.years_preferred || null,
                        industries: splitComma(draftExperience.industries),
                        project_types: splitComma(draftExperience.project_types),
                        leadership_expectations: draftExperience.leadership_expectations || null,
                    },
                };
                break;
            case 'cultural_fit':
                data = {
                    cultural_fit: {
                        values: splitComma(draftCulturalFit.values),
                        team_dynamics: draftCulturalFit.team_dynamics || null,
                        work_style: draftCulturalFit.work_style || null,
                    },
                };
                break;
            case 'language_guidance':
                data = {
                    language_guidance: {
                        tone: draftLanguageGuidance.tone || null,
                        key_terms: splitComma(draftLanguageGuidance.key_terms),
                        phrases_to_mirror: splitComma(draftLanguageGuidance.phrases_to_mirror),
                    },
                };
                break;
            case 'red_flags':
                data = { red_flags: draftRedFlags.split('\n').filter(Boolean) };
                break;
        }

        setSaving(true);
        router.put(`/job-postings/${posting.id}/profile`, data, {
            preserveScroll: true,
            onSuccess: () => setEditingSection(null),
            onFinish: () => setSaving(false),
        });
    }

    function splitComma(value: string): string[] {
        return value.split(',').map((s) => s.trim()).filter(Boolean);
    }

    function updateSkill(list: SkillItem[], index: number, field: keyof SkillItem, value: string): SkillItem[] {
        return list.map((item, i) => (i === index ? { ...item, [field]: value } : item));
    }

    function removeSkill(list: SkillItem[], index: number): SkillItem[] {
        return list.filter((_, i) => i !== index);
    }

    function addSkill(list: SkillItem[]): SkillItem[] {
        return [...list, { name: '', importance: 'preferred', category: '' }];
    }

    useEffect(() => {
        if (!posting.analyzed_at) {
            const interval = setInterval(() => {
                router.reload({ only: ['posting'] });
            }, 3000);
            return () => clearInterval(interval);
        }
    }, [posting.analyzed_at]);

    function sectionHeaderProps(title: string, section: SectionName) {
        return {
            title,
            section,
            editingSection,
            saving,
            onEdit: () => startEditing(section),
            onSave: () => saveSection(section),
            onCancel: cancelEditing,
        };
    }

    function renderSkillEditor(skills: SkillItem[], setSkills: (s: SkillItem[]) => void) {
        return (
            <div className="space-y-2">
                {skills.map((skill, i) => (
                    <div key={i} className="flex items-center gap-2">
                        <Input
                            value={skill.name}
                            onChange={(e) => setSkills(updateSkill(skills, i, 'name', e.target.value))}
                            placeholder="Skill name"
                            className="flex-1"
                        />
                        <Select value={skill.importance} onValueChange={(v) => setSkills(updateSkill(skills, i, 'importance', v))}>
                            <SelectTrigger className="w-[130px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="required">Required</SelectItem>
                                <SelectItem value="preferred">Preferred</SelectItem>
                                <SelectItem value="nice_to_have">Nice to have</SelectItem>
                            </SelectContent>
                        </Select>
                        <Input
                            value={skill.category}
                            onChange={(e) => setSkills(updateSkill(skills, i, 'category', e.target.value))}
                            placeholder="Category"
                            className="w-32"
                        />
                        <Button variant="ghost" size="icon" className="h-8 w-8 shrink-0" onClick={() => setSkills(removeSkill(skills, i))}>
                            <Trash2 className="h-4 w-4" />
                        </Button>
                    </div>
                ))}
                <Button variant="outline" size="sm" onClick={() => setSkills(addSkill(skills))}>
                    <Plus className="mr-1 h-4 w-4" /> Add Skill
                </Button>
            </div>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={posting.title ?? 'Job Posting'} />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                {posting.analyzed_at && (
                    <>
                        <PipelineSteps
                            steps={[
                                { label: 'Job Posting', href: `/job-postings/${posting.id}`, status: 'active' },
                                { label: 'Ideal Candidate', status: profile ? 'completed' : 'upcoming' },
                                { label: 'Gap Analysis', href: latestGapAnalysis ? `/gap-analyses/${latestGapAnalysis.id}` : undefined, status: latestGapAnalysis ? 'completed' : 'upcoming' },
                                { label: 'Resume', status: 'upcoming' },
                                { label: 'Application', status: 'upcoming' },
                            ]}
                        />
                        {profile && (
                            latestGapAnalysis ? (
                                <PipelineNextAction
                                    label="View Gap Analysis"
                                    description="Your gap analysis is ready"
                                    href={`/gap-analyses/${latestGapAnalysis.id}`}
                                />
                            ) : (
                                <PipelineNextAction
                                    label="Run Gap Analysis"
                                    description="Compare your profile against this role"
                                    onClick={() => router.post(`/job-postings/${posting.id}/gap-analysis`)}
                                />
                            )
                        )}
                    </>
                )}
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">{posting.title ?? 'Untitled Posting'}</h1>
                        <p className="text-muted-foreground text-lg">
                            {posting.company ?? 'Unknown Company'}
                            {posting.location && ` · ${posting.location}`}
                        </p>
                        <div className="mt-1 flex flex-wrap gap-2 text-sm text-muted-foreground">
                            {posting.seniority_level && <span>{posting.seniority_level}</span>}
                            {posting.compensation && <span>· {posting.compensation}</span>}
                            {posting.remote_policy && <span>· {posting.remote_policy}</span>}
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/job-postings/${posting.id}/edit`}><Edit className="mr-1 h-4 w-4" /> Edit</Link>
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => router.post(`/job-postings/${posting.id}/reanalyze`)}>
                            <RefreshCw className="mr-1 h-4 w-4" /> Reanalyze
                        </Button>
                        <Button variant="destructive" size="sm" onClick={() => { if (confirm('Delete this posting?')) router.delete(JobPostingController.destroy(posting.id).url); }}>
                            <Trash2 className="mr-1 h-4 w-4" /> Delete
                        </Button>
                    </div>
                </div>

                {!posting.analyzed_at && (
                    <Card>
                        <CardContent className="flex items-center gap-3 py-8">
                            <Loader2 className="text-primary h-6 w-6 animate-spin" />
                            <p className="text-muted-foreground">Analyzing job posting... This usually takes 15-30 seconds.</p>
                        </CardContent>
                    </Card>
                )}

                {profile && (
                    <>
                        <Separator />
                        <Heading title="Ideal Candidate Profile" description={profile.is_user_edited ? "Edited by you." : "AI-generated profile based on the job posting analysis."} />

                        {profile.candidate_summary && (
                            <Card className="border-primary/20 bg-primary/5">
                                <CardContent className="py-4">
                                    <p className="text-sm leading-relaxed">{profile.candidate_summary}</p>
                                </CardContent>
                            </Card>
                        )}

                        {/* Required Skills */}
                        <Card>
                            <SectionHeader {...sectionHeaderProps('Required Skills', 'required_skills')} />
                            <CardContent>
                                {editingSection === 'required_skills' ? (
                                    renderSkillEditor(draftRequiredSkills, setDraftRequiredSkills)
                                ) : profile.required_skills.length > 0 ? (
                                    <div className="flex flex-wrap gap-2">
                                        {profile.required_skills.map((skill, i) => (
                                            <Badge key={i} variant="default">{skill.name}</Badge>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">No required skills defined.</p>
                                )}
                            </CardContent>
                        </Card>

                        {/* Preferred Skills */}
                        <Card>
                            <SectionHeader {...sectionHeaderProps('Preferred Skills', 'preferred_skills')} />
                            <CardContent>
                                {editingSection === 'preferred_skills' ? (
                                    renderSkillEditor(draftPreferredSkills, setDraftPreferredSkills)
                                ) : profile.preferred_skills.length > 0 ? (
                                    <div className="flex flex-wrap gap-2">
                                        {profile.preferred_skills.map((skill, i) => (
                                            <Badge key={i} variant="secondary">{skill.name}</Badge>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">No preferred skills defined.</p>
                                )}
                            </CardContent>
                        </Card>

                        {/* Experience Profile */}
                        <Card>
                            <SectionHeader {...sectionHeaderProps('Experience Profile', 'experience_profile')} />
                            <CardContent>
                                {editingSection === 'experience_profile' ? (
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <Label>Minimum Years</Label>
                                            <Input
                                                type="number"
                                                min={0}
                                                value={draftExperience.years_minimum || ''}
                                                onChange={(e) => setDraftExperience({ ...draftExperience, years_minimum: parseInt(e.target.value) || 0 })}
                                            />
                                        </div>
                                        <div>
                                            <Label>Preferred Years</Label>
                                            <Input
                                                type="number"
                                                min={0}
                                                value={draftExperience.years_preferred || ''}
                                                onChange={(e) => setDraftExperience({ ...draftExperience, years_preferred: parseInt(e.target.value) || 0 })}
                                            />
                                        </div>
                                        <div>
                                            <Label>Industries (comma-separated)</Label>
                                            <Input
                                                value={draftExperience.industries}
                                                onChange={(e) => setDraftExperience({ ...draftExperience, industries: e.target.value })}
                                                placeholder="e.g. FinTech, Healthcare"
                                            />
                                        </div>
                                        <div>
                                            <Label>Project Types (comma-separated)</Label>
                                            <Input
                                                value={draftExperience.project_types}
                                                onChange={(e) => setDraftExperience({ ...draftExperience, project_types: e.target.value })}
                                                placeholder="e.g. SaaS, Enterprise"
                                            />
                                        </div>
                                        <div className="sm:col-span-2">
                                            <Label>Leadership Expectations</Label>
                                            <Input
                                                value={draftExperience.leadership_expectations}
                                                onChange={(e) => setDraftExperience({ ...draftExperience, leadership_expectations: e.target.value })}
                                                placeholder="e.g. Team lead experience preferred"
                                            />
                                        </div>
                                    </div>
                                ) : (
                                    <div className="space-y-2 text-sm">
                                        {profile.experience_profile?.years_minimum != null && (
                                            <p><span className="font-medium">Minimum years:</span> {profile.experience_profile.years_minimum}</p>
                                        )}
                                        {profile.experience_profile?.years_preferred != null && (
                                            <p><span className="font-medium">Preferred years:</span> {profile.experience_profile.years_preferred}</p>
                                        )}
                                        {profile.experience_profile?.industries && profile.experience_profile.industries.length > 0 && (
                                            <p><span className="font-medium">Industries:</span> {profile.experience_profile.industries.join(', ')}</p>
                                        )}
                                        {profile.experience_profile?.project_types && profile.experience_profile.project_types.length > 0 && (
                                            <p><span className="font-medium">Project types:</span> {profile.experience_profile.project_types.join(', ')}</p>
                                        )}
                                        {profile.experience_profile?.leadership_expectations && (
                                            <p><span className="font-medium">Leadership:</span> {profile.experience_profile.leadership_expectations}</p>
                                        )}
                                        {!profile.experience_profile?.years_minimum && !profile.experience_profile?.years_preferred && !profile.experience_profile?.industries?.length && !profile.experience_profile?.project_types?.length && !profile.experience_profile?.leadership_expectations && (
                                            <p className="text-muted-foreground">No experience profile defined.</p>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Cultural Fit */}
                        <Card>
                            <SectionHeader {...sectionHeaderProps('Cultural Fit', 'cultural_fit')} />
                            <CardContent>
                                {editingSection === 'cultural_fit' ? (
                                    <div className="space-y-4">
                                        <div>
                                            <Label>Values (comma-separated)</Label>
                                            <Input
                                                value={draftCulturalFit.values}
                                                onChange={(e) => setDraftCulturalFit({ ...draftCulturalFit, values: e.target.value })}
                                                placeholder="e.g. Innovation, Collaboration"
                                            />
                                        </div>
                                        <div>
                                            <Label>Team Dynamics</Label>
                                            <Input
                                                value={draftCulturalFit.team_dynamics}
                                                onChange={(e) => setDraftCulturalFit({ ...draftCulturalFit, team_dynamics: e.target.value })}
                                                placeholder="e.g. Cross-functional agile teams"
                                            />
                                        </div>
                                        <div>
                                            <Label>Work Style</Label>
                                            <Input
                                                value={draftCulturalFit.work_style}
                                                onChange={(e) => setDraftCulturalFit({ ...draftCulturalFit, work_style: e.target.value })}
                                                placeholder="e.g. Self-directed, autonomous"
                                            />
                                        </div>
                                    </div>
                                ) : (
                                    <div className="space-y-2 text-sm">
                                        {profile.cultural_fit?.values && profile.cultural_fit.values.length > 0 && (
                                            <div className="flex flex-wrap gap-1">
                                                {profile.cultural_fit.values.map((v, i) => <Badge key={i} variant="outline">{v}</Badge>)}
                                            </div>
                                        )}
                                        {profile.cultural_fit?.team_dynamics && <p><span className="font-medium">Team:</span> {profile.cultural_fit.team_dynamics}</p>}
                                        {profile.cultural_fit?.work_style && <p><span className="font-medium">Style:</span> {profile.cultural_fit.work_style}</p>}
                                        {!profile.cultural_fit?.values?.length && !profile.cultural_fit?.team_dynamics && !profile.cultural_fit?.work_style && (
                                            <p className="text-muted-foreground">No cultural fit defined.</p>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Language Guidance */}
                        <Card>
                            <SectionHeader {...sectionHeaderProps('Language Guidance', 'language_guidance')} />
                            <CardContent>
                                {editingSection === 'language_guidance' ? (
                                    <div className="space-y-4">
                                        <div>
                                            <Label>Tone</Label>
                                            <Input
                                                value={draftLanguageGuidance.tone}
                                                onChange={(e) => setDraftLanguageGuidance({ ...draftLanguageGuidance, tone: e.target.value })}
                                                placeholder="e.g. Professional yet approachable"
                                            />
                                        </div>
                                        <div>
                                            <Label>Key Terms (comma-separated)</Label>
                                            <Input
                                                value={draftLanguageGuidance.key_terms}
                                                onChange={(e) => setDraftLanguageGuidance({ ...draftLanguageGuidance, key_terms: e.target.value })}
                                                placeholder="e.g. scalability, microservices"
                                            />
                                        </div>
                                        <div>
                                            <Label>Phrases to Mirror (comma-separated)</Label>
                                            <Input
                                                value={draftLanguageGuidance.phrases_to_mirror}
                                                onChange={(e) => setDraftLanguageGuidance({ ...draftLanguageGuidance, phrases_to_mirror: e.target.value })}
                                                placeholder="e.g. data-driven, customer-centric"
                                            />
                                        </div>
                                    </div>
                                ) : (
                                    <div className="space-y-2 text-sm">
                                        {profile.language_guidance?.tone && <p><span className="font-medium">Tone:</span> {profile.language_guidance.tone}</p>}
                                        {profile.language_guidance?.key_terms && profile.language_guidance.key_terms.length > 0 && (
                                            <div className="flex flex-wrap gap-1">
                                                {profile.language_guidance.key_terms.map((t, i) => <Badge key={i} variant="outline" className="text-xs">{t}</Badge>)}
                                            </div>
                                        )}
                                        {profile.language_guidance?.phrases_to_mirror && profile.language_guidance.phrases_to_mirror.length > 0 && (
                                            <p><span className="font-medium">Phrases to mirror:</span> {profile.language_guidance.phrases_to_mirror.join(', ')}</p>
                                        )}
                                        {!profile.language_guidance?.tone && !profile.language_guidance?.key_terms?.length && !profile.language_guidance?.phrases_to_mirror?.length && (
                                            <p className="text-muted-foreground">No language guidance defined.</p>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Red Flags */}
                        <Card>
                            <SectionHeader {...sectionHeaderProps('Red Flags', 'red_flags')} />
                            <CardContent>
                                {editingSection === 'red_flags' ? (
                                    <Textarea
                                        value={draftRedFlags}
                                        onChange={(e) => setDraftRedFlags(e.target.value)}
                                        rows={5}
                                        placeholder="One red flag per line..."
                                    />
                                ) : profile.red_flags.length > 0 ? (
                                    <ul className="list-inside list-disc space-y-1 text-sm text-muted-foreground">
                                        {profile.red_flags.map((flag, i) => <li key={i}>{flag}</li>)}
                                    </ul>
                                ) : (
                                    <p className="text-sm text-muted-foreground">No red flags defined.</p>
                                )}
                            </CardContent>
                        </Card>

                        {latestGapAnalysis ? (
                            <Card className="border-success bg-success/5">
                                <CardContent className="flex items-center justify-between py-5">
                                    <div className="flex items-center gap-3">
                                        <Target className="text-success h-6 w-6" />
                                        <div>
                                            <p className="font-semibold">
                                                Gap Analysis Complete
                                                {latestGapAnalysis.overall_match_score != null && (
                                                    <span className="text-muted-foreground ml-2 text-sm font-normal">
                                                        {latestGapAnalysis.overall_match_score}% match
                                                    </span>
                                                )}
                                            </p>
                                            <p className="text-muted-foreground text-sm">View your results or run a fresh analysis.</p>
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button variant="outline" size="sm" onClick={() => router.post(`/job-postings/${posting.id}/gap-analysis`)}>
                                            Run New
                                        </Button>
                                        <Button asChild>
                                            <Link href={`/gap-analyses/${latestGapAnalysis.id}`}>
                                                View Gap Analysis <ArrowRight className="ml-1 h-4 w-4" />
                                            </Link>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ) : (
                            <Card className="border-primary bg-primary/5">
                                <CardContent className="flex items-center justify-between py-5">
                                    <div className="flex items-center gap-3">
                                        <Target className="text-primary h-6 w-6" />
                                        <div>
                                            <p className="font-semibold">Ready to see how you match?</p>
                                            <p className="text-muted-foreground text-sm">Run a gap analysis to compare your profile against this role.</p>
                                        </div>
                                    </div>
                                    <Button onClick={() => router.post(`/job-postings/${posting.id}/gap-analysis`)}>
                                        Run Gap Analysis <ArrowRight className="ml-1 h-4 w-4" />
                                    </Button>
                                </CardContent>
                            </Card>
                        )}
                    </>
                )}

                <Separator />
                <Card>
                    <CardHeader><CardTitle className="text-base">Original Posting</CardTitle></CardHeader>
                    <CardContent>
                        <pre className="text-muted-foreground max-h-96 overflow-auto whitespace-pre-wrap text-sm">{posting.raw_text}</pre>
                    </CardContent>
                </Card>
            </div>

            {posting.analyzed_at && (
                <PipelineAssistantPanel context={{ step: 'job_posting', pipelineKey: `job_posting:${posting.id}` }} />
            )}
        </AppLayout>
    );
}
