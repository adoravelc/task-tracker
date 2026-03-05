<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import adoravelcLogo from '@/assets/adoravelc logo.png'

const router = useRouter()
const authStore = useAuthStore()

const handleLogout = async () => {
  await authStore.logout()
  await router.push('/login')
}
</script>

<template>
  <aside class="sidebar">
    <div class="sidebar-top">
      <h1 class="logo">TaskTracker</h1>

      <nav class="menu">
        <RouterLink to="/dashboard" class="menu-link">Dashboard</RouterLink>
        <RouterLink to="/projects" class="menu-link">Project</RouterLink>
        <RouterLink to="/tasks" class="menu-link">Task</RouterLink>
      </nav>
    </div>

    <div class="sidebar-bottom">
      <p class="profile-name">Admin Energeek</p>
      <button type="button" class="logout-button" @click="handleLogout">Logout</button>
      <div class="sidebar-brand-footer">
        <img :src="adoravelcLogo" alt="adoravelc logo" class="sidebar-logo-bottom" />
        <div class="sidebar-links">
          <a href="https://github.com/adoravelc" target="_blank" rel="noopener noreferrer">Github</a>
          <a href="https://www.linkedin.com/in/chavel-aiko-ratu/" target="_blank" rel="noopener noreferrer">LinkedIn</a>
        </div>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.sidebar {
  position: fixed;
  left: 0;
  top: 0;
  width: 260px;
  height: 100vh;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border-right: 1px solid var(--color-border);
  background: #2b2b2b;
  box-shadow: inset -1px 0 0 rgba(255, 255, 255, 0.04);
}

.sidebar-top {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.logo {
  margin: 0 0 1rem;
  color: var(--color-primary);
  font-size: 1.5rem;
  font-weight: 700;
  text-align: center;
  letter-spacing: 0.3px;
}

.menu {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.menu-link {
  display: block;
  padding: 0.65rem 0.8rem;
  border-radius: 8px;
  text-decoration: none;
  color: var(--color-secondary);
  border: 1px solid transparent;
  transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

.menu-link:hover {
  background: rgba(207, 115, 164, 0.15);
  border-color: rgba(207, 115, 164, 0.4);
  transform: translateX(2px);
}

.menu-link.router-link-active,
.menu-link.router-link-exact-active {
  background: var(--color-primary);
  color: var(--color-secondary);
  box-shadow: 0 6px 14px rgba(207, 115, 164, 0.25);
}

.profile-name {
  margin: 0;
  color: var(--color-secondary);
  font-weight: 600;
  text-align: center;
}

.sidebar-bottom {
  display: flex;
  flex-direction: column;
  gap: 0.22rem;
}

.logout-button {
  width: 100%;
  border: none;
  border-radius: 8px;
  padding: 0.65rem 0.75rem;
  cursor: pointer;
  background: var(--color-primary);
  color: var(--color-secondary);
  transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
}

.logout-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 16px rgba(207, 115, 164, 0.3);
}

.sidebar-brand-footer {
  margin-top: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.00rem;
  text-align: center;
}

.sidebar-logo-bottom {
  width: 78px;
  height: 78px;
  object-fit: contain;
  margin: 0 auto -0.7rem;
}

.sidebar-links {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 0.7rem;
  font-size: 0.72rem;
  line-height: 1;
}

.sidebar-links a {
  color: #f0b6d2;
  text-decoration: none;
}

.sidebar-links a:hover {
  text-decoration: underline;
}
</style>
