import { router, usePage } from '@inertiajs/react';
import { Check, Copy, FileText, Gift, Sparkles, Target } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { create as resumeUploadCreate } from '@/routes/resume-upload';
import { create as jobPostingsCreate } from '@/routes/job-postings';

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

    function dismiss(navigateTo?: string) {
        setOpen(false);
        router.post('/welcome/dismiss', {}, {
            preserveScroll: true,
            onSuccess: () => {
                if (navigateTo) {
                    router.visit(navigateTo);
                }
            },
        });
    }

    function copyLink() {
        navigator.clipboard.writeText(referralUrl);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    }

    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && dismiss()}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Sparkles className="size-5 text-yellow-500" />
                        Welcome to CareerForge!
                    </DialogTitle>
                    <DialogDescription>
                        You have <strong className="text-foreground">250 credits</strong> to get started. How would you like to begin?
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-3 sm:grid-cols-2">
                    <button
                        onClick={() => dismiss(resumeUploadCreate().url)}
                        className="group flex flex-col items-center gap-3 rounded-lg border p-5 text-center transition-colors hover:border-primary hover:bg-primary/5"
                    >
                        <div className="rounded-full bg-primary/10 p-3 transition-colors group-hover:bg-primary/20">
                            <FileText className="size-6 text-primary" />
                        </div>
                        <div>
                            <p className="font-medium">Upload Resume</p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Import your existing resume to auto-fill your library
                            </p>
                        </div>
                    </button>

                    <button
                        onClick={() => dismiss(jobPostingsCreate().url)}
                        className="group flex flex-col items-center gap-3 rounded-lg border p-5 text-center transition-colors hover:border-primary hover:bg-primary/5"
                    >
                        <div className="rounded-full bg-primary/10 p-3 transition-colors group-hover:bg-primary/20">
                            <Target className="size-6 text-primary" />
                        </div>
                        <div>
                            <p className="font-medium">Analyze a Job</p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Paste a job URL to see how you match instantly
                            </p>
                        </div>
                    </button>
                </div>

                {referralCode && (
                    <div className="rounded-lg border bg-muted/50 p-3">
                        <div className="mb-1.5 flex items-center gap-2 text-xs font-medium">
                            <Gift className="size-3.5 text-primary" />
                            Earn 250 more credits
                        </div>
                        <p className="mb-2 text-xs text-muted-foreground">
                            Share your referral link — you both earn 250 bonus credits.
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
            </DialogContent>
        </Dialog>
    );
}
