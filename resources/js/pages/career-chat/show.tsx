import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import { Briefcase, ChevronLeft, Loader2, MessageCircle, Package, Send } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import ReactMarkdown from 'react-markdown';
import ExtractionReviewPanel from '@/components/extraction-review-panel';
import type {ExtractionData} from '@/components/extraction-review-panel';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import VoiceInputButton from '@/components/voice-input-button';
import AppLayout from '@/layouts/app-layout';
import { chat, extract, index as careerChatIndex, show } from '@/routes/career-chat';
import type { BreadcrumbItem } from '@/types';

type Message = {
    role: 'user' | 'assistant';
    content: string;
};

type ChatSessionData = {
    id: number;
    title: string;
    mode: string;
    status: string;
    job_posting: { id: number; title: string; company: string } | null;
};

type SessionSummary = {
    id: number;
    title: string;
    mode: string;
    status: string;
    job_posting: { id: number; title: string; company: string } | null;
    updated_at: string;
};

export default function CareerChatShow({
    chatSession,
    messages: initialMessages,
    sessions,
}: {
    chatSession: ChatSessionData;
    messages: Message[];
    sessions: SessionSummary[];
}) {
    const [messages, setMessages] = useState<Message[]>(initialMessages);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [extracting, setExtracting] = useState(false);
    const [extractionData, setExtractionData] = useState<ExtractionData | null>(null);
    const [sheetOpen, setSheetOpen] = useState(false);
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const messagesEndRef = useRef<HTMLDivElement>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Career Chat', href: careerChatIndex() },
        { title: chatSession.title, href: show(chatSession.id) },
    ];

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, loading]);

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        if (params.get('extract') === '1' && initialMessages.length >= 2 && !extractionData) {
            handleExtract();
            // Clean up the URL
            window.history.replaceState({}, '', window.location.pathname);
        }
    }, []);

    async function sendMessage(text?: string) {
        const message = text || input.trim();
        if (!message || loading) return;

        setInput('');
        setMessages((prev) => [...prev, { role: 'user', content: message }]);
        setLoading(true);

        try {
            const { data } = await axios.post(chat(chatSession.id).url, { message });
            setMessages((prev) => [...prev, { role: 'assistant', content: data.message }]);
        } finally {
            setLoading(false);
        }
    }

    async function handleExtract() {
        setExtracting(true);
        try {
            const { data } = await axios.post(extract(chatSession.id).url);
            setExtractionData(data);
            setSheetOpen(true);
        } finally {
            setExtracting(false);
        }
    }

    function startChat() {
        sendMessage("Hi! I'd like to explore and capture my professional experience. Can you help me articulate my career accomplishments?");
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={chatSession.title} />

            <div className="flex h-[calc(100vh-4rem)] overflow-hidden">
                {/* Session sidebar */}
                <aside
                    className={`border-r bg-background ${sidebarOpen ? 'w-72' : 'hidden'} flex flex-col overflow-y-auto lg:flex lg:w-72`}
                >
                    <div className="flex items-center justify-between border-b p-3">
                        <h3 className="text-sm font-semibold">Sessions</h3>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={careerChatIndex().url}>
                                View All
                            </Link>
                        </Button>
                    </div>
                    <div className="flex-1 space-y-1 p-2">
                        {sessions.map((s) => (
                            <Link
                                key={s.id}
                                href={show(s.id).url}
                                className={`block rounded-md px-3 py-2 text-sm transition-colors hover:bg-accent ${
                                    s.id === chatSession.id ? 'bg-accent font-medium' : ''
                                }`}
                            >
                                <span className="line-clamp-1">{s.title}</span>
                                <span className="text-muted-foreground text-xs">{s.updated_at}</span>
                            </Link>
                        ))}
                    </div>
                </aside>

                {/* Main chat area */}
                <div className="flex flex-1 flex-col">
                    {/* Header */}
                    <div className="flex items-center gap-3 border-b px-4 py-3">
                        <Button variant="ghost" size="icon" className="lg:hidden" onClick={() => setSidebarOpen(!sidebarOpen)}>
                            <ChevronLeft className="h-4 w-4" />
                        </Button>
                        <div className="min-w-0 flex-1">
                            <div className="flex items-center gap-2">
                                <h2 className="truncate text-base font-semibold">{chatSession.title}</h2>
                                {chatSession.mode === 'job_specific' && (
                                    <Badge variant="secondary" className="shrink-0">
                                        <Briefcase className="mr-1 h-3 w-3" /> Job-Specific
                                    </Badge>
                                )}
                            </div>
                            {chatSession.job_posting && (
                                <p className="text-muted-foreground truncate text-sm">
                                    {chatSession.job_posting.title} at {chatSession.job_posting.company}
                                </p>
                            )}
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleExtract}
                            disabled={extracting || messages.length < 2}
                            title="Extract Experiences"
                        >
                            {extracting ? (
                                <Loader2 className="mr-1 h-3 w-3 animate-spin" />
                            ) : (
                                <Package className="mr-1 h-3 w-3" />
                            )}
                            {extracting ? 'Extracting...' : 'Extract'}
                        </Button>
                    </div>

                    {/* Messages */}
                    <div className="flex-1 overflow-y-auto p-4">
                        <div className="mx-auto max-w-3xl space-y-4">
                            {messages.length === 0 ? (
                                <Card>
                                    <CardContent className="flex flex-col items-center gap-4 py-12">
                                        <MessageCircle className="text-muted-foreground h-12 w-12" />
                                        <p className="text-muted-foreground max-w-md text-center">
                                            {chatSession.mode === 'job_specific'
                                                ? 'Start a coaching session focused on your target role. The AI will help you uncover and articulate relevant experience.'
                                                : 'Start a career coaching session to discover and capture your professional experience.'}
                                        </p>
                                        <Button onClick={startChat}>
                                            <MessageCircle className="mr-2 h-4 w-4" /> Start Conversation
                                        </Button>
                                    </CardContent>
                                </Card>
                            ) : (
                                <>
                                    {messages.map((msg, i) => (
                                        <div key={i} className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                                            {msg.role === 'user' ? (
                                                <div className="bg-primary text-primary-foreground max-w-[80%] rounded-lg px-4 py-2 text-sm whitespace-pre-wrap">
                                                    {msg.content}
                                                </div>
                                            ) : (
                                                <div className="prose prose-sm dark:prose-invert bg-muted max-w-[80%] rounded-lg px-4 py-2">
                                                    <ReactMarkdown>{msg.content}</ReactMarkdown>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                    {loading && (
                                        <div className="flex justify-start">
                                            <div className="bg-muted rounded-lg px-4 py-2">
                                                <Loader2 className="h-4 w-4 animate-spin" />
                                            </div>
                                        </div>
                                    )}
                                </>
                            )}
                            <div ref={messagesEndRef} />
                        </div>
                    </div>

                    {/* Input area */}
                    {messages.length > 0 && (
                        <div className="border-t px-4 py-3">
                            <div className="mx-auto max-w-3xl space-y-2">
                                <div className="flex gap-2">
                                    <VoiceInputButton
                                        onTranscript={(text) => setInput((prev) => (prev ? prev + ' ' + text : text))}
                                        disabled={loading}
                                    />
                                    <Input
                                        value={input}
                                        onChange={(e) => setInput(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && !e.shiftKey && sendMessage()}
                                        placeholder="Type your response..."
                                        className="flex-1"
                                    />
                                    <Button onClick={() => sendMessage()} disabled={loading || !input.trim()}>
                                        <Send className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {extractionData && (
                <ExtractionReviewPanel
                    data={extractionData}
                    chatSessionId={chatSession.id}
                    open={sheetOpen}
                    onClose={() => setSheetOpen(false)}
                />
            )}
        </AppLayout>
    );
}
