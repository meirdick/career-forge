export type ExtractionType = 'new' | 'enhancement';

export type ParsedExperience = {
    company: string;
    title: string;
    location?: string;
    started_at: string;
    ended_at?: string;
    is_current: boolean;
    description?: string;
    extraction_type?: ExtractionType;
    enhances?: string;
};

export type ParsedAccomplishment = {
    title: string;
    description: string;
    impact?: string;
    experience_index?: number;
    extraction_type?: ExtractionType;
    enhances?: string;
};

export type ParsedSkill = { name: string; category: string; extraction_type?: ExtractionType; enhances?: string };

export type ParsedEducation = {
    type: string;
    institution: string;
    title: string;
    field?: string;
    completed_at?: string;
    extraction_type?: ExtractionType;
    enhances?: string;
};

export type ParsedProject = {
    name: string;
    description: string;
    role?: string;
    outcome?: string;
    experience_index?: number;
    extraction_type?: ExtractionType;
    enhances?: string;
};

export type ParsedUrl = {
    url: string;
    type: string;
    label?: string;
};

export type ExtractionData = {
    experiences: ParsedExperience[];
    accomplishments: ParsedAccomplishment[];
    skills: ParsedSkill[];
    education: ParsedEducation[];
    projects: ParsedProject[];
    urls: ParsedUrl[];
};

export type SectionKey = keyof ExtractionData;

export type MatchStatus = 'new' | 'will_update' | 'duplicate';

export type ItemMatchInfo = {
    status: MatchStatus;
    existing_summary?: string;
    fills?: string[];
};

export type OverlapGroup = {
    experience_index: number;
    project_indices: number[];
    reason: string;
};

export type MatchAnalysis = {
    matches: Record<SectionKey, Record<number, ItemMatchInfo>>;
    overlaps: OverlapGroup[];
};
