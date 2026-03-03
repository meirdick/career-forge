import { Head, router, Link } from '@inertiajs/react';
import { Globe, Save } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

export default function ShowTransparency({ application, page }: { application: ApplicationData; page: TransparencyPage }) {
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

            <div className="mx-auto max-w-3xl space-y-6 p-4">
                <div className="flex items-start justify-between">
                    <Heading
                        title="AI Transparency Companion"
                        description={`${application.role} at ${application.company}`}
                    />
                    <div className="flex gap-2">
                        {page.is_published && (
                            <Link href={`/t/${page.slug}`} target="_blank">
                                <Button variant="outline" size="sm">
                                    <Globe className="mr-1 h-4 w-4" /> View Public Page
                                </Button>
                            </Link>
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
                        <textarea
                            value={form.authorship_statement}
                            onChange={(e) => setForm({ ...form, authorship_statement: e.target.value })}
                            rows={4}
                            className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm"
                            placeholder="Describe how AI was used in creating your application materials..."
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Research Summary</CardTitle></CardHeader>
                    <CardContent>
                        <textarea
                            value={form.research_summary}
                            onChange={(e) => setForm({ ...form, research_summary: e.target.value })}
                            rows={4}
                            className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm"
                            placeholder="Summarize the research AI performed on the role and company..."
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Ideal Candidate Profile Summary</CardTitle></CardHeader>
                    <CardContent>
                        <textarea
                            value={form.ideal_profile_summary}
                            onChange={(e) => setForm({ ...form, ideal_profile_summary: e.target.value })}
                            rows={4}
                            className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm"
                            placeholder="Describe the ideal candidate profile that was generated..."
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Tools & Repository</CardTitle></CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <Label htmlFor="tool_description">Tool Description</Label>
                            <textarea
                                id="tool_description"
                                value={form.tool_description}
                                onChange={(e) => setForm({ ...form, tool_description: e.target.value })}
                                rows={3}
                                className="border-input bg-background mt-1 flex w-full rounded-md border px-3 py-2 text-sm"
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
            </div>
        </AppLayout>
    );
}
