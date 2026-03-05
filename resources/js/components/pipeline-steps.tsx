import { Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';

type Step = {
    label: string;
    href?: string;
    status: 'completed' | 'active' | 'upcoming';
};

export default function PipelineSteps({ steps }: { steps: Step[] }) {
    return (
        <>
            {/* Desktop stepper */}
            <div className="mb-6 hidden items-center sm:flex">
                {steps.map((step, i) => (
                    <div key={i} className="flex items-center">
                        {i > 0 && (
                            <div
                                className={cn(
                                    'h-0.5 w-8 md:w-12',
                                    step.status === 'upcoming'
                                        ? 'border-t border-dashed border-muted-foreground/30'
                                        : 'bg-primary',
                                )}
                            />
                        )}
                        <StepCircle step={step} index={i} />
                    </div>
                ))}
            </div>

            {/* Mobile compact */}
            <div className="mb-6 sm:hidden">
                {steps.map((step, i) => {
                    if (step.status !== 'active') return null;
                    return (
                        <div key={i} className="flex items-center gap-2">
                            <span className="rounded-full bg-primary px-2.5 py-0.5 text-xs font-medium text-primary-foreground">
                                Step {i + 1} of {steps.length}
                            </span>
                            <span className="text-sm font-medium">{step.label}</span>
                        </div>
                    );
                })}
            </div>
        </>
    );
}

function StepCircle({ step, index }: { step: Step; index: number }) {
    const circle = (
        <div className="flex items-center gap-2">
            <div
                className={cn(
                    'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold transition-colors',
                    step.status === 'completed' && 'bg-primary text-primary-foreground',
                    step.status === 'active' && 'ring-2 ring-primary bg-background text-primary',
                    step.status === 'upcoming' && 'bg-muted text-muted-foreground',
                )}
            >
                {step.status === 'completed' ? (
                    <Check className="h-3.5 w-3.5" />
                ) : (
                    index + 1
                )}
            </div>
            <span
                className={cn(
                    'hidden text-xs font-medium whitespace-nowrap md:inline',
                    step.status === 'active' && 'text-foreground',
                    step.status === 'completed' && 'text-foreground',
                    step.status === 'upcoming' && 'text-muted-foreground',
                )}
            >
                {step.label}
            </span>
        </div>
    );

    if (step.href && step.status !== 'upcoming') {
        return (
            <Link href={step.href} className="group flex items-center gap-2 transition-opacity hover:opacity-80">
                {circle}
            </Link>
        );
    }

    return circle;
}
