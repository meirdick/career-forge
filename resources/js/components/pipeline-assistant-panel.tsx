import { router } from '@inertiajs/react';
import axios from 'axios';
import { CheckCircle2, Loader2, MessageCircle, Send } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import ReactMarkdown from 'react-markdown';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet';

type ChatMessage = { role: 'user' | 'assistant'; content: string; toolActions?: string[] };

type PipelineContext = {
    step: 'job_posting' | 'gap_analysis' | 'resume_builder' | 'application';
    pipelineKey: string;
};

const stepLabels: Record<string, string> = {
    job_posting: 'Job Posting Assistant',
    gap_analysis: 'Gap Analysis Coach',
    resume_builder: 'Resume Assistant',
    application: 'Application Assistant',
};

const stepTargets: Record<string, string> = {
    job_posting: 'candidate profile',
    gap_analysis: 'gap analysis',
    resume_builder: 'resume',
    application: 'application',
};

export default function PipelineAssistantPanel({ context }: { context: PipelineContext }) {
    const [open, setOpen] = useState(false);
    const [sessionId, setSessionId] = useState<number | null>(null);
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [input, setInput] = useState('');
    const [sending, setSending] = useState(false);
    const [resolving, setResolving] = useState(false);
    const [resolved, setResolved] = useState(false);
    const chatEndRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        chatEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    async function resolveSession() {
        if (resolved || resolving) return;
        setResolving(true);
        try {
            const response = await axios.post('/pipeline-chat/resolve', {
                step: context.step,
                pipeline_key: context.pipelineKey,
            });
            setSessionId(response.data.session_id);
            setMessages(response.data.messages);
            setResolved(true);
        } finally {
            setResolving(false);
        }
    }

    function handleOpen() {
        setOpen(true);
        if (!resolved) {
            resolveSession();
        }
    }

    async function sendMessage() {
        if (!input.trim() || sending || !sessionId) return;
        const userMessage = input.trim();
        setInput('');
        setMessages((prev) => [...prev, { role: 'user', content: userMessage }]);
        setSending(true);

        try {
            const response = await axios.post(`/pipeline-chat/${sessionId}/chat`, {
                message: userMessage,
            });

            const toolActions: string[] = response.data.tool_actions ?? [];

            setMessages((prev) => [...prev, { role: 'assistant', content: response.data.message, toolActions }]);

            if (toolActions.length > 0) {
                router.reload();
            }
        } catch {
            setMessages((prev) => [...prev, { role: 'assistant', content: 'Sorry, there was an error. Please try again.' }]);
        } finally {
            setSending(false);
        }
    }

    return (
        <>
            <Button
                onClick={handleOpen}
                className="fixed right-6 bottom-6 z-50 h-12 gap-2 rounded-full px-4 shadow-elevated animate-fade-in-up"
                size="lg"
            >
                <MessageCircle className="h-5 w-5" />
                <span className="hidden sm:inline">{stepLabels[context.step]}</span>
            </Button>

            <Sheet open={open} onOpenChange={setOpen}>
                <SheetContent side="right" className="flex w-full flex-col sm:max-w-md">
                    <SheetHeader>
                        <SheetTitle>{stepLabels[context.step]}</SheetTitle>
                    </SheetHeader>

                    <div className="flex flex-1 flex-col overflow-hidden">
                        <div className="flex-1 space-y-3 overflow-y-auto p-4">
                            {resolving && (
                                <div className="flex items-center gap-2 py-4">
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    <span className="text-muted-foreground text-sm">Loading conversation...</span>
                                </div>
                            )}

                            {!resolving && messages.length === 0 && (
                                <p className="text-muted-foreground text-sm">
                                    Ask me anything about this step. I have context about your current progress and can help you move forward.
                                </p>
                            )}

                            {messages.map((msg, i) => (
                                <div key={i} className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                                    {msg.role === 'user' ? (
                                        <div className="bg-primary text-primary-foreground max-w-[85%] rounded-lg px-3 py-2 text-sm">
                                            <p className="whitespace-pre-wrap">{msg.content}</p>
                                        </div>
                                    ) : (
                                        <div className="max-w-[85%] space-y-1.5">
                                            <div className="prose prose-sm dark:prose-invert bg-muted rounded-lg px-3 py-2">
                                                <ReactMarkdown>{msg.content}</ReactMarkdown>
                                            </div>
                                            {msg.toolActions && msg.toolActions.length > 0 && (
                                                <div className="flex items-center gap-1.5 px-1">
                                                    <CheckCircle2 className="h-3.5 w-3.5 text-green-600 dark:text-green-400" />
                                                    <span className="text-muted-foreground text-xs">
                                                        Changes applied to your {stepTargets[context.step]}
                                                    </span>
                                                </div>
                                            )}
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

                        <div className="border-t p-4">
                            <div className="flex gap-2">
                                <Input
                                    value={input}
                                    onChange={(e) => setInput(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && !e.shiftKey && sendMessage()}
                                    placeholder="Ask a question..."
                                    disabled={sending || !sessionId}
                                />
                                <Button size="sm" onClick={sendMessage} disabled={sending || !input.trim() || !sessionId}>
                                    <Send className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </SheetContent>
            </Sheet>
        </>
    );
}
