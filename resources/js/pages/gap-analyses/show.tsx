import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, CheckCircle, FileText, Loader2, RefreshCw } from 'lucide-react';
import { useEffect, useRef } from 'react';
import GapActionCard from '@/components/gap-resolution/gap-action-card';
import LibraryAdditionsSummary from '@/components/gap-resolution/library-additions-summary';
import Heading from '@/components/heading';
import MatchScoreRing from '@/components/match-score-ring';
import PipelineAssistantPanel from '@/components/pipeline-assistant-panel';
import type {PipelineAssistantHandle} from '@/components/pipeline-assistant-panel';
import PipelineNextAction from '@/components/pipeline-next-action';
import PipelineSteps from '@/components/pipeline-steps';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Strength = { area: string; evidence: string; relevance: string };
type Gap = { area: string; description: string; classification: string; suggestion: string };
type Experience = {
    id: number;
    title: string;
    company: string;
    description: string | null;
    accomplishments: { id: number; title: string }[];
    skills: { id: number; name: string }[];
};
type GapResolution = {
    status: string;
    experience_id?: number;
    reframe_original?: string;
    reframe_suggestion?: string;
    rationale?: string;
    answer?: string;
    note?: string;
};

type GapAnalysis = {
    id: number;
    strengths: Strength[];
    gaps: Gap[];
    gap_resolutions: Record<string, GapResolution> | null;
    overall_match_score: number | null;
    previous_match_score: number | null;
    ai_summary: string | null;
    ideal_candidate_profile: {
        job_posting: {
            id: number;
            title: string | null;
            company: string | null;
        };
    };
};

type LibraryAdditions = {
    accomplishments: {
        id: number;
        title: string;
        description: string;
        experience_id: number | null;
        experience?: { id: number; title: string; company: string } | null;
    }[];
    skills: {
        id: number;
        name: string;
        category: string;
        proficiency: string | null;
        ai_inferred_proficiency: string | null;
    }[];
};

type LatestResume = {
    id: number;
    title: string;
    is_finalized: boolean;
} | null;

