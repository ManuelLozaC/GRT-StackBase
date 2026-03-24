import axios from 'axios';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8080/api/v1',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json'
    }
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('grt_token');
    const oficinaId = localStorage.getItem('grt_oficina_id');

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    if (oficinaId) {
        config.headers['X-Oficina-Id'] = oficinaId;
    }

    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error?.response?.status === 401) {
            localStorage.removeItem('grt_token');
            localStorage.removeItem('grt_oficina_id');
            localStorage.removeItem('grt_usuario');
            window.location.href = '/auth/login';
        }

        return Promise.reject(error);
    }
);

export default api;
