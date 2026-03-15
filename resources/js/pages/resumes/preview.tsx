import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ChevronDown, Download, Settings } from 'lucide-react';
import Heading from '@/components/heading';
import ResumeDocument from '@/components/resume-templates/resume-document';
import TemplatePicker from '@/components/resume-templates/template-picker';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { useState } from 'react';

type Variant = { id: number; content: string; formatted_content: string };
type Section = { id: number; title: string; sort_order: number; is_hidden: boolean; selected_variant: Variant | null };
type Contact = {
    name?: string;
    email?: string;
    phone?: string;
    location?: string;
    linkedin_url?: string;
    portfolio_links?: { url: string; label: string }[];
};
type HeaderConfig = {
    name_preference: string;
    show_email: boolean;
    show_phone: boolean;
    show_location: boolean;
    show_linkedin: boolean;
    show_portfolio: boolean;
};
type ResumeData = {
    id: number;
    title: string;
    template: string;
    is_finalized: boolean;
    header_config: HeaderConfig | null;
    sections: Section[];
    job_posting: { title: string | null; company: string | null } | null;
};

const headerToggleFields = [
    { key: 'show_email' as const, label: 'Email' },
    { key: 'show_phone' as const, label: 'Phone' },
    { key: 'show_location' as const, label: 'Location' },
    { key: 'show_linkedin' as const, label: 'LinkedIn' },
    { key: 'show_portfolio' as const, label: 'Portfolio Links' },
];

export default function PreviewResume({ resume, contact, globalHeaderConfig }: { resume: ResumeData; contact: Contact; globalHeaderConfig: HeaderConfig }) {
    const [headerOpen, setHeaderOpen] = useState(false);

    const effectiveConfig: HeaderConfig = { ...globalHeaderConfig, ...(resume.header_config ?? {}) };

    function updateHeaderConfig(key: string, value: boolean | string) {
        const updated = { ...effectiveConfig, [key]: value };
        router.put(`/resumes/${resume.id}`, { header_config: updated }, { preserveScroll: true });
    }

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Resumes', href: '/resumes' },
        { title: resume.title, href: `/resumes/${resume.id}` },
        { title: 'Preview', href: `/resumes/${resume.id}/preview` },
    ];

    function changeTemplate(key: string) {
        router.put(`/resumes/${resume.id}`, { template: key }, { preserveScroll: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Preview - ${resume.title}`} />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                <div className="flex items-start justify-between">
                    <Heading
                        title="Resume Preview"
                        description={resume.job_posting ? `${resume.job_posting.title ?? 'Untitled'} at ${resume.job_posting.company ?? 'Unknown'}` : undefined}
                    />
                    <div className="flex gap-2">
                        <Link href={`/resumes/${resume.id}`}>
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="mr-1 h-4 w-4" /> Back to Editor
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Template Picker */}
                <div>
                    <h3 className="mb-2 text-sm font-medium">Template</h3>
                    <TemplatePicker selected={resume.template ?? 'classic'} onChange={changeTemplate} disabled={resume.is_finalized} />
                </div>

                {/* Header Settings */}
                {!resume.is_finalized && (
                    <Collapsible open={headerOpen} onOpenChange={setHeaderOpen}>
                        <CollapsibleTrigger asChild>
                            <Button variant="ghost" size="sm" className="gap-2">
                                <Settings className="h-4 w-4" />
                                Header Settings
                                <ChevronDown className={`h-4 w-4 transition-transform ${headerOpen ? 'rotate-180' : ''}`} />
                            </Button>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <Card className="mt-2">
                                <CardContent className="space-y-4 pt-4">
                                    <div>
                                        <Label className="mb-2 block text-sm font-medium">Name Preference</Label>
                                        <div className="flex items-center gap-4">
                                            <label className="flex items-center gap-2 text-sm">
                                                <input
                                                    type="radio"
                                                    name="name_preference"
                                                    value="display_name"
                                                    checked={effectiveConfig.name_preference === 'display_name'}
                                                    onChange={() => updateHeaderConfig('name_preference', 'display_name')}
                                                    className="accent-primary"
                                                />
                                                Display Name
                                            </label>
                                            <label className="flex items-center gap-2 text-sm">
                                                <input
                                                    type="radio"
                                                    name="name_preference"
                                                    value="legal_name"
                                                    checked={effectiveConfig.name_preference === 'legal_name'}
                                                    onChange={() => updateHeaderConfig('name_preference', 'legal_name')}
                                                    className="accent-primary"
                                                />
                                                Legal Name
                                            </label>
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-sm font-medium">Show on Resume</Label>
                                        {headerToggleFields.map((field) => (
                                            <label key={field.key} className="flex items-center gap-2">
                                                <Checkbox
                                                    checked={effectiveConfig[field.key]}
                                                    onCheckedChange={(checked) => updateHeaderConfig(field.key, !!checked)}
                                                />
                                                <span className="text-sm">{field.label}</span>
                                            </label>
                                        ))}
                                    </div>
                                    <p className="text-muted-foreground text-xs">
                                        These override your global defaults from the Identity page.
                                    </p>
                                </CardContent>
                            </Card>
                        </CollapsibleContent>
                    </Collapsible>
                )}

                {/* Export Actions */}
                <div className="flex gap-3">
                    <a href={`/resumes/${resume.id}/export/pdf`}>
                        <Button size="sm">
                            <Download className="mr-1 h-4 w-4" /> Download PDF
                        </Button>
                    </a>
                    <a href={`/resumes/${resume.id}/export/docx`}>
                        <Button variant="outline" size="sm">
                            <Download className="mr-1 h-4 w-4" /> Download DOCX
                        </Button>
                    </a>
                </div>

                {/* Resume Document Preview */}
                <ResumeDocument template={resume.template ?? 'classic'} contact={contact} sections={resume.sections.filter((s) => !s.is_hidden)} />
            </div>
        </AppLayout>
    );
}
