import axios from 'axios';
import { uiFeedbackStore } from '@/core/ui/uiFeedbackStore';

let accessToken = null;

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8080/api/v1',
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
        uiFeedbackStore.reportHttpError(error);

        return Promise.reject(error);
    }
);

export default api;
