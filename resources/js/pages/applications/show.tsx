import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import { FileText, Globe, Loader2, Mail, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
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

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
    applied: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    interviewing: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    offer: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    withdrawn: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
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

    async function generateCoverLetter() {
        setGeneratingCoverLetter(true);
        try {
            const { data } = await axios.post(`/applications/${application.id}/generate-cover-letter`);
            setCoverLetter(data.cover_letter);
        } finally {
            setGeneratingCoverLetter(false);
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

            <div className="mx-auto max-w-3xl space-y-6 p-4">
                <div className="flex items-start justify-between">
                    <Heading
                        title={application.role}
                        description={application.company}
                    />
                    <div className="flex gap-2">
                        <Link href={`/applications/${application.id}/transparency`}>
                            <Button variant="outline" size="sm">
                                <Globe className="mr-1 h-4 w-4" /> Transparency
                            </Button>
                        </Link>
                        <Link href={`/applications/${application.id}/edit`}>
                            <Button variant="outline" size="sm">
                                <Pencil className="mr-1 h-4 w-4" /> Edit
                            </Button>
                        </Link>
                        <Button variant="destructive" size="sm" onClick={() => { if (confirm('Delete this application?')) router.delete(`/applications/${application.id}`); }}>
                            <Trash2 className="mr-1 h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Badge className={statusColors[application.status] ?? ''}>{application.status}</Badge>
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
                                <textarea
                                    value={statusNote}
                                    onChange={(e) => setStatusNote(e.target.value)}
                                    placeholder="Optional note about this status change..."
                                    rows={2}
                                    className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm"
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
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={generateCoverLetter}
                            disabled={generatingCoverLetter}
                        >
                            {generatingCoverLetter ? <Loader2 className="mr-1 h-4 w-4 animate-spin" /> : <FileText className="mr-1 h-4 w-4" />}
                            {coverLetter ? 'Regenerate' : 'Generate'}
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {coverLetter ? (
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
                        <textarea
                            value={noteContent}
                            onChange={(e) => setNoteContent(e.target.value)}
                            placeholder="Add a note..."
                            rows={2}
                            className="border-input bg-background flex flex-1 rounded-md border px-3 py-2 text-sm"
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
                        <div className="space-y-2">
                            {application.status_changes.map((change) => (
                                <div key={change.id} className="text-sm">
                                    <span className="text-muted-foreground">{new Date(change.created_at).toLocaleString()}</span>
                                    {' '}
                                    {change.from_status && <><Badge variant="outline" className="text-xs">{change.from_status}</Badge> → </>}
                                    <Badge className={statusColors[change.to_status] ?? ''} >{change.to_status}</Badge>
                                    {change.notes && <span className="text-muted-foreground ml-2">— {change.notes}</span>}
                                </div>
                            ))}
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
