import { writable } from 'svelte/store';

export interface Toast {
    id: string;
    type: 'success' | 'error' | 'warning' | 'info';
    title: string;
    message?: string;
    duration?: number;
}

/**
 * Toast store for managing notification messages
 */
function createToastStore() {
    const { subscribe, update } = writable<Toast[]>([]);

    return {
        subscribe,
        /**
         * Add a new toast notification
         */
        add: (toast: Omit<Toast, 'id'>) => {
            const id = Math.random().toString(36).substr(2, 9);
            const newToast: Toast = {
                id,
                duration: 5000,
                ...toast,
            };

            update(toasts => [...toasts, newToast]);

            // Auto remove toast after duration
            if (newToast.duration && newToast.duration > 0) {
                setTimeout(() => {
                    toastStore.remove(id);
                }, newToast.duration);
            }

            return id;
        },
        /**
         * Remove a toast by ID
         */
        remove: (id: string) => {
            update(toasts => toasts.filter(toast => toast.id !== id));
        },
        /**
         * Clear all toasts
         */
        clear: () => {
            update(() => []);
        },
        /**
         * Show success toast
         */
        success: (title: string, message?: string, duration?: number) => {
            const toast: Omit<Toast, 'id'> = { type: 'success', title };
            if (message !== undefined) toast.message = message;
            if (duration !== undefined) toast.duration = duration;
            return toastStore.add(toast);
        },
        /**
         * Show error toast
         */
        error: (title: string, message?: string, duration?: number) => {
            const toast: Omit<Toast, 'id'> = { type: 'error', title };
            if (message !== undefined) toast.message = message;
            if (duration !== undefined) toast.duration = duration;
            return toastStore.add(toast);
        },
        /**
         * Show warning toast
         */
        warning: (title: string, message?: string, duration?: number) => {
            const toast: Omit<Toast, 'id'> = { type: 'warning', title };
            if (message !== undefined) toast.message = message;
            if (duration !== undefined) toast.duration = duration;
            return toastStore.add(toast);
        },
        /**
         * Show info toast
         */
        info: (title: string, message?: string, duration?: number) => {
            const toast: Omit<Toast, 'id'> = { type: 'info', title };
            if (message !== undefined) toast.message = message;
            if (duration !== undefined) toast.duration = duration;
            return toastStore.add(toast);
        },
    };
}

export const toastStore = createToastStore();