import { router } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { commit } from '@/routes/career-chat';
import ExtractionReviewContent from './extraction-review/extraction-review-content';
import type { ExtractionData } from './extraction-review/types';

export type { ExtractionData };

interface ExtractionReviewPanelProps {
    data: ExtractionData;
    chatSessionId: number;
    open: boolean;
    onClose: () => void;
}

export default function ExtractionReviewPanel({ data, chatSessionId, open, onClose }: ExtractionReviewPanelProps) {
    const [importing, setImporting] = useState(false);

    function handleImport(payload: ExtractionData) {
        setImporting(true);
        router.post(commit(chatSessionId).url, payload, {
            onSuccess: () => {
                setImporting(false);
                onClose();
            },
            onError: () => {
                setImporting(false);
            },
        });
    }

    return (
        <Sheet open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <SheetContent className="w-full overflow-y-auto sm:max-w-lg">
                <SheetHeader>
                    <SheetTitle>Extracted Experiences</SheetTitle>
                </SheetHeader>

                <div className="py-4">
                    <ExtractionReviewContent data={data} onImport={handleImport} importing={importing} compact />
                </div>
            </SheetContent>
        </Sheet>
    );
}
