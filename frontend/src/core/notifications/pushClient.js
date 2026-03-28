import { initializeApp, getApps } from 'firebase/app';
import { deleteToken, getMessaging, getToken, isSupported, onMessage } from 'firebase/messaging';
import api from '@/service/api';

const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
    measurementId: import.meta.env.VITE_FIREBASE_MEASUREMENT_ID
};

let foregroundUnsubscribe = null;

function getFirebaseApp() {
    return getApps()[0] ?? initializeApp(firebaseConfig);
}

async function getMessagingInstance() {
    if (!(await isSupported())) {
        throw new Error('Este navegador no soporta notificaciones push web.');
    }

    return getMessaging(getFirebaseApp());
}

function buildDeviceName() {
    const platform = navigator.platform || 'web';
    const browser = navigator.userAgentData?.brands?.[0]?.brand || navigator.userAgent;

    return `${platform} / ${browser}`.slice(0, 110);
}

export async function registerPushNotifications() {
    if (!('Notification' in window) || !('serviceWorker' in navigator)) {
        throw new Error('El navegador actual no soporta service workers o notificaciones.');
    }

    const permission = await Notification.requestPermission();

    if (permission !== 'granted') {
        throw new Error('El usuario no otorgo permisos de notificacion.');
    }

    const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
    const messaging = await getMessagingInstance();
    const token = await getToken(messaging, {
        vapidKey: import.meta.env.VITE_FIREBASE_VAPID_PUBLIC_KEY,
        serviceWorkerRegistration: registration
    });

    if (!token) {
        throw new Error('No se pudo obtener un token de FCM para este navegador.');
    }

    await api.post('/v1/notifications/push-subscriptions', {
        token,
        device_name: buildDeviceName(),
        platform: navigator.platform || 'web',
        browser: navigator.userAgentData?.brands?.map((item) => item.brand).join(', ') || navigator.userAgent,
        endpoint: registration.scope,
        subscription: {
            permission
        }
    });

    return token;
}

export async function unregisterPushNotifications() {
    const messaging = await getMessagingInstance();
    const registration = await navigator.serviceWorker.getRegistration('/firebase-messaging-sw.js');
    const token = await getToken(messaging, {
        vapidKey: import.meta.env.VITE_FIREBASE_VAPID_PUBLIC_KEY,
        serviceWorkerRegistration: registration
    });

    if (token) {
        await api.delete('/v1/notifications/push-subscriptions', {
            data: { token }
        });
        await deleteToken(messaging);
    }

    if (registration) {
        await registration.unregister();
    }
}

export async function getPushSubscriptionStatus() {
    const response = await api.get('/v1/notifications/push-subscriptions');

    return response.data.datos;
}

export async function bindForegroundPushListener(handler) {
    const messaging = await getMessagingInstance();

    foregroundUnsubscribe?.();
    foregroundUnsubscribe = onMessage(messaging, handler);
}

export function releaseForegroundPushListener() {
    foregroundUnsubscribe?.();
    foregroundUnsubscribe = null;
}
