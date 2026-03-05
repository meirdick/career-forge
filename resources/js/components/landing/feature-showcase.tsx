import { Badge } from '@/components/ui/badge';
import { useInView } from '@/hooks/use-in-view';

function FeatureBlock({
    heading,
    description,
    visual,
    reverse = false,
}: {
    heading: string;
    description: string;
    visual: React.ReactNode;
    reverse?: boolean;
}) {
    const { ref, isInView } = useInView();

    return (
        <div
            ref={ref}
            className={`flex flex-col gap-8 lg:gap-16 ${reverse ? 'lg:flex-row-reverse' : 'lg:flex-row'} items-center`}
        >
            <div
                className={`flex-1 transition-all duration-600 ${isInView ? 'opacity-100 translate-x-0' : `opacity-0 ${reverse ? 'translate-x-6' : '-translate-x-6'}`}`}
            >
                <h3 className="mb-3 text-2xl font-bold tracking-tight">{heading}</h3>
                <p className="text-muted-foreground leading-relaxed">{description}</p>
            </div>
            <div
                className={`flex-1 w-full transition-all duration-600 [transition-delay:150ms] ${isInView ? 'opacity-100 translate-x-0' : `opacity-0 ${reverse ? '-translate-x-6' : 'translate-x-6'}`}`}
            >
                {visual}
            </div>
        </div>
    );
}

function ExperienceLibraryMock() {
    return (
        <div className="rounded-xl border bg-card p-5 shadow-card">
            <div className="mb-4 flex items-center gap-3">
                <div className="h-10 w-10 rounded-full bg-primary/10" />
                <div>
                    <div className="text-sm font-semibold">Senior Software Engineer</div>
                    <div className="text-xs text-muted-foreground">Acme Corp &middot; 2021 &ndash; Present</div>
                </div>
            </div>
            <p className="mb-3 text-sm text-muted-foreground">
                Led migration of monolithic PHP application to microservices architecture, reducing deploy times by 70%.
            </p>
            <div className="flex flex-wrap gap-1.5">
                <Badge variant="secondary">PHP</Badge>
                <Badge variant="secondary">Laravel</Badge>
                <Badge variant="secondary">Microservices</Badge>
                <Badge variant="secondary">Leadership</Badge>
            </div>
        </div>
    );
}

function AnalysisMock() {
    return (
        <div className="rounded-xl border bg-card p-5 shadow-card">
            <div className="mb-4 text-sm font-semibold">Ideal Candidate Profile</div>
            <div className="space-y-3">
                {[
                    { label: 'Must Have', items: ['React', 'TypeScript', '3+ yrs'], color: 'bg-destructive/10 text-destructive' },
                    { label: 'Nice to Have', items: ['Next.js', 'GraphQL'], color: 'bg-warning/10 text-warning' },
                    { label: 'Culture Signals', items: ['Remote-first', 'Async comms'], color: 'bg-success/10 text-success' },
                ].map(({ label, items, color }) => (
                    <div key={label}>
                        <div className={`mb-1.5 inline-flex rounded-md px-2 py-0.5 text-xs font-medium ${color}`}>
                            {label}
                        </div>
                        <div className="flex flex-wrap gap-1.5">
                            {items.map((item) => (
                                <Badge key={item} variant="outline">{item}</Badge>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

function ResumeVariantsMock() {
    return (
        <div className="grid gap-3 sm:grid-cols-3">
            {['Technical Focus', 'Leadership Focus', 'Hybrid'].map((variant, i) => (
                <div
                    key={variant}
                    className={`rounded-xl border bg-card p-4 shadow-card transition-shadow hover:shadow-card-hover ${i === 0 ? 'ring-2 ring-primary' : ''}`}
                >
                    <div className="mb-2 flex items-center justify-between">
                        <span className="text-xs font-semibold">{variant}</span>
                        {i === 0 && (
                            <span className="flex h-4 w-4 items-center justify-center rounded-full bg-primary text-[10px] text-primary-foreground">
                                &#10003;
                            </span>
                        )}
                    </div>
                    <div className="space-y-1.5">
                        <div className="h-2 rounded bg-muted" />
                        <div className="h-2 w-4/5 rounded bg-muted" />
                        <div className="h-2 w-3/5 rounded bg-muted" />
                    </div>
                </div>
            ))}
        </div>
    );
}

export default function FeatureShowcase() {
    return (
        <section className="px-6 py-16 lg:py-24">
            <div className="mx-auto max-w-5xl space-y-20 lg:space-y-28">
                <FeatureBlock
                    heading="Your career, structured and searchable"
                    description="Every role, project, and accomplishment lives in your experience library. Tag skills, attach evidence, and let AI surface exactly the right experience for each application."
                    visual={<ExperienceLibraryMock />}
                />
                <FeatureBlock
                    heading="Understand what they really want"
                    description="Paste a job posting and CareerForge deconstructs it into must-haves, nice-to-haves, and culture signals — so you know exactly where you stand before you apply."
                    visual={<AnalysisMock />}
                    reverse
                />
                <FeatureBlock
                    heading="Every section, multiple versions"
                    description="CareerForge generates several takes on each resume section. Pick the angle that best tells your story for this specific role — technical depth, leadership impact, or a blend of both."
                    visual={<ResumeVariantsMock />}
                />
            </div>
        </section>
    );
}
