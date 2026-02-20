export type NotificationType = "general";

export type NotificationPriority = "low" | "medium" | "high" | "urgent";

export interface Notification {
    id: string;
    type: NotificationType;
    priority: NotificationPriority;
    title: string;
    message: string;
    created_at: string;
    read_at?: string | null;
    action_url?: string | null;
}

export interface NotificationStats {
    total: number;
    unread: number;
    read: number;
}
