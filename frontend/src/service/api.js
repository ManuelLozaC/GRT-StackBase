import axios from 'axios';
import { uiFeedbackStore } from '@/core/ui/uiFeedbackStore';

let accessToken = null;

function normalizeApiBaseUrl(rawBaseUrl) {
    const fallbackBaseUrl = 'http://localhost:8080/api';

    if (!rawBaseUrl || typeof rawBaseUrl !== 'string') {
        return fallbackBaseUrl;
    }

    const trimmedBaseUrl = rawBaseUrl.trim().replace(/\/+$/, '');

    if (trimmedBaseUrl.endsWith('/api/v1')) {
        return trimmedBaseUrl.slice(0, -3);
    }

    return trimmedBaseUrl;
}

const api = axios.create({
    baseURL: normalizeApiBaseUrl(import.meta.env.VITE_API_URL),
    withCredentials: true,
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json'
    }
});

api.interceptors.request.use((config) => {
    if (accessToken) {
        config.headers.Authorization = `Bearer ${accessToken}`;
    }

    return config;
});

export function setApiAccessToken(token) {
    accessToken = token;
}

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (!error?.config?.suppressUiError) {
            uiFeedbackStore.reportHttpError(error);
        }

        return Promise.reject(error);
    }
);

export default api;
