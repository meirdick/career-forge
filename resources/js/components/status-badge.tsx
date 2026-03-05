import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

const statusConfig: Record<string, { label: string; className: string }> = {
    draft: {
        label: 'Draft',
        className: 'bg-status-draft/15 text-status-draft border-status-draft/25',
    },
    applied: {
        label: 'Applied',
        className: 'bg-status-applied/15 text-status-applied border-status-applied/25',
    },
    interviewing: {
        label: 'Interviewing',
        className: 'bg-status-interviewing/15 text-status-interviewing border-status-interviewing/25',
    },
    offer: {
        label: 'Offer',
        className: 'bg-status-offer/15 text-status-offer border-status-offer/25',
    },
    rejected: {
        label: 'Rejected',
        className: 'bg-status-rejected/15 text-status-rejected border-status-rejected/25',
    },
    withdrawn: {
        label: 'Withdrawn',
        className: 'bg-status-withdrawn/15 text-status-withdrawn border-status-withdrawn/25',
    },
};

export default function StatusBadge({
    status,
    className,
}: {
    status: string;
    className?: string;
}) {
    const config = statusConfig[status];

    if (!config) {
        return (
            <Badge variant="secondary" className={className}>
                {status}
            </Badge>
        );
    }

    return (
        <Badge variant="outline" className={cn(config.className, className)}>
            {config.label}
        </Badge>
    );
}
