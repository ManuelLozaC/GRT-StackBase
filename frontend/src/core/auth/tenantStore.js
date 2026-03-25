import api from '@/service/api';
import { notificationStore } from '@/core/notifications/notificationStore';
import { computed, reactive } from 'vue';
import { sessionStore } from './sessionStore';

const state = reactive({
    switchingOrganization: false
});

async function switchActiveOrganization(organizationId) {
    state.switchingOrganization = true;

    try {
        const response = await api.patch('/v1/auth/active-organization', {
            organizacion_id: organizationId
        });

        sessionStore.setUser(response.data.datos);
        await notificationStore.loadNotifications();

        return sessionStore.state.user;
    } finally {
        state.switchingOrganization = false;
    }
}

export const tenantStore = {
    state,
    organizations: computed(() => sessionStore.state.user?.organizaciones ?? []),
    activeOrganization: computed(() => sessionStore.state.user?.organizacion_activa ?? null),
    switchActiveOrganization
};
