import { AlertTriangle, Check, Loader2, Pencil, Sparkles, X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import type { ExtractionType, ItemMatchInfo, ParsedAccomplishment, ParsedEducation, ParsedExperience, ParsedProject, ParsedSkill, ParsedUrl, SectionKey } from './types';

interface ItemCardProps {
    selected: boolean;
    onToggle: () => void;
    onEdit: () => void;
    onEnhance?: () => void;
    enhancing?: boolean;
    pendingEnhancement?: Record<string, unknown> | null;
    onAcceptEnhancement?: () => void;
    onRejectEnhancement?: () => void;
    compact?: boolean;
    matchInfo?: ItemMatchInfo;
}

function ExtractionTypeBadge({ type, enhances }: { type?: ExtractionType; enhances?: string }) {
    if (!type) return null;

    if (type === 'enhancement') {
        return (
            <Badge variant="outline" className="border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300">
                Enhanced{enhances ? ` · ${enhances}` : ''}
            </Badge>
        );
    }

    return (
        <Badge variant="outline" className="border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-950 dark:text-green-300">
            New
        </Badge>
    );
}

function MatchStatusBadge({ matchInfo }: { matchInfo?: ItemMatchInfo }) {
    if (!matchInfo) return null;

    if (matchInfo.status === 'new') {
        return (
            <Badge variant="outline" className="border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-950 dark:text-green-300">
                New
            </Badge>
        );
    }

    if (matchInfo.status === 'will_update') {
        const fillsLabel = matchInfo.fills?.join(', ') ?? '';
        return (
            <Tooltip>
                <TooltipTrigger asChild>
                    <Badge variant="outline" className="border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300">
                        Will Update
                    </Badge>
                </TooltipTrigger>
                {fillsLabel && <TooltipContent>Will fill: {fillsLabel}</TooltipContent>}
            </Tooltip>
        );
    }

    return (
        <Badge variant="outline" className="border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300">
            Duplicate
        </Badge>
    );
}

function MatchSummaryLine({ matchInfo, compact }: { matchInfo?: ItemMatchInfo; compact?: boolean }) {
    if (!matchInfo?.existing_summary || matchInfo.status === 'new') return null;

    return (
        <p className={`text-muted-foreground/70 italic ${compact ? 'text-xs' : 'text-xs'}`}>
            Matches: {matchInfo.existing_summary}
        </p>
    );
}

function MissingFieldBadge({ fields }: { fields: string[] }) {
    if (fields.length === 0) return null;
    const label = fields.length === 1 ? `Missing ${fields[0]}` : `Missing ${fields.join(', ')}`;
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Badge variant="outline" className="gap-1 border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-400">
                    <AlertTriangle className="h-3 w-3" />
                    {label}
                </Badge>
            </TooltipTrigger>
            <TooltipContent>Click edit to add — this data improves your resume quality</TooltipContent>
        </Tooltip>
    );
}

function CardActions({
    onEdit,
    onEnhance,
    enhancing,
}: {
    onEdit: () => void;
    onEnhance?: () => void;
    enhancing?: boolean;
}) {
    return (
        <div className="flex shrink-0 gap-1">
            <Button
                variant="ghost"
                size="icon"
                className="h-6 w-6"
                onClick={(e) => {
                    e.stopPropagation();
                    onEdit();
                }}
                title="Edit"
            >
                <Pencil className="h-3 w-3" />
            </Button>
            {onEnhance && (
                <Button
                    variant="ghost"
                    size="icon"
                    className="h-6 w-6"
                    disabled={enhancing}
                    onClick={(e) => {
                        e.stopPropagation();
                        onEnhance();
                    }}
                    title="Enhance with AI"
                >
                    {enhancing ? <Loader2 className="h-3 w-3 animate-spin" /> : <Sparkles className="h-3 w-3" />}
                </Button>
            )}
        </div>
    );
}

function EnhancementBanner({
    enhancement,
    onAccept,
    onReject,
}: {
    enhancement: Record<string, unknown>;
    onAccept: () => void;
    onReject: () => void;
}) {
    return (
        <div className="border-primary/20 bg-primary/5 mt-2 rounded-md border p-2">
            <div className="mb-1 flex items-center justify-between">
                <span className="text-xs font-medium">AI Enhancement</span>
                <div className="flex gap-1">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="h-5 w-5"
                        onClick={(e) => {
                            e.stopPropagation();
                            onAccept();
                        }}
                        title="Accept"
                    >
                        <Check className="h-3 w-3 text-green-600" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="h-5 w-5"
                        onClick={(e) => {
                            e.stopPropagation();
                            onReject();
                        }}
                        title="Reject"
                    >
                        <X className="h-3 w-3 text-red-500" />
                    </Button>
                </div>
            </div>
            <div className="space-y-1 text-xs">
                {Object.entries(enhancement).map(([key, value]) =>
                    value ? (
                        <p key={key}>
                            <span className="text-muted-foreground capitalize">{key}:</span> {String(value)}
                        </p>
                    ) : null,
                )}
            </div>
        </div>
    );
}

