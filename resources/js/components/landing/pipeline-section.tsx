import { Eye, FileText, Library, Target, TrendingUp } from 'lucide-react';
import { useInView } from '@/hooks/use-in-view';

const steps = [
    {
        icon: Library,
        title: 'Build Your Library',
        description: 'Capture every role, skill, and accomplishment.',
    },
    {
        icon: Target,
        title: 'Analyze the Target',
        description: 'Paste a job posting. AI builds the ideal candidate profile.',
    },
    {
        icon: TrendingUp,
        title: 'Bridge the Gap',
        description: 'See how your experience maps to what they want.',
    },
    {
        icon: FileText,
        title: 'Generate Tailored Resumes',
        description: 'Multiple variants per section. You pick the combination.',
    },
    {
        icon: Eye,
        title: 'Apply with Transparency',
        description: 'Share how AI helped craft your application.',
    },
] as const;

export default function PipelineSection() {
    const { ref, isInView } = useInView({ threshold: 0.1 });

    return (
        <section id="how-it-works" ref={ref} className="px-6 py-16 lg:py-24">
            <div className="mx-auto max-w-6xl">
                <h2 className="mb-14 text-center text-3xl font-bold tracking-tight sm:text-4xl">
                    From experience to opportunity in five steps
                </h2>

                {/* Desktop: horizontal timeline */}
                <div className="hidden lg:block">
                    <div className="relative grid grid-cols-5 gap-4">
                        {/* Connecting line */}
                        <div
                            aria-hidden="true"
                            className="absolute top-7 left-[10%] right-[10%] h-px bg-gradient-to-r from-transparent via-border to-transparent"
                        />

                        {steps.map(({ icon: Icon, title, description }, i) => (
                            <div
                                key={title}
                                className={`relative flex flex-col items-center text-center transition-all duration-600 ${isInView ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'}`}
                                style={{ transitionDelay: `${i * 120}ms` }}
                            >
                                <div className="relative z-10 mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                    <Icon className="h-6 w-6" />
                                </div>
                                <h3 className="mb-1.5 text-sm font-semibold">{title}</h3>
                                <p className="text-sm text-muted-foreground leading-relaxed">{description}</p>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Mobile / tablet: vertical timeline */}
                <div className="lg:hidden">
                    <div className="relative space-y-8 pl-10">
                        {/* Vertical line */}
                        <div
                            aria-hidden="true"
                            className="absolute top-2 bottom-2 left-[18px] w-px bg-gradient-to-b from-transparent via-border to-transparent"
                        />

                        {steps.map(({ icon: Icon, title, description }, i) => (
                            <div
                                key={title}
                                className={`relative flex gap-4 transition-all duration-500 ${isInView ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-4'}`}
                                style={{ transitionDelay: `${i * 100}ms` }}
                            >
                                <div className="absolute -left-10 flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <Icon className="h-4 w-4" />
                                </div>
                                <div>
                                    <h3 className="mb-1 text-sm font-semibold">{title}</h3>
                                    <p className="text-sm text-muted-foreground">{description}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}
