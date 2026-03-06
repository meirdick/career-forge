export type ParsedExperience = {
    company: string;
    title: string;
    location?: string;
    started_at: string;
    ended_at?: string;
    is_current: boolean;
    description?: string;
};

export type ParsedAccomplishment = {
    title: string;
    description: string;
    impact?: string;
    experience_index?: number;
};

export type ParsedSkill = { name: string; category: string };

export type ParsedEducation = {
    type: string;
    institution: string;
    title: string;
    field?: string;
    completed_at?: string;
};

export type ParsedProject = {
    name: string;
    description: string;
    role?: string;
    outcome?: string;
    experience_index?: number;
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
