export interface MenuItem {
    id: string;
    label: string;
    icon: string;
    link: string;
    badge?: number;
    submenu?: MenuItem[];
}

export interface MenuGroup {
    title: string;
    items: MenuItem[];
}
