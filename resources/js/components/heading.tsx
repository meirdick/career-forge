import type { ReactNode } from 'react';

export default function Heading({
    title,
    description,
    variant = 'default',
    actions,
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
    actions?: ReactNode;
}) {
    return (
        <header className={variant === 'small' ? '' : 'mb-8 space-y-0.5'}>
            <div className="flex items-center justify-between gap-4">
                <div className="min-w-0">
                    <h2
                        className={
                            variant === 'small'
                                ? 'mb-0.5 text-base font-medium'
                                : 'text-xl font-semibold tracking-tight'
                        }
                    >
                        {title}
                    </h2>
                    {description && (
                        <p className="text-sm text-muted-foreground">{description}</p>
                    )}
                </div>
                {actions && <div className="flex shrink-0 items-center gap-2">{actions}</div>}
            </div>
        </header>
    );
}
