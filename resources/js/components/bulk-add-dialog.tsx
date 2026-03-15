import { router } from '@inertiajs/react';
import { AlertTriangle, Layers, Loader2 } from 'lucide-react';
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
import { Label } from '@/components/ui/label';

function isUnsupportedUrl(url: string): boolean {
    try {
        const host = new URL(url).hostname.toLowerCase();
        return host === 'linkedin.com' || host.endsWith('.linkedin.com');
    } catch {
        return false;
    }
}

export default function BulkAddDialog() {
    const [open, setOpen] = useState(false);
    const [text, setText] = useState('');
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    function submit(e: FormEvent) {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        const urls = text
            .split('\n')
            .map((line) => line.trim())
            .filter(Boolean);

        router.post('/job-postings/bulk', { urls }, {
            onSuccess: () => {
                setOpen(false);
                setText('');
            },
            onError: (errs) => {
                setErrors(errs);
            },
            onFinish: () => setProcessing(false),
        });
    }

    function handleOpenChange(value: boolean) {
        setOpen(value);
        if (!value) {
            setText('');
            setErrors({});
        }
    }

    const urlCount = text.split('\n').filter((line) => line.trim()).length;
    const hasErrors = Object.keys(errors).length > 0;
    const unsupportedCount = useMemo(
        () => text.split('\n').map((line) => line.trim()).filter((line) => line && isUnsupportedUrl(line)).length,
        [text],
    );

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogTrigger asChild>
                <Button variant="outline"><Layers className="mr-1 h-4 w-4" /> Bulk Add</Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Bulk Add Job Postings</DialogTitle>
                        <DialogDescription>Paste one URL per line (up to 20). Each will be fetched and analyzed automatically.</DialogDescription>
                    </DialogHeader>
                    <div className="mt-4 space-y-2">
                        <Label htmlFor="bulk-add-urls">Job Posting URLs</Label>
                        <textarea
                            id="bulk-add-urls"
                            className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 aria-invalid:border-destructive flex min-h-[120px] w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none disabled:pointer-events-none disabled:opacity-50"
                            placeholder={"https://example.com/jobs/senior-engineer\nhttps://example.com/jobs/staff-engineer"}
                            value={text}
                            onChange={(e) => setText(e.target.value)}
                            aria-invalid={hasErrors}
                            autoFocus
                        />
                        {unsupportedCount > 0 && !hasErrors && (
                            <p className="flex items-center gap-1 text-sm text-amber-600 dark:text-amber-400">
                                <AlertTriangle className="h-4 w-4 shrink-0" />
                                {unsupportedCount} LinkedIn URL{unsupportedCount !== 1 ? 's' : ''} detected. LinkedIn URLs cannot be automatically fetched.
                            </p>
                        )}
                        {hasErrors && (
                            <div className="space-y-1">
                                {Object.entries(errors).map(([key, message]) => (
                                    <p key={key} className="text-destructive text-sm">{message}</p>
                                ))}
                            </div>
                        )}
                        {urlCount > 0 && (
                            <p className="text-muted-foreground text-xs">
                                {urlCount} URL{urlCount !== 1 ? 's' : ''} detected
                                {urlCount > 20 && <span className="text-destructive"> (max 20)</span>}
                            </p>
                        )}
                    </div>
                    <DialogFooter className="mt-6">
                        <Button type="submit" disabled={processing || urlCount === 0 || urlCount > 20}>
                            {processing && <Loader2 className="mr-1 h-4 w-4 animate-spin" />}
                            Add {urlCount > 0 ? urlCount : ''} Posting{urlCount !== 1 ? 's' : ''}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
