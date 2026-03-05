<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/utils/axios'

interface TaskCategory {
  id: number
  name: string
}

interface ProjectTask {
  id: number
  title: string
  description: string
  due_date: string
  category: TaskCategory | null
}

interface ProjectDetail {
  id: number
  name: string
  description: string
  status: 'active' | 'archived'
  tasks: ProjectTask[]
}

const KANBAN_CATEGORIES = ['TODO', 'IN PROGRESS', 'TESTING', 'DONE', 'PENDING'] as const

const route = useRoute()
const router = useRouter()

const project = ref<ProjectDetail | null>(null)
const categories = ref<TaskCategory[]>([])

const isLoading = ref(true)
const errorMessage = ref('')

const isCreateTaskOpen = ref(false)
const isEditProjectOpen = ref(false)
const isSavingTask = ref(false)
const isSavingProject = ref(false)
const movingTaskId = ref<number | null>(null)

const taskForm = ref({
  title: '',
  description: '',
  due_date: '',
  category_id: '',
})

const projectForm = ref({
  name: '',
  description: '',
  status: 'active' as 'active' | 'archived',
})

const categoryByName = computed<Record<string, TaskCategory>>(() => {
  return categories.value.reduce<Record<string, TaskCategory>>((accumulator, category) => {
    accumulator[category.name] = category
    return accumulator
  }, {})
})

const groupedTasks = computed<Record<string, ProjectTask[]>>(() => {
  const groups: Record<string, ProjectTask[]> = {}

  for (const categoryName of KANBAN_CATEGORIES) {
    groups[categoryName] = []
  }

  if (!project.value) {
    return groups
  }

  for (const task of project.value.tasks) {
    const categoryName = task.category?.name ?? 'PENDING'

    if (!groups[categoryName]) {
      groups[categoryName] = []
    }

    groups[categoryName].push(task)
  }

  return groups
})

const formatDate = (value: string) => {
  return new Date(value).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

const goBackToProjects = () => {
  router.push('/projects')
}

const fetchProject = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const response = await api.get<{ data: ProjectDetail }>(`/projects/${route.params.id}`)
    project.value = response.data.data
  } catch {
    errorMessage.value = 'Failed to load project details.'
  } finally {
    isLoading.value = false
  }
}

const fetchCategories = async () => {
  const response = await api.get<{ data: TaskCategory[] }>('/categories')
  categories.value = response.data.data
}

const openCreateTaskModal = () => {
  taskForm.value = {
    title: '',
    description: '',
    due_date: '',
    category_id: categoryByName.value.TODO ? String(categoryByName.value.TODO.id) : '',
  }
  isCreateTaskOpen.value = true
}

const openEditProjectModal = () => {
  if (!project.value) {
    return
  }

  projectForm.value = {
    name: project.value.name,
    description: project.value.description,
    status: project.value.status,
  }
  isEditProjectOpen.value = true
}

const submitCreateTask = async () => {
  if (!project.value) {
    return
  }

  if (
    !taskForm.value.title.trim() ||
    !taskForm.value.description.trim() ||
    !taskForm.value.due_date ||
    !taskForm.value.category_id
  ) {
    errorMessage.value = 'Please complete all task fields.'
    return
  }

  isSavingTask.value = true
  errorMessage.value = ''

  try {
    await api.post('/tasks', {
      project_id: project.value.id,
      category_id: Number(taskForm.value.category_id),
      title: taskForm.value.title.trim(),
      description: taskForm.value.description.trim(),
      due_date: taskForm.value.due_date,
    })

    isCreateTaskOpen.value = false
    await fetchProject()
  } catch {
    errorMessage.value = 'Failed to create task.'
  } finally {
    isSavingTask.value = false
  }
}

const submitEditProject = async () => {
  if (!project.value) {
    return
  }

  if (!projectForm.value.name.trim() || !projectForm.value.description.trim()) {
    errorMessage.value = 'Project name and description are required.'
    return
  }

  isSavingProject.value = true
  errorMessage.value = ''

  try {
    const response = await api.put<{ data: ProjectDetail }>(`/projects/${project.value.id}`, {
      name: projectForm.value.name.trim(),
      description: projectForm.value.description.trim(),
      status: projectForm.value.status,
    })

    project.value = {
      ...project.value,
      ...response.data.data,
      tasks: project.value.tasks,
    }

    isEditProjectOpen.value = false
  } catch {
    errorMessage.value = 'Failed to update project.'
  } finally {
    isSavingProject.value = false
  }
}

const moveTaskToNextCategory = async (task: ProjectTask) => {
  if (!task.category) {
    return
  }

  const currentIndex = KANBAN_CATEGORIES.findIndex((item) => item === task.category?.name)
  const nextIndex = currentIndex === -1 ? 0 : (currentIndex + 1) % KANBAN_CATEGORIES.length
  const nextCategoryName = KANBAN_CATEGORIES[nextIndex]
  const nextCategory = categoryByName.value[nextCategoryName]

  if (!nextCategory) {
    return
  }

  movingTaskId.value = task.id
  errorMessage.value = ''

  try {
    const response = await api.put<{ data: ProjectTask }>(`/tasks/${task.id}`, {
      category_id: nextCategory.id,
    })

    const updatedTask = response.data.data
    const targetTask = project.value?.tasks.find((item) => item.id === task.id)

    if (targetTask) {
      targetTask.category = updatedTask.category
    }
  } catch {
    errorMessage.value = 'Failed to move task category.'
  } finally {
    movingTaskId.value = null
  }
}

onMounted(async () => {
  await Promise.all([fetchCategories(), fetchProject()])
})
</script>

