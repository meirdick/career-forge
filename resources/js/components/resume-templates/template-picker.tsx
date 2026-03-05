import { cn } from '@/lib/utils';
import { Check } from 'lucide-react';
import { templates, type TemplateConfig } from './template-config';

type TemplatePickerProps = {
    selected: string;
    onChange: (key: string) => void;
    disabled?: boolean;
};

function MiniPreview({ config }: { config: TemplateConfig }) {
    const isModern = config.key === 'moderncv';

    return (
        <div className="flex h-24 w-full flex-col overflow-hidden rounded border bg-white dark:bg-gray-900">
            {/* Header area */}
            <div className={cn('px-2 py-1.5', isModern ? 'bg-slate-800' : 'border-b border-gray-200 dark:border-gray-700')}>
                <div className={cn('mx-auto h-1.5 w-10 rounded-full', isModern ? 'bg-white/80' : 'bg-gray-800 dark:bg-gray-200')} />
                <div className={cn('mx-auto mt-0.5 h-1 w-14 rounded-full', isModern ? 'bg-white/40' : 'bg-gray-300 dark:bg-gray-600')} />
            </div>
            {/* Section lines */}
            <div className="flex-1 space-y-1.5 px-2 py-1.5">
                <div className={cn('h-1 w-8 rounded-full', config.key.includes('engineering') ? 'bg-gray-700 dark:bg-gray-300' : 'bg-gray-400 dark:bg-gray-500')} />
                <div className="space-y-0.5">
                    <div className="h-0.5 w-full rounded-full bg-gray-200 dark:bg-gray-700" />
                    <div className="h-0.5 w-4/5 rounded-full bg-gray-200 dark:bg-gray-700" />
                    <div className="h-0.5 w-3/5 rounded-full bg-gray-200 dark:bg-gray-700" />
                </div>
                <div className={cn('h-1 w-6 rounded-full', config.key.includes('engineering') ? 'bg-gray-700 dark:bg-gray-300' : 'bg-gray-400 dark:bg-gray-500')} />
                <div className="space-y-0.5">
                    <div className="h-0.5 w-full rounded-full bg-gray-200 dark:bg-gray-700" />
                    <div className="h-0.5 w-3/4 rounded-full bg-gray-200 dark:bg-gray-700" />
                </div>
            </div>
        </div>
    );
}

export default function TemplatePicker({ selected, onChange, disabled }: TemplatePickerProps) {
    return (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            {templates.map((config) => {
                const isSelected = config.key === selected;

                return (
                    <button
                        key={config.key}
                        type="button"
                        disabled={disabled}
                        onClick={() => onChange(config.key)}
                        className={cn(
                            'group relative rounded-lg border-2 p-2 text-left transition-all',
                            isSelected
                                ? 'border-primary bg-primary/5 ring-primary/20 ring-2'
                                : 'border-gray-200 hover:border-gray-400 dark:border-gray-700 dark:hover:border-gray-500',
                            disabled && 'cursor-not-allowed opacity-50',
                        )}
                    >
                        {isSelected && (
                            <div className="bg-primary absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full text-white">
                                <Check className="h-3 w-3" />
                            </div>
                        )}
                        <MiniPreview config={config} />
                        <p className="mt-1.5 text-xs font-medium">{config.name}</p>
                        <p className="text-muted-foreground text-xs leading-tight">{config.description}</p>
                    </button>
                );
            })}
        </div>
    );
}
