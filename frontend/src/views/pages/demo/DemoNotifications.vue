<script setup>
import DemoPageHero from '@/components/demo/DemoPageHero.vue';
import DemoPatternGuide from '@/components/demo/DemoPatternGuide.vue';
import StateEmpty from '@/components/core/StateEmpty.vue';
import StateSkeleton from '@/components/core/StateSkeleton.vue';
import { notificationStore } from '@/core/notifications/notificationStore';
import { bindForegroundPushListener, getPushSubscriptionStatus, registerPushNotifications, releaseForegroundPushListener, showForegroundSystemNotification, unregisterPushNotifications } from '@/core/notifications/pushClient';
import { settingsStore } from '@/core/settings/settingsStore';
import api from '@/service/api';
import { computed, onMounted, onUnmounted, reactive } from 'vue';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

const form = reactive({
    title: 'Proceso completado',
    message: 'La demo genero una notificacion interna correctamente.',
    level: 'info',
    action_url: '',
    channels: ['internal']
});
const pushState = reactive({
    supported: false,
    loading: false,
    enabling: false,
    disabling: false,
    configured: false,
    permission: typeof Notification !== 'undefined' ? Notification.permission : 'unsupported',
    subscriptions: []
});
const deliveryHistory = reactive({
    loading: false,
    items: []
});

const channelOptions = computed(() => [
    { label: 'Internal', value: 'internal' },
    { label: `Email ${settingsStore.featureFlags.value.feature_notifications_email ? '' : '(flag off)'}`, value: 'email' },
    { label: `Push ${settingsStore.featureFlags.value.feature_notifications_push ? '' : '(flag off)'}`, value: 'push' }
]);
const hasPushSubscription = computed(() => pushState.subscriptions.some((item) => item.is_active));

async function createNotification() {
    try {
        await api.post('/v1/demo/notifications', form);
        await notificationStore.loadNotifications();
        await loadDeliveryHistory();

        toast.add({
            severity: 'success',
            summary: 'Notificacion creada',
            detail: 'La notificacion demo ya aparece en la bandeja interna.',
            life: 2500
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudo crear la notificacion',
            detail: error?.response?.data?.mensaje ?? error?.message ?? 'Ocurrio un error al enviar la notificacion demo.',
            life: 4000
        });
    }
}

async function markAsRead(notification) {
    if (notification.read_at) {
        return;
    }

    await notificationStore.markAsRead(notification.uuid);
}

async function markAllAsRead() {
    await notificationStore.markAllAsRead();

    toast.add({
        severity: 'success',
        summary: 'Bandeja actualizada',
        detail: 'Todas las notificaciones quedaron marcadas como leidas.',
        life: 2500
    });
}

function formatDate(value) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('es-BO', {
        dateStyle: 'medium',
        timeStyle: 'short'
    }).format(new Date(value));
}

function resolveSeverity(level) {
    if (level === 'success') {
        return 'success';
    }

    if (level === 'warning') {
        return 'warning';
    }

    if (level === 'danger') {
        return 'danger';
    }

    return 'info';
}

function resolveDeliverySeverity(status) {
    return (
        {
            delivered: 'success',
            simulated: 'info',
            skipped_disabled: 'warning',
            skipped_preference: 'secondary'
        }[status] ?? 'contrast'
    );
}

function resolveDeliveryHistorySeverity(status) {
    return (
        {
            delivered: 'success',
            queued: 'info',
            failed: 'danger',
            skipped_disabled: 'warning',
            skipped_preference: 'secondary',
            skipped_missing_target: 'contrast'
        }[status] ?? 'contrast'
    );
}

async function loadPushStatus() {
    pushState.loading = true;
    pushState.supported = 'Notification' in window && 'serviceWorker' in navigator;
    pushState.permission = pushState.supported ? Notification.permission : 'unsupported';

    try {
        const data = await getPushSubscriptionStatus();

        pushState.configured = Boolean(data.configured);
        pushState.subscriptions = data.subscriptions ?? [];
    } catch {
        pushState.configured = false;
        pushState.subscriptions = [];
    } finally {
        pushState.loading = false;
    }
}

