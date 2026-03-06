import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import AiUpgradePrompt from '@/components/ai-upgrade-prompt';
import WelcomeModal from '@/components/welcome-modal';
import type { AppLayoutProps } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    const { flash } = usePage().props;
    const [showUpgrade, setShowUpgrade] = useState(false);

    useEffect(() => {
        if (flash.ai_access_denied) {
            setShowUpgrade(true);
        }
    }, [flash.ai_access_denied]);

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
            <AiUpgradePrompt open={showUpgrade} onClose={() => setShowUpgrade(false)} />
            <WelcomeModal />
        </AppShell>
    );
}
