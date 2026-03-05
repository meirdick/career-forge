import { Eye } from 'lucide-react';
import { useInView } from '@/hooks/use-in-view';

export default function TransparencyCallout() {
    const { ref, isInView } = useInView();

    return (
        <section ref={ref} className="px-6 py-16 lg:py-24">
            <div
                className={`mx-auto max-w-3xl text-center transition-all duration-600 ${isInView ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'}`}
            >
                <div className="relative mx-auto mb-6 flex h-16 w-16 items-center justify-center">
                    <div className="absolute inset-0 rounded-full bg-primary/10 animate-pulse-glow" />
                    <Eye className="relative h-8 w-8 text-primary" />
                </div>

                <h2 className="mb-4 text-3xl font-bold tracking-tight sm:text-4xl">
                    AI transparency is a feature, not a footnote
                </h2>
                <p className="mx-auto max-w-xl text-muted-foreground leading-relaxed">
                    Every AI-generated section includes a companion transparency page showing the prompts,
                    the reasoning, and the choices made. Share it with hiring managers to build trust
                    and demonstrate thoughtfulness.
                </p>
            </div>
        </section>
    );
}