async function loadDeliveryHistory() {
    deliveryHistory.loading = true;

    try {
        const response = await api.get('/v1/notifications/deliveries');
        deliveryHistory.items = response.data.datos ?? [];
    } finally {
        deliveryHistory.loading = false;
    }
}

async function enablePush() {
    pushState.enabling = true;

    try {
        await registerPushNotifications();
        await loadPushStatus();
        toast.add({
            severity: 'success',
            summary: 'Push habilitado',
            detail: 'Este navegador ya quedo registrado para recibir notificaciones push.',
            life: 3000
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudo habilitar push',
            detail: error?.message ?? 'Revisa permisos del navegador y configuracion de Firebase.',
            life: 4000
        });
    } finally {
        pushState.enabling = false;
    }
}

async function disablePush() {
    pushState.disabling = true;

    try {
        await unregisterPushNotifications();
        await loadPushStatus();
        toast.add({
            severity: 'success',
            summary: 'Push deshabilitado',
            detail: 'Este navegador ya no recibira notificaciones push.',
            life: 3000
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudo deshabilitar push',
            detail: error?.message ?? 'No se pudo revocar la suscripcion actual.',
            life: 4000
        });
    } finally {
        pushState.disabling = false;
    }
}

onMounted(async () => {
    await loadPushStatus();
    await loadDeliveryHistory();

    try {
        await bindForegroundPushListener((payload) => {
            showForegroundSystemNotification(payload).catch(() => {});
            toast.add({
                severity: 'info',
                summary: payload.notification?.title ?? payload.data?.title ?? 'Nueva notificacion push',
                detail: payload.notification?.body ?? payload.data?.message ?? 'Llegó una notificacion en foreground.',
                life: 5000
            });
        });
    } catch {
        pushState.supported = false;
    }
});

