import { router } from '@inertiajs/react';
import { AlertTriangle, Loader2, Zap } from 'lucide-react';
import type { FormEvent} from 'react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

function isUnsupportedUrl(url: string): boolean {
    try {
        const host = new URL(url).hostname.toLowerCase();
        return host === 'linkedin.com' || host.endsWith('.linkedin.com');
    } catch {
        return false;
    }
}

export default function QuickAddDialog() {
    const [open, setOpen] = useState(false);
    const [url, setUrl] = useState('');
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const hasUnsupportedUrl = useMemo(() => isUnsupportedUrl(url), [url]);

    function submit(e: FormEvent) {
        e.preventDefault();
        setProcessing(true);
        setError(null);

        router.post('/job-postings/quick', { url }, {
            onSuccess: () => {
                setOpen(false);
                setUrl('');
            },
            onError: (errors) => {
                setError(errors.url ?? 'Something went wrong.');
            },
            onFinish: () => setProcessing(false),
        });
    }

    function handleOpenChange(value: boolean) {
        setOpen(value);
        if (!value) {
            setUrl('');
            setError(null);
        }
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogTrigger asChild>
                <Button variant="outline"><Zap className="mr-1 h-4 w-4" /> Quick Add</Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Quick Add Job Posting</DialogTitle>
                        <DialogDescription>Paste a job posting URL and we'll fetch and analyze it automatically.</DialogDescription>
                    </DialogHeader>
                    <div className="mt-4 space-y-2">
                        <Label htmlFor="quick-add-url">Job Posting URL</Label>
                        <Input
                            id="quick-add-url"
                            type="url"
                            placeholder="https://example.com/jobs/senior-engineer"
                            value={url}
                            onChange={(e) => setUrl(e.target.value)}
                            aria-invalid={!!error}
                            autoFocus
                        />
                        {hasUnsupportedUrl && !error && (
                            <p className="text-warning flex items-center gap-1 text-sm text-amber-600 dark:text-amber-400">
                                <AlertTriangle className="h-4 w-4 shrink-0" />
                                LinkedIn URLs cannot be automatically fetched. Use "New Posting" to paste the job description instead.
                            </p>
                        )}
                        {error && <p className="text-destructive text-sm">{error}</p>}
                    </div>
                    <DialogFooter className="mt-6">
                        <Button type="submit" disabled={processing || !url.trim()}>
                            {processing && <Loader2 className="mr-1 h-4 w-4 animate-spin" />}
                            Add Posting
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
