import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';

const user = ref(null);
const token = ref(null);
const isAuthenticated = computed(() => !!token.value);

export function useAuth() {
  const router = useRouter();

  const init = () => {
    let storedToken = localStorage.getItem('access_token');
    let storedUser = localStorage.getItem('user');

    // Si algo está vacío, null o "undefined" → limpiar
    if (
      !storedToken ||
      storedToken === "undefined" ||
      storedToken === "" ||
      !storedUser ||
      storedUser === "undefined"
    ) {
      console.warn("🔄 Limpiando localStorage… (token corrupto)");
      localStorage.clear();
      return;
    }

    try {
      const parsed = JSON.parse(storedUser);
      user.value = parsed;
      token.value = storedToken;
    } catch (err) {
      console.warn("❌ JSON corrupto en localStorage. Limpio todo.", err);
      localStorage.clear();
    }
  };

  const login = async (identifier, password) => {
    try {
      const response = await api.post('/api/auth/login', { identifier, password });

      if (response.data?.ok) {
        const { access_token, user: userData } = response.data;

        localStorage.setItem('access_token', access_token);
        localStorage.setItem('user', JSON.stringify(userData));

        token.value = access_token;
        user.value = userData;

        return { ok: true, user: userData };
      } else {
        return { ok: false, error: response.data?.detail || 'Credenciales inválidas' };
      }
    } catch (error) {
      const errorMsg = error.response?.data?.detail || 'Error de conexión';
      return { ok: false, error: errorMsg };
    }
  };

  const register = async (payload) => {
    try {
      if (payload.role_id === 4) {
        return { ok: false, error: 'No está permitido registrarse como SuperAdmin' };
      }

      const response = await api.post('/api/users/register', payload);

      if (response.data?.ok) {
        return { ok: true, user: response.data.user };
      } else {
        return { ok: false, errors: response.data?.errors || [response.data?.detail] };
      }
    } catch (error) {
      const detail = error.response?.data || error.message;
      if (error.response?.status === 400 && error.response?.data?.detail) {
        return { ok: false, errors: [error.response.data.detail] };
      }
      return { ok: false, error: detail };
    }
  };

  const logout = () => {
    console.warn("👋 Logout: limpiando session y localStorage");
    localStorage.clear();
    sessionStorage.clear();
    token.value = null;
    user.value = null;
    router.push('/login');
  };

  const getUser = () => {
    if (user.value) return user.value;

    let storedUser = localStorage.getItem('user');

    if (!storedUser || storedUser === "undefined") {
      return null;
    }

    try {
      user.value = JSON.parse(storedUser);
      return user.value;
    } catch {
      localStorage.clear();
      return null;
    }
  };

  const hasRole = (roleId) => {
    const currentUser = getUser();
    return currentUser?.role_id === roleId;
  };

  init();

  return {
    user: computed(() => user.value),
    token: computed(() => token.value),
    isAuthenticated,
    login,
    register,
    logout,
    getUser,
    hasRole
  };
}
