import { Link } from '@inertiajs/react';
import {
    Briefcase,
    GraduationCap,
    LayoutGrid,
    Library,
    LinkIcon,
    Sparkles,
    Target,
    Upload,
    User,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as educationIndex } from '@/routes/education';
import { index as evidenceIndex } from '@/routes/evidence';
import { index as experienceLibraryIndex } from '@/routes/experience-library';
import { edit as identityEdit } from '@/routes/identity';
import { index as skillsIndex } from '@/routes/skills';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const experienceLibraryItems: NavItem[] = [
    {
        title: 'Timeline',
        href: experienceLibraryIndex(),
        icon: Library,
    },
    {
        title: 'Skills',
        href: skillsIndex(),
        icon: Sparkles,
    },
    {
        title: 'Identity',
        href: identityEdit(),
        icon: User,
    },
    {
        title: 'Education',
        href: educationIndex(),
        icon: GraduationCap,
    },
    {
        title: 'Evidence',
        href: evidenceIndex(),
        icon: LinkIcon,
    },
    {
        title: 'Upload',
        href: '/resume-upload',
        icon: Upload,
    },
];

const applicationsItems: NavItem[] = [
    {
        title: 'Job Postings',
        href: '/job-postings',
        icon: Target,
    },
    {
        title: 'Applications',
        href: '/applications',
        icon: Briefcase,
    },
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                <NavMain items={experienceLibraryItems} label="Experience Library" />
                <NavMain items={applicationsItems} label="Applications" />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
