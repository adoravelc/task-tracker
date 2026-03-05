<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { Chart, ArcElement, Tooltip, Legend, DoughnutController } from 'chart.js'
import api from '@/utils/axios'

Chart.register(DoughnutController, ArcElement, Tooltip, Legend)

interface UpcomingTask {
  id: number
  title: string
  due_date: string
  category?: {
    id: number
    name: string
  }
  project?: {
    id: number
    name: string
  }
}

interface TaskCategoryDistributionItem {
  name: string
  count: number
}

interface DashboardStats {
  active_projects_count: number
  incomplete_tasks_count: number
  upcoming_tasks: UpcomingTask[]
  task_category_distribution: TaskCategoryDistributionItem[]
}

const stats = ref<DashboardStats>({
  active_projects_count: 0,
  incomplete_tasks_count: 0,
  upcoming_tasks: [],
  task_category_distribution: [],
})

const isLoading = ref(true)
const errorMessage = ref('')
const chartCanvas = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart<'doughnut', number[], string> | null = null

const getCurrentDateLabel = () => {
  return new Date().toLocaleDateString('id-ID', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
}

const getCategoryBadgeClass = (categoryName?: string) => {
  switch (categoryName) {
    case 'TODO':
      return 'bg-red-100 text-red-700'
    case 'IN PROGRESS':
      return 'bg-orange-100 text-orange-700'
    case 'TESTING':
      return 'bg-amber-100 text-amber-700'
    case 'DONE':
      return 'bg-emerald-100 text-emerald-700'
    default:
      return 'bg-gray-100 text-gray-700'
  }
}

const getRelativeDueLabel = (dueDate: string) => {
  const today = new Date()
  const target = new Date(dueDate)
  const millisecondsPerDay = 1000 * 60 * 60 * 24

  const todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate())
  const targetStart = new Date(target.getFullYear(), target.getMonth(), target.getDate())

  const dayDiff = Math.round((targetStart.getTime() - todayStart.getTime()) / millisecondsPerDay)

  if (dayDiff < 0) {
    return `Overdue ${Math.abs(dayDiff)} day${Math.abs(dayDiff) > 1 ? 's' : ''}`
  }

  if (dayDiff === 0) {
    return 'Due today'
  }

  if (dayDiff === 1) {
    return 'Due in 1 day'
  }

  return `Due in ${dayDiff} days`
}

const renderDoughnutChart = () => {
  if (!chartCanvas.value) {
    return
  }

  const fallbackOrder = ['TODO', 'IN PROGRESS', 'TESTING', 'DONE', 'PENDING']
  const normalizedDistribution = fallbackOrder.map((name) => {
    const found = stats.value.task_category_distribution.find((item) => item.name === name)

    return {
      name,
      count: found?.count ?? 0,
    }
  })

  const labels = normalizedDistribution.map((item) => item.name)
  const values = normalizedDistribution.map((item) => Number(item.count) || 0)

  if (values.every((value) => value === 0)) {
    values[0] = 1
  }

  if (chartInstance) {
    chartInstance.destroy()
  }

  chartInstance = new Chart(chartCanvas.value, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [
        {
          data: values,
          backgroundColor: ['#fecaca', '#fed7aa', '#fde68a', '#a7f3d0', '#d1d5db'],
          borderColor: '#f8fafc',
          borderWidth: 2,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            boxWidth: 14,
            boxHeight: 14,
            padding: 14,
          },
        },
      },
      cutout: '65%',
    },
  })
}

const fetchStats = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const response = await api.get<{ data: DashboardStats }>('/dashboard/stats')
    stats.value = {
      ...response.data.data,
      task_category_distribution: response.data.data.task_category_distribution ?? [],
    }
  } catch {
    errorMessage.value = 'Failed to load dashboard data.'
  } finally {
    isLoading.value = false
    await nextTick()

    if (!errorMessage.value) {
      renderDoughnutChart()
    }
  }
}

onMounted(fetchStats)

onBeforeUnmount(() => {
  if (chartInstance) {
    chartInstance.destroy()
  }
})
</script>

<template>
  <section class="min-h-[calc(100vh-3rem)] bg-gray-50 text-gray-800">
    <div class="mx-auto flex max-w-7xl flex-col p-8" style="row-gap: 6mm;">
      <header class="rounded-2xl border border-gray-200 bg-white p-6 shadow-md">
        <h1 class="text-2xl font-bold text-[#cf73a4]">Selamat Datang, Admin Energeek</h1>
        <p class="mt-1 text-sm text-gray-500">{{ getCurrentDateLabel() }}</p>
      </header>

      <p v-if="errorMessage" class="rounded-lg bg-red-50 px-4 py-2 text-sm text-red-700">
        {{ errorMessage }}
      </p>

      <div v-if="isLoading" class="text-sm text-gray-500">Loading dashboard data...</div>

      <div v-else class="flex flex-col" style="row-gap: 6mm;">
        <div class="grid gap-x-8 md:grid-cols-2" style="row-gap: 6mm;">
          <article class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-md transition hover:-translate-y-0.5 hover:shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-500">Active Projects</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.active_projects_count }}</p>
              </div>
              <div class="rounded-xl bg-[#fce8f4] p-3 text-[#cf73a4]">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M3 7h18M6 3h12a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/>
                </svg>
              </div>
            </div>
          </article>

          <article class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-md transition hover:-translate-y-0.5 hover:shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-500">Unfinished Tasks</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.incomplete_tasks_count }}</p>
              </div>
              <div class="rounded-xl bg-amber-100 p-3 text-amber-700">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M9 11l3 3L22 4"/>
                  <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
              </div>
            </div>
          </article>
        </div>

        <div class="grid gap-x-10 xl:grid-cols-5" style="row-gap: 6mm;">
          <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-md xl:col-span-2">
            <p class="text-sm font-semibold text-gray-700">Task Category Distribution</p>
            <div class="mt-6 h-80 rounded-xl border border-gray-100 bg-gray-50 p-3">
              <canvas ref="chartCanvas"></canvas>
            </div>
          </article>

          <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-md xl:col-span-3">
            <p class="text-sm font-semibold text-gray-700">Upcoming Tasks</p>

            <ul v-if="stats.upcoming_tasks.length" class="mt-5 flex flex-col" style="row-gap: 5mm;">
              <li
                v-for="task in stats.upcoming_tasks"
                :key="task.id"
                class="rounded-xl border border-gray-200 bg-gray-50 px-5 py-4 shadow-sm"
              >
                <div class="flex flex-wrap items-start justify-between gap-x-2" style="row-gap: 3mm;">
                  <div>
                    <p class="font-semibold text-gray-900">{{ task.title }}</p>
                    <p class="text-xs text-gray-500">{{ task.project?.name || 'No project' }}</p>
                  </div>

                  <div class="flex items-center gap-2">
                    <span
                      class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                      :class="getCategoryBadgeClass(task.category?.name)"
                    >
                      {{ task.category?.name || 'PENDING' }}
                    </span>
                    <span class="text-xs font-medium text-gray-600">{{ getRelativeDueLabel(task.due_date) }}</span>
                  </div>
                </div>
              </li>
            </ul>

            <p v-else class="mt-4 text-sm text-gray-500">No upcoming unfinished tasks.</p>
          </article>
        </div>
      </div>
    </div>
  </section>
</template>