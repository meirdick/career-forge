import { Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useInView } from '@/hooks/use-in-view';
import { dashboard, login, register } from '@/routes';

export default function FinalCta({ canRegister }: { canRegister: boolean }) {
    const { auth } = usePage().props;
    const { ref, isInView } = useInView();

    return (
        <section
            ref={ref}
            className="relative overflow-hidden px-6 py-20 lg:py-28"
        >
            <div aria-hidden="true" className="pointer-events-none absolute inset-0 bg-gradient-to-b from-primary/5 to-transparent" />
            <div
                className={`relative mx-auto max-w-2xl text-center transition-all duration-600 ${isInView ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'}`}
            >
                <h2 className="mb-4 text-3xl font-bold tracking-tight sm:text-4xl">
                    Stop rewriting. Start forging.
                </h2>
                <p className="mb-8 text-muted-foreground">
                    Your career deserves more than a one-size-fits-all approach.
                </p>

                {auth.user ? (
                    <Button size="lg" asChild>
                        <Link href={dashboard()}>
                            Go to Dashboard <ArrowRight className="ml-2 h-4 w-4" />
                        </Link>
                    </Button>
                ) : (
                    <Button size="lg" asChild>
                        <Link href={canRegister ? register() : login()}>
                            Get Started &mdash; It&apos;s Free <ArrowRight className="ml-2 h-4 w-4" />
                        </Link>
                    </Button>
                )}

                <p className="mt-6 text-xs text-muted-foreground">
                    Self-hosted. Open source. Your data never leaves your machine.
                </p>
            </div>
        </section>
    );
}
