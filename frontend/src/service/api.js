import axios from 'axios';

<<<<<<< HEAD
let accessToken = null;

=======
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3
const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8080/api/v1',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json'
    }
});

api.interceptors.request.use((config) => {
<<<<<<< HEAD
    if (accessToken) {
        config.headers.Authorization = `Bearer ${accessToken}`;
=======
    const token = localStorage.getItem('grt_token');
    const oficinaId = localStorage.getItem('grt_oficina_id');

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    if (oficinaId) {
        config.headers['X-Oficina-Id'] = oficinaId;
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3
    }

    return config;
});

<<<<<<< HEAD
export function setApiAccessToken(token) {
    accessToken = token;
}
=======
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
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3

export default api;