export function ExperienceCard({
    item,
    selected,
    onToggle,
    onEdit,
    onEnhance,
    enhancing,
    pendingEnhancement,
    onAcceptEnhancement,
    onRejectEnhancement,
    compact,
    matchInfo,
}: ItemCardProps & { item: ParsedExperience }) {
    const missingFields: string[] = [];
    if (!item.started_at) missingFields.push('start date');
    if (!item.is_current && !item.ended_at) missingFields.push('end date');
    if (!item.description) missingFields.push('description');

    return (
        <Card className={`cursor-pointer ${!selected ? 'opacity-40' : ''} ${compact ? 'py-3 gap-2' : ''}`}>
            <CardHeader className={`pb-2 ${compact ? 'px-4' : ''}`}>
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0 flex-1 cursor-pointer" onClick={onToggle}>
                        <div className="flex flex-wrap items-center gap-2">
                            <CardTitle className={compact ? 'text-sm' : 'text-base'}>
                                {item.title} at {item.company}
                            </CardTitle>
                            <ExtractionTypeBadge type={item.extraction_type} enhances={item.enhances} />
                            <MatchStatusBadge matchInfo={matchInfo} />
                            {selected && <MissingFieldBadge fields={missingFields} />}
                        </div>
                        <p className={`text-muted-foreground ${compact ? 'text-xs' : 'text-sm'}`}>
                            {item.started_at ?? '???'} — {item.is_current ? 'Present' : (item.ended_at ?? 'N/A')}
                            {item.location && ` · ${item.location}`}
                        </p>
                        <MatchSummaryLine matchInfo={matchInfo} compact={compact} />
                    </div>
                    {selected && <CardActions onEdit={onEdit} onEnhance={onEnhance} enhancing={enhancing} />}
                </div>
            </CardHeader>
            {(item.description || pendingEnhancement) && (
                <CardContent className={`pt-0 ${compact ? 'px-4' : ''}`}>
                    {item.description && <p className={`text-muted-foreground ${compact ? 'text-xs' : 'text-sm'}`}>{item.description}</p>}
                    {pendingEnhancement && onAcceptEnhancement && onRejectEnhancement && (
                        <EnhancementBanner enhancement={pendingEnhancement} onAccept={onAcceptEnhancement} onReject={onRejectEnhancement} />
                    )}
                </CardContent>
            )}
        </Card>
    );
}

export function AccomplishmentCard({
    item,
    selected,
    onToggle,
    onEdit,
    onEnhance,
    enhancing,
    pendingEnhancement,
    onAcceptEnhancement,
    onRejectEnhancement,
    compact,
    matchInfo,
}: ItemCardProps & { item: ParsedAccomplishment }) {
    return (
        <Card className={`cursor-pointer ${!selected ? 'opacity-40' : ''} ${compact ? 'py-3 gap-2' : ''}`}>
            <CardHeader className={`pb-2 ${compact ? 'px-4' : ''}`}>
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0 flex-1 cursor-pointer" onClick={onToggle}>
                        <div className="flex flex-wrap items-center gap-2">
                            <CardTitle className={compact ? 'text-sm' : 'text-base'}>{item.title}</CardTitle>
                            <ExtractionTypeBadge type={item.extraction_type} enhances={item.enhances} />
                            <MatchStatusBadge matchInfo={matchInfo} />
                        </div>
                        <MatchSummaryLine matchInfo={matchInfo} compact={compact} />
                    </div>
                    {selected && <CardActions onEdit={onEdit} onEnhance={onEnhance} enhancing={enhancing} />}
                </div>
            </CardHeader>
            <CardContent className={`pt-0 ${compact ? 'px-4' : ''}`}>
                <p className={`text-muted-foreground ${compact ? 'text-xs' : 'text-sm'}`}>{item.description}</p>
                {item.impact && <p className={`mt-1 font-medium ${compact ? 'text-xs' : 'text-sm'}`}>{item.impact}</p>}
                {pendingEnhancement && onAcceptEnhancement && onRejectEnhancement && (
                    <EnhancementBanner enhancement={pendingEnhancement} onAccept={onAcceptEnhancement} onReject={onRejectEnhancement} />
                )}
            </CardContent>
        </Card>
    );
}

export function SkillBadges({
    skills,
    selected,
    onToggle,
    matchInfoMap,
}: {
    skills: ParsedSkill[];
    selected: Set<number>;
    onToggle: (section: SectionKey, index: number) => void;
    matchInfoMap?: Record<number, ItemMatchInfo>;
}) {
    return (
        <div className="flex flex-wrap gap-2">
            {skills.map((skill, i) => {
                const isDuplicate = matchInfoMap?.[i]?.status === 'duplicate';
                return (
                    <Badge
                        key={i}
                        variant={selected.has(i) ? 'secondary' : 'outline'}
                        className={`cursor-pointer ${isDuplicate ? 'opacity-50' : ''}`}
                        onClick={() => onToggle('skills', i)}
                    >
                        {skill.name}
                        {isDuplicate && <span className="ml-1 text-amber-600 dark:text-amber-400">*</span>}
                    </Badge>
                );
            })}
        </div>
    );
}