<template>
  <section class="min-h-[calc(100vh-3rem)] bg-[#333333] p-6 text-white">
    <div class="mx-auto max-w-[1400px]">
      <button
        type="button"
        class="mb-4 rounded-lg border border-[#cf73a4] px-4 py-2 text-sm font-semibold text-[#cf73a4]"
        @click="goBackToProjects"
      >
        ← Back to Projects
      </button>

      <p v-if="isLoading" class="text-sm text-white/80">Loading project details...</p>
      <p v-else-if="errorMessage" class="mb-4 rounded-lg bg-white/10 px-4 py-2 text-sm">{{ errorMessage }}</p>

      <template v-if="project && !isLoading">
        <header class="mb-6 rounded-xl bg-white p-5 text-[#333333] shadow">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <h1 class="text-2xl font-bold text-[#cf73a4]">{{ project.name }}</h1>
              <p class="mt-2 text-sm text-[#666666]">{{ project.description }}</p>
              <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-[#333333]">
                Status: {{ project.status }}
              </p>
            </div>

            <div class="flex gap-2">
              <button
                type="button"
                class="rounded-lg bg-[#cf73a4] px-4 py-2 text-sm font-semibold text-white"
                @click="openCreateTaskModal"
              >
                Tambah Task
              </button>
              <button
                type="button"
                class="rounded-lg border border-[#cf73a4] px-4 py-2 text-sm font-semibold text-[#cf73a4]"
                @click="openEditProjectModal"
              >
                Edit Project
              </button>
            </div>
          </div>
        </header>

        <div class="overflow-x-auto pb-2">
          <div class="grid min-w-[1200px] grid-cols-5 gap-4 xl:min-w-full">
            <section
              v-for="categoryName in KANBAN_CATEGORIES"
              :key="categoryName"
              class="rounded-xl bg-white p-4 text-[#333333] shadow"
            >
              <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-[#cf73a4]">
                {{ categoryName }}
              </h2>

              <div class="space-y-3">
                <article
                  v-for="task in groupedTasks[categoryName]"
                  :key="task.id"
                  class="rounded-lg border border-[#eeeeee] bg-[#f8f8f8] p-3"
                >
                  <h3 class="text-sm font-semibold">{{ task.title }}</h3>
                  <p class="mt-1 text-xs text-[#666666]">{{ task.description }}</p>
                  <p class="mt-2 text-xs font-medium">Due: {{ formatDate(task.due_date) }}</p>

                  <button
                    type="button"
                    class="mt-3 rounded-md bg-[#cf73a4] px-2.5 py-1 text-xs font-semibold text-white disabled:opacity-60"
                    :disabled="movingTaskId === task.id"
                    @click="moveTaskToNextCategory(task)"
                  >
                    Move Next
                  </button>
                </article>

                <p
                  v-if="groupedTasks[categoryName].length === 0"
                  class="rounded-lg border border-dashed border-[#dddddd] p-3 text-xs text-[#888888]"
                >
                  No tasks.
                </p>
              </div>
            </section>
          </div>
        </div>
      </template>
    </div>

    <div
      v-if="isCreateTaskOpen"
      class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4"
    >
      <div class="w-full max-w-lg rounded-xl bg-white p-5 text-[#333333] shadow-xl">
        <h3 class="text-lg font-bold text-[#cf73a4]">Tambah Task</h3>

        <form class="mt-4 space-y-3" @submit.prevent="submitCreateTask">
          <input
            v-model="taskForm.title"
            type="text"
            placeholder="Task title"
            class="w-full rounded-lg border border-[#dddddd] px-3 py-2 text-sm outline-none"
          />

          <textarea
            v-model="taskForm.description"
            rows="3"
            placeholder="Task description"
            class="w-full rounded-lg border border-[#dddddd] px-3 py-2 text-sm outline-none"
          />

          <div class="grid grid-cols-2 gap-3">
            <input
              v-model="taskForm.due_date"
              type="date"
              class="w-full rounded-lg border border-[#dddddd] px-3 py-2 text-sm outline-none"
            />

            <select
              v-model="taskForm.category_id"
              class="w-full rounded-lg border border-[#dddddd] px-3 py-2 text-sm outline-none"
            >
              <option value="">Select category</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button
              type="button"
              class="rounded-lg border border-[#cf73a4] px-4 py-2 text-sm font-semibold text-[#cf73a4]"
              @click="isCreateTaskOpen = false"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="rounded-lg bg-[#cf73a4] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
              :disabled="isSavingTask"
            >
              Save Task
            </button>
          </div>
        </form>
      </div>
    </div>

    <div
      v-if="isEditProjectOpen"
      class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4"
    >
      <div class="w-full max-w-lg rounded-xl bg-white p-5 text-[#333333] shadow-xl">
        <h3 class="text-lg font-bold text-[#cf73a4]">Edit Project</h3>

        <form class="mt-4 space-y-3" @submit.prevent="submitEditProject">
          <input
            v-model="projectForm.name"
            type="text"
            placeholder="Project name"
            class="w-full rounded-lg border border-[#dddddd] px-3 py-2 text-sm outline-none"
          />

          <textarea
            v-model="projectForm.description"
            rows="3"
            placeholder="Project description"
            class="w-full rounded-lg border border-[#dddddd] px-3 py-2 text-sm outline-none"
          />

          <select
            v-model="projectForm.status"
            class="w-full rounded-lg border border-[#dddddd] px-3 py-2 text-sm outline-none"
          >
            <option value="active">active</option>
            <option value="archived">archived</option>
          </select>

          <div class="flex justify-end gap-2 pt-2">
            <button
              type="button"
              class="rounded-lg border border-[#cf73a4] px-4 py-2 text-sm font-semibold text-[#cf73a4]"
              @click="isEditProjectOpen = false"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="rounded-lg bg-[#cf73a4] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
              :disabled="isSavingProject"
            >
              Update Project
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>
