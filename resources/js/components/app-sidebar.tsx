import { Link, usePage } from '@inertiajs/react';
import {
    Briefcase,
    FileText,
    GraduationCap,
    LayoutGrid,
    Library,
    LinkIcon,
    MessageCircle,
    Sparkles,
    Tag,
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
import { index as applicationsIndex } from '@/routes/applications';
import { index as careerChatIndex } from '@/routes/career-chat';
import { index as educationIndex } from '@/routes/education';
import { index as evidenceIndex } from '@/routes/evidence';
import { index as experienceLibraryIndex } from '@/routes/experience-library';
import { edit as identityEdit } from '@/routes/identity';
import { index as jobPostingsIndex } from '@/routes/job-postings';
import { create as resumeUploadCreate } from '@/routes/resume-upload';
import { index as resumesIndex } from '@/routes/resumes';
import { index as skillsIndex } from '@/routes/skills';
import { index as tagsIndex } from '@/routes/tags';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const pipelineItems: NavItem[] = [
    {
        title: 'Job Postings',
        href: jobPostingsIndex(),
        icon: Target,
    },
    {
        title: 'Resumes',
        href: resumesIndex(),
        icon: FileText,
    },
    {
        title: 'Applications',
        href: applicationsIndex(),
        icon: Briefcase,
    },
];

const experienceLibraryItems: NavItem[] = [
    {
        title: 'Upload Resume',
        href: resumeUploadCreate(),
        icon: Upload,
    },
    {
        title: 'Links',
        href: evidenceIndex(),
        icon: LinkIcon,
    },
    {
        title: 'Work History',
        href: experienceLibraryIndex(),
        icon: Library,
    },
    {
        title: 'Skills',
        href: skillsIndex(),
        icon: Sparkles,
    },
    {
        title: 'Education',
        href: educationIndex(),
        icon: GraduationCap,
    },
    {
        title: 'Identity',
        href: identityEdit(),
        icon: User,
    },
];

const toolsItems: NavItem[] = [
    {
        title: 'Career Chat',
        href: careerChatIndex(),
        icon: MessageCircle,
    },
    {
        title: 'Tags',
        href: tagsIndex(),
        icon: Tag,
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
                <NavMain items={pipelineItems} label="Pipeline" />
                <NavMain items={experienceLibraryItems} label="Experience Library" />
                <NavMain items={toolsItems} label="Tools" />
            </SidebarContent>

            <SidebarFooter>
                {profileCompleteness !== null && (
                    <div className="group-data-[collapsible=icon]:hidden px-3 py-2">
                        <div className="flex items-center justify-between text-xs">
                            <span className="text-muted-foreground">Profile</span>
                            <span className="font-medium">{profileCompleteness}%</span>
                        </div>
                        <div className="bg-primary/10 mt-1 h-1.5 w-full rounded-full">
                            <div
                                className="bg-primary h-1.5 rounded-full transition-all duration-500"
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
