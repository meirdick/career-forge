import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { show } from '@/routes/billing';
import { checkout } from '@/routes/billing';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Billing',
        href: show(),
    },
];

type CreditTransaction = {
    id: number;
    type: string;
    amount: number;
    balance_after: number;
    description: string;
    created_at: string;
};

export default function Billing({
    balance,
    transactions,
    creditsPerPurchase,
    purchasePriceCents,
    freeTierUsage,
    accessMode,
    purchaseSuccess,
}: {
    balance: number;
    transactions: CreditTransaction[];
    creditsPerPurchase: number;
    purchasePriceCents: number;
    freeTierUsage: {
        job_postings_used: number;
        job_postings_limit: number;
        documents_used: number;
        documents_limit: number;
    };
    accessMode: string;
    purchaseSuccess?: boolean;
}) {
    const priceDisplay = `$${(purchasePriceCents / 100).toFixed(2)}`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing" />

            <h1 className="sr-only">Billing</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Credit balance"
                        description="Purchase credits to use AI features"
                    />

                    {purchaseSuccess && (
                        <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                            Credits purchased successfully!
                        </div>
                    )}

                    <div className="rounded-lg border p-6">
                        <div className="text-center">
                            <p className="text-4xl font-bold">{balance}</p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                credits available
                            </p>
                        </div>

                        <div className="mt-6 flex justify-center">
                            <Button
                                onClick={() =>
                                    router.post(checkout().url, undefined, {
                                        preserveScroll: true,
                                    })
                                }
                            >
                                Buy {creditsPerPurchase} credits ({priceDisplay}
                                )
                            </Button>
                        </div>
                    </div>
                </div>

                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Free tier usage"
                        description="Your current free tier consumption"
                    />

                    <div className="space-y-3">
                        <div className="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <p className="font-medium">Job postings analyzed</p>
                                <p className="text-sm text-muted-foreground">
                                    Free tier limit
                                </p>
                            </div>
                            <p className="text-sm font-medium">
                                {freeTierUsage.job_postings_used} /{' '}
                                {freeTierUsage.job_postings_limit}
                            </p>
                        </div>

                        <div className="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <p className="font-medium">Documents uploaded</p>
                                <p className="text-sm text-muted-foreground">
                                    Free tier limit
                                </p>
                            </div>
                            <p className="text-sm font-medium">
                                {freeTierUsage.documents_used} /{' '}
                                {freeTierUsage.documents_limit}
                            </p>
                        </div>
                    </div>
                </div>

                {transactions.length > 0 && (
                    <div className="space-y-6">
                        <Heading
                            variant="small"
                            title="Transaction history"
                            description="Recent credit transactions"
                        />

                        <div className="space-y-2">
                            {transactions.map((tx) => (
                                <div
                                    key={tx.id}
                                    className="flex items-center justify-between rounded-lg border p-3"
                                >
                                    <div>
                                        <p className="text-sm font-medium">
                                            {tx.description}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {new Date(
                                                tx.created_at,
                                            ).toLocaleDateString()}
                                        </p>
                                    </div>
                                    <p
                                        className={`text-sm font-medium ${tx.amount > 0 ? 'text-green-600' : 'text-red-600'}`}
                                    >
                                        {tx.amount > 0 ? '+' : ''}
                                        {tx.amount}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </SettingsLayout>
        </AppLayout>
    );
}
