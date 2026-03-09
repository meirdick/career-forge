import { Transition } from '@headlessui/react';
import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
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

type ResumeHeaderConfig = {
    name_preference: 'display_name' | 'legal_name';
    show_email: boolean;
    show_phone: boolean;
    show_location: boolean;
    show_linkedin: boolean;
    show_portfolio: boolean;
};

type UserInfo = {
    name: string;
    legal_name: string | null;
};

const fields = [
    { key: 'values', label: 'Core Values', placeholder: 'What principles guide your professional decisions?' },
    { key: 'philosophy', label: 'Work Philosophy', placeholder: 'What is your approach to building great software?' },
    { key: 'passions', label: 'Passions', placeholder: 'What aspects of your work excite you most?' },
    { key: 'leadership_style', label: 'Leadership Style', placeholder: 'How do you lead teams and projects?' },
    { key: 'collaboration_approach', label: 'Collaboration Approach', placeholder: 'How do you prefer to work with others?' },
    { key: 'communication_style', label: 'Communication Style', placeholder: 'How do you communicate in professional settings?' },
    { key: 'cultural_preferences', label: 'Cultural Preferences', placeholder: 'What kind of company culture do you thrive in?' },
] as const;

const toggleFields = [
    { key: 'show_email', label: 'Email' },
    { key: 'show_phone', label: 'Phone' },
    { key: 'show_location', label: 'Location' },
    { key: 'show_linkedin', label: 'LinkedIn' },
    { key: 'show_portfolio', label: 'Portfolio Links' },
] as const;

export default function Identity({ identity, user, resumeHeaderConfig }: { identity: Identity; user: UserInfo; resumeHeaderConfig: ResumeHeaderConfig }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Professional Identity" />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
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
                                    <Textarea
                                        id={field.key}
                                        name={field.key}
                                        defaultValue={identity?.[field.key] ?? ''}
                                        placeholder={field.placeholder}
                                        rows={3}
                                    />
                                    <InputError message={errors[field.key as keyof typeof errors]} />
                                </div>
                            ))}

                            <Separator />

                            <Heading
                                variant="small"
                                title="Resume Header"
                                description="Configure which contact details appear on your exported resumes. These are the global defaults — you can override per resume."
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="legal_name">Legal Name</Label>
                                <Input
                                    id="legal_name"
                                    name="legal_name"
                                    defaultValue={user.legal_name ?? ''}
                                    placeholder="If different from display name"
                                />
                                <p className="text-muted-foreground text-xs">Used when &quot;Legal Name&quot; is selected as name preference below.</p>
                                <InputError message={errors.legal_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label>Name Preference</Label>
                                <div className="flex items-center gap-4">
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="radio"
                                            name="resume_header_config[name_preference]"
                                            value="display_name"
                                            defaultChecked={resumeHeaderConfig.name_preference === 'display_name'}
                                            className="accent-primary"
                                        />
                                        Display Name
                                    </label>
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="radio"
                                            name="resume_header_config[name_preference]"
                                            value="legal_name"
                                            defaultChecked={resumeHeaderConfig.name_preference === 'legal_name'}
                                            className="accent-primary"
                                        />
                                        Legal Name
                                    </label>
                                </div>
                            </div>

                            <div className="space-y-3">
                                <Label>Show on Resume</Label>
                                {toggleFields.map((field) => (
                                    <label key={field.key} className="flex items-center gap-2">
                                        <input type="hidden" name={`resume_header_config[${field.key}]`} value="0" />
                                        <Checkbox
                                            name={`resume_header_config[${field.key}]`}
                                            defaultChecked={resumeHeaderConfig[field.key]}
                                            value="1"
                                        />
                                        <span className="text-sm">{field.label}</span>
                                    </label>
                                ))}
                            </div>

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
