import { Head, Link, router } from '@inertiajs/react';
import { Edit, Loader2, RefreshCw, Trash2 } from 'lucide-react';
import { useEffect } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
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

export default function ShowJobPosting({ posting }: { posting: JobPosting }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Job Postings', href: '/job-postings' },
        { title: posting.title ?? 'Posting', href: `/job-postings/${posting.id}` },
    ];

    const profile = posting.ideal_candidate_profile;

    useEffect(() => {
        if (!posting.analyzed_at) {
            const interval = setInterval(() => {
                router.reload({ only: ['posting'] });
            }, 3000);
            return () => clearInterval(interval);
        }
    }, [posting.analyzed_at]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={posting.title ?? 'Job Posting'} />

            <div className="mx-auto max-w-3xl space-y-6 p-4">
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
                        <Heading title="Ideal Candidate Profile" description="AI-generated profile based on the job posting analysis." />

                        {profile.required_skills.length > 0 && (
                            <Card>
                                <CardHeader><CardTitle className="text-base">Required Skills</CardTitle></CardHeader>
                                <CardContent className="flex flex-wrap gap-2">
                                    {profile.required_skills.map((skill, i) => (
                                        <Badge key={i} variant="default">{skill.name}</Badge>
                                    ))}
                                </CardContent>
                            </Card>
                        )}

                        {profile.preferred_skills.length > 0 && (
                            <Card>
                                <CardHeader><CardTitle className="text-base">Preferred Skills</CardTitle></CardHeader>
                                <CardContent className="flex flex-wrap gap-2">
                                    {profile.preferred_skills.map((skill, i) => (
                                        <Badge key={i} variant="secondary">{skill.name}</Badge>
                                    ))}
                                </CardContent>
                            </Card>
                        )}

                        {profile.experience_profile && (
                            <Card>
                                <CardHeader><CardTitle className="text-base">Experience Profile</CardTitle></CardHeader>
                                <CardContent className="space-y-2 text-sm">
                                    {profile.experience_profile.years_minimum != null && (
                                        <p><span className="font-medium">Minimum years:</span> {profile.experience_profile.years_minimum}</p>
                                    )}
                                    {profile.experience_profile.industries && profile.experience_profile.industries.length > 0 && (
                                        <p><span className="font-medium">Industries:</span> {profile.experience_profile.industries.join(', ')}</p>
                                    )}
                                    {profile.experience_profile.leadership_expectations && (
                                        <p><span className="font-medium">Leadership:</span> {profile.experience_profile.leadership_expectations}</p>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        {profile.cultural_fit && (
                            <Card>
                                <CardHeader><CardTitle className="text-base">Cultural Fit</CardTitle></CardHeader>
                                <CardContent className="space-y-2 text-sm">
                                    {profile.cultural_fit.values && profile.cultural_fit.values.length > 0 && (
                                        <div className="flex flex-wrap gap-1">
                                            {profile.cultural_fit.values.map((v, i) => <Badge key={i} variant="outline">{v}</Badge>)}
                                        </div>
                                    )}
                                    {profile.cultural_fit.team_dynamics && <p><span className="font-medium">Team:</span> {profile.cultural_fit.team_dynamics}</p>}
                                    {profile.cultural_fit.work_style && <p><span className="font-medium">Style:</span> {profile.cultural_fit.work_style}</p>}
                                </CardContent>
                            </Card>
                        )}

                        {profile.language_guidance && (
                            <Card>
                                <CardHeader><CardTitle className="text-base">Language Guidance</CardTitle></CardHeader>
                                <CardContent className="space-y-2 text-sm">
                                    {profile.language_guidance.tone && <p><span className="font-medium">Tone:</span> {profile.language_guidance.tone}</p>}
                                    {profile.language_guidance.key_terms && profile.language_guidance.key_terms.length > 0 && (
                                        <div className="flex flex-wrap gap-1">
                                            {profile.language_guidance.key_terms.map((t, i) => <Badge key={i} variant="outline" className="text-xs">{t}</Badge>)}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        {profile.red_flags.length > 0 && (
                            <Card>
                                <CardHeader><CardTitle className="text-base">Red Flags</CardTitle></CardHeader>
                                <CardContent>
                                    <ul className="list-inside list-disc space-y-1 text-sm text-muted-foreground">
                                        {profile.red_flags.map((flag, i) => <li key={i}>{flag}</li>)}
                                    </ul>
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
        </AppLayout>
    );
}