export default function ShowGapAnalysis({ gapAnalysis, experiences, libraryAdditions, latestResume }: { gapAnalysis: GapAnalysis; experiences: Experience[]; libraryAdditions: LibraryAdditions; latestResume: LatestResume }) {
    const assistantRef = useRef<PipelineAssistantHandle>(null);
    const posting = gapAnalysis.ideal_candidate_profile.job_posting;
    const isAnalyzing = gapAnalysis.strengths.length === 0 && gapAnalysis.gaps.length === 0 && !gapAnalysis.ai_summary;
    const resolutions = gapAnalysis.gap_resolutions ?? {};

    const resolvedCount = Object.values(resolutions).filter((r) => r.status === 'resolved' || r.status === 'acknowledged').length;
    const totalGaps = gapAnalysis.gaps.length;
    const scoreDelta =
        gapAnalysis.previous_match_score != null && gapAnalysis.overall_match_score != null
            ? gapAnalysis.overall_match_score - gapAnalysis.previous_match_score
            : null;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Job Postings', href: '/job-postings' },
        { title: posting.title ?? 'Posting', href: `/job-postings/${posting.id}` },
        { title: 'Gap Analysis', href: `/gap-analyses/${gapAnalysis.id}` },
    ];

    useEffect(() => {
        if (isAnalyzing) {
            const interval = setInterval(() => {
                router.reload({ only: ['gapAnalysis'] });
            }, 3000);
            return () => clearInterval(interval);
        }
    }, [isAnalyzing]);

    function handleResolutionChange() {
        router.reload({ only: ['gapAnalysis'] });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gap Analysis" />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                <PipelineSteps
                    steps={[
                        { label: 'Job Posting', href: `/job-postings/${posting.id}`, status: 'completed' },
                        { label: 'Ideal Candidate', href: `/job-postings/${posting.id}`, status: 'completed' },
                        { label: 'Gap Analysis', href: `/gap-analyses/${gapAnalysis.id}`, status: latestResume ? 'completed' : 'active' },
                        { label: 'Resume', href: latestResume ? `/resumes/${latestResume.id}` : undefined, status: latestResume ? 'active' : 'upcoming' },
                        { label: 'Application', href: latestResume ? '/applications/create' : undefined, status: 'upcoming' },
                    ]}
                />
                <div className="flex items-start justify-between">
                    <Heading title="Gap Analysis" description={`${posting.title ?? 'Untitled'} at ${posting.company ?? 'Unknown Company'}`} />
                    {!isAnalyzing && (
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => router.post(`/gap-analyses/${gapAnalysis.id}/reanalyze`)}
                            >
                                <RefreshCw className="mr-1 h-4 w-4" />
                                Re-analyze
                            </Button>
                            {latestResume ? (
                                <Button size="sm" asChild>
                                    <Link href={`/resumes/${latestResume.id}`}>
                                        <FileText className="mr-1 h-4 w-4" />
                                        View Resume
                                        <ArrowRight className="ml-1 h-4 w-4" />
                                    </Link>
                                </Button>
                            ) : (
                                <Button variant="outline" size="sm" onClick={() => router.post(`/gap-analyses/${gapAnalysis.id}/resume`)}>
                                    Generate Resume
                                </Button>
                            )}
                        </div>
                    )}
                </div>

                {isAnalyzing && (
                    <Card>
                        <CardContent className="flex items-center gap-3 py-8">
                            <Loader2 className="text-primary h-6 w-6 animate-spin" />
                            <p className="text-muted-foreground">Analyzing your profile against the ideal candidate... This usually takes 20-40 seconds.</p>
                        </CardContent>
                    </Card>
                )}

                {gapAnalysis.overall_match_score != null && (
                    <Card>
                        <CardContent className="flex items-center gap-6 py-6">
                            <MatchScoreRing
                                score={gapAnalysis.overall_match_score}
                                delta={scoreDelta ?? undefined}
                            />
                            <div>
                                <p className="text-lg font-semibold">Overall Match Score</p>
                                <p className="text-muted-foreground text-sm">
                                    {gapAnalysis.overall_match_score >= 80 ? 'Strong match — you\'re well positioned for this role.' :
                                     gapAnalysis.overall_match_score >= 60 ? 'Good match — a few areas to strengthen.' :
                                     gapAnalysis.overall_match_score >= 40 ? 'Moderate match — consider addressing the gaps below.' :
                                     'Low match — significant gaps to address.'}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {gapAnalysis.ai_summary && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Summary</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-muted-foreground text-sm">{gapAnalysis.ai_summary}</p>
                        </CardContent>
                    </Card>
                )}

                {gapAnalysis.strengths.length > 0 && (
                    <>
                        <Separator />
                        <h2 className="text-lg font-semibold">Strengths ({gapAnalysis.strengths.length})</h2>
                        {gapAnalysis.strengths.map((s, i) => (
                            <Card key={i} className="border-l-4 border-l-success">
                                <CardHeader className="pb-2">
                                    <div className="flex items-center gap-2">
                                        <CheckCircle className="h-4 w-4 text-success" />
                                        <CardTitle className="text-base">{s.area}</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-1 pt-0">
                                    <p className="text-muted-foreground text-sm">{s.evidence}</p>
                                    <p className="text-muted-foreground text-xs italic">{s.relevance}</p>
                                </CardContent>
                            </Card>
                        ))}
                    </>
                )}

                {gapAnalysis.gaps.length > 0 && (
                    <>
                        <Separator />
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold">Gaps ({totalGaps})</h2>
                            {totalGaps > 0 && (
                                <div className="flex items-center gap-2">
                                    <div className="bg-muted h-2 w-24 overflow-hidden rounded-full">
                                        <div
                                            className="h-full rounded-full bg-success transition-all"
                                            style={{ width: `${(resolvedCount / totalGaps) * 100}%` }}
                                        />
                                    </div>
                                    <span className="text-muted-foreground text-xs">
                                        {resolvedCount} of {totalGaps} addressed
                                    </span>
                                </div>
                            )}
                        </div>
                        {gapAnalysis.gaps.map((g, i) => (
                            <GapActionCard
                                key={i}
                                gap={g}
                                gapAnalysisId={gapAnalysis.id}
                                experiences={experiences}
                                resolution={resolutions[g.area]}
                                onResolutionChange={handleResolutionChange}
                                onCoach={(message) => assistantRef.current?.openWithMessage(message)}
                            />
                        ))}
                    </>
                )}

                {!isAnalyzing && (libraryAdditions.accomplishments.length > 0 || libraryAdditions.skills.length > 0) && (
                    <>
                        <Separator />
                        <LibraryAdditionsSummary
                            gapAnalysisId={gapAnalysis.id}
                            accomplishments={libraryAdditions.accomplishments}
                            skills={libraryAdditions.skills}
                            experiences={experiences}
                            onOrganized={() => router.reload({ only: ['libraryAdditions'] })}
                        />
                    </>
                )}

                {!isAnalyzing && (
                    latestResume ? (
                        <PipelineNextAction
                            label="View Resume"
                            description="Continue to your generated resume"
                            href={`/resumes/${latestResume.id}`}
                        />
                    ) : (
                        <PipelineNextAction
                            label="Generate Resume"
                            description="Create a tailored resume based on this analysis"
                            onClick={() => router.post(`/gap-analyses/${gapAnalysis.id}/resume`)}
                        />
                    )
                )}
            </div>

            {!isAnalyzing && (
                <PipelineAssistantPanel ref={assistantRef} context={{ step: 'gap_analysis', pipelineKey: `gap_analysis:${gapAnalysis.id}` }} />
            )}
        </AppLayout>
    );
}
