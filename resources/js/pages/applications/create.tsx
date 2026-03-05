import { Head } from '@inertiajs/react';
import { Form } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
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

            <div className="mx-auto max-w-4xl space-y-6 p-4">
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
                                    <Select name="resume_id" defaultValue="">
                                        <SelectTrigger className="mt-1 w-full">
                                            <SelectValue placeholder="None" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">None</SelectItem>
                                            {resumes.map((r) => (
                                                <SelectItem key={r.id} value={String(r.id)}>{r.title}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}

                            <div>
                                <Label htmlFor="submission_email">Submission Email (optional)</Label>
                                <Input id="submission_email" name="submission_email" type="email" className="mt-1" />
                                <InputError message={errors.submission_email} />
                            </div>

                            <div>
                                <Label htmlFor="notes">Notes (optional)</Label>
                                <Textarea
                                    id="notes"
                                    name="notes"
                                    rows={3}
                                    className="mt-1"
                                />
                            </div>

                            <div>
                                <Label htmlFor="cover_letter">Cover Letter (optional)</Label>
                                <Textarea
                                    id="cover_letter"
                                    name="cover_letter"
                                    rows={6}
                                    className="mt-1"
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
