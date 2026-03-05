import { Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { useEffect, useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

export default function HeroSection({ canRegister }: { canRegister: boolean }) {
    const { auth } = usePage().props;
    const [scrolled, setScrolled] = useState(false);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 10);
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    return (
        <>
            {/* Sticky Nav */}
            <header
                className={`sticky top-0 z-50 backdrop-blur-xl bg-background/80 transition-[border-color] duration-300 ${scrolled ? 'border-b' : 'border-b border-transparent'}`}
            >
                <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                    <div className="flex items-center gap-2.5">
                        <div className="flex h-8 w-8 items-center justify-center rounded-md bg-primary">
                            <AppLogoIcon className="size-4 fill-current text-primary-foreground" />
                        </div>
                        <span className="text-lg font-bold tracking-tight">CareerForge</span>
                    </div>
                    <nav className="flex items-center gap-3">
                        {auth.user ? (
                            <Button asChild>
                                <Link href={dashboard()}>Dashboard</Link>
                            </Button>
                        ) : (
                            <>
                                <Button variant="ghost" asChild>
                                    <Link href={login()}>Log in</Link>
                                </Button>
                                {canRegister && (
                                    <Button asChild>
                                        <Link href={register()}>Get Started</Link>
                                    </Button>
                                )}
                            </>
                        )}
                    </nav>
                </div>
            </header>

            {/* Hero */}
            <section className="relative overflow-hidden px-6 py-20 lg:py-32">
                {/* Animated gradient orbs */}
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -top-32 left-1/2 -translate-x-1/2"
                >
                    <div className="absolute -left-64 top-0 h-[480px] w-[480px] rounded-full bg-primary/20 blur-3xl animate-pulse-glow" />
                    <div className="absolute -right-48 top-24 h-[400px] w-[400px] rounded-full bg-info/15 blur-3xl animate-pulse-glow [animation-delay:2s]" />
                </div>

                <div className="relative mx-auto max-w-3xl text-center">
                    {/* Badge */}
                    <div
                        className="mb-8 inline-flex items-center rounded-full border bg-accent px-4 py-1.5 text-sm text-accent-foreground opacity-0 animate-slide-up"
                    >
                        Open Source &middot; Self-Hosted &middot; Your Data
                    </div>

                    {/* Headline */}
                    <h1
                        className="mb-6 text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl opacity-0 animate-slide-up [animation-delay:100ms]"
                    >
                        Your career is more than{' '}
                        <span className="bg-gradient-to-r from-primary to-info bg-clip-text text-transparent">
                            one page
                        </span>
                    </h1>

                    {/* Subheading */}
                    <p
                        className="mx-auto mb-10 max-w-2xl text-lg text-muted-foreground opacity-0 animate-slide-up [animation-delay:200ms]"
                    >
                        CareerForge builds a persistent experience library, then uses AI to bridge the gap
                        between who you are and what each role demands.
                    </p>

                    {/* CTAs */}
                    <div
                        className="flex flex-wrap items-center justify-center gap-4 opacity-0 animate-slide-up [animation-delay:300ms]"
                    >
                        {auth.user ? (
                            <Button size="lg" asChild>
                                <Link href={dashboard()}>
                                    Go to Dashboard <ArrowRight className="ml-2 h-4 w-4" />
                                </Link>
                            </Button>
                        ) : (
                            <>
                                <Button size="lg" asChild>
                                    <Link href={canRegister ? register() : login()}>
                                        Start Building <ArrowRight className="ml-2 h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button size="lg" variant="outline" asChild>
                                    <a href="#how-it-works">See How It Works &darr;</a>
                                </Button>
                            </>
                        )}
                    </div>
                </div>
            </section>
        </>
    );
}
