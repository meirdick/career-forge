import { Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';

type Step = {
    label: string;
    href?: string;
    status: 'completed' | 'active' | 'upcoming';
};

export default function PipelineSteps({ steps }: { steps: Step[] }) {
    const activeIndex = steps.findIndex((s) => s.status === 'active');

    return (
        <>
            {/* Desktop stepper */}
            <nav aria-label="Progress" className="mb-8 hidden sm:block">
                <ol className="flex items-center">
                    {steps.map((step, i) => (
                        <li key={i} className={cn('relative flex items-center', i < steps.length - 1 && 'flex-1')}>
                            <StepIndicator step={step} index={i} />
                            {i < steps.length - 1 && <StepConnector nextStatus={steps[i + 1].status} />}
                        </li>
                    ))}
                </ol>
            </nav>

            {/* Mobile stepper */}
            <nav aria-label="Progress" className="mb-6 sm:hidden">
                <div className="flex items-center gap-3">
                    <div className="flex items-center gap-1.5">
                        {steps.map((step, i) => (
                            <div
                                key={i}
                                className={cn(
                                    'h-1.5 rounded-full transition-all',
                                    i === activeIndex ? 'w-6 bg-primary' : 'w-1.5',
                                    step.status === 'completed' && 'bg-primary',
                                    step.status === 'upcoming' && 'bg-muted-foreground/20',
                                )}
                            />
                        ))}
                    </div>
                    <span className="text-sm text-muted-foreground">
                        Step {activeIndex + 1} of {steps.length}
                    </span>
                    <span className="text-sm font-medium">{steps[activeIndex]?.label}</span>
                </div>
            </nav>
        </>
    );
}

function StepConnector({ nextStatus }: { nextStatus: Step['status'] }) {
    return (
        <div className="mx-1 h-0.5 flex-1 overflow-hidden rounded-full bg-muted md:mx-2">
            <div
                className={cn(
                    'h-full rounded-full bg-primary transition-all duration-500',
                    nextStatus === 'upcoming' ? 'w-0' : 'w-full',
                )}
            />
        </div>
    );
}

function StepIndicator({ step, index }: { step: Step; index: number }) {
    const indicator = (
        <span className="group relative flex flex-col items-center">
            <span
                className={cn(
                    'relative z-10 flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold transition-all duration-200',
                    step.status === 'completed' && 'bg-primary text-primary-foreground shadow-sm',
                    step.status === 'active' &&
                        'bg-primary/10 text-primary ring-2 ring-primary shadow-[0_0_0_4px_rgba(var(--color-primary)/0.1)]',
                    step.status === 'upcoming' && 'bg-muted text-muted-foreground',
                )}
            >
                {step.status === 'completed' ? <Check className="h-4 w-4" strokeWidth={2.5} /> : index + 1}
            </span>
            <span
                className={cn(
                    'mt-2 hidden text-xs font-medium whitespace-nowrap md:block',
                    step.status === 'active' && 'text-primary',
                    step.status === 'completed' && 'text-foreground',
                    step.status === 'upcoming' && 'text-muted-foreground',
                )}
            >
                {step.label}
            </span>
        </span>
    );

    if (step.href && step.status !== 'upcoming') {
        return (
            <Link href={step.href} className="transition-opacity hover:opacity-75">
                {indicator}
            </Link>
        );
    }

    return indicator;
}
