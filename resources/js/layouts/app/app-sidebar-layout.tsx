import { usePage } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import AiUpgradePrompt from '@/components/ai-upgrade-prompt';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import WelcomeModal from '@/components/welcome-modal';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    const { flash } = usePage().props;
    const [dismissedFlash, setDismissedFlash] = useState<unknown>(null);

    const showUpgrade = !!flash.ai_access_denied && flash.ai_access_denied !== dismissedFlash;

    const handleClose = useCallback(() => {
        setDismissedFlash(flash.ai_access_denied);
    }, [flash.ai_access_denied]);

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
            <AiUpgradePrompt open={showUpgrade} onClose={handleClose} />
            <WelcomeModal />
        </AppShell>
    );
}
