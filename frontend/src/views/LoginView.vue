<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { getApiErrorMessage } from '@/utils/apiError'

const authStore = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const errorMessage = ref('')
const isLoading = ref(false)

const submitLogin = async () => {
  errorMessage.value = ''
  isLoading.value = true

  try {
    await authStore.login(email.value, password.value)
    await router.push('/dashboard')
  } catch (error: unknown) {
    errorMessage.value = getApiErrorMessage(error, 'Email or password is wrong.')
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <main class="login-page">
    <form class="login-form" @submit.prevent="submitLogin">
      <div class="brand">
        <h1>TaskTracker</h1>
        <p>Sign in to manage your projects and tasks in one place.</p>
      </div>

      <label for="email">Email</label>
      <input id="email" v-model="email" type="email" required autocomplete="email" />

      <label for="password">Password</label>
      <input id="password" v-model="password" type="password" required autocomplete="current-password" />

      <p v-if="errorMessage" class="error">{{ errorMessage }}</p>

      <button type="submit" :disabled="isLoading">
        {{ isLoading ? 'Logging in...' : 'Login' }}
      </button>
    </form>
  </main>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  background: var(--color-background);
}

.login-form {
  width: 100%;
  max-width: 420px;
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
  padding: 1.8rem;
  border: 1px solid var(--color-border);
  border-radius: 10px;
  background: var(--color-secondary);
  color: #333333;
}

.brand {
  margin-bottom: 0.75rem;
}

.brand h1 {
  margin: 0;
  color: var(--color-primary);
  font-weight: 600;
  line-height: 1.2;
}

.brand p {
  margin-top: 0.35rem;
  color: #333333;
  font-size: 0.92rem;
}

.login-form input {
  padding: 0.6rem 0.75rem;
  border: 1px solid rgba(51, 51, 51, 0.3);
  border-radius: 6px;
  color: #333333;
  background: var(--color-secondary);
}

.login-form input:focus {
  outline: 2px solid var(--color-primary);
  border-color: var(--color-primary);
}

.login-form button {
  margin-top: 0.5rem;
  padding: 0.65rem 0.75rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  color: var(--color-secondary);
  background: var(--color-primary);
}

.login-form button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.error {
  margin: 0;
  color: var(--color-primary);
  font-size: 0.9rem;
}
</style>