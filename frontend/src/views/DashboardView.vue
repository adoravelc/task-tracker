<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const handleLogout = async () => {
  await authStore.logout()
  await router.push('/login')
}
</script>

<template>
  <main class="dashboard-page">
    <aside class="sidebar">
      <h2>Project Tracker</h2>
      <button type="button" @click="handleLogout">Logout</button>
    </aside>

    <section class="content"></section>
  </main>
</template>

<style scoped>
.dashboard-page {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 240px 1fr;
  background: var(--color-background);
}

.sidebar {
  padding: 1.25rem;
  border-right: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  background: #2b2b2b;
}

.sidebar h2 {
  margin: 0;
  color: var(--color-secondary);
  font-size: 1.1rem;
}

.sidebar button {
  border: none;
  border-radius: 6px;
  padding: 0.65rem 0.75rem;
  background: var(--color-primary);
  color: var(--color-secondary);
  cursor: pointer;
}

.content {
  padding: 1.5rem;
}
</style>