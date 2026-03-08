import type { Auth } from '@/types/auth';

export type AiAccessProps = {
    mode?: 'selfhosted' | 'byok' | 'credits';
    credits?: number;
    gatingEnabled: boolean;
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
