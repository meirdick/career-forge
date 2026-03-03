import { Transition } from '@headlessui/react';
import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import ProfessionalIdentityController from '@/actions/App/Http/Controllers/ExperienceLibrary/ProfessionalIdentityController';
import { edit as identityEdit } from '@/routes/identity';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Experience Library', href: '/experience-library' },
    { title: 'Identity', href: identityEdit() },
];

type Identity = {
    id: number;
    values: string | null;
    philosophy: string | null;
    passions: string | null;
    leadership_style: string | null;
    collaboration_approach: string | null;
    communication_style: string | null;
    cultural_preferences: string | null;
} | null;

const fields = [
    { key: 'values', label: 'Core Values', placeholder: 'What principles guide your professional decisions?' },
    { key: 'philosophy', label: 'Work Philosophy', placeholder: 'What is your approach to building great software?' },
    { key: 'passions', label: 'Passions', placeholder: 'What aspects of your work excite you most?' },
    { key: 'leadership_style', label: 'Leadership Style', placeholder: 'How do you lead teams and projects?' },
    { key: 'collaboration_approach', label: 'Collaboration Approach', placeholder: 'How do you prefer to work with others?' },
    { key: 'communication_style', label: 'Communication Style', placeholder: 'How do you communicate in professional settings?' },
    { key: 'cultural_preferences', label: 'Cultural Preferences', placeholder: 'What kind of company culture do you thrive in?' },
] as const;

export default function Identity({ identity }: { identity: Identity }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Professional Identity" />

            <div className="mx-auto max-w-2xl space-y-6 p-4">
                <Heading
                    title="Professional Identity"
                    description="Define who you are as a professional. This helps AI generate authentic, personalized content."
                />

                <Form
                    {...ProfessionalIdentityController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, recentlySuccessful, errors }) => (
                        <>
                            {fields.map((field) => (
                                <div key={field.key} className="grid gap-2">
                                    <Label htmlFor={field.key}>{field.label}</Label>
                                    <textarea
                                        id={field.key}
                                        name={field.key}
                                        defaultValue={identity?.[field.key] ?? ''}
                                        placeholder={field.placeholder}
                                        rows={3}
                                        className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm"
                                    />
                                    <InputError message={errors[field.key as keyof typeof errors]} />
                                </div>
                            ))}

                            <div className="flex items-center gap-4">
                                <Button disabled={processing}>Save</Button>
                                <Transition
                                    show={recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p className="text-sm text-neutral-600">Saved</p>
                                </Transition>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
