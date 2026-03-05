import { Link } from '@inertiajs/react';
import { Check } from 'lucide-react';

type Step = {
    label: string;
    href?: string;
    status: 'completed' | 'active' | 'upcoming';
};

export default function PipelineSteps({ steps }: { steps: Step[] }) {
    return (
        <div className="mb-6 flex items-center gap-1 overflow-x-auto">
            {steps.map((step, i) => (
                <div key={i} className="flex items-center">
                    {i > 0 && <div className="text-muted-foreground mx-1 text-xs">→</div>}
                    {step.href && step.status !== 'upcoming' ? (
                        <Link
                            href={step.href}
                            className={`rounded-full px-3 py-1 text-xs font-medium whitespace-nowrap transition-colors ${
                                step.status === 'active'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-muted-foreground hover:bg-muted/80'
                            }`}
                        >
                            {step.status === 'completed' && <Check className="mr-1 inline h-3 w-3" />}
                            {step.label}
                        </Link>
                    ) : (
                        <span
                            className={`rounded-full px-3 py-1 text-xs font-medium whitespace-nowrap ${
                                step.status === 'active'
                                    ? 'bg-primary text-primary-foreground'
                                    : step.status === 'completed'
                                      ? 'bg-muted text-muted-foreground'
                                      : 'text-muted-foreground/50'
                            }`}
                        >
                            {step.status === 'completed' && <Check className="mr-1 inline h-3 w-3" />}
                            {step.label}
                        </span>
                    )}
                </div>
            ))}
        </div>
    );
}
