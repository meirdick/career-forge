import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import RichTextEditor from '@/components/rich-text-editor';
import AppLayout from '@/layouts/app-layout';
import ExperienceController from '@/actions/App/Http/Controllers/ExperienceLibrary/ExperienceController';
import { create as experienceCreate } from '@/routes/experiences';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Experience Library', href: '/experience-library' },
    { title: 'New Experience', href: experienceCreate() },
];

type Skill = { id: number; name: string };

export default function CreateExperience({ skills }: { skills: Skill[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add Experience" />

            <div className="mx-auto max-w-2xl space-y-6 p-4">
                <Heading title="Add Experience" description="Add a professional role or position" />

                <Card>
                    <CardContent className="pt-6">
                        <Form {...ExperienceController.store.form()} className="space-y-4">
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <Label htmlFor="company">Company</Label>
                                            <Input id="company" name="company" required placeholder="Acme Corp" />
                                            <InputError message={errors.company} />
                                        </div>
                                        <div>
                                            <Label htmlFor="title">Job Title</Label>
                                            <Input id="title" name="title" required placeholder="Senior Engineer" />
                                            <InputError message={errors.title} />
                                        </div>
                                    </div>

                                    <div>
                                        <Label htmlFor="location">Location</Label>
                                        <Input id="location" name="location" placeholder="San Francisco, CA" />
                                        <InputError message={errors.location} />
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <Label htmlFor="started_at">Start Date</Label>
                                            <Input id="started_at" name="started_at" type="date" required />
                                            <InputError message={errors.started_at} />
                                        </div>
                                        <div>
                                            <Label htmlFor="ended_at">End Date</Label>
                                            <Input id="ended_at" name="ended_at" type="date" />
                                            <InputError message={errors.ended_at} />
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <Checkbox id="is_current" name="is_current" value="1" />
                                        <Label htmlFor="is_current" className="font-normal">I currently work here</Label>
                                    </div>

                                    <div>
                                        <Label htmlFor="description">Description</Label>
                                        <RichTextEditor name="description" placeholder="Describe your role and responsibilities..." />
                                        <InputError message={errors.description} />
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <Label htmlFor="reporting_to">Reporting To</Label>
                                            <Input id="reporting_to" name="reporting_to" placeholder="VP of Engineering" />
                                        </div>
                                        <div>
                                            <Label htmlFor="team_size">Team Size</Label>
                                            <Input id="team_size" name="team_size" type="number" min="1" placeholder="8" />
                                        </div>
                                    </div>

                                    <div>
                                        <Label htmlFor="reason_for_leaving">Reason for Leaving</Label>
                                        <Input id="reason_for_leaving" name="reason_for_leaving" placeholder="Seeking new challenges" />
                                    </div>

                                    <input type="hidden" name="sort_order" value="0" />

                                    <div className="flex justify-end gap-2 pt-4">
                                        <Button type="submit" disabled={processing}>Create Experience</Button>
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
