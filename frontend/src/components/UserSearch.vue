<template>
  <div class="user-search mb-3">
    <label class="form-label">{{ label }}</label>
    <div class="input-group">
      <input
        type="text"
        class="form-control"
        v-model="searchQuery"
        :placeholder="placeholder"
        @keyup.enter="search"
      />
      <button class="btn btn-outline-primary" @click="search" :disabled="loading">
        <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
        <i v-else class="bi bi-search"></i>
        Buscar
      </button>
    </div>
    
    <div v-if="user" class="alert alert-success mt-2 mb-0">
      <i class="bi bi-check-circle-fill me-2"></i>
      <strong>Usuario encontrado:</strong> {{ user.first_name }} {{ user.last_name }} 
      ({{ user.email }}) - <small class="text-muted">ID: {{ user.id }}</small>
    </div>
    
    <div v-if="error" class="alert alert-danger mt-2 mb-0">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      {{ error }}
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import { getUserById, searchUserByEmail } from '@/services/api';

export default {
  name: 'UserSearch',
  props: {
    label: { type: String, default: 'Buscar Usuario' },
    placeholder: { type: String, default: 'Ingresa ID o Email' },
  },
  emits: ['user-found', 'user-cleared'],
  setup(props, { emit }) {
    const searchQuery = ref('');
    const user = ref(null);
    const error = ref(null);
    const loading = ref(false);

    const search = async () => {
      if (!searchQuery.value.trim()) {
        error.value = 'Ingresa un ID o Email';
        return;
      }

      loading.value = true;
      error.value = null;
      user.value = null;

      try {
        // Intentar por ID primero (es numérico)
        if (/^\d+$/.test(searchQuery.value)) {
          const response = await getUserById(parseInt(searchQuery.value));
          user.value = response.data.user;
        } else {
          // Buscar por email
          const response = await searchUserByEmail(searchQuery.value);
          const users = response.data.users?.data || [];
          if (users.length > 0) {
            user.value = users[0];
          } else {
            throw new Error('Usuario no encontrado');
          }
        }
        
        emit('user-found', user.value);
      } catch (err) {
        error.value = err.response?.data?.detail || err.message || 'Usuario no encontrado';
        emit('user-cleared');
      } finally {
        loading.value = false;
      }
    };

    return { searchQuery, user, error, loading, search };
  },
};
</script>

<style scoped>
.user-search .alert {
  font-size: 0.9rem;
  padding: 0.5rem 0.75rem;
}
</style>
