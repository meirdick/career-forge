import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { CheckCircle, Loader2, MessageCircle, Send, Target, XCircle } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import ReactMarkdown from 'react-markdown';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Strength = { area: string; evidence: string; relevance: string };
type Gap = { area: string; description: string; classification: string; suggestion: string };

type GapAnalysis = {
    id: number;
    strengths: Strength[];
    gaps: Gap[];
    overall_match_score: number | null;
    ai_summary: string | null;
    is_finalized: boolean;
    ideal_candidate_profile: {
        job_posting: {
            id: number;
            title: string | null;
            company: string | null;
        };
    };
};

const classificationColors: Record<string, string> = {
    reframable: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    promptable: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    genuine: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
};

type ChatMessage = { role: 'user' | 'assistant'; content: string };

export default function ShowGapAnalysis({ gapAnalysis }: { gapAnalysis: GapAnalysis }) {
    const posting = gapAnalysis.ideal_candidate_profile.job_posting;
    const isAnalyzing = gapAnalysis.strengths.length === 0 && gapAnalysis.gaps.length === 0 && !gapAnalysis.ai_summary;

    const [showChat, setShowChat] = useState(false);
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [input, setInput] = useState('');
    const [sending, setSending] = useState(false);
    const [conversationId, setConversationId] = useState<string | null>(null);
    const chatEndRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        chatEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    async function sendMessage() {
        if (!input.trim() || sending) return;
        const userMessage = input.trim();
        setInput('');
        setMessages((prev) => [...prev, { role: 'user', content: userMessage }]);
        setSending(true);

        try {
            const response = await axios.post(`/gap-analyses/${gapAnalysis.id}/chat`, {
                message: userMessage,
                conversation_id: conversationId,
            });
            setMessages((prev) => [...prev, { role: 'assistant', content: response.data.message }]);
            setConversationId(response.data.conversation_id);
        } catch {
            setMessages((prev) => [...prev, { role: 'assistant', content: 'Sorry, there was an error. Please try again.' }]);
        } finally {
            setSending(false);
        }
    }

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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gap Analysis" />

            <div className="mx-auto max-w-3xl space-y-6 p-4">
                <div className="flex items-start justify-between">
                    <Heading
                        title="Gap Analysis"
                        description={`${posting.title ?? 'Untitled'} at ${posting.company ?? 'Unknown Company'}`}
                    />
                    {!gapAnalysis.is_finalized && !isAnalyzing && (
                        <div className="flex gap-2">
                            <Button onClick={() => router.post(`/gap-analyses/${gapAnalysis.id}/finalize`)}>
                                Finalize
                            </Button>
                            <Button variant="outline" onClick={() => router.post(`/gap-analyses/${gapAnalysis.id}/resume`)}>
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
                            <div className="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                                <Target className="text-primary h-8 w-8" />
                            </div>
                            <div>
                                <p className="text-3xl font-bold">{gapAnalysis.overall_match_score}%</p>
                                <p className="text-muted-foreground text-sm">Overall match score</p>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {gapAnalysis.ai_summary && (
                    <Card>
                        <CardHeader><CardTitle className="text-base">Summary</CardTitle></CardHeader>
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
                                    <p className="text-xs text-muted-foreground italic">{s.relevance}</p>
                                </CardContent>
                            </Card>
                        ))}
                    </>
                )}

                {gapAnalysis.gaps.length > 0 && (
                    <>
                        <Separator />
                        <h2 className="text-lg font-semibold">Gaps ({gapAnalysis.gaps.length})</h2>
                        {gapAnalysis.gaps.map((g, i) => (
                            <Card key={i}>
                                <CardHeader className="pb-2">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <XCircle className="h-4 w-4 text-red-400" />
                                            <CardTitle className="text-base">{g.area}</CardTitle>
                                        </div>
                                        <Badge className={classificationColors[g.classification] ?? ''}>{g.classification}</Badge>
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-1 pt-0">
                                    <p className="text-muted-foreground text-sm">{g.description}</p>
                                    <p className="text-sm font-medium">{g.suggestion}</p>
                                </CardContent>
                            </Card>
                        ))}
                    </>
                )}

                {gapAnalysis.gaps.length > 0 && !gapAnalysis.is_finalized && !isAnalyzing && (
                    <>
                        <Separator />
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold">Gap Closure Coach</h2>
                            <Button variant={showChat ? 'default' : 'outline'} size="sm" onClick={() => setShowChat(!showChat)}>
                                <MessageCircle className="mr-1 h-4 w-4" /> {showChat ? 'Hide Chat' : 'Start Coaching'}
                            </Button>
                        </div>

                        {showChat && (
                            <Card>
                                <CardContent className="pt-4">
                                    <div className="mb-3 max-h-80 space-y-3 overflow-y-auto">
                                        {messages.length === 0 && (
                                            <p className="text-muted-foreground text-sm">
                                                Start a conversation with the Gap Closure Coach to address your gaps. The coach will ask questions to uncover experience you may have overlooked.
                                            </p>
                                        )}
                                        {messages.map((msg, i) => (
                                            <div key={i} className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                                                {msg.role === 'user' ? (
                                                    <div className="bg-primary text-primary-foreground max-w-[80%] rounded-lg px-3 py-2 text-sm">
                                                        <p className="whitespace-pre-wrap">{msg.content}</p>
                                                    </div>
                                                ) : (
                                                    <div className="prose prose-sm dark:prose-invert bg-muted max-w-[80%] rounded-lg px-3 py-2">
                                                        <ReactMarkdown>{msg.content}</ReactMarkdown>
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                        {sending && (
                                            <div className="flex justify-start">
                                                <div className="bg-muted rounded-lg px-3 py-2">
                                                    <Loader2 className="h-4 w-4 animate-spin" />
                                                </div>
                                            </div>
                                        )}
                                        <div ref={chatEndRef} />
                                    </div>
                                    <div className="flex gap-2">
                                        <Input
                                            value={input}
                                            onChange={(e) => setInput(e.target.value)}
                                            onKeyDown={(e) => e.key === 'Enter' && !e.shiftKey && sendMessage()}
                                            placeholder="Tell me about your experience..."
                                            disabled={sending}
                                        />
                                        <Button size="sm" onClick={sendMessage} disabled={sending || !input.trim()}>
                                            <Send className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </>
                )}

                {gapAnalysis.is_finalized && (
                    <Badge variant="secondary" className="mt-4">Finalized</Badge>
                )}
            </div>
        </AppLayout>
    );
}
