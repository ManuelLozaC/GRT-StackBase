import api from '@/service/api';
import { notificationStore } from '@/core/notifications/notificationStore';
import { computed, reactive } from 'vue';
import { sessionStore } from './sessionStore';

const state = reactive({
    switchingOrganization: false,
    switchingWorkAssignment: false
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

async function switchActiveWorkAssignment(workAssignmentId) {
    state.switchingWorkAssignment = true;

    try {
        const response = await api.patch('/v1/auth/active-work-assignment', {
            asignacion_laboral_id: workAssignmentId
        });

        sessionStore.setUser(response.data.datos);

        return sessionStore.state.user;
    } finally {
        state.switchingWorkAssignment = false;
    }
}

export const tenantStore = {
    state,
    organizations: computed(() => sessionStore.state.user?.organizaciones ?? []),
    activeOrganization: computed(() => sessionStore.state.user?.organizacion_activa ?? null),
    activeWorkAssignment: computed(() => sessionStore.state.user?.asignacion_laboral_activa ?? null),
    availableWorkAssignments: computed(() => sessionStore.state.user?.asignaciones_laborales_disponibles ?? []),
    switchActiveOrganization,
    switchActiveWorkAssignment
};
