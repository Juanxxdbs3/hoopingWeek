import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_BROKER_URL || 'http://localhost:5000',
  timeout: 20000,
  headers: {
    'Content-Type': 'application/json'
  }
});

// Interceptor REQUEST: token automático
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('access_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Interceptor RESPONSE: 401 -> logout
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
export const getFields = (opts = {}) => api.get('/api/fields', { params: opts, timeout: opts.timeout ?? 60000 });
export const getFieldById = (id) => api.get(`/api/fields/${id}`);
export const createField = (data) => api.post('/api/fields', data);
export const updateField = (id, data) => api.put(`/api/fields/${id}`, data);
export const deleteField = (id, force = false) => api.delete(`/api/fields/${id}${force ? '?force=true' : ''}`);
export const changeFieldState = (id, state) => api.patch(`/api/fields/${id}/state`, { state }); // state: 'active'|'maintenance'|'inactive'|'closed'

// Field availability & hours
export const getFieldAvailability = (id, date) => api.get(`/api/fields/${id}/availability`, { params: { date } });

// Operating hours & exceptions
export const getOperatingHours = (fieldId) => api.get(`/api/fields/${fieldId}/operating-hours`);
export const createOperatingHour = (fieldId, data) => api.post(`/api/fields/${fieldId}/operating-hours`, data);
export const deleteOperatingHour = (fieldId, day_of_week) => api.delete(`/api/fields/${fieldId}/operating-hours/${day_of_week}`);

export const getExceptionsRange = (fieldId, start_date, end_date) =>
  api.get(`/api/fields/${fieldId}/exceptions/range`, { params: { start_date, end_date } });
export const createFieldException = (fieldId, data) => api.post(`/api/fields/${fieldId}/exceptions`, data);
export const deleteFieldException = (exceptionId) => api.delete(`/api/exceptions/${exceptionId}`);

// TEAMS
export const getTeams = (params = {}) => api.get('/api/teams', { params });
export const getTeamById = (id) => api.get(`/api/teams/${id}`);
export const createTeam = (data) => api.post('/api/teams', data);
export const updateTeam = (id, data) => api.put(`/api/teams/${id}`, data);
export const deleteTeam = (id) => api.delete(`/api/teams/${id}`);
export const getTeamMembers = (teamId) => api.get(`/api/teams/${teamId}/members`);
export const addTeamMember = (teamId, athlete) => api.post(`/api/teams/${teamId}/members`, athlete);
export const removeTeamMember = (teamId, athleteId) => api.delete(`/api/teams/${teamId}/members/${athleteId}`);

// CHAMPIONSHIPS (y otros endpoints existentes)
export const getChampionships = (params = {}) => api.get('/api/championships', { params });
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

// CHAMPIONSHIP TEAMS & MATCHES
export const getChampionshipTeams = (id) => api.get(`/api/championships/${id}/teams`);
export const addTeamToChampionship = (id, teamId) => api.post(`/api/championships/${id}/teams`, { team_id: teamId });
export const removeTeamFromChampionship = (id, teamId) => api.delete(`/api/championships/${id}/teams/${teamId}`);

export const getChampionshipMatchesEnriched = (championship_id) => api.get(`/api/championships/${championship_id}/matches_enriched`);
export const getChampionshipMatches = (championship_id) => api.get('/api/matches', { params: { championship_id } });
export const createChampionshipMatch = (championshipId, data) => api.post(`/api/championships/${championshipId}/matches`, data);

export default api;
