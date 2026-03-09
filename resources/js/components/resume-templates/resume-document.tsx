import { cn } from '@/lib/utils';
import { getTemplateConfig } from './template-config';

type PortfolioLink = {
    url: string;
    label: string;
};

type ContactInfo = {
    name?: string;
    email?: string;
    phone?: string;
    location?: string;
    linkedin_url?: string;
    portfolio_links?: PortfolioLink[];
};

type Section = {
    id: number;
    title: string;
    sort_order: number;
    selected_variant: { content: string; formatted_content: string } | null;
};

type ResumeDocumentProps = {
    template: string;
    contact: ContactInfo;
    sections: Section[];
    className?: string;
};

export default function ResumeDocument({ template, contact, sections, className }: ResumeDocumentProps) {
    const config = getTemplateConfig(template);

    const contactParts = [contact.email, contact.phone, contact.location, contact.linkedin_url, ...(contact.portfolio_links ?? []).map((l) => l.label)].filter(Boolean);

    const sortedSections = [...sections].sort((a, b) => a.sort_order - b.sort_order);

    return (
        <div
            className={cn(
                'rounded-lg border bg-white shadow-lg dark:bg-gray-950',
                'aspect-[8.5/11] w-full overflow-y-auto',
                config.bodyClass,
                className,
            )}
        >
            <div className="p-8">
                {/* Contact Header */}
                <div className={config.headerClass}>
                    <h1 className={config.nameClass}>{contact.name ?? 'Candidate'}</h1>
                    {contactParts.length > 0 && <p className={config.contactClass}>{contactParts.join(' | ')}</p>}
                </div>

                {/* Sections */}
                <div className={cn('mt-4 space-y-4', template === 'moderncv' ? 'mt-6' : '')}>
                    {sortedSections.map((section) => (
                        <div key={section.id}>
                            <h2 className={config.sectionHeadingClass}>{section.title}</h2>
                            {section.selected_variant && (
                                <div
                                    className="prose prose-sm dark:prose-invert max-w-none text-sm leading-relaxed"
                                    dangerouslySetInnerHTML={{ __html: section.selected_variant.formatted_content }}
                                />
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
