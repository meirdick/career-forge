import { Form, Head } from '@inertiajs/react';
import JobPostingController from '@/actions/App/Http/Controllers/JobPostingController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type JobPosting = {
    id: number;
    title: string | null;
    company: string | null;
    url: string | null;
    raw_text: string;
};

export default function EditJobPosting({ posting }: { posting: JobPosting }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Job Postings', href: '/job-postings' },
        { title: posting.title ?? 'Posting', href: `/job-postings/${posting.id}` },
        { title: 'Edit', href: `/job-postings/${posting.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Job Posting" />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                <Heading title="Edit Job Posting" />

                <Card>
                    <CardContent className="pt-6">
                        <Form {...JobPostingController.update.form(posting.id)} className="space-y-4">
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <Label htmlFor="title">Job Title</Label>
                                            <Input id="title" name="title" defaultValue={posting.title ?? ''} />
                                            <InputError message={errors.title} />
                                        </div>
                                        <div>
                                            <Label htmlFor="company">Company</Label>
                                            <Input id="company" name="company" defaultValue={posting.company ?? ''} />
                                            <InputError message={errors.company} />
                                        </div>
                                    </div>

                                    <div>
                                        <Label htmlFor="url">Posting URL</Label>
                                        <Input id="url" name="url" type="url" defaultValue={posting.url ?? ''} />
                                        <InputError message={errors.url} />
                                    </div>

                                    <div>
                                        <Label htmlFor="raw_text">Job Posting Text</Label>
                                        <Textarea
                                            id="raw_text"
                                            name="raw_text"
                                            required
                                            rows={12}
                                            defaultValue={posting.raw_text}
                                        />
                                        <InputError message={errors.raw_text} />
                                    </div>

                                    <div className="flex justify-end">
                                        <Button type="submit" disabled={processing}>Update Posting</Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
