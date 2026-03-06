import { router, usePage } from '@inertiajs/react';
import { Check, Copy, Gift, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

export default function WelcomeModal() {
    const { showWelcome, referralCode } = usePage().props;
    const [open, setOpen] = useState(showWelcome);
    const [copied, setCopied] = useState(false);

    if (!showWelcome) {
        return null;
    }

    const referralUrl = referralCode
        ? `${window.location.origin}/register?ref=${referralCode}`
        : '';

    function dismiss() {
        setOpen(false);
        router.post('/welcome/dismiss', {}, { preserveScroll: true });
    }

    function copyLink() {
        navigator.clipboard.writeText(referralUrl);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    }

    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && dismiss()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Sparkles className="size-5 text-yellow-500" />
                        Welcome to CareerForge!
                    </DialogTitle>
                    <DialogDescription>
                        You have <strong className="text-foreground">250 credits</strong> to get started. Use them to parse resumes, analyze job postings, generate tailored resumes, and more.
                    </DialogDescription>
                </DialogHeader>

                {referralCode && (
                    <div className="rounded-lg border bg-muted/50 p-4">
                        <div className="mb-2 flex items-center gap-2 text-sm font-medium">
                            <Gift className="size-4 text-primary" />
                            Earn 250 more credits
                        </div>
                        <p className="mb-3 text-sm text-muted-foreground">
                            Share your referral link with a friend. When they sign up, you both earn 250 bonus credits.
                        </p>
                        <div className="flex gap-2">
                            <Input
                                readOnly
                                value={referralUrl}
                                className="text-xs"
                                onClick={(e) => e.currentTarget.select()}
                            />
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={copyLink}
                                className="shrink-0"
                            >
                                {copied ? (
                                    <Check className="size-4" />
                                ) : (
                                    <Copy className="size-4" />
                                )}
                            </Button>
                        </div>
                    </div>
                )}

                <DialogFooter>
                    <Button onClick={dismiss} className="w-full sm:w-auto">
                        Get Started
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
