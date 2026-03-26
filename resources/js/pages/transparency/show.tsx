import { Head, router, Link } from '@inertiajs/react';
import { Eye, Globe, Save } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type TransparencyPage = {
    id: number;
    slug: string;
    authorship_statement: string;
    research_summary: string;
    ideal_profile_summary: string;
    section_decisions: { section: string; variant: string; reason: string }[];
    tool_description: string | null;
    repository_url: string | null;
    is_published: boolean;
};

type ApplicationData = {
    id: number;
    company: string;
    role: string;
};

type RecentView = { viewed_at: string; referer: string | null };

export default function ShowTransparency({ application, page, viewCount = 0, recentViews = [] }: { application: ApplicationData; page: TransparencyPage; viewCount?: number; recentViews?: RecentView[] }) {
    const [form, setForm] = useState({
        authorship_statement: page.authorship_statement ?? '',
        research_summary: page.research_summary ?? '',
        ideal_profile_summary: page.ideal_profile_summary ?? '',
        tool_description: page.tool_description ?? '',
        repository_url: page.repository_url ?? '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Applications', href: '/applications' },
        { title: `${application.role} at ${application.company}`, href: `/applications/${application.id}` },
        { title: 'Transparency', href: `/applications/${application.id}/transparency` },
    ];

    function save() {
        router.put(`/applications/${application.id}/transparency`, form, { preserveScroll: true });
    }

    function publish() {
        router.post(`/applications/${application.id}/transparency/publish`);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="AI Transparency" />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                <div className="flex items-start justify-between">
                    <Heading
                        title="AI Transparency Companion"
                        description={`${application.role} at ${application.company}`}
                    />
                    <div className="flex gap-2">
                        {page.is_published && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={`/t/${page.slug}`} target="_blank">
                                    <Globe className="mr-1 h-4 w-4" /> View Public Page
                                </Link>
                            </Button>
                        )}
                        {!page.is_published && (
                            <Button variant="outline" size="sm" onClick={publish}>
                                <Globe className="mr-1 h-4 w-4" /> Publish
                            </Button>
                        )}
                        <Button size="sm" onClick={save}>
                            <Save className="mr-1 h-4 w-4" /> Save
                        </Button>
                    </div>
                </div>

                {page.is_published && <Badge variant="secondary">Published</Badge>}

                <Card>
                    <CardHeader><CardTitle className="text-base">Authorship Statement</CardTitle></CardHeader>
                    <CardContent>
                        <Textarea
                            value={form.authorship_statement}
                            onChange={(e) => setForm({ ...form, authorship_statement: e.target.value })}
                            rows={4}
                            placeholder="Describe how AI was used in creating your application materials..."
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Research Summary</CardTitle></CardHeader>
                    <CardContent>
                        <Textarea
                            value={form.research_summary}
                            onChange={(e) => setForm({ ...form, research_summary: e.target.value })}
                            rows={4}
                            placeholder="Summarize the research AI performed on the role and company..."
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Ideal Candidate Profile Summary</CardTitle></CardHeader>
                    <CardContent>
                        <Textarea
                            value={form.ideal_profile_summary}
                            onChange={(e) => setForm({ ...form, ideal_profile_summary: e.target.value })}
                            rows={4}
                            placeholder="Describe the ideal candidate profile that was generated..."
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Tools & Repository</CardTitle></CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <Label htmlFor="tool_description">Tool Description</Label>
                            <Textarea
                                id="tool_description"
                                value={form.tool_description}
                                onChange={(e) => setForm({ ...form, tool_description: e.target.value })}
                                rows={3}
                                className="mt-1"
                                placeholder="Describe the AI tools used..."
                            />
                        </div>
                        <div>
                            <Label htmlFor="repository_url">Repository URL</Label>
                            <Input
                                id="repository_url"
                                value={form.repository_url}
                                onChange={(e) => setForm({ ...form, repository_url: e.target.value })}
                                placeholder="https://github.com/..."
                                className="mt-1"
                            />
                        </div>
                    </CardContent>
                </Card>

                {page.is_published && (
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <Eye className="h-4 w-4 text-muted-foreground" />
                                <CardTitle className="text-base">Page Views ({viewCount})</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {recentViews.length === 0 ? (
                                <p className="text-muted-foreground text-sm">No views yet.</p>
                            ) : (
                                <div className="space-y-1">
                                    {recentViews.map((view, i) => (
                                        <div key={i} className="flex justify-between text-sm">
                                            <span className="text-muted-foreground">{new Date(view.viewed_at).toLocaleString()}</span>
                                            {view.referer && <span className="truncate text-muted-foreground text-xs max-w-[200px]">{view.referer}</span>}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
