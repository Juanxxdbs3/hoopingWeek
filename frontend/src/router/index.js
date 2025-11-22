import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import LoginView from '../views/LoginView.vue'
import SignupView from '../views/SignUpView.vue'
import AdminDashboard from '../views/Roles/Admin/AdminDashboard.vue'
import AthleteDashboard from '../views/Roles/Athlete/AthleteDashboard.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', name: 'home', component: HomeView },
    { path: '/login', name: 'login', component: LoginView },
    { path: '/signup', name: 'signup', component: SignupView },

    {
      path: '/admin',
      name: 'admin',
      component: AdminDashboard,
      meta: { requiresAuth: true, roles: [4] }
    },
    {
      path: '/athlete',
      name: 'athlete',
      component: () => import('../views/Roles/Athlete/AthleteDashboard.vue'),
      meta: { requiresAuth: true, roles: [1] }
    },
    {
      path: '/athlete/dashboard',
      name: 'athlete.dashboard',
      component: AthleteDashboard,
      meta: { requiresAuth: true, roles: [1] } // Role 1 = Atleta
    },
    {
      path: '/trainer',
      name: 'trainer',
      component: () => import('../views/Roles/Trainer/TrainerDashboard.vue'),
      meta: { requiresAuth: true, roles: [2] }
    },
    {
      path: '/manager',
      name: 'manager',
      component: () => import('../views/Roles/Manager/ManagerDashboard.vue'),
      meta: { requiresAuth: true, roles: [3] }
    },
    {
      path: '/admin/reservations',
      name: 'admin.reservations',
      component: () => import('../views/Roles/Admin/AdminReservations.vue'),
      meta: { requiresAuth:true, roles: [4] }
    },
    {
      path: '/admin/shifts',
      name: 'admin.shifts',
      component: () => import('../views/Roles/Admin/AdminManagerShifts.vue'),
      meta: { requiresAuth:true, roles: [4] }
    },
    {
      path: '/admin/users',
      name: 'admin.users',
      component: () => import('../views/Roles/Admin/AdminUsers.vue'),
      meta: { requiresAuth:true, roles: [4] }
    }
  ]
})

// GUARD GLOBAL
router.beforeEach((to, from, next) => {
  let token = localStorage.getItem('access_token');
  let userStr = localStorage.getItem('user');

  const isTokenValid =
    token &&
    token !== "undefined" &&
    token !== "" &&
    userStr &&
    userStr !== "undefined";

  // ❌ Si token corrupto → limpiar y mandar a login
  if (!isTokenValid) {
    localStorage.clear();
    if (to.meta.requiresAuth) {
      return next('/login');
    }
  }

  let user = null;
  if (isTokenValid) {
    try {
      user = JSON.parse(userStr);
    } catch {
      localStorage.clear();
      return next('/login');
    }
  }

  // 🔐 Rutas protegidas
  if (to.meta.requiresAuth) {
    if (!isTokenValid) return next('/login');

    if (to.meta.roles && !to.meta.roles.includes(user.role_id)) {
      alert('No tienes permisos para acceder a esta página');
      return next('/');
    }
  }

  // 🔁 Si va a /login estando logueado → redirige por rol
  if (to.path === '/login' && isTokenValid) {
    switch (user.role_id) {
      case 4: return next('/admin');
      case 3: return next('/manager');
      case 2: return next('/trainer');
      case 1: return next('/athlete');
      default: return next('/');
    }
  }

  // 🔁 Igual para /signup
  if (to.path === '/signup' && isTokenValid) {
    switch (user.role_id) {
      case 4: return next('/admin');
      case 3: return next('/manager');
      case 2: return next('/trainer');
      case 1: return next('/athlete');
      default: return next('/');
    }
  }

  return next();
});

export default router;
