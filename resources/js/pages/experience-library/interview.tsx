import { Head } from '@inertiajs/react';
import axios from 'axios';
import { Loader2, MessageCircle, Send } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import InterviewController from '@/actions/App/Http/Controllers/ExperienceLibrary/InterviewController';
import { index as interviewIndex } from '@/routes/interview';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Experience Library', href: '/experience-library' },
    { title: 'Interview', href: interviewIndex() },
];

type Message = {
    role: 'user' | 'assistant';
    content: string;
};

export default function Interview() {
    const [messages, setMessages] = useState<Message[]>([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [conversationId, setConversationId] = useState<string | null>(null);
    const [started, setStarted] = useState(false);
    const messagesEndRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, loading]);

    async function sendMessage(text?: string) {
        const message = text || input.trim();
        if (!message || loading) return;

        setInput('');
        setMessages((prev) => [...prev, { role: 'user', content: message }]);
        setLoading(true);

        try {
            const { data } = await axios.post(InterviewController.chat().url, {
                message,
                conversation_id: conversationId,
            });

            setMessages((prev) => [...prev, { role: 'assistant', content: data.message }]);
            setConversationId(data.conversation_id);
        } finally {
            setLoading(false);
        }
    }

    function startInterview() {
        setStarted(true);
        sendMessage("Hi! I'd like to build out my professional experience library. Can you interview me about my career?");
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Career Interview" />

            <div className="mx-auto max-w-3xl space-y-6 p-4">
                <Heading
                    title="Career Interview"
                    description="Have a conversation about your career to discover and capture your professional experience"
                />

                {!started ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-4 py-12">
                            <MessageCircle className="h-12 w-12 text-muted-foreground" />
                            <p className="text-center text-muted-foreground max-w-md">
                                Start a guided interview to uncover your skills, accomplishments, and projects through natural conversation. The AI will ask questions about your career and help you articulate your professional experience.
                            </p>
                            <Button onClick={startInterview}>
                                <MessageCircle className="mr-2 h-4 w-4" /> Start Interview
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="space-y-4 pt-6">
                            <div className="max-h-[500px] space-y-3 overflow-y-auto pr-2">
                                {messages.map((msg, i) => (
                                    <div
                                        key={i}
                                        className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}
                                    >
                                        <div
                                            className={`max-w-[80%] rounded-lg px-4 py-2 text-sm whitespace-pre-wrap ${
                                                msg.role === 'user'
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'bg-muted'
                                            }`}
                                        >
                                            {msg.content}
                                        </div>
                                    </div>
                                ))}
                                {loading && (
                                    <div className="flex justify-start">
                                        <div className="bg-muted rounded-lg px-4 py-2">
                                            <Loader2 className="h-4 w-4 animate-spin" />
                                        </div>
                                    </div>
                                )}
                                <div ref={messagesEndRef} />
                            </div>

                            <div className="flex gap-2">
                                <Input
                                    value={input}
                                    onChange={(e) => setInput(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && !e.shiftKey && sendMessage()}
                                    placeholder="Type your response..."
                                    disabled={loading}
                                />
                                <Button onClick={() => sendMessage()} disabled={loading || !input.trim()}>
                                    <Send className="h-4 w-4" />
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