export function EducationCard({
    item,
    selected,
    onToggle,
    onEdit,
    onEnhance,
    enhancing,
    pendingEnhancement,
    onAcceptEnhancement,
    onRejectEnhancement,
    compact,
    matchInfo,
}: ItemCardProps & { item: ParsedEducation }) {
    const missingFields: string[] = [];
    if (!item.completed_at) missingFields.push('completion date');
    if (!item.field) missingFields.push('field of study');

    return (
        <Card className={`cursor-pointer ${!selected ? 'opacity-40' : ''} ${compact ? 'py-3 gap-2' : ''}`}>
            <CardHeader className={`pb-2 ${compact ? 'px-4' : ''}`}>
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0 flex-1 cursor-pointer" onClick={onToggle}>
                        <div className="flex flex-wrap items-center gap-2">
                            <CardTitle className={compact ? 'text-sm' : 'text-base'}>{item.title}</CardTitle>
                            <ExtractionTypeBadge type={item.extraction_type} enhances={item.enhances} />
                            <MatchStatusBadge matchInfo={matchInfo} />
                            {selected && <MissingFieldBadge fields={missingFields} />}
                        </div>
                        <p className={`text-muted-foreground ${compact ? 'text-xs' : 'text-sm'}`}>
                            {item.institution}
                            {item.field && ` · ${item.field}`}
                        </p>
                        <MatchSummaryLine matchInfo={matchInfo} compact={compact} />
                    </div>
                    {selected && <CardActions onEdit={onEdit} onEnhance={onEnhance} enhancing={enhancing} />}
                </div>
                {pendingEnhancement && onAcceptEnhancement && onRejectEnhancement && (
                    <EnhancementBanner enhancement={pendingEnhancement} onAccept={onAcceptEnhancement} onReject={onRejectEnhancement} />
                )}
            </CardHeader>
        </Card>
    );
}

export function ProjectCard({
    item,
    selected,
    onToggle,
    onEdit,
    onEnhance,
    enhancing,
    pendingEnhancement,
    onAcceptEnhancement,
    onRejectEnhancement,
    compact,
    matchInfo,
}: ItemCardProps & { item: ParsedProject }) {
    const missingFields: string[] = [];
    if (!item.role) missingFields.push('role');
    if (!item.outcome) missingFields.push('outcome');

    return (
        <Card className={`cursor-pointer ${!selected ? 'opacity-40' : ''} ${compact ? 'py-3 gap-2' : ''}`}>
            <CardHeader className={`pb-2 ${compact ? 'px-4' : ''}`}>
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0 flex-1 cursor-pointer" onClick={onToggle}>
                        <div className="flex flex-wrap items-center gap-2">
                            <CardTitle className={compact ? 'text-sm' : 'text-base'}>{item.name}</CardTitle>
                            <ExtractionTypeBadge type={item.extraction_type} enhances={item.enhances} />
                            <MatchStatusBadge matchInfo={matchInfo} />
                            {selected && <MissingFieldBadge fields={missingFields} />}
                        </div>
                        {item.role && <p className={`text-muted-foreground ${compact ? 'text-xs' : 'text-sm'}`}>{item.role}</p>}
                        <MatchSummaryLine matchInfo={matchInfo} compact={compact} />
                    </div>
                    {selected && <CardActions onEdit={onEdit} onEnhance={onEnhance} enhancing={enhancing} />}
                </div>
            </CardHeader>
            <CardContent className={`pt-0 ${compact ? 'px-4' : ''}`}>
                <p className={`text-muted-foreground ${compact ? 'text-xs' : 'text-sm'}`}>{item.description}</p>
                {pendingEnhancement && onAcceptEnhancement && onRejectEnhancement && (
                    <EnhancementBanner enhancement={pendingEnhancement} onAccept={onAcceptEnhancement} onReject={onRejectEnhancement} />
                )}
            </CardContent>
        </Card>
    );
}

const urlTypeLabels: Record<string, string> = {
    linkedin: 'LinkedIn',
    github: 'GitHub',
    portfolio: 'Portfolio',
    article: 'Article',
    other: 'Other',
};

export function LinkBadges({
    urls,
    selected,
    onToggle,
    matchInfoMap,
}: {
    urls: ParsedUrl[];
    selected: Set<number>;
    onToggle: (section: SectionKey, index: number) => void;
    matchInfoMap?: Record<number, ItemMatchInfo>;
}) {
    return (
        <div className="space-y-2">
            {urls.map((url, i) => {
                const isDuplicate = matchInfoMap?.[i]?.status === 'duplicate';
                return (
                    <div
                        key={i}
                        className={`flex cursor-pointer items-center gap-2 rounded-md border p-2 ${selected.has(i) ? '' : 'opacity-40'}`}
                        onClick={() => onToggle('urls', i)}
                    >
                        <Badge variant={selected.has(i) ? 'secondary' : 'outline'} className="shrink-0">
                            {urlTypeLabels[url.type] ?? url.type}
                        </Badge>
                        <span className="min-w-0 truncate text-sm">{url.label || url.url}</span>
                        {isDuplicate && (
                            <Badge variant="outline" className="ml-auto shrink-0 border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                Duplicate
                            </Badge>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
