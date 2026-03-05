import axios from 'axios';
import { Check, ChevronDown, ChevronUp, Loader2, RotateCcw, Sparkles, X } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

type Gap = { area: string; description: string; classification: string; suggestion: string };
type Experience = {
    id: number;
    title: string;
    company: string;
    description: string | null;
    accomplishments: { id: number; title: string }[];
    skills: { id: number; name: string }[];
};
type Resolution = {
    status: string;
    experience_id?: number;
    reframe_original?: string;
    reframe_suggestion?: string;
    rationale?: string;
    answer?: string;
    note?: string;
};

const classificationColors: Record<string, string> = {
    reframable: 'bg-info/15 text-info border-info/25',
    promptable: 'bg-warning/15 text-warning border-warning/25',
    genuine: 'bg-destructive/15 text-destructive border-destructive/25',
};

const classificationBorders: Record<string, string> = {
    reframable: 'border-l-info',
    promptable: 'border-l-warning',
    genuine: 'border-l-destructive',
};

const statusIcons: Record<string, React.ReactNode> = {
    resolved: <Check className="h-4 w-4 text-green-500" />,
    acknowledged: <Check className="h-4 w-4 text-amber-500" />,
    pending_review: <Sparkles className="h-4 w-4 text-blue-500" />,
};

