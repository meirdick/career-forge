import { Head, router } from '@inertiajs/react';
import { CheckCircle, Loader2, RefreshCw, Target, TrendingUp } from 'lucide-react';
import { useEffect } from 'react';
import GapActionCard from '@/components/gap-resolution/gap-action-card';
import Heading from '@/components/heading';
import PipelineAssistantPanel from '@/components/pipeline-assistant-panel';
import PipelineSteps from '@/components/pipeline-steps';
import { Badge } from '@/components/ui/badge';
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

export default function ShowGapAnalysis({ gapAnalysis, experiences }: { gapAnalysis: GapAnalysis; experiences: Experience[] }) {
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

            <div className="mx-auto max-w-3xl space-y-6 p-4">
                <PipelineSteps
                    steps={[
                        { label: 'Job Posting', href: `/job-postings/${posting.id}`, status: 'completed' },
                        { label: 'Ideal Candidate', href: `/job-postings/${posting.id}`, status: 'completed' },
                        { label: 'Gap Analysis', href: `/gap-analyses/${gapAnalysis.id}`, status: 'active' },
                        { label: 'Resume', status: 'upcoming' },
                        { label: 'Application', status: 'upcoming' },
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
                            <Button variant="outline" size="sm" onClick={() => router.post(`/gap-analyses/${gapAnalysis.id}/resume`)}>
                                Generate Resume
                            </Button>
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
                        <CardContent className="flex items-center gap-4 py-6">
                            <div className="bg-primary/10 flex h-16 w-16 items-center justify-center rounded-full">
                                <Target className="text-primary h-8 w-8" />
                            </div>
                            <div>
                                <div className="flex items-center gap-3">
                                    <p className="text-3xl font-bold">{gapAnalysis.overall_match_score}%</p>
                                    {scoreDelta != null && scoreDelta !== 0 && (
                                        <Badge variant={scoreDelta > 0 ? 'default' : 'destructive'} className="flex items-center gap-1">
                                            <TrendingUp className={`h-3 w-3 ${scoreDelta < 0 ? 'rotate-180' : ''}`} />
                                            {scoreDelta > 0 ? '+' : ''}
                                            {scoreDelta} pts
                                        </Badge>
                                    )}
                                </div>
                                <p className="text-muted-foreground text-sm">Overall match score</p>
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
                            <Card key={i}>
                                <CardHeader className="pb-2">
                                    <div className="flex items-center gap-2">
                                        <CheckCircle className="h-4 w-4 text-green-500" />
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
                                            className="h-full rounded-full bg-green-500 transition-all"
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
                            />
                        ))}
                    </>
                )}

            </div>

            {!isAnalyzing && (
                <PipelineAssistantPanel context={{ step: 'gap_analysis', pipelineKey: `gap_analysis:${gapAnalysis.id}` }} />
            )}
        </AppLayout>
    );
}
