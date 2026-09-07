import { Link } from '@inertiajs/react';
import {
    ClipboardCheck,
    FileText,
    FlaskConical,
    FolderKanban,
    Layers,
    LayoutGrid,
    ChartNoAxesColumn,
    Radar,
    Search,
    ShieldAlert,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
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
import { dashboard, metrics, search } from '@/routes';
import { index as decisionsIndex } from '@/routes/decisions';
import { index as projectsIndex } from '@/routes/projects';
import { index as prototypesIndex } from '@/routes/prototypes';
import { index as radarIndex } from '@/routes/radar';
import { index as securityNotesIndex } from '@/routes/security-notes';
import { index as technologiesIndex } from '@/routes/technologies';
import { index as vettingIndex } from '@/routes/vetting';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Practice',
        href: metrics(),
        icon: ChartNoAxesColumn,
    },
    {
        title: 'Search',
        href: search(),
        icon: Search,
    },
    {
        title: 'Projects',
        href: projectsIndex(),
        icon: FolderKanban,
    },
    {
        title: 'Decisions',
        href: decisionsIndex(),
        icon: FileText,
    },
    {
        title: 'Vetting',
        href: vettingIndex(),
        icon: ClipboardCheck,
    },
    {
        title: 'Prototypes',
        href: prototypesIndex(),
        icon: FlaskConical,
    },
    {
        title: 'Security',
        href: securityNotesIndex(),
        icon: ShieldAlert,
    },
    {
        title: 'Technologies',
        href: technologiesIndex(),
        icon: Layers,
    },
    {
        title: 'Radar',
        href: radarIndex(),
        icon: Radar,
    },
];

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
            </SidebarContent>

            <SidebarFooter className="mt-auto">
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