export default function GapActionCard({
    gap,
    gapAnalysisId,
    experiences,
    resolution,
    onResolutionChange,
}: {
    gap: Gap;
    gapAnalysisId: number;
    experiences: Experience[];
    resolution?: Resolution;
    onResolutionChange: () => void;
}) {
    const [expanded, setExpanded] = useState(false);
    const [loading, setLoading] = useState(false);
    const [selectedExperienceId, setSelectedExperienceId] = useState<string>('');
    const [reframeResult, setReframeResult] = useState<{ reframed_content: string; rationale: string } | null>(
        resolution?.status === 'pending_review' && resolution.reframe_suggestion
            ? { reframed_content: resolution.reframe_suggestion, rationale: resolution.rationale ?? '' }
            : null,
    );
    const [answerText, setAnswerText] = useState('');

    const isResolved = resolution?.status === 'resolved' || resolution?.status === 'acknowledged';
    const encodedArea = encodeURIComponent(gap.area);

    async function handleReframe() {
        if (!selectedExperienceId || loading) return;
        setLoading(true);
        try {
            const response = await axios.post(`/gap-analyses/${gapAnalysisId}/resolve/${encodedArea}/reframe`, {
                experience_id: parseInt(selectedExperienceId),
            });
            setReframeResult(response.data);
            onResolutionChange();
        } catch {
            // Error handling
        } finally {
            setLoading(false);
        }
    }

    async function handleAcceptReframe() {
        setLoading(true);
        try {
            await axios.post(`/gap-analyses/${gapAnalysisId}/resolve/${encodedArea}/accept-reframe`);
            setReframeResult(null);
            onResolutionChange();
        } finally {
            setLoading(false);
        }
    }

    async function handleRejectReframe() {
        setLoading(true);
        try {
            await axios.post(`/gap-analyses/${gapAnalysisId}/resolve/${encodedArea}/reject-reframe`);
            setReframeResult(null);
            onResolutionChange();
        } finally {
            setLoading(false);
        }
    }

    async function handleAnswer() {
        if (!answerText.trim() || loading) return;
        setLoading(true);
        try {
            await axios.post(`/gap-analyses/${gapAnalysisId}/resolve/${encodedArea}/answer`, {
                answer: answerText,
            });
            setAnswerText('');
            onResolutionChange();
        } finally {
            setLoading(false);
        }
    }

    async function handleAcknowledge() {
        setLoading(true);
        try {
            await axios.post(`/gap-analyses/${gapAnalysisId}/resolve/${encodedArea}/acknowledge`);
            onResolutionChange();
        } finally {
            setLoading(false);
        }
    }

    return (
        <Card className={isResolved ? 'border-l-4 border-l-success' : `border-l-4 ${classificationBorders[gap.classification] ?? ''}`}>
            <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        {isResolved ? statusIcons[resolution!.status] : <div className="h-4 w-4 rounded-full bg-gray-300 dark:bg-gray-600" />}
                        <CardTitle className="text-base">{gap.area}</CardTitle>
                    </div>
                    <div className="flex items-center gap-2">
                        <Badge className={classificationColors[gap.classification] ?? ''}>{gap.classification}</Badge>
                        {!isResolved && (
                            <Button variant="ghost" size="sm" onClick={() => setExpanded(!expanded)}>
                                {expanded ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                            </Button>
                        )}
                    </div>
                </div>
            </CardHeader>
            <CardContent className="space-y-3 pt-0">
                <p className="text-muted-foreground text-sm">{gap.description}</p>
                <p className="text-sm font-medium">{gap.suggestion}</p>

                {isResolved && resolution?.status === 'resolved' && resolution.answer && (
                    <div className="rounded-md border border-green-200 bg-green-50 p-3 dark:border-green-900 dark:bg-green-950">
                        <p className="text-xs font-medium text-green-700 dark:text-green-400">Your answer:</p>
                        <p className="mt-1 text-sm">{resolution.answer}</p>
                    </div>
                )}

                {isResolved && resolution?.status === 'resolved' && resolution.reframe_suggestion && (
                    <div className="rounded-md border border-green-200 bg-green-50 p-3 dark:border-green-900 dark:bg-green-950">
                        <p className="text-xs font-medium text-green-700 dark:text-green-400">Accepted reframe:</p>
                        <p className="mt-1 text-sm">{resolution.reframe_suggestion}</p>
                    </div>
                )}

                {isResolved && resolution?.status === 'acknowledged' && (
                    <div className="rounded-md border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950">
                        <p className="text-xs font-medium text-amber-700 dark:text-amber-400">Acknowledged as genuine gap</p>
                    </div>
                )}

                {expanded && !isResolved && (
                    <div className="border-t pt-3">
                        {gap.classification === 'reframable' && (
                            <ReframableAction
                                experiences={experiences}
                                selectedExperienceId={selectedExperienceId}
                                onSelectExperience={setSelectedExperienceId}
                                onReframe={handleReframe}
                                reframeResult={reframeResult}
                                onAccept={handleAcceptReframe}
                                onReject={handleRejectReframe}
                                loading={loading}
                            />
                        )}

                        {gap.classification === 'promptable' && (
                            <PromptableAction
                                suggestion={gap.suggestion}
                                answerText={answerText}
                                onAnswerChange={setAnswerText}
                                onSubmit={handleAnswer}
                                loading={loading}
                            />
                        )}

                        {gap.classification === 'genuine' && (
                            <GenuineAction onAcknowledge={handleAcknowledge} loading={loading} />
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function ReframableAction({
    experiences,
    selectedExperienceId,
    onSelectExperience,
    onReframe,
    reframeResult,
    onAccept,
    onReject,
    loading,
}: {
    experiences: Experience[];
    selectedExperienceId: string;
    onSelectExperience: (id: string) => void;
    onReframe: () => void;
    reframeResult: { reframed_content: string; rationale: string } | null;
    onAccept: () => void;
    onReject: () => void;
    loading: boolean;
}) {
    return (
        <div className="space-y-3">
            <p className="text-muted-foreground text-sm">Select an experience to reframe for this requirement:</p>
            <div className="flex gap-2">
                <Select value={selectedExperienceId} onValueChange={onSelectExperience}>
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder="Select experience..." />
                    </SelectTrigger>
                    <SelectContent>
                        {experiences.map((exp) => (
                            <SelectItem key={exp.id} value={exp.id.toString()}>
                                {exp.title} at {exp.company}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Button size="sm" onClick={onReframe} disabled={!selectedExperienceId || loading}>
                    {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Sparkles className="h-4 w-4" />}
                    <span className="ml-1">Reframe</span>
                </Button>
            </div>

            {reframeResult && (
                <div className="space-y-2 rounded-md border p-3">
                    <p className="text-xs font-medium text-blue-600 dark:text-blue-400">Suggested reframe:</p>
                    <p className="text-sm">{reframeResult.reframed_content}</p>
                    <p className="text-muted-foreground text-xs italic">{reframeResult.rationale}</p>
                    <div className="flex gap-2">
                        <Button size="sm" variant="default" onClick={onAccept} disabled={loading}>
                            {loading ? <Loader2 className="mr-1 h-3 w-3 animate-spin" /> : <Check className="mr-1 h-3 w-3" />}
                            Accept
                        </Button>
                        <Button size="sm" variant="outline" onClick={onReject} disabled={loading}>
                            <X className="mr-1 h-3 w-3" /> Reject
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}

function PromptableAction({
    suggestion,
    answerText,
    onAnswerChange,
    onSubmit,
    loading,
}: {
    suggestion: string;
    answerText: string;
    onAnswerChange: (text: string) => void;
    onSubmit: () => void;
    loading: boolean;
}) {
    return (
        <div className="space-y-3">
            <div className="rounded-md bg-yellow-50 p-3 dark:bg-yellow-950">
                <p className="text-sm font-medium text-yellow-800 dark:text-yellow-200">{suggestion}</p>
            </div>
            <Textarea
                placeholder="Describe your relevant experience..."
                value={answerText}
                onChange={(e) => onAnswerChange(e.target.value)}
                rows={3}
            />
            <Button size="sm" onClick={onSubmit} disabled={!answerText.trim() || loading}>
                {loading ? <Loader2 className="mr-1 h-3 w-3 animate-spin" /> : null}
                Save Answer
            </Button>
        </div>
    );
}

function GenuineAction({ onAcknowledge, loading }: { onAcknowledge: () => void; loading: boolean }) {
    return (
        <div className="space-y-3">
            <p className="text-muted-foreground text-sm">
                This appears to be a genuine gap. Acknowledging it helps focus your preparation strategy — consider addressing it in your cover letter or
                interview talking points.
            </p>
            <Button size="sm" variant="outline" onClick={onAcknowledge} disabled={loading}>
                {loading ? <Loader2 className="mr-1 h-3 w-3 animate-spin" /> : <Check className="mr-1 h-3 w-3" />}
                Acknowledge Gap
            </Button>
        </div>
    );
}
