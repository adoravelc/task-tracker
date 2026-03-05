<script setup lang="ts">
import { onMounted, ref } from 'vue'
import api from '@/utils/axios'

interface UpcomingTask {
  id: number
  title: string
  due_date: string
  project?: {
    id: number
    name: string
  }
}

interface DashboardStats {
  active_projects_count: number
  incomplete_tasks_count: number
  upcoming_tasks: UpcomingTask[]
}

const stats = ref<DashboardStats>({
  active_projects_count: 0,
  incomplete_tasks_count: 0,
  upcoming_tasks: [],
})

const isLoading = ref(true)
const errorMessage = ref('')

const fetchStats = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const response = await api.get<{ data: DashboardStats }>('/dashboard/stats')
    stats.value = response.data.data
  } catch {
    errorMessage.value = 'Failed to load dashboard data.'
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchStats)
</script>

<template>
  <section class="min-h-[calc(100vh-3rem)] bg-[#333333] text-white">
    <div class="mx-auto max-w-6xl p-6">
      <h1 class="text-2xl font-bold text-[#cf73a4]">Dashboard</h1>

      <p v-if="errorMessage" class="mt-4 rounded-lg bg-white/10 px-4 py-2 text-sm text-white">
        {{ errorMessage }}
      </p>

      <div v-if="isLoading" class="mt-6 text-sm text-white/80">Loading dashboard data...</div>

      <div v-else class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <article class="rounded-xl bg-white p-5 text-[#333333] shadow">
          <p class="text-sm font-medium">Total Active Projects</p>
          <p class="mt-2 text-3xl font-bold">{{ stats.active_projects_count }}</p>
        </article>

        <article class="rounded-xl bg-white p-5 text-[#333333] shadow">
          <p class="text-sm font-medium">Unfinished Tasks</p>
          <p class="mt-2 text-3xl font-bold">{{ stats.incomplete_tasks_count }}</p>
        </article>

        <article class="rounded-xl bg-white p-5 text-[#333333] shadow md:col-span-2 xl:col-span-1">
          <p class="text-sm font-medium">Tasks Nearing Due Date</p>

          <ul v-if="stats.upcoming_tasks.length" class="mt-3 space-y-2">
            <li
              v-for="task in stats.upcoming_tasks"
              :key="task.id"
              class="rounded-lg bg-[#f8f8f8] px-3 py-2"
            >
              <p class="font-semibold">{{ task.title }}</p>
              <p class="text-xs text-[#666666]">Due: {{ task.due_date }}</p>
            </li>
          </ul>

          <p v-else class="mt-3 text-sm text-[#666666]">No upcoming unfinished tasks.</p>
        </article>
      </div>
    </div>
  </section>
</template>