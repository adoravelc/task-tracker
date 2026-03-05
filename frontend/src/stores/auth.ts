import { defineStore } from 'pinia'
import api from '@/utils/axios'

interface User {
  id: number
  name: string
  email: string
}

interface LoginResponse {
  token: string
  user?: User
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as User | null,
    token: localStorage.getItem('token') as string | null,
  }),

  getters: {
    isAuthenticated: (state): boolean => Boolean(state.token),
  },

  actions: {
    async fetchUser() {
      const response = await api.get<User>('/user')
      this.user = response.data
    },

    async login(email: string, password: string) {
      const response = await api.post<LoginResponse>('/login', { email, password })
      this.token = response.data.token
      localStorage.setItem('token', response.data.token)

      await this.fetchUser()
    },

    async logout() {
      try {
        await api.post('/logout')
      } finally {
        this.user = null
        this.token = null
        localStorage.removeItem('token')
      }
    },
  },
})