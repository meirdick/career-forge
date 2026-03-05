import { Code, Eye, Lock, Shield } from 'lucide-react';
import { useInView } from '@/hooks/use-in-view';

const signals = [
    { icon: Shield, label: 'Self-Hosted' },
    { icon: Lock, label: 'Your Data Stays Yours' },
    { icon: Code, label: 'Open Source' },
    { icon: Eye, label: 'Full AI Transparency' },
] as const;

export default function TrustBar() {
    const { ref, isInView } = useInView();

    return (
        <section ref={ref} className="border-y bg-muted/30">
            <div className="mx-auto grid max-w-4xl grid-cols-2 gap-6 px-6 py-8 lg:grid-cols-4 lg:gap-8">
                {signals.map(({ icon: Icon, label }, i) => (
                    <div
                        key={label}
                        className={`flex items-center justify-center gap-2.5 text-sm text-muted-foreground transition-all duration-500 ${isInView ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'}`}
                        style={{ transitionDelay: `${i * 80}ms` }}
                    >
                        <Icon className="h-4 w-4 shrink-0" />
                        <span>{label}</span>
                    </div>
                ))}
            </div>
        </section>
    );
}
