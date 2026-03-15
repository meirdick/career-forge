import { router } from '@inertiajs/react';
import axios from 'axios';
import { ArrowUp, Check, CheckCircle2, MessageCircle, Sparkles } from 'lucide-react';
import { useCallback, useEffect, useImperativeHandle, useRef, useState } from 'react';
import ReactMarkdown from 'react-markdown';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';

type ChatMessage = { role: 'user' | 'assistant'; content: string; toolActions?: string[] };

type PipelineContext = {
    step: 'job_posting' | 'gap_analysis' | 'resume_builder' | 'application';
    pipelineKey: string;
};

type StepConfig = {
    label: string;
    subtitle: string;
    placeholder: string;
    prompts: string[];
};

const stepConfig: Record<string, StepConfig> = {
    job_posting: {
        label: 'Job Posting Assistant',
        subtitle: 'Analyzes the role and builds your candidate profile',
        placeholder: 'Ask about this job posting...',
        prompts: [
            'What are the key requirements for this role?',
            'How well does my profile match this job?',
            'What skills should I highlight?',
        ],
    },
    gap_analysis: {
        label: 'Gap Analysis Coach',
        subtitle: 'Identifies skill gaps and recommends evidence',
        placeholder: 'Ask about your gaps...',
        prompts: [
            'What are my most critical gaps?',
            'How can I address my weakest areas?',
            'Which gaps should I prioritize first?',
        ],
    },
    resume_builder: {
        label: 'Resume Assistant',
        subtitle: 'Helps tailor your resume to the role',
        placeholder: 'Ask about your resume...',
        prompts: [
            'How can I improve my resume for this role?',
            'What achievements should I highlight?',
            'Review my resume bullet points',
        ],
    },
    application: {
        label: 'Application Assistant',
        subtitle: 'Guides you through the application process',
        placeholder: 'Ask about your application...',
        prompts: [
            'Help me write a cover letter',
            'What should I emphasize in my application?',
            'How can I stand out as a candidate?',
        ],
    },
};

function AiAvatar({ size = 'md' }: { size?: 'sm' | 'md' }) {
    const sizeClasses = size === 'sm' ? 'size-6' : 'size-8';
    const iconClasses = size === 'sm' ? 'size-3' : 'size-4';

    return (
        <div className={`bg-primary/10 text-primary relative flex ${sizeClasses} shrink-0 items-center justify-center rounded-full`}>
            <Sparkles className={iconClasses} />
        </div>
    );
}

