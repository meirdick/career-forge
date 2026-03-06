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

export type AiAccessDeniedFlash = {
    message: string;
    access_mode: string;
    purpose: string;
    cost: number;
    balance: number;
} | null;

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            aiAccess: AiAccessProps;
            flash: {
                ai_access_denied: AiAccessDeniedFlash;
            };
            showWelcome: boolean;
            referralCode: string | null;
            [key: string]: unknown;
        };
    }
}
