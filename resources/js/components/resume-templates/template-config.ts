export type TemplateConfig = {
    key: string;
    name: string;
    description: string;
    headerClass: string;
    nameClass: string;
    contactClass: string;
    sectionHeadingClass: string;
    accentClass: string;
    bodyClass: string;
};

export const templates: TemplateConfig[] = [
    {
        key: 'classic',
        name: 'Classic',
        description: 'Traditional single-column layout with clean typography',
        headerClass: 'text-center border-b-2 border-gray-800 dark:border-gray-200 pb-3',
        nameClass: 'text-2xl font-bold tracking-wide text-gray-900 dark:text-gray-100',
        contactClass: 'text-xs text-gray-500 dark:text-gray-400 mt-1',
        sectionHeadingClass: 'text-xs font-bold uppercase tracking-widest text-gray-800 dark:text-gray-200 border-b border-gray-300 dark:border-gray-600 pb-1 mb-2',
        accentClass: 'border-gray-300 dark:border-gray-600',
        bodyClass: 'font-sans',
    },
    {
        key: 'moderncv',
        name: 'Modern CV',
        description: 'Two-column feel with accent colors and modern style',
        headerClass: 'bg-slate-800 dark:bg-slate-900 text-white px-6 py-4 -mx-8 -mt-8 rounded-t-lg',
        nameClass: 'text-2xl font-bold tracking-wide',
        contactClass: 'text-xs text-slate-300 mt-1',
        sectionHeadingClass: 'text-sm font-bold text-blue-700 dark:text-blue-400 border-l-3 border-blue-700 dark:border-blue-400 pl-3 mb-2',
        accentClass: 'border-blue-700 dark:border-blue-400',
        bodyClass: 'font-sans',
    },
    {
        key: 'sb2nov',
        name: 'SB2Nov',
        description: 'Compact single-column format popular in tech',
        headerClass: 'text-center pb-2',
        nameClass: 'text-xl font-bold text-gray-900 dark:text-gray-100',
        contactClass: 'text-xs text-gray-600 dark:text-gray-400 mt-0.5',
        sectionHeadingClass: 'text-xs font-bold uppercase tracking-widest text-gray-900 dark:text-gray-100 border-b-2 border-gray-900 dark:border-gray-100 pb-0.5 mb-1.5',
        accentClass: 'border-gray-900 dark:border-gray-100',
        bodyClass: 'font-sans text-sm',
    },
    {
        key: 'engineeringresumes',
        name: 'Engineering',
        description: 'Dense, achievement-focused engineering layout',
        headerClass: 'text-center border-b border-gray-400 dark:border-gray-500 pb-2',
        nameClass: 'text-lg font-bold text-gray-900 dark:text-gray-100',
        contactClass: 'text-xs text-gray-600 dark:text-gray-400 mt-0.5',
        sectionHeadingClass: 'text-xs font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200 border-b border-gray-400 dark:border-gray-500 pb-0.5 mb-1.5',
        accentClass: 'border-gray-400 dark:border-gray-500',
        bodyClass: 'font-serif text-sm leading-tight',
    },
    {
        key: 'engineeringclassic',
        name: 'Engineering Classic',
        description: 'Clean engineering format with clear section hierarchy',
        headerClass: 'text-center border-b-2 border-gray-700 dark:border-gray-300 pb-3',
        nameClass: 'text-xl font-bold text-gray-900 dark:text-gray-100',
        contactClass: 'text-xs text-gray-500 dark:text-gray-400 mt-1',
        sectionHeadingClass: 'text-sm font-bold text-gray-800 dark:text-gray-200 border-b border-gray-300 dark:border-gray-600 pb-1 mb-2 uppercase tracking-wide',
        accentClass: 'border-gray-700 dark:border-gray-300',
        bodyClass: 'font-serif',
    },
];

export function getTemplateConfig(key: string): TemplateConfig {
    return templates.find((t) => t.key === key) ?? templates[0];
}
