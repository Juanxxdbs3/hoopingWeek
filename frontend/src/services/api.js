// src/services/api.js
import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_BROKER_URL || 'http://localhost:5000', // ✅ Puerto 5000
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json'
  }
});

// Interceptor de REQUEST: añadir token automáticamente
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('access_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Interceptor de RESPONSE: manejar errores 401 (token expirado)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('access_token');
      localStorage.removeItem('user');
      
      if (window.location.pathname !== '/login') {
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);

// ========== MÉTODOS API ==========

// AUTH
export const login = (credentials) => api.post('/api/auth/login', credentials);
export const getCurrentUser = () => api.get('/api/auth/me');
export const register = (userData) => api.post('/api/users/register', userData);

// USERS
export const getUsers = (params = {}) => api.get('/api/users', { params });
export const getUserById = (id) => api.get(`/api/users/${id}`);
export const searchUserByEmail = (email) => api.get('/api/users', { params: { email } });
export const updateUser = (id, data) => api.put(`/api/users/${id}`, data);
export const deleteUser = (id) => api.delete(`/api/users/${id}`);

// RESERVATIONS
export const getReservations = (params = {}) => api.get('/api/reservations', { params });
export const getReservationById = (id) => api.get(`/api/reservations/${id}`);
export const createReservation = (data) => api.post('/api/reservations/create-validated', data);
export const approveReservation = (id, note = null) => api.patch(`/api/reservations/${id}/approve`, { note });
export const rejectReservation = (id, reason) => api.patch(`/api/reservations/${id}/reject`, { rejection_reason: reason });
export const cancelReservation = (id, reason) => api.patch(`/api/reservations/${id}/cancel`, { reason });

// FIELDS
export const getFields = () => api.get('/api/fields');
export const getFieldById = (id) => api.get(`/api/fields/${id}`);
export const getFieldAvailability = (id, date) => api.get(`/api/fields/${id}/availability`, { params: { date } });

// TEAMS
export const getTeams = (params = {}) => api.get('/api/teams', { params });
export const getTeamById = (id) => api.get(`/api/teams/${id}`);
export const createTeam = (data) => api.post('/api/teams', data);
export const updateTeam = (id, data) => api.put(`/api/teams/${id}`, data);
export const deleteTeam = (id) => api.delete(`/api/teams/${id}`);
export const getTeamMembers = (teamId) => api.get(`/api/teams/${teamId}/members`);
export const addTeamMember = (teamId, athleteId) => api.post(`/api/teams/${teamId}/members`, { athlete_id: athleteId });
export const removeTeamMember = (teamId, athleteId) => api.delete(`/api/teams/${teamId}/members/${athleteId}`);

// CHAMPIONSHIPS
export const getChampionships = () => api.get('/api/championships');
export const getChampionshipById = (id) => api.get(`/api/championships/${id}`);
export const createChampionship = (data) => api.post('/api/championships', data);
export const updateChampionship = (id, data) => api.put(`/api/championships/${id}`, data);
export const deleteChampionship = (id) => api.delete(`/api/championships/${id}`);

// MANAGER SHIFTS
export const getManagerShifts = (params = {}) => api.get('/api/manager-shifts', { params });
export const getManagerShiftById = (id) => api.get(`/api/manager-shifts/${id}`);
export const createManagerShift = (data) => api.post('/api/manager-shifts', data);
export const updateManagerShift = (id, data) => api.put(`/api/manager-shifts/${id}`, data);
export const deleteManagerShift = (id) => api.delete(`/api/manager-shifts/${id}`);

export default api;