onUnmounted(() => {
    releaseForegroundPushListener();
});
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <DemoPageHero badge="Demo Module / Notifications" title="Demo funcional de notificaciones" description="Valida notificaciones internas, bandeja del usuario, marcado de lectura y contador de no leidas.">
                <template #aside>
                    <div class="demo-notification-summary">
                        <div>
                            <strong>{{ notificationStore.state.items.length }}</strong>
                            <span>notificaciones</span>
                        </div>
                        <div>
                            <strong>{{ notificationStore.unreadCount }}</strong>
                            <span>sin leer</span>
                        </div>
                    </div>
                </template>
            </DemoPageHero>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="card flex flex-col gap-4">
                <div>
                    <h3 class="m-0 mb-2">Generar notificacion demo</h3>
                    <p class="m-0 text-sm text-color-secondary">Crea una notificacion para el usuario actual y prueba el enrutamiento por canales del core.</p>
                </div>

                <input v-model="form.title" type="text" class="demo-input" placeholder="Titulo" />
                <textarea v-model="form.message" rows="4" class="demo-textarea" placeholder="Mensaje"></textarea>

                <label class="demo-field">
                    <span>Nivel</span>
                    <select v-model="form.level" class="demo-select">
                        <option value="info">Info</option>
                        <option value="success">Success</option>
                        <option value="warning">Warning</option>
                        <option value="danger">Danger</option>
                    </select>
                </label>

                <label class="demo-field">
                    <span>Canales</span>
                    <MultiSelect v-model="form.channels" :options="channelOptions" optionLabel="label" optionValue="value" display="chip" class="w-full" />
                </label>

                <input v-model="form.action_url" type="text" class="demo-input" placeholder="URL de accion opcional" />

                <button class="demo-primary-button" @click="createNotification">Crear notificacion</button>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="mb-2 text-sm font-semibold text-slate-800">Estado push web</div>
                    <div class="space-y-2 text-sm text-slate-600">
                        <div><strong>Soporte navegador:</strong> {{ pushState.supported ? 'Si' : 'No' }}</div>
                        <div><strong>Permiso:</strong> {{ pushState.permission }}</div>
                        <div><strong>FCM servidor:</strong> {{ pushState.configured ? 'Configurado' : 'Pendiente' }}</div>
                        <div><strong>Dispositivos activos:</strong> {{ pushState.subscriptions.filter((item) => item.is_active).length }}</div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <button class="demo-primary-button" :disabled="!pushState.supported || pushState.enabling" @click="enablePush">
                            {{ pushState.enabling ? 'Habilitando...' : 'Habilitar push en este navegador' }}
                        </button>
                        <button class="demo-secondary-button" :disabled="!hasPushSubscription || pushState.disabling" @click="disablePush">
                            {{ pushState.disabling ? 'Deshabilitando...' : 'Deshabilitar push' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="card">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
                    <div>
                        <h3 class="m-0">Bandeja interna</h3>
                        <p class="m-0 text-sm text-color-secondary">Esta misma bandeja alimenta la campanita del layout y sirve como base para futuras preferencias y canales.</p>
                    </div>
                    <div class="demo-actions">
                        <button class="demo-secondary-button" :disabled="notificationStore.state.loading" @click="notificationStore.loadNotifications">
                            {{ notificationStore.state.loading ? 'Actualizando...' : 'Actualizar' }}
                        </button>
                        <button class="demo-secondary-button" :disabled="notificationStore.unreadCount === 0" @click="markAllAsRead">Marcar todas como leidas</button>
                    </div>
                </div>

                <StateSkeleton v-if="notificationStore.state.loading" />

                <StateEmpty v-else-if="notificationStore.state.items.length === 0" title="Todavia no hay notificaciones" description="Genera una desde la demo para probar el flujo interno y los canales operativos." icon="pi pi-bell" />

                <div v-else class="demo-notification-list">
                    <article v-for="notification in notificationStore.state.items" :key="notification.uuid" class="demo-notification-card" :class="{ unread: !notification.read_at }">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold">{{ notification.title }}</div>
                                <div class="text-sm text-color-secondary">{{ formatDate(notification.created_at) }}</div>
                            </div>
                            <Tag :severity="resolveSeverity(notification.level)" :value="notification.level" />
                        </div>

                        <p class="text-sm mb-3 mt-3">{{ notification.message }}</p>

                        <div v-if="notification.deliveries?.length" class="demo-deliveries">
                            <Tag v-for="delivery in notification.deliveries" :key="`${notification.uuid}-${delivery.channel}`" :severity="resolveDeliverySeverity(delivery.status)" :value="`${delivery.channel}: ${delivery.status}`" />
                        </div>

                        <div class="demo-card-footer">
                            <span class="text-sm text-color-secondary">{{ notification.read_at ? `Leida ${formatDate(notification.read_at)}` : 'Pendiente de lectura' }}</span>

                            <div class="demo-actions">
                                <router-link v-if="notification.action_url" :to="notification.action_url" class="demo-link">Abrir accion</router-link>
                                <button v-if="!notification.read_at" class="demo-secondary-button" @click="markAsRead(notification)">Marcar leida</button>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="card">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
                    <div>
                        <h3 class="m-0">Historial de entregas</h3>
                        <p class="m-0 text-sm text-color-secondary">Muestra el estado operativo de email, push e internal, incluyendo cola, destino y detalle del proveedor.</p>
                    </div>
                    <div class="demo-actions">
                        <button class="demo-secondary-button" :disabled="deliveryHistory.loading" @click="loadDeliveryHistory">
                            {{ deliveryHistory.loading ? 'Actualizando...' : 'Actualizar historial' }}
                        </button>
                    </div>
                </div>

                <StateSkeleton v-if="deliveryHistory.loading" />

                <StateEmpty v-else-if="deliveryHistory.items.length === 0" title="Todavia no hay entregas" description="Genera correos, push o notificaciones internas para ver aqui el resultado por canal." icon="pi pi-send" />

                <div v-else class="demo-notification-list">
                    <article v-for="delivery in deliveryHistory.items" :key="delivery.id" class="demo-notification-card">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold">{{ delivery.title || 'Entrega sin titulo visible' }}</div>
                                <div class="text-sm text-color-secondary">{{ formatDate(delivery.created_at) }}</div>
                            </div>
                            <Tag :severity="resolveDeliveryHistorySeverity(delivery.status)" :value="`${delivery.channel}: ${delivery.status}`" />
                        </div>

                        <p class="text-sm mb-3 mt-3">{{ delivery.message || delivery.status_detail }}</p>

                        <div class="demo-delivery-grid">
                            <div><strong>Destino:</strong> {{ delivery.destination || '-' }}</div>
                            <div><strong>Procesado:</strong> {{ formatDate(delivery.processed_at) }}</div>
                            <div><strong>Mailer:</strong> {{ delivery.mailer || '-' }}</div>
                            <div><strong>Origen:</strong> {{ delivery.source || '-' }}</div>
                        </div>

                        <div class="demo-card-footer">
                            <span class="text-sm text-color-secondary">{{ delivery.status_detail }}</span>
                            <router-link v-if="delivery.action_url" :to="delivery.action_url" class="demo-link">Abrir accion</router-link>
                        </div>
                    </article>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <DemoPatternGuide
                title="Guia para notificaciones y bandeja"
                :when-to-use="['cuando una accion necesita dejar rastro visible para el usuario', 'cuando una misma notificacion puede salir por varios canales', 'cuando la bandeja forma parte del shell y del flujo diario']"
                :avoid-when="['cuando el mensaje deberia ser solo un toast efimero sin historial', 'cuando se notifican eventos sin valor real para el usuario final', 'cuando los canales externos no respetan preferencias o flags']"
                :wiring="[
                    'crear la notificacion, refrescar bandeja y mostrar feedback inmediato',
                    'mostrar estado de lectura y entregas por canal en la misma vista',
                    'mantener action_url opcional para conectar la notificacion con una pantalla real'
                ]"
                :notes="['esta demo conversa con el core real de notificaciones', 'si el flujo se vuelve mas complejo, combinar con settings y preferencias del usuario']"
            />
        </div>
    </div>
</template>

<style scoped>
.demo-notification-summary {
    display: flex;
    gap: 1rem;
}

.demo-notification-summary > div {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 7rem;
    padding: 0.9rem 1rem;
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    background: linear-gradient(135deg, var(--surface-card), var(--surface-ground));
}

.demo-notification-summary strong {
    font-size: 1.35rem;
}

.demo-notification-summary span {
    font-size: 0.85rem;
    color: var(--text-color-secondary);
}

.demo-input,
.demo-textarea,
.demo-select {
    width: 100%;
    border: 1px solid var(--surface-border);
    border-radius: 0.85rem;
    padding: 0.85rem 1rem;
    background: var(--surface-card);
    color: var(--text-color);
}

.demo-field,
.demo-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.demo-field {
    flex-direction: column;
}

.demo-primary-button,
.demo-secondary-button {
    border: 0;
    border-radius: 999px;
    padding: 0.75rem 1rem;
    font-weight: 600;
    cursor: pointer;
}

.demo-primary-button {
    background: var(--primary-color);
    color: var(--primary-contrast-color);
}

.demo-secondary-button {
    background: var(--surface-200);
    color: var(--text-color);
}

.demo-primary-button:disabled,
.demo-secondary-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.demo-notification-card {
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    padding: 1rem;
    background: var(--surface-card);
}

.demo-notification-list {
    display: grid;
    gap: 1rem;
}

.demo-notification-card.unread {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 1px color-mix(in srgb, var(--primary-color) 35%, transparent);
}

.demo-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.demo-link {
    color: var(--primary-color);
    font-weight: 600;
    text-decoration: none;
}

.demo-deliveries {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.demo-delivery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 0.75rem 1rem;
    margin-bottom: 1rem;
    font-size: 0.92rem;
    color: var(--text-color-secondary);
}
</style>
