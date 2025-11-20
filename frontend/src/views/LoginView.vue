<template>
  <div class="login-page min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card p-4 shadow-sm" style="max-width:420px; width:100%;">
      <h3 class="mb-2">¡Bienvenido de vuelta!</h3>
      <p class="text-muted mb-3">Ingresa tus credenciales para continuar</p>

      <form @submit.prevent="handleLogin">
        <div class="mb-3">
          <label class="form-label">Email o Cédula</label>
          <input v-model="identifier" class="form-control" :disabled="loading" required />
        </div>

        <div class="mb-3">
          <label class="form-label">Contraseña</label>
          <input v-model="password" type="password" class="form-control" :disabled="loading" required />
        </div>

        <div v-if="error" class="alert alert-danger">{{ error }}</div>

        <button class="btn btn-primary w-100" :disabled="loading">
          {{ loading ? 'Ingresando...' : 'Ingresar' }}
        </button>

        <div class="text-center mt-3">
          <router-link to="/signup">Crear cuenta</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth';

const router = useRouter();
const auth = useAuth();

const identifier = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

const handleLogin = async () => {
  error.value = '';
  loading.value = true;
  try {
    const result = await auth.login(identifier.value, password.value);
    if (result.ok) {
      const user = result.user;
      // redirigir por rol (seguro y explícito)
      if (user.role_id === 4) router.push('/admin');
      else if (user.role_id === 3) router.push('/manager');
      else if (user.role_id === 2) router.push('/trainer');
      else if (user.role_id === 1) router.push('/athlete');
      else router.push('/');
    } else {
      error.value = result.error || 'Credenciales inválidas';
    }
  } catch (e) {
    error.value = 'Error de conexión';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.login-page { background: linear-gradient(135deg,#667eea22,#764ba222); }
.card { border-radius: 12px; }
</style>
