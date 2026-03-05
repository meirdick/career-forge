import { cn } from '@/lib/utils';

function scoreColor(score: number): string {
    if (score >= 80) return 'text-success';
    if (score >= 60) return 'text-info';
    if (score >= 40) return 'text-warning';
    return 'text-destructive';
}

function strokeColor(score: number): string {
    if (score >= 80) return 'stroke-success';
    if (score >= 60) return 'stroke-info';
    if (score >= 40) return 'stroke-warning';
    return 'stroke-destructive';
}

export default function MatchScoreRing({
    score,
    size = 96,
    strokeWidth = 6,
    delta,
    className,
}: {
    score: number;
    size?: number;
    strokeWidth?: number;
    delta?: number;
    className?: string;
}) {
    const radius = (size - strokeWidth) / 2;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (score / 100) * circumference;
    const center = size / 2;

    return (
        <div className={cn('relative inline-flex items-center justify-center', className)}>
            <svg width={size} height={size} className="-rotate-90">
                <circle
                    cx={center}
                    cy={center}
                    r={radius}
                    fill="none"
                    strokeWidth={strokeWidth}
                    className="stroke-muted"
                />
                <circle
                    cx={center}
                    cy={center}
                    r={radius}
                    fill="none"
                    strokeWidth={strokeWidth}
                    strokeLinecap="round"
                    strokeDasharray={circumference}
                    strokeDashoffset={offset}
                    className={cn('transition-all duration-700 ease-out', strokeColor(score))}
                />
            </svg>
            <div className="absolute inset-0 flex flex-col items-center justify-center">
                <span className={cn('text-xl font-bold', scoreColor(score))}>
                    {score}%
                </span>
                {delta !== undefined && delta !== 0 && (
                    <span className={cn(
                        'text-xs font-medium',
                        delta > 0 ? 'text-success' : 'text-destructive',
                    )}>
                        {delta > 0 ? '+' : ''}{delta}
                    </span>
                )}
            </div>
        </div>
    );
}
