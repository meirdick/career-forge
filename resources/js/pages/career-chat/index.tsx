import { Head, Link, useForm } from '@inertiajs/react';
import { Briefcase, MessageCircle, Package, Plus } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import EmptyState from '@/components/empty-state';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { index as careerChatIndex, show, store } from '@/routes/career-chat';
import type { BreadcrumbItem } from '@/types';

type ChatSessionSummary = {
    id: number;
    title: string;
    mode: string;
    status: string;
    has_conversation: boolean;
    job_posting: { id: number; title: string; company: string } | null;
    updated_at: string;
};

type JobPostingSummary = {
    id: number;
    title: string;
    company: string;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Career Chat', href: careerChatIndex() }];

export default function CareerChatIndex({
    sessions,
    jobPostings,
}: {
    sessions: ChatSessionSummary[];
    jobPostings: JobPostingSummary[];
}) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const form = useForm({
        title: '',
        mode: 'general',
        job_posting_id: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.post(store().url, {
            onSuccess: () => setDialogOpen(false),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Career Chat" />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                <div className="flex items-start justify-between">
                    <Heading title="Career Chat" description="AI-powered career coaching to discover, articulate, and capture your professional experience" />
                    <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                        <DialogTrigger asChild>
                            <Button>
                                <Plus className="mr-1 h-4 w-4" /> New Chat
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Start a New Chat</DialogTitle>
                            </DialogHeader>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="title">Title (optional)</Label>
                                    <Input
                                        id="title"
                                        value={form.data.title}
                                        onChange={(e) => form.setData('title', e.target.value)}
                                        placeholder="e.g. Leadership experiences"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label>Mode</Label>
                                    <Select value={form.data.mode} onValueChange={(v) => form.setData('mode', v)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="general">General Career Coaching</SelectItem>
                                            <SelectItem value="job_specific">Job-Specific Coaching</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                {form.data.mode === 'job_specific' && (
                                    <div className="space-y-2">
                                        <Label>Job Posting</Label>
                                        <Select
                                            value={form.data.job_posting_id}
                                            onValueChange={(v) => form.setData('job_posting_id', v)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select a job posting" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {jobPostings.map((jp) => (
                                                    <SelectItem key={jp.id} value={String(jp.id)}>
                                                        {jp.title} at {jp.company}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                )}

                                <Button type="submit" disabled={form.processing} className="w-full">
                                    {form.processing ? 'Creating...' : 'Start Chat'}
                                </Button>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {sessions.length === 0 ? (
                    <EmptyState
                        icon={MessageCircle}
                        title="No chat sessions yet"
                        description="Start a career coaching session to uncover your skills, accomplishments, and projects through natural conversation."
                    />
                ) : (
                    <div className="space-y-3">
                        {sessions.map((session) => (
                            <Card key={session.id} className="transition-colors hover:bg-accent/50">
                                <Link href={show(session.id).url} className="block">
                                    <CardHeader className="pb-2">
                                        <div className="flex items-start justify-between">
                                            <CardTitle className="text-base">{session.title}</CardTitle>
                                            <div className="flex gap-2">
                                                {session.mode === 'job_specific' && (
                                                    <Badge variant="secondary">
                                                        <Briefcase className="mr-1 h-3 w-3" /> Job-Specific
                                                    </Badge>
                                                )}
                                                {session.status === 'archived' && <Badge variant="outline">Archived</Badge>}
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="pt-0">
                                        <p className="text-muted-foreground text-sm">
                                            {session.job_posting && (
                                                <span>
                                                    {session.job_posting.title} at {session.job_posting.company} ·{' '}
                                                </span>
                                            )}
                                            {session.updated_at}
                                        </p>
                                    </CardContent>
                                </Link>
                                {session.has_conversation && (
                                    <div className="border-t px-6 py-2">
                                        <Link href={`${show(session.id).url}?extract=1`}>
                                            <Button variant="ghost" size="sm" className="h-7 text-xs">
                                                <Package className="mr-1 h-3 w-3" /> Extract Experiences
                                            </Button>
                                        </Link>
                                    </div>
                                )}
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
