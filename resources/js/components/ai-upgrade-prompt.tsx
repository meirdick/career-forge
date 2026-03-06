import { router } from '@inertiajs/react';
import { show as showApiKeys } from '@/routes/api-keys';
import { show as showBilling } from '@/routes/billing';

export default function AiUpgradePrompt({
    open,
    onClose,
}: {
    open: boolean;
    onClose: () => void;
}) {
    if (!open) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div className="mx-4 w-full max-w-md rounded-lg bg-background p-6 shadow-lg">
                <h2 className="text-lg font-semibold">
                    AI access limit reached
                </h2>
                <p className="mt-2 text-sm text-muted-foreground">
                    You&apos;ve used up your free tier allowance. To continue
                    using AI features, add your own API key or purchase credits.
                </p>

                <div className="mt-6 flex flex-col gap-3">
                    <button
                        onClick={() => {
                            onClose();
                            router.visit(showApiKeys().url);
                        }}
                        className="w-full rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-muted"
                    >
                        Add API Key (BYOK)
                    </button>

                    <button
                        onClick={() => {
                            onClose();
                            router.visit(showBilling().url);
                        }}
                        className="w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        Buy Credits ($5 = 500 credits)
                    </button>
                </div>

                <button
                    onClick={onClose}
                    className="mt-4 w-full text-center text-sm text-muted-foreground hover:text-foreground"
                >
                    Cancel
                </button>
            </div>
        </div>
    );
}
