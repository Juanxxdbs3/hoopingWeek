<template>
  <div class="container py-5">
    <div class="row">
      <div class="col-12">
        <div class="alert alert-success">
          <h4 class="alert-heading">¡Bienvenido, {{ userName }}!</h4>
          <p class="mb-0">Has iniciado sesión correctamente como <strong>Atleta</strong>.</p>
          <hr>
          <p class="mb-0">
            <small>Email: {{ userEmail }}</small><br>
            <small>ID: {{ userId }}</small>
          </p>
        </div>

        <div class="card">
          <div class="card-body text-center">
            <h5>Dashboard de Atleta</h5>
            <p class="text-muted">Próximamente podrás ver tus reservas aquí</p>
            <button class="btn btn-danger" @click="logout">
              <i class="bi bi-box-arrow-right me-2"></i>
              Cerrar Sesión
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const user = JSON.parse(localStorage.getItem('user') || '{}');

const userName = computed(() => `${user.first_name} ${user.last_name}`);
const userEmail = computed(() => user.email);
const userId = computed(() => user.id);

const logout = () => {
  localStorage.removeItem('access_token');
  localStorage.removeItem('user');
  router.push('/login');
};
</script>
