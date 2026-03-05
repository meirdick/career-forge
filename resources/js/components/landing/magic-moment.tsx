import { Badge } from '@/components/ui/badge';
import { useInView } from '@/hooks/use-in-view';

export default function MagicMoment() {
    const { ref, isInView } = useInView();

    return (
        <section ref={ref} className="px-6 py-16 lg:py-24 bg-muted/30">
            <div className="mx-auto max-w-5xl">
                <h2
                    className={`mb-4 text-center text-3xl font-bold tracking-tight sm:text-4xl transition-all duration-600 ${isInView ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'}`}
                >
                    From scattered experience to strategic precision
                </h2>
                <p
                    className={`mb-12 text-center text-muted-foreground transition-all duration-600 [transition-delay:100ms] ${isInView ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'}`}
                >
                    CareerForge doesn&apos;t write your resume. It helps you decide what to say.
                </p>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* Before */}
                    <div
                        className={`relative overflow-hidden rounded-xl border bg-card p-6 transition-all duration-600 [transition-delay:200ms] ${isInView ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'}`}
                    >
                        <div className="mb-4 inline-flex rounded-full bg-muted px-3 py-1 text-xs font-medium text-muted-foreground">
                            Before
                        </div>
                        <div className="space-y-3 select-none" aria-hidden="true">
                            <div className="h-3 rounded bg-muted-foreground/10" />
                            <div className="h-3 w-[90%] rounded bg-muted-foreground/10" />
                            <div className="h-3 w-[75%] rounded bg-muted-foreground/10" />
                            <div className="mt-4 h-3 rounded bg-muted-foreground/10" />
                            <div className="h-3 w-[85%] rounded bg-muted-foreground/10" />
                            <div className="h-3 w-[60%] rounded bg-muted-foreground/10" />
                            <div className="mt-4 h-3 rounded bg-muted-foreground/10" />
                            <div className="h-3 w-[70%] rounded bg-muted-foreground/10" />
                        </div>
                        <div className="absolute inset-0 bg-gradient-to-b from-transparent to-card/80" />
                    </div>

                    {/* After */}
                    <div
                        className={`rounded-xl border bg-card p-6 transition-all duration-600 [transition-delay:350ms] ${isInView ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'}`}
                    >
                        <div className="mb-4 flex items-center justify-between">
                            <span className="inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                                After
                            </span>
                            <Badge>92% Match</Badge>
                        </div>
                        <div className="space-y-4">
                            <div>
                                <div className="mb-1 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Summary</div>
                                <div className="text-sm leading-relaxed">
                                    Results-driven engineer with 5+ years building scalable web applications.
                                    Led cross-functional team of 8 through successful platform migration.
                                </div>
                            </div>
                            <div>
                                <div className="mb-1 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Key Skills</div>
                                <div className="flex flex-wrap gap-1.5">
                                    <Badge variant="secondary">React</Badge>
                                    <Badge variant="secondary">TypeScript</Badge>
                                    <Badge variant="secondary">System Design</Badge>
                                    <Badge variant="secondary">Team Leadership</Badge>
                                </div>
                            </div>
                            <div>
                                <div className="mb-1 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Impact</div>
                                <div className="text-sm leading-relaxed">
                                    Reduced deploy times by 70% and improved API response latency by 3x.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
