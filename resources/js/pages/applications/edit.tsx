import { Head } from '@inertiajs/react';
import { Form } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type ApplicationData = {
    id: number;
    company: string;
    role: string;
    notes: string | null;
    cover_letter: string | null;
    submission_email: string | null;
};

export default function EditApplication({ application }: { application: ApplicationData }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Applications', href: '/applications' },
        { title: `${application.role} at ${application.company}`, href: `/applications/${application.id}` },
        { title: 'Edit', href: `/applications/${application.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Application" />

            <div className="mx-auto max-w-3xl space-y-6 p-4">
                <Heading title="Edit Application" />

                <Form action={`/applications/${application.id}`} method="put">
                    {({ errors, processing }) => (
                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="company">Company</Label>
                                <Input id="company" name="company" defaultValue={application.company} required className="mt-1" />
                                <InputError message={errors.company} />
                            </div>

                            <div>
                                <Label htmlFor="role">Role</Label>
                                <Input id="role" name="role" defaultValue={application.role} required className="mt-1" />
                                <InputError message={errors.role} />
                            </div>

                            <div>
                                <Label htmlFor="submission_email">Submission Email</Label>
                                <Input id="submission_email" name="submission_email" type="email" defaultValue={application.submission_email ?? ''} className="mt-1" />
                                <InputError message={errors.submission_email} />
                            </div>

                            <div>
                                <Label htmlFor="notes">Notes</Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    defaultValue={application.notes ?? ''}
                                    rows={3}
                                    className="border-input bg-background mt-1 flex w-full rounded-md border px-3 py-2 text-sm"
                                />
                            </div>

                            <div>
                                <Label htmlFor="cover_letter">Cover Letter</Label>
                                <textarea
                                    id="cover_letter"
                                    name="cover_letter"
                                    defaultValue={application.cover_letter ?? ''}
                                    rows={6}
                                    className="border-input bg-background mt-1 flex w-full rounded-md border px-3 py-2 text-sm"
                                />
                            </div>

                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving...' : 'Save Changes'}
                            </Button>
                        </div>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
