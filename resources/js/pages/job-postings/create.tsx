import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import JobPostingController from '@/actions/App/Http/Controllers/JobPostingController';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Job Postings', href: '/job-postings' },
    { title: 'New', href: '/job-postings/create' },
];

export default function CreateJobPosting() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add Job Posting" />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                <Heading title="Add Job Posting" description="Paste a job posting to generate an ideal candidate profile." />

                <Card>
                    <CardContent className="pt-6">
                        <Form {...JobPostingController.store.form()} className="space-y-4">
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <Label htmlFor="title">Job Title (optional)</Label>
                                            <Input id="title" name="title" placeholder="Senior Software Engineer" />
                                            <InputError message={errors.title} />
                                        </div>
                                        <div>
                                            <Label htmlFor="company">Company (optional)</Label>
                                            <Input id="company" name="company" placeholder="Acme Corp" />
                                            <InputError message={errors.company} />
                                        </div>
                                    </div>

                                    <div>
                                        <Label htmlFor="url">Posting URL</Label>
                                        <Input id="url" name="url" type="url" placeholder="https://careers.example.com/job/123" />
                                        <p className="text-muted-foreground text-xs mt-1">Provide a URL to auto-fetch the posting content, or paste the text below.</p>
                                        <InputError message={errors.url} />
                                    </div>

                                    <div>
                                        <Label htmlFor="raw_text">Job Posting Text</Label>
                                        <Textarea
                                            id="raw_text"
                                            name="raw_text"
                                            rows={12}
                                            placeholder="Paste the full job posting text here, or provide a URL above to auto-fetch..."
                                        />
                                        <InputError message={errors.raw_text} />
                                    </div>

                                    <div className="flex justify-end">
                                        <Button type="submit" disabled={processing}>
                                            {processing ? 'Creating...' : 'Create & Analyze'}
                                        </Button>
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
