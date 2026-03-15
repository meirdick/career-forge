import { Transition } from '@headlessui/react';
import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { ExternalLink, GripVertical, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import UserLinkController from '@/actions/App/Http/Controllers/Settings/UserLinkController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { displayUrl } from '@/lib/utils';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit(),
    },
];

type UserLink = {
    id: number;
    url: string;
    label: string | null;
    type: string;
    sort_order: number;
};

const linkTypeLabels: Record<string, string> = {
    portfolio: 'Portfolio',
    github: 'GitHub',
    website: 'Website',
    other: 'Other',
};

function UserLinksSection({ links }: { links: UserLink[] }) {
    const [showForm, setShowForm] = useState(false);

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <Heading
                    variant="small"
                    title="Links"
                    description="Add portfolio, GitHub, and other links to include on resumes"
                />
                <Button variant="outline" size="sm" onClick={() => setShowForm(!showForm)}>
                    {showForm ? <X className="mr-1 h-4 w-4" /> : <Plus className="mr-1 h-4 w-4" />}
                    {showForm ? 'Cancel' : 'Add Link'}
                </Button>
            </div>

            {showForm && (
                <Card>
                    <CardContent className="pt-4">
                        <Form
                            {...UserLinkController.store.form()}
                            options={{ preserveScroll: true, onSuccess: () => setShowForm(false) }}
                            className="grid gap-3 sm:grid-cols-2"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="sm:col-span-2">
                                        <Label htmlFor="link_url">URL</Label>
                                        <Input id="link_url" name="url" type="url" required placeholder="https://..." />
                                        <InputError message={errors.url} />
                                    </div>
                                    <div>
                                        <Label htmlFor="link_label">Label (optional)</Label>
                                        <Input id="link_label" name="label" placeholder="My Portfolio" />
                                        <InputError message={errors.label} />
                                    </div>
                                    <div>
                                        <Label htmlFor="link_type">Type</Label>
                                        <Select name="type" defaultValue="portfolio">
                                            <SelectTrigger className="w-full">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {Object.entries(linkTypeLabels).map(([value, label]) => (
                                                    <SelectItem key={value} value={value}>{label}</SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.type} />
                                    </div>
                                    <div className="sm:col-span-2 flex justify-end">
                                        <Button type="submit" size="sm" disabled={processing}>Save</Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            )}

            {links.length > 0 ? (
                <div className="space-y-2">
                    {links.map((link) => (
                        <div key={link.id} className="flex items-center gap-3 rounded-md border px-3 py-2">
                            <GripVertical className="h-4 w-4 shrink-0 text-muted-foreground/40" />
                            <div className="min-w-0 flex-1">
                                <div className="flex items-center gap-2">
                                    <span className="truncate text-sm font-medium">{displayUrl(link.url, link.label)}</span>
                                    <span className="shrink-0 rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground">{linkTypeLabels[link.type] ?? link.type}</span>
                                </div>
                                <a href={link.url} target="_blank" rel="noopener noreferrer" className="flex items-center gap-1 text-xs text-muted-foreground hover:text-primary">
                                    {link.url} <ExternalLink className="h-3 w-3" />
                                </a>
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-8 w-8 shrink-0"
                                onClick={() => router.delete(UserLinkController.destroy(link.id).url, { preserveScroll: true })}
                            >
                                <Trash2 className="h-4 w-4" />
                            </Button>
                        </div>
                    ))}
                </div>
            ) : (
                !showForm && <p className="text-sm text-muted-foreground">No links added yet.</p>
            )}
        </div>
    );
}

export default function Profile({
    mustVerifyEmail,
    status,
    userLinks,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    userLinks: UserLink[];
}) {
    const { auth } = usePage().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Profile information"
                        description="Update your name, email address, and contact details"
                    />

                    <Form
                        {...ProfileController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>

                                    <Input
                                        id="name"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.name}
                                        name="name"
                                        required
                                        autoComplete="name"
                                        placeholder="Full name"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="legal_name">Legal Name</Label>

                                    <Input
                                        id="legal_name"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.legal_name ?? ''}
                                        name="legal_name"
                                        placeholder="If different from display name"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.legal_name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>

                                    <Input
                                        id="email"
                                        type="email"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.email}
                                        name="email"
                                        required
                                        autoComplete="username"
                                        placeholder="Email address"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.email}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Phone</Label>

                                    <Input
                                        id="phone"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.phone ?? ''}
                                        name="phone"
                                        autoComplete="tel"
                                        placeholder="Phone number"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.phone}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="location">Location</Label>

                                    <Input
                                        id="location"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.location ?? ''}
                                        name="location"
                                        placeholder="City, State"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.location}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="linkedin_url">LinkedIn URL</Label>

                                    <Input
                                        id="linkedin_url"
                                        type="url"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.linkedin_url ?? ''}
                                        name="linkedin_url"
                                        placeholder="https://linkedin.com/in/yourprofile"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.linkedin_url}
                                    />
                                </div>

                                {mustVerifyEmail &&
                                    auth.user.email_verified_at === null && (
                                        <div>
                                            <p className="-mt-4 text-sm text-muted-foreground">
                                                Your email address is
                                                unverified.{' '}
                                                <Link
                                                    href={send()}
                                                    as="button"
                                                    className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                                >
                                                    Click here to resend the
                                                    verification email.
                                                </Link>
                                            </p>

                                            {status ===
                                                'verification-link-sent' && (
                                                <div className="mt-2 text-sm font-medium text-green-600">
                                                    A new verification link has
                                                    been sent to your email
                                                    address.
                                                </div>
                                            )}
                                        </div>
                                    )}

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        data-test="update-profile-button"
                                    >
                                        Save
                                    </Button>

                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-neutral-600">
                                            Saved
                                        </p>
                                    </Transition>
                                </div>
                            </>
                        )}
                    </Form>

                    <Separator />

                    <UserLinksSection links={userLinks} />
                </div>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
