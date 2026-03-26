import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import { Download, FileText, Globe, Loader2, Mail, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import PipelineAssistantPanel from '@/components/pipeline-assistant-panel';
import PipelineSteps from '@/components/pipeline-steps';
import StatusBadge from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Note = { id: number; content: string; created_at: string };
type StatusChange = { id: number; from_status: string | null; to_status: string; notes: string | null; created_at: string };
type ApplicationData = {
    id: number;
    company: string;
    role: string;
    status: string;
    applied_at: string | null;
    notes: string | null;
    cover_letter: string | null;
    submission_email: string | null;
    job_posting: { id: number; title: string | null } | null;
    resume: { id: number; title: string } | null;
    application_notes: Note[];
    status_changes: StatusChange[];
    transparency_page: { slug: string; is_published: boolean } | null;
};


const allStatuses = ['draft', 'applied', 'interviewing', 'offer', 'rejected', 'withdrawn'];

export default function ShowApplication({ application }: { application: ApplicationData }) {
    const [noteContent, setNoteContent] = useState('');
    const [statusNote, setStatusNote] = useState('');
    const [newStatus, setNewStatus] = useState('');
    const [generatingCoverLetter, setGeneratingCoverLetter] = useState(false);
    const [generatingEmail, setGeneratingEmail] = useState(false);
    const [coverLetter, setCoverLetter] = useState(application.cover_letter);
    const [submissionEmail, setSubmissionEmail] = useState(application.submission_email);
    const [editingCoverLetter, setEditingCoverLetter] = useState(false);
    const [coverLetterDraft, setCoverLetterDraft] = useState(application.cover_letter ?? '');
    const [savingCoverLetter, setSavingCoverLetter] = useState(false);

    async function generateCoverLetter() {
        setGeneratingCoverLetter(true);
        try {
            const { data } = await axios.post(`/applications/${application.id}/generate-cover-letter`);
            setCoverLetter(data.cover_letter);
            setCoverLetterDraft(data.cover_letter);
        } finally {
            setGeneratingCoverLetter(false);
        }
    }

    async function saveCoverLetter() {
        setSavingCoverLetter(true);
        try {
            await axios.put(`/applications/${application.id}/cover-letter`, {
                cover_letter: coverLetterDraft,
            });
            setCoverLetter(coverLetterDraft);
            setEditingCoverLetter(false);
        } finally {
            setSavingCoverLetter(false);
        }
    }

    async function generateEmail() {
        setGeneratingEmail(true);
        try {
            const { data } = await axios.post(`/applications/${application.id}/generate-email`);
            setSubmissionEmail(data.submission_email);
        } finally {
            setGeneratingEmail(false);
        }
    }

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Applications', href: '/applications' },
        { title: `${application.role} at ${application.company}`, href: `/applications/${application.id}` },
    ];

    function addNote() {
        if (!noteContent.trim()) return;
        router.post(`/applications/${application.id}/notes`, { content: noteContent }, {
            preserveScroll: true,
            onSuccess: () => setNoteContent(''),
        });
    }

    function updateStatus() {
        if (!newStatus) return;
        router.patch(`/applications/${application.id}/status`, {
            status: newStatus,
            notes: statusNote || undefined,
        }, {
            preserveScroll: true,
            onSuccess: () => { setNewStatus(''); setStatusNote(''); },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${application.role} at ${application.company}`} />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                {application.job_posting && (
                    <PipelineSteps
                        steps={[
                            { label: 'Job Posting', href: `/job-postings/${application.job_posting.id}`, status: 'completed' },
                            { label: 'Ideal Candidate', status: 'completed' },
                            { label: 'Gap Analysis', status: 'completed' },
                            { label: 'Resume', status: 'completed' },
                            { label: 'Application', href: `/applications/${application.id}`, status: 'active' },
                        ]}
                    />
                )}
                <div className="flex items-start justify-between">
                    <Heading
                        title={application.role}
                        description={application.company}
                    />
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/applications/${application.id}/transparency`}>
                                <Globe className="mr-1 h-4 w-4" /> Transparency
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/applications/${application.id}/edit`}>
                                <Pencil className="mr-1 h-4 w-4" /> Edit
                            </Link>
                        </Button>
                        <Button variant="destructive" size="sm" onClick={() => { if (confirm('Delete this application?')) router.delete(`/applications/${application.id}`); }}>
                            <Trash2 className="mr-1 h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    <StatusBadge status={application.status} />
                    {application.applied_at && (
                        <Badge variant="outline">Applied {new Date(application.applied_at).toLocaleDateString()}</Badge>
                    )}
                </div>

                {/* Status Update */}
                <Card>
                    <CardHeader><CardTitle className="text-base">Update Status</CardTitle></CardHeader>
                    <CardContent className="space-y-3">
                        <div className="flex flex-wrap gap-2">
                            {allStatuses.filter(s => s !== application.status).map((status) => (
                                <Button
                                    key={status}
                                    variant={newStatus === status ? 'default' : 'outline'}
                                    size="sm"
                                    onClick={() => setNewStatus(status)}
                                >
                                    {status}
                                </Button>
                            ))}
                        </div>
                        {newStatus && (
                            <div className="space-y-2">
                                <Textarea
                                    value={statusNote}
                                    onChange={(e) => setStatusNote(e.target.value)}
                                    placeholder="Optional note about this status change..."
                                    rows={2}
                                />
                                <Button size="sm" onClick={updateStatus}>
                                    Update to {newStatus}
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Cover Letter */}
                <Card>
                    <CardHeader className="flex-row items-center justify-between">
                        <CardTitle className="text-base">Cover Letter</CardTitle>
                        <div className="flex gap-2">
                            {coverLetter && !editingCoverLetter && (
                                <>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => {
                                            setCoverLetterDraft(coverLetter);
                                            setEditingCoverLetter(true);
                                        }}
                                    >
                                        <Pencil className="mr-1 h-4 w-4" /> Edit
                                    </Button>
                                    <a href={`/applications/${application.id}/cover-letter/export/pdf`} download>
                                        <Button variant="outline" size="sm" aria-label="Export cover letter as PDF">
                                            <Download className="mr-1 h-4 w-4" /> PDF
                                        </Button>
                                    </a>
                                    <a href={`/applications/${application.id}/cover-letter/export/docx`} download>
                                        <Button variant="outline" size="sm" aria-label="Export cover letter as DOCX">
                                            <Download className="mr-1 h-4 w-4" /> DOCX
                                        </Button>
                                    </a>
                                </>
                            )}
                            {editingCoverLetter && (
                                <>
                                    <Button size="sm" onClick={saveCoverLetter} disabled={savingCoverLetter}>
                                        {savingCoverLetter ? <Loader2 className="mr-1 h-4 w-4 animate-spin" /> : null}
                                        Save
                                    </Button>
                                    <Button variant="ghost" size="sm" onClick={() => setEditingCoverLetter(false)}>
                                        Cancel
                                    </Button>
                                </>
                            )}
                            {!editingCoverLetter && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={generateCoverLetter}
                                    disabled={generatingCoverLetter}
                                >
                                    {generatingCoverLetter ? <Loader2 className="mr-1 h-4 w-4 animate-spin" /> : <FileText className="mr-1 h-4 w-4" />}
                                    {coverLetter ? 'Regenerate' : 'Generate'}
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        {editingCoverLetter ? (
                            <Textarea
                                value={coverLetterDraft}
                                onChange={(e) => setCoverLetterDraft(e.target.value)}
                                rows={12}
                            />
                        ) : coverLetter ? (
                            <p className="text-sm whitespace-pre-wrap">{coverLetter}</p>
                        ) : (
                            <p className="text-muted-foreground text-sm">
                                {generatingCoverLetter ? 'Generating cover letter...' : 'No cover letter yet. Click Generate to create one from your resume and job posting.'}
                            </p>
                        )}
                    </CardContent>
                </Card>

                {/* Submission Email */}
                <Card>
                    <CardHeader className="flex-row items-center justify-between">
                        <CardTitle className="text-base">Submission Email</CardTitle>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={generateEmail}
                            disabled={generatingEmail}
                        >
                            {generatingEmail ? <Loader2 className="mr-1 h-4 w-4 animate-spin" /> : <Mail className="mr-1 h-4 w-4" />}
                            {submissionEmail ? 'Regenerate' : 'Generate'}
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {submissionEmail ? (
                            <p className="text-sm whitespace-pre-wrap">{submissionEmail}</p>
                        ) : (
                            <p className="text-muted-foreground text-sm">
                                {generatingEmail ? 'Generating email...' : 'No submission email yet. Click Generate to draft one.'}
                            </p>
                        )}
                    </CardContent>
                </Card>

                {/* Notes/Details */}
                {application.notes && (
                    <Card>
                        <CardHeader><CardTitle className="text-base">Application Notes</CardTitle></CardHeader>
                        <CardContent>
                            <p className="text-muted-foreground text-sm whitespace-pre-wrap">{application.notes}</p>
                        </CardContent>
                    </Card>
                )}

                {/* Linked Artifacts */}
                {(application.job_posting || application.resume) && (
                    <Card>
                        <CardHeader><CardTitle className="text-base">Linked Artifacts</CardTitle></CardHeader>
                        <CardContent className="space-y-2">
                            {application.job_posting && (
                                <Link href={`/job-postings/${application.job_posting.id}`} className="text-primary block text-sm underline">
                                    Job Posting: {application.job_posting.title ?? 'Untitled'}
                                </Link>
                            )}
                            {application.resume && (
                                <Link href={`/resumes/${application.resume.id}`} className="text-primary block text-sm underline">
                                    Resume: {application.resume.title}
                                </Link>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* Notes */}
                <Separator />
                <h2 className="text-lg font-semibold">Notes</h2>
                <div className="space-y-3">
                    <div className="flex gap-2">
                        <Textarea
                            value={noteContent}
                            onChange={(e) => setNoteContent(e.target.value)}
                            placeholder="Add a note..."
                            rows={2}
                            className="flex-1"
                        />
                        <Button size="sm" onClick={addNote} disabled={!noteContent.trim()}>
                            Add
                        </Button>
                    </div>
                    {application.application_notes.map((note) => (
                        <Card key={note.id}>
                            <CardContent className="flex items-start justify-between pt-4">
                                <div>
                                    <p className="text-sm whitespace-pre-wrap">{note.content}</p>
                                    <p className="text-muted-foreground mt-1 text-xs">{new Date(note.created_at).toLocaleString()}</p>
                                </div>
                                <Button variant="ghost" size="sm" onClick={() => router.delete(`/applications/${application.id}/notes/${note.id}`, { preserveScroll: true })}>
                                    <Trash2 className="h-3 w-3" />
                                </Button>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Status Timeline */}
                {application.status_changes.length > 0 && (
                    <>
                        <Separator />
                        <h2 className="text-lg font-semibold">Status History</h2>
                        <div className="relative space-y-4 border-l-2 border-muted pl-6">
                            {application.status_changes.map((change) => (
                                <div key={change.id} className="relative text-sm">
                                    <div className="bg-primary absolute -left-[31px] top-1 h-3 w-3 rounded-full" />
                                    <div className="flex flex-wrap items-center gap-2">
                                        {change.from_status && <><StatusBadge status={change.from_status} /> <span className="text-muted-foreground">→</span> </>}
                                        <StatusBadge status={change.to_status} />
                                    </div>
                                    <p className="text-muted-foreground mt-0.5 text-xs">{new Date(change.created_at).toLocaleString()}</p>
                                    {change.notes && <p className="text-muted-foreground mt-1 text-xs">{change.notes}</p>}
                                </div>
                            ))}
                        </div>
                    </>
                )}
            </div>

            {application.job_posting && (
                <PipelineAssistantPanel context={{ step: 'application', pipelineKey: `job_posting:${application.job_posting.id}` }} />
            )}
        </AppLayout>
    );
}
