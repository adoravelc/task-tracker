<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { getApiErrorMessage } from '@/utils/apiError'
import adoravelcLogoDark from '@/assets/adoravelc-logo-dark.png'

const authStore = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const isPasswordVisible = ref(false)
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
    <div class="login-shell">
      <form class="login-form" @submit.prevent="submitLogin">
        <div class="brand">
          <h1>TaskTracker</h1>
          <p>Sign in to manage your projects and tasks in one place.</p>
        </div>

        <label for="email">Email</label>
        <input
          id="email"
          v-model="email"
          type="email"
          placeholder="example@energeek.id"
          required
          autocomplete="email"
        />

        <label for="password">Password</label>
        <div class="password-field">
          <input
            id="password"
            v-model="password"
            :type="isPasswordVisible ? 'text' : 'password'"
            placeholder="******"
            required
            autocomplete="current-password"
          />
          <button
            type="button"
            class="toggle-password"
            :aria-label="isPasswordVisible ? 'Hide password' : 'Show password'"
            @click="isPasswordVisible = !isPasswordVisible"
          >
            <svg
              v-if="!isPasswordVisible"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
              aria-hidden="true"
            >
              <path d="M2 12C3.8 7.8 7.5 5 12 5s8.2 2.8 10 7c-1.8 4.2-5.5 7-10 7s-8.2-2.8-10-7Z" stroke="currentColor" stroke-width="1.8"/>
              <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/>
            </svg>
            <svg
              v-else
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
              aria-hidden="true"
            >
              <path d="M2 12C3.8 7.8 7.5 5 12 5s8.2 2.8 10 7c-1.8 4.2-5.5 7-10 7s-8.2-2.8-10-7Z" stroke="currentColor" stroke-width="1.8"/>
              <path d="M4 4l16 16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
          </button>
        </div>

        <p v-if="errorMessage" class="error">{{ errorMessage }}</p>

        <button type="submit" class="submit-button" :disabled="isLoading">
          {{ isLoading ? 'Logging in...' : 'Login' }}
        </button>

        <img :src="adoravelcLogoDark" alt="adoravelc logo" class="brand-logo-bottom" />
      </form>

      <div class="login-footer">
        <p class="login-rights">All Rights Reserved - Chavel Aiko Ratu</p>
        <div class="credits-links">
          <a href="https://github.com/adoravelc" target="_blank" rel="noopener noreferrer">Github</a>
          <a href="https://www.linkedin.com/in/chavel-aiko-ratu/" target="_blank" rel="noopener noreferrer">LinkedIn</a>
        </div>
      </div>
    </div>
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

.login-shell {
  width: 100%;
  max-width: 420px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.6rem;
}

.login-form {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  padding: 1.45rem 1.8rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: 10px;
  background: var(--color-secondary);
  color: #333333;
}

.brand {
  margin-bottom: 0.75rem;
  text-align: center;
}

.brand h1 {
  margin: 0;
  color: var(--color-primary);
  font-size: 2rem;
  font-weight: 700;
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

.password-field {
  position: relative;
}

.password-field input {
  width: 100%;
  padding-right: 2.2rem;
}

.toggle-password {
  position: absolute;
  right: 0.45rem;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: transparent;
  color: #666666;
  padding: 0;
  margin: 0;
  font-size: 0.92rem;
  line-height: 1;
}

.toggle-password svg {
  width: 16px;
  height: 16px;
  display: block;
}

.login-form input:focus {
  outline: 2px solid var(--color-primary);
  border-color: var(--color-primary);
}

.submit-button {
  margin-top: 0.35rem;
  padding: 0.65rem 0.75rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  color: var(--color-secondary);
  background: var(--color-primary);
}

.submit-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.error {
  margin: 0;
  color: var(--color-primary);
  font-size: 0.9rem;
}

.credits-links {
  display: flex;
  justify-content: center;
  gap: 0.9rem;
  font-size: 0.76rem;
}

.credits-links a {
  color: var(--color-primary);
  text-decoration: none;
}

.credits-links a:hover {
  text-decoration: underline;
}

.brand-logo-bottom {
  width: 52px;
  height: 52px;
  object-fit: contain;
  margin: -0.05rem auto -0.4rem;
}

.login-footer {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.login-rights {
  margin: 0;
  font-size: 0.68rem;
  color: rgba(255, 255, 255, 0.86);
  text-align: center;
  letter-spacing: 0.2px;
}
</style>