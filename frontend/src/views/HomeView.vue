<template>
  <div class="full-height d-flex flex-column align-items-center justify-content-center text-center">
    <table border="0" class="home-table">
      <thead>
        <tr>
          <th style="width:80%; text-align: left;">
            <span class="edoc-logo">HoopingWeek.</span>
            <span class="edoc-logo-sub">| SISTEMA DE RESERVAS</span>
          </th>
          <th style="width:10%;">
            <router-link to="/login" class="non-style-link">
              <p class="nav-item">LOGIN</p>
            </router-link>
          </th>
          <th style="width:10%;">
            <router-link to="/signup" class="non-style-link">
              <p class="nav-item" style="padding-right: 10px;">REGISTRO</p>
            </router-link>
          </th>
        </tr>
      </thead>

      <tbody>
        <tr>
          <td colspan="3">
            <p class="heading-text">Evita filas y demoras.</p>
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <p class="sub-text2">
              ¿Quieres entrenar hoy? <br>
              Reserva tu espacio deportivo en línea con HoopingWeek. <br>
              Ofrecemos un servicio rápido y eficiente.
            </p>
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <div class="text-center">
              <router-link to="/login">
                <input type="button" value="Hacer Reserva" class="login-btn btn-primary btn"
                  style="padding-left: 25px;padding-right: 25px;padding-top: 10px;padding-bottom: 10px;">
              </router-link>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <p class="sub-text2 footer-hashen">Una solución web para gestión deportiva.</p>
  </div>
</template>

<script setup>
// Nada especial aquí
</script>

<style scoped>
@import '../assets/css/index.css';

/* si ya lo tienes en index.css no es necesario repetirlo */
.full-height {
  background-image: url('@/assets/img/bg01.jpg');
  background-repeat: no-repeat;
  background-attachment: fixed;
  background-size: cover;
  height: 100vh;
  width: 100%;
}

.home-table {
  width: 100%;
}
</style>
