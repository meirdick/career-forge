import { Link, usePage } from '@inertiajs/react';
import {
    Briefcase,
    FileText,
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
        title: 'Resumes',
        href: '/resumes',
        icon: FileText,
    },
    {
        title: 'Applications',
        href: '/applications',
        icon: Briefcase,
    },
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const { profileCompleteness } = usePage<{ profileCompleteness: number | null }>().props;

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
                {profileCompleteness !== null && (
                    <div className="px-3 py-2">
                        <div className="flex items-center justify-between text-xs">
                            <span className="text-muted-foreground">Profile</span>
                            <span className="font-medium">{profileCompleteness}%</span>
                        </div>
                        <div className="bg-muted mt-1 h-1.5 w-full rounded-full">
                            <div
                                className="bg-primary h-1.5 rounded-full transition-all"
                                style={{ width: `${profileCompleteness}%` }}
                            />
                        </div>
                    </div>
                )}
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
