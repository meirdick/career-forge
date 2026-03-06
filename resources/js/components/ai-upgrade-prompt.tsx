import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { show as showBilling } from '@/routes/billing';

export default function AiUpgradePrompt({
    open,
    onClose,
}: {
    open: boolean;
    onClose: () => void;
}) {
    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>You're out of credits</DialogTitle>
                    <DialogDescription>
                        Purchase more credits to continue using AI-powered features like resume parsing, job analysis, and tailored resume generation.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button
                        onClick={() => {
                            onClose();
                            router.visit(showBilling().url);
                        }}
                    >
                        Buy Credits ($5 = 500 credits)
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
