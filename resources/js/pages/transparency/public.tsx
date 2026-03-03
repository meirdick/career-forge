import { Head } from '@inertiajs/react';

type TransparencyPage = {
    slug: string;
    authorship_statement: string;
    research_summary: string;
    ideal_profile_summary: string;
    section_decisions: { section: string; variant: string; reason: string }[];
    tool_description: string | null;
    repository_url: string | null;
    content_html: string | null;
};

export default function PublicTransparency({ page }: { page: TransparencyPage }) {
    return (
        <>
            <Head title="AI Transparency Statement" />

            <div className="mx-auto max-w-2xl px-4 py-12">
                <h1 className="mb-8 text-3xl font-bold">AI Transparency Statement</h1>

                <div className="space-y-8">
                    {page.authorship_statement && (
                        <section>
                            <h2 className="mb-2 text-xl font-semibold">Authorship Statement</h2>
                            <p className="text-muted-foreground whitespace-pre-wrap">{page.authorship_statement}</p>
                        </section>
                    )}

                    {page.research_summary && (
                        <section>
                            <h2 className="mb-2 text-xl font-semibold">Research Summary</h2>
                            <p className="text-muted-foreground whitespace-pre-wrap">{page.research_summary}</p>
                        </section>
                    )}

                    {page.ideal_profile_summary && (
                        <section>
                            <h2 className="mb-2 text-xl font-semibold">Ideal Candidate Profile</h2>
                            <p className="text-muted-foreground whitespace-pre-wrap">{page.ideal_profile_summary}</p>
                        </section>
                    )}

                    {page.section_decisions.length > 0 && (
                        <section>
                            <h2 className="mb-2 text-xl font-semibold">Section Decisions</h2>
                            <ul className="list-inside list-disc space-y-1">
                                {page.section_decisions.map((d, i) => (
                                    <li key={i} className="text-muted-foreground">
                                        <strong>{d.section}</strong> - {d.variant}: {d.reason}
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}

                    {page.tool_description && (
                        <section>
                            <h2 className="mb-2 text-xl font-semibold">Tools Used</h2>
                            <p className="text-muted-foreground whitespace-pre-wrap">{page.tool_description}</p>
                        </section>
                    )}

                    {page.repository_url && (
                        <section>
                            <p className="text-muted-foreground">
                                Repository: <a href={page.repository_url} className="text-primary underline" target="_blank" rel="noopener noreferrer">{page.repository_url}</a>
                            </p>
                        </section>
                    )}
                </div>

                <div className="text-muted-foreground mt-12 border-t pt-4 text-center text-xs">
                    Generated with CareerForge
                </div>
            </div>
        </>
    );
}