function ToolActionsCard({ actions }: { actions: string[] }) {
    return (
        <div className="border-success/30 bg-success/5 mt-2 rounded-lg border px-3 py-2.5">
            <div className="text-success mb-1.5 flex items-center gap-1.5 text-xs font-medium">
                <CheckCircle2 className="size-3.5" />
                <span>
                    {actions.length} {actions.length === 1 ? 'change' : 'changes'} applied
                </span>
            </div>
            <ul className="space-y-1">
                {actions.map((action, i) => (
                    <li key={i} className="text-muted-foreground flex items-start gap-1.5 text-xs">
                        <Check className="text-success mt-0.5 size-3 shrink-0" />
                        <span>{action}</span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

function ThinkingIndicator() {
    return (
        <div className="flex items-start gap-2.5 px-5 py-1">
            <AiAvatar size="sm" />
            <div className="flex items-center gap-2 pt-1">
                <div className="flex gap-1">
                    <span className="bg-muted-foreground/40 size-1.5 animate-bounce rounded-full [animation-delay:0ms]" />
                    <span className="bg-muted-foreground/40 size-1.5 animate-bounce rounded-full [animation-delay:150ms]" />
                    <span className="bg-muted-foreground/40 size-1.5 animate-bounce rounded-full [animation-delay:300ms]" />
                </div>
                <span className="text-muted-foreground text-xs">Thinking...</span>
            </div>
        </div>
    );
}

function EmptyState({ config, onPromptClick }: { config: StepConfig; onPromptClick: (prompt: string) => void }) {
    return (
        <div className="flex flex-1 flex-col items-center justify-center px-6">
            <div className="bg-primary/10 text-primary mb-4 flex size-12 items-center justify-center rounded-full">
                <Sparkles className="size-6" />
            </div>
            <h3 className="text-foreground mb-1 text-sm font-semibold">{config.label}</h3>
            <p className="text-muted-foreground mb-6 text-center text-xs">{config.subtitle}</p>
            <div className="flex w-full flex-col gap-2">
                {config.prompts.map((prompt, i) => (
                    <button
                        key={i}
                        onClick={() => onPromptClick(prompt)}
                        className="text-muted-foreground hover:bg-muted hover:text-foreground rounded-lg border px-3 py-2.5 text-left text-sm transition-colors"
                    >
                        {prompt}
                    </button>
                ))}
            </div>
        </div>
    );
}

function ChatInput({
    value,
    onChange,
    onSend,
    placeholder,
    disabled,
}: {
    value: string;
    onChange: (value: string) => void;
    onSend: () => void;
    placeholder: string;
    disabled: boolean;
}) {
    const textareaRef = useRef<HTMLTextAreaElement>(null);

    useEffect(() => {
        const el = textareaRef.current;
        if (el) {
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 96) + 'px';
        }
    }, [value]);

    return (
        <div className="border-t bg-background px-5 py-4">
            <div className="relative">
                <textarea
                    ref={textareaRef}
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            if (!disabled) {
                                onSend();
                            }
                        }
                    }}
                    placeholder={placeholder}
                    rows={1}
                    className="border-input bg-muted/50 placeholder:text-muted-foreground focus:ring-ring w-full resize-none rounded-xl py-3 pr-11 pl-3.5 text-sm focus:ring-1 focus:outline-none"
                />
                <button
                    onClick={onSend}
                    disabled={disabled || !value.trim()}
                    className="bg-primary text-primary-foreground hover:bg-primary/90 absolute right-2 bottom-2 flex size-7 items-center justify-center rounded-lg transition-colors disabled:opacity-30"
                >
                    <ArrowUp className="size-4" />
                </button>
            </div>
        </div>
    );
}

export type PipelineAssistantHandle = {
    openWithMessage: (message: string) => void;
};

export default function PipelineAssistantPanel({ context, ref }: { context: PipelineContext; ref?: React.Ref<PipelineAssistantHandle> }) {
    const [open, setOpen] = useState(false);
    const [sessionId, setSessionId] = useState<number | null>(null);
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [input, setInput] = useState('');
    const [sending, setSending] = useState(false);
    const [resolving, setResolving] = useState(false);
    const [resolved, setResolved] = useState(false);
    const [pendingMessage, setPendingMessage] = useState<string | null>(null);
    const chatEndRef = useRef<HTMLDivElement>(null);

    const config = stepConfig[context.step];

    useImperativeHandle(ref, () => ({
        openWithMessage(message: string) {
            setPendingMessage(message);
            setOpen(true);
            if (!resolved) {
                resolveSession();
            }
        },
    }));

    useEffect(() => {
        chatEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, sending]);

    useEffect(() => {
        if (pendingMessage && sessionId && !sending) {
            const msg = pendingMessage;
            setPendingMessage(null);
            sendMessage(msg);
        }
    }, [pendingMessage, sessionId, sending]);

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

    const sendMessage = useCallback(
        async (messageOverride?: string) => {
            const userMessage = messageOverride?.trim() || input.trim();
            if (!userMessage || sending || !sessionId) return;

            if (!messageOverride) {
                setInput('');
            }
            setMessages((prev) => [...prev, { role: 'user', content: userMessage }]);
            setSending(true);

            try {
                const csrfToken = document.cookie
                    .split('; ')
                    .find((row) => row.startsWith('XSRF-TOKEN='))
                    ?.split('=')[1];

                const response = await fetch(`/pipeline-chat/${sessionId}/chat`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'text/event-stream',
                        'X-XSRF-TOKEN': csrfToken ? decodeURIComponent(csrfToken) : '',
                    },
                    body: JSON.stringify({ message: userMessage }),
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const reader = response.body!.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                let assistantAdded = false;

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const parts = buffer.split('\n\n');
                    buffer = parts.pop() ?? '';

                    for (const part of parts) {
                        if (!part.startsWith('data: ')) continue;

                        let data;
                        try {
                            data = JSON.parse(part.slice(6));
                        } catch {
                            continue;
                        }

                        if (data.type === 'text') {
                            if (!assistantAdded) {
                                setMessages((prev) => [...prev, { role: 'assistant', content: data.delta }]);
                                assistantAdded = true;
                            } else {
                                setMessages((prev) => {
                                    const updated = [...prev];
                                    const last = updated[updated.length - 1];
                                    if (last.role === 'assistant') {
                                        updated[updated.length - 1] = { ...last, content: last.content + data.delta };
                                    }
                                    return updated;
                                });
                            }
                        } else if (data.type === 'done') {
                            const toolActions: string[] = data.tool_actions ?? [];
                            if (toolActions.length > 0) {
                                setMessages((prev) => {
                                    const updated = [...prev];
                                    const last = updated[updated.length - 1];
                                    if (last.role === 'assistant') {
                                        updated[updated.length - 1] = { ...last, toolActions };
                                    }
                                    return updated;
                                });
                                router.reload();
                            }
                        }
                    }
                }

                if (!assistantAdded) {
                    setMessages((prev) => [...prev, { role: 'assistant', content: 'No response received. Please try again.' }]);
                }
            } catch {
                setMessages((prev) => {
                    const last = prev[prev.length - 1];
                    if (last?.role === 'assistant') {
                        return prev;
                    }
                    return [...prev, { role: 'assistant', content: 'Sorry, there was an error. Please try again.' }];
                });
            } finally {
                setSending(false);
            }
        },
        [input, sending, sessionId],
    );

    function handlePromptClick(prompt: string) {
        sendMessage(prompt);
    }

    return (
        <>
            <Button
                onClick={handleOpen}
                className="fixed right-6 bottom-6 z-50 h-12 gap-2 rounded-full px-4 shadow-elevated animate-fade-in-up"
                size="lg"
            >
                <MessageCircle className="h-5 w-5" />
                <span className="hidden sm:inline">{config.label}</span>
            </Button>

            <Sheet open={open} onOpenChange={setOpen}>
                <SheetContent side="right" className="flex w-full flex-col gap-0 sm:max-w-lg">
                    <SheetHeader className="border-b px-5 pb-3 pt-5 shadow-xs">
                        <div className="flex items-center gap-3">
                            <div className="relative">
                                <AiAvatar size="md" />
                                <span className="bg-success absolute -right-0.5 -bottom-0.5 size-2.5 rounded-full ring-2 ring-white dark:ring-zinc-950" />
                            </div>
                            <div>
                                <SheetTitle className="text-sm">{config.label}</SheetTitle>
                                <SheetDescription className="text-xs">{config.subtitle}</SheetDescription>
                            </div>
                        </div>
                    </SheetHeader>

                    <div className="flex flex-1 flex-col overflow-hidden">
                        {resolving && (
                            <div className="flex flex-1 items-center justify-center">
                                <div className="flex items-center gap-2">
                                    <div className="flex gap-1">
                                        <span className="bg-muted-foreground/40 size-1.5 animate-bounce rounded-full [animation-delay:0ms]" />
                                        <span className="bg-muted-foreground/40 size-1.5 animate-bounce rounded-full [animation-delay:150ms]" />
                                        <span className="bg-muted-foreground/40 size-1.5 animate-bounce rounded-full [animation-delay:300ms]" />
                                    </div>
                                    <span className="text-muted-foreground text-sm">Loading conversation...</span>
                                </div>
                            </div>
                        )}

                        {!resolving && resolved && messages.length === 0 && !sending && (
                            <EmptyState config={config} onPromptClick={handlePromptClick} />
                        )}

                        {(messages.length > 0 || sending) && (
                            <div className="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                                {messages.map((msg, i) => (
                                    <div key={i}>
                                        {msg.role === 'user' ? (
                                            <div className="flex justify-end">
                                                <div className="bg-primary text-primary-foreground max-w-[85%] rounded-2xl px-3.5 py-2 text-sm">
                                                    <p className="whitespace-pre-wrap">{msg.content}</p>
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="flex items-start gap-2.5">
                                                <AiAvatar size="sm" />
                                                <div className="max-w-[85%]">
                                                    <div className="prose prose-sm dark:prose-invert max-w-none text-sm">
                                                        <ReactMarkdown>{msg.content}</ReactMarkdown>
                                                    </div>
                                                    {msg.toolActions && msg.toolActions.length > 0 && (
                                                        <ToolActionsCard actions={msg.toolActions} />
                                                    )}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                ))}

                                {sending && messages[messages.length - 1]?.role !== 'assistant' && <ThinkingIndicator />}
                                <div ref={chatEndRef} />
                            </div>
                        )}
                    </div>

                    <ChatInput
                        value={input}
                        onChange={setInput}
                        onSend={() => sendMessage()}
                        placeholder={config.placeholder}
                        disabled={sending || !sessionId}
                    />
                </SheetContent>
            </Sheet>
        </>
    );
}
