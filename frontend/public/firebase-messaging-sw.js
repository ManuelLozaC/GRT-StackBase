/* global importScripts, firebase */

importScripts('https://www.gstatic.com/firebasejs/11.10.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/11.10.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: 'AIzaSyAMbH5yUGpqtIewh7Xh13EN5K0cu_T7SuM',
    authDomain: 'grt-stackbase.firebaseapp.com',
    projectId: 'grt-stackbase',
    storageBucket: 'grt-stackbase.firebasestorage.app',
    messagingSenderId: '426460083781',
    appId: '1:426460083781:web:01882639d113f2d1a2d52c',
    measurementId: 'G-Z60RE2QRMT'
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
    const notification = payload.notification ?? {};
    const title = notification.title ?? payload.data?.title ?? 'Nueva notificacion';
    const options = {
        body: notification.body ?? payload.data?.message ?? '',
        data: {
            action_url: payload.data?.action_url ?? '/'
        }
    };

    self.registration.showNotification(title, options);
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const actionUrl = event.notification.data?.action_url ?? '/';
    event.waitUntil(self.clients.openWindow(actionUrl));
});
