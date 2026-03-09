import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type PipelineNextActionProps = {
    label: string;
    description?: string;
    href?: string;
    onClick?: () => void;
    className?: string;
};

export default function PipelineNextAction({ label, description, href, onClick, className }: PipelineNextActionProps) {
    const content = (
        <div className={cn('flex items-center justify-between rounded-lg border border-primary/20 bg-primary/5 px-4 py-3', className)}>
            <div className="flex items-center gap-3">
                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10">
                    <ArrowRight className="h-4 w-4 text-primary" />
                </div>
                <div>
                    <p className="text-sm font-medium">{label}</p>
                    {description && <p className="text-xs text-muted-foreground">{description}</p>}
                </div>
            </div>
            <Button size="sm" asChild={!!href} onClick={onClick}>
                {href ? (
                    <Link href={href}>
                        {label} <ArrowRight className="ml-1 h-3.5 w-3.5" />
                    </Link>
                ) : (
                    <>
                        {label} <ArrowRight className="ml-1 h-3.5 w-3.5" />
                    </>
                )}
            </Button>
        </div>
    );

    return content;
}
