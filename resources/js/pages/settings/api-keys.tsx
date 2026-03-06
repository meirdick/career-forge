import { Transition } from '@headlessui/react';
import { Form, Head, router } from '@inertiajs/react';
import ApiKeyController from '@/actions/App/Http/Controllers/Settings/ApiKeyController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { show } from '@/routes/api-keys';
import { destroy } from '@/routes/api-keys';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'API Keys',
        href: show(),
    },
];

type ApiKey = {
    id: number;
    provider: string;
    is_active: boolean;
    validated_at: string | null;
    created_at: string;
};

export default function ApiKeys({
    apiKeys,
    providers,
}: {
    apiKeys: ApiKey[];
    providers: string[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="API Keys" />

            <h1 className="sr-only">API Keys</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="API Keys"
                        description="Add your own API key to use AI features with your own account"
                    />

                    <Form
                        {...ApiKeyController.store.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="provider">Provider</Label>

                                    <Select name="provider" defaultValue="">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select a provider" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {providers.map((provider) => (
                                                <SelectItem
                                                    key={provider}
                                                    value={provider}
                                                >
                                                    {provider.charAt(0).toUpperCase() +
                                                        provider.slice(1)}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>

                                    <InputError
                                        className="mt-2"
                                        message={errors.provider}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="api_key">API Key</Label>

                                    <Input
                                        id="api_key"
                                        type="password"
                                        className="mt-1 block w-full"
                                        name="api_key"
                                        required
                                        placeholder="sk-..."
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.api_key}
                                    />
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button disabled={processing}>
                                        Validate & Save
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
                </div>

                {apiKeys.length > 0 && (
                    <div className="space-y-6">
                        <Heading
                            variant="small"
                            title="Saved keys"
                            description="Your stored API keys"
                        />

                        <div className="space-y-3">
                            {apiKeys.map((apiKey) => (
                                <div
                                    key={apiKey.id}
                                    className="flex items-center justify-between rounded-lg border p-4"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {apiKey.provider.charAt(0).toUpperCase() +
                                                apiKey.provider.slice(1)}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {apiKey.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                            {apiKey.validated_at &&
                                                ` \u00b7 Validated ${new Date(apiKey.validated_at).toLocaleDateString()}`}
                                        </p>
                                    </div>

                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        onClick={() =>
                                            router.delete(
                                                destroy(apiKey.id).url,
                                                {
                                                    preserveScroll: true,
                                                },
                                            )
                                        }
                                    >
                                        Remove
                                    </Button>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </SettingsLayout>
        </AppLayout>
    );
}
