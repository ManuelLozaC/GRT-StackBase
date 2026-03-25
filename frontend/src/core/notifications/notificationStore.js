import api from '@/service/api';
import { computed, reactive } from 'vue';

const state = reactive({
    loading: false,
    items: [],
    unreadCount: 0
});

async function loadNotifications() {
    state.loading = true;

    try {
        const response = await api.get('/v1/notifications');
        state.items = response.data.datos ?? [];
        state.unreadCount = response.data.meta?.unread_count ?? 0;

        return state.items;
    } finally {
        state.loading = false;
    }
}

async function markAsRead(notificationUuid) {
    const response = await api.patch(`/v1/notifications/${notificationUuid}/read`);
    const updated = response.data.datos;
    const index = state.items.findIndex((item) => item.uuid === notificationUuid);

    if (index >= 0) {
        state.items[index] = updated;
    }

    state.unreadCount = Math.max(0, state.items.filter((item) => !item.read_at).length);

    return updated;
}

async function markAllAsRead() {
    await api.post('/v1/notifications/read-all');
    state.items = state.items.map((item) => ({
        ...item,
        read_at: item.read_at || new Date().toISOString()
    }));
    state.unreadCount = 0;
}

function reset() {
    state.items = [];
    state.unreadCount = 0;
}

export const notificationStore = {
    state,
    unreadCount: computed(() => state.unreadCount),
    recentNotifications: computed(() => state.items.slice(0, 5)),
    loadNotifications,
    markAsRead,
    markAllAsRead,
    reset
};
