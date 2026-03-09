import { Form, Head } from '@inertiajs/react';
import ExperienceController from '@/actions/App/Http/Controllers/ExperienceLibrary/ExperienceController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import RichTextEditor from '@/components/rich-text-editor';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { edit as experienceEdit } from '@/routes/experiences';
import type { BreadcrumbItem } from '@/types';

type Skill = { id: number; name: string };

type Experience = {
    id: number;
    company: string;
    title: string;
    location: string | null;
    started_at: string;
    ended_at: string | null;
    is_current: boolean;
    description: string | null;
    reporting_to: string | null;
    team_size: number | null;
    reason_for_leaving: string | null;
    skills: Skill[];
};

export default function EditExperience({ experience }: { experience: Experience }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Experience Library', href: '/experience-library' },
        { title: experience.company, href: `/experiences/${experience.id}` },
        { title: 'Edit', href: experienceEdit(experience.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${experience.title}`} />

            <div className="mx-auto max-w-2xl space-y-6 p-4">
                <Heading title="Edit Experience" description={`${experience.title} at ${experience.company}`} />

                <Card>
                    <CardContent className="pt-6">
                        <Form {...ExperienceController.update.form(experience.id)} className="space-y-4">
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <Label htmlFor="company">Company</Label>
                                            <Input id="company" name="company" required defaultValue={experience.company} />
                                            <InputError message={errors.company} />
                                        </div>
                                        <div>
                                            <Label htmlFor="title">Job Title</Label>
                                            <Input id="title" name="title" required defaultValue={experience.title} />
                                            <InputError message={errors.title} />
                                        </div>
                                    </div>

                                    <div>
                                        <Label htmlFor="location">Location</Label>
                                        <Input id="location" name="location" defaultValue={experience.location ?? ''} />
                                        <InputError message={errors.location} />
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <Label htmlFor="started_at">Start Date</Label>
                                            <Input id="started_at" name="started_at" type="date" required defaultValue={experience.started_at} />
                                            <InputError message={errors.started_at} />
                                        </div>
                                        <div>
                                            <Label htmlFor="ended_at">End Date</Label>
                                            <Input id="ended_at" name="ended_at" type="date" defaultValue={experience.ended_at ?? ''} />
                                            <InputError message={errors.ended_at} />
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <Checkbox id="is_current" name="is_current" value="1" defaultChecked={experience.is_current} />
                                        <Label htmlFor="is_current" className="font-normal">I currently work here</Label>
                                    </div>

                                    <div>
                                        <Label htmlFor="description">Description</Label>
                                        <RichTextEditor name="description" defaultValue={experience.description ?? ''} placeholder="Describe your role and responsibilities..." />
                                        <InputError message={errors.description} />
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <Label htmlFor="reporting_to">Reporting To</Label>
                                            <Input id="reporting_to" name="reporting_to" defaultValue={experience.reporting_to ?? ''} />
                                        </div>
                                        <div>
                                            <Label htmlFor="team_size">Team Size</Label>
                                            <Input id="team_size" name="team_size" type="number" min="1" defaultValue={experience.team_size ?? ''} />
                                        </div>
                                    </div>

                                    <div>
                                        <Label htmlFor="reason_for_leaving">Reason for Leaving</Label>
                                        <Input id="reason_for_leaving" name="reason_for_leaving" defaultValue={experience.reason_for_leaving ?? ''} />
                                    </div>

                                    <div className="flex justify-end gap-2 pt-4">
                                        <Button type="submit" disabled={processing}>Update Experience</Button>
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
