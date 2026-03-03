import { Head } from '@inertiajs/react';
import { Form } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type ResumeOption = {
    id: number;
    title: string;
    job_posting: { title: string | null; company: string | null } | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Applications', href: '/applications' },
    { title: 'New Application', href: '/applications/create' },
];

export default function CreateApplication({ resumes }: { resumes: ResumeOption[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Application" />

            <div className="mx-auto max-w-3xl space-y-6 p-4">
                <Heading title="New Application" description="Create a new job application." />

                <Form action="/applications" method="post">
                    {({ errors, processing }) => (
                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="company">Company</Label>
                                <Input id="company" name="company" required className="mt-1" />
                                <InputError message={errors.company} />
                            </div>

                            <div>
                                <Label htmlFor="role">Role</Label>
                                <Input id="role" name="role" required className="mt-1" />
                                <InputError message={errors.role} />
                            </div>

                            {resumes.length > 0 && (
                                <div>
                                    <Label htmlFor="resume_id">Resume (optional)</Label>
                                    <select id="resume_id" name="resume_id" className="border-input bg-background mt-1 flex w-full rounded-md border px-3 py-2 text-sm">
                                        <option value="">None</option>
                                        {resumes.map((r) => (
                                            <option key={r.id} value={r.id}>{r.title}</option>
                                        ))}
                                    </select>
                                </div>
                            )}

                            <div>
                                <Label htmlFor="submission_email">Submission Email (optional)</Label>
                                <Input id="submission_email" name="submission_email" type="email" className="mt-1" />
                                <InputError message={errors.submission_email} />
                            </div>

                            <div>
                                <Label htmlFor="notes">Notes (optional)</Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows={3}
                                    className="border-input bg-background mt-1 flex w-full rounded-md border px-3 py-2 text-sm"
                                />
                            </div>

                            <div>
                                <Label htmlFor="cover_letter">Cover Letter (optional)</Label>
                                <textarea
                                    id="cover_letter"
                                    name="cover_letter"
                                    rows={6}
                                    className="border-input bg-background mt-1 flex w-full rounded-md border px-3 py-2 text-sm"
                                />
                            </div>

                            <Button type="submit" disabled={processing}>
                                {processing ? 'Creating...' : 'Create Application'}
                            </Button>
                        </div>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
