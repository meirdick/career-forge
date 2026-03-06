import type { Auth } from '@/types/auth';

export type AiAccessProps = {
    mode?: 'selfhosted' | 'byok' | 'credits' | 'free_tier';
    credits?: number;
    gatingEnabled: boolean;
    freeTierUsage?: {
        job_postings_used: number;
        job_postings_limit: number;
        documents_used: number;
        documents_limit: number;
    };
    hasApiKey?: boolean;
};

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            aiAccess: AiAccessProps;
            [key: string]: unknown;
        };
    }
}
