<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/utils/axios'
import { getApiErrorMessage } from '@/utils/apiError'

interface TaskCategory {
  id: number
  name: string
}

interface ProjectTask {
  id: number
  title: string
  description: string
  due_date: string
  project_id?: number
  category_id?: number
  category: TaskCategory | null
}

interface ProjectDetail {
  id: number
  name: string
  description: string
  status: 'active' | 'archived'
  created_at?: string
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
const isEditTaskOpen = ref(false)
const isSavingTask = ref(false)
const isSavingProject = ref(false)
const isSavingTaskEdit = ref(false)
const deletingTaskId = ref<number | null>(null)
const draggingTaskId = ref<number | null>(null)
const dragOverCategory = ref<string | null>(null)

const categoryToneClass: Record<string, { column: string; badge: string; card: string }> = {
  TODO: {
    column: 'border-t-4 border-t-red-400',
    badge: 'bg-red-100 text-red-700',
    card: 'bg-red-50 border-red-200',
  },
  'IN PROGRESS': {
    column: 'border-t-4 border-t-orange-400',
    badge: 'bg-orange-100 text-orange-700',
    card: 'bg-orange-50 border-orange-200',
  },
  TESTING: {
    column: 'border-t-4 border-t-amber-400',
    badge: 'bg-amber-100 text-amber-700',
    card: 'bg-amber-50 border-amber-200',
  },
  DONE: {
    column: 'border-t-4 border-t-emerald-500',
    badge: 'bg-emerald-100 text-emerald-700',
    card: 'bg-emerald-50 border-emerald-200',
  },
  PENDING: {
    column: 'border-t-4 border-t-gray-400',
    badge: 'bg-gray-200 text-gray-700',
    card: 'bg-gray-100 border-gray-300',
  },
}

const getColumnToneClass = (categoryName: string) => {
  return categoryToneClass[categoryName]?.column ?? 'border-t-4 border-t-gray-300'
}

const getBadgeToneClass = (categoryName: string) => {
  return categoryToneClass[categoryName]?.badge ?? 'bg-gray-100 text-gray-700'
}

const getCardToneClass = (categoryName: string) => {
  return categoryToneClass[categoryName]?.card ?? 'bg-white border-[#e5e7eb]'
}

const taskForm = ref({
  title: '',
  description: '',
  due_date: '',
  category_id: '',
})

const editTaskForm = ref({
  id: 0,
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

const totalTasks = computed(() => project.value?.tasks.length ?? 0)

const doneTasks = computed(() => {
  if (!project.value) {
    return 0
  }

  return project.value.tasks.filter((task) => task.category?.name === 'DONE').length
})

const doneSummary = computed(() => `${doneTasks.value} / ${totalTasks.value}`)

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
  } catch (error: unknown) {
    errorMessage.value = getApiErrorMessage(error, 'Unable to load project details.')
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

const openEditTaskModal = (task: ProjectTask) => {
  editTaskForm.value = {
    id: task.id,
    title: task.title,
    description: task.description,
    due_date: task.due_date.slice(0, 10),
    category_id: String(task.category_id ?? task.category?.id ?? ''),
  }

  isEditTaskOpen.value = true
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
  } catch (error: unknown) {
    errorMessage.value = getApiErrorMessage(error, 'Unable to create task.')
  } finally {
    isSavingTask.value = false
  }
}

const submitEditTask = async () => {
  if (
    !editTaskForm.value.title.trim() ||
    !editTaskForm.value.description.trim() ||
    !editTaskForm.value.due_date ||
    !editTaskForm.value.category_id
  ) {
    errorMessage.value = 'Please complete all task fields.'
    return
  }

  isSavingTaskEdit.value = true
  errorMessage.value = ''

  try {
    await api.put(`/tasks/${editTaskForm.value.id}`, {
      title: editTaskForm.value.title.trim(),
      description: editTaskForm.value.description.trim(),
      due_date: editTaskForm.value.due_date,
      category_id: Number(editTaskForm.value.category_id),
    })

    isEditTaskOpen.value = false
    await fetchProject()
  } catch (error: unknown) {
    errorMessage.value = getApiErrorMessage(error, 'Unable to update task.')
  } finally {
    isSavingTaskEdit.value = false
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
  } catch (error: unknown) {
    errorMessage.value = getApiErrorMessage(error, 'Unable to update project.')
  } finally {
    isSavingProject.value = false
  }
}

const deleteTask = async (task: ProjectTask) => {
  const confirmed = window.confirm(`Hapus task \"${task.title}\"?`)
  if (!confirmed) {
    return
  }

  deletingTaskId.value = task.id
  errorMessage.value = ''

  try {
    await api.delete(`/tasks/${task.id}`)
    await fetchProject()
  } catch (error: unknown) {
    errorMessage.value = getApiErrorMessage(error, 'Unable to delete task.')
  } finally {
    deletingTaskId.value = null
  }
}

const handleTaskDragStart = (event: DragEvent, taskId: number) => {
  draggingTaskId.value = taskId

  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', String(taskId))
  }
}

const handleTaskDragEnd = () => {
  draggingTaskId.value = null
  dragOverCategory.value = null
}

const handleColumnDragOver = (event: DragEvent, categoryName: string) => {
  event.preventDefault()

  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'move'
  }

  dragOverCategory.value = categoryName
}

const handleColumnDragLeave = (categoryName: string) => {
  if (dragOverCategory.value === categoryName) {
    dragOverCategory.value = null
  }
}

const handleColumnDrop = async (event: DragEvent, categoryName: string) => {
  event.preventDefault()

  if (!project.value) {
    return
  }

  const taskIdFromDataTransfer = event.dataTransfer?.getData('text/plain')
  const taskId = taskIdFromDataTransfer ? Number(taskIdFromDataTransfer) : draggingTaskId.value

  if (!taskId) {
    handleTaskDragEnd()
    return
  }

  const droppedTask = project.value.tasks.find((task) => task.id === taskId)
  const targetCategory = categoryByName.value[categoryName]

  if (!droppedTask || !targetCategory) {
    handleTaskDragEnd()
    return
  }

  if (droppedTask.category?.id === targetCategory.id) {
    handleTaskDragEnd()
    return
  }

  errorMessage.value = ''

  try {
    await api.put(`/tasks/${droppedTask.id}`, {
      category_id: targetCategory.id,
    })

    droppedTask.category = targetCategory
    droppedTask.category_id = targetCategory.id
  } catch (error: unknown) {
    errorMessage.value = getApiErrorMessage(error, 'Unable to move task to selected category.')
  } finally {
    handleTaskDragEnd()
  }
}

onMounted(async () => {
  await Promise.all([fetchCategories(), fetchProject()])
})
</script>

<template>
  <section class="min-h-[calc(100vh-3rem)] bg-[#333333] p-6 text-white">
    <div class="mx-auto flex max-w-[1500px] flex-col" style="row-gap: 6mm;">
      <button
        type="button"
        class="rounded-lg border border-[#cf73a4] px-4 py-2 text-sm font-semibold text-[#cf73a4]"
        @click="goBackToProjects"
      >
        ← Back to Projects
      </button>

      <p v-if="isLoading" class="text-sm text-white/80">Loading project details...</p>
      <p v-else-if="errorMessage" class="rounded-lg bg-white/10 px-4 py-2 text-sm">{{ errorMessage }}</p>

      <template v-if="project && !isLoading">
        <header class="rounded-xl bg-white p-5 text-[#333333] shadow">
          <div class="flex flex-wrap items-start justify-between gap-4 border-b border-[#e5e7eb] pb-4">
            <div>
              <h1 class="text-3xl font-bold text-[#333333]">{{ project.name }}</h1>
              <p class="mt-2 text-sm text-[#6b7280]">{{ project.description }}</p>
            </div>

            <div class="flex items-center gap-2">
              <span
                class="rounded-full px-3 py-1 text-xs font-semibold"
                :class="
                  project.status === 'active'
                    ? 'bg-emerald-100 text-emerald-700'
                    : 'bg-gray-200 text-gray-700'
                "
              >
                {{ project.status === 'active' ? 'Active' : 'Archived' }}
              </span>
              <button
                type="button"
                class="rounded-md border border-[#d1d5db] px-3 py-1.5 text-xs font-semibold text-[#374151]"
                @click="openEditProjectModal"
              >
                Edit
              </button>
            </div>
          </div>

          <div class="mt-4 flex flex-wrap gap-8 text-xs font-semibold uppercase tracking-wide text-[#6b7280]">
            <div>
              <p>Dibuat</p>
              <p class="mt-1 text-sm font-bold text-[#111827]">
                {{ project.created_at ? formatDate(project.created_at) : '-' }}
              </p>
            </div>
            <div>
              <p>Total Task</p>
              <p class="mt-1 text-sm font-bold text-[#111827]">{{ totalTasks }}</p>
            </div>
            <div>
              <p>Selesai</p>
              <p class="mt-1 text-sm font-bold text-emerald-700">{{ doneSummary }}</p>
            </div>
          </div>
        </header>

        <div class="flex items-center justify-between">
          <h2 class="text-base font-bold">Task</h2>

          <div class="flex gap-[3mm]">
              <button
                type="button"
                class="rounded-lg bg-[#cf73a4] px-4 py-2 text-sm font-semibold text-white"
                @click="openCreateTaskModal"
              >
                Tambah Task
              </button>
          </div>
        </div>

        <div class="overflow-x-auto pb-2">
          <div class="grid min-w-[1200px] grid-cols-5 gap-4 xl:min-w-full">
            <section
              v-for="categoryName in KANBAN_CATEGORIES"
              :key="categoryName"
              class="rounded-xl border border-white/20 bg-white/95 p-3 text-[#333333] transition"
              :class="[
                getColumnToneClass(categoryName),
                dragOverCategory === categoryName ? 'ring-2 ring-[#cf73a4]' : '',
              ]"
              @dragover="handleColumnDragOver($event, categoryName)"
              @dragleave="handleColumnDragLeave(categoryName)"
              @drop="handleColumnDrop($event, categoryName)"
            >
              <div class="mb-3 flex items-center justify-between border-b border-[#e5e7eb] pb-2">
                <h2 class="text-xs font-bold uppercase tracking-wide text-[#374151]">{{ categoryName }}</h2>
                <span
                  class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                  :class="getBadgeToneClass(categoryName)"
                >
                  {{ (groupedTasks[categoryName] ?? []).length }}
                </span>
              </div>

              <div class="space-y-[6mm]">
                <article
                  v-for="task in groupedTasks[categoryName] ?? []"
                  :key="task.id"
                  class="cursor-grab rounded-lg border p-3 active:cursor-grabbing"
                  :class="getCardToneClass(categoryName)"
                  draggable="true"
                  @dragstart="handleTaskDragStart($event, task.id)"
                  @dragend="handleTaskDragEnd"
                >
                  <h3 class="text-sm font-semibold text-[#111827]">{{ task.title }}</h3>
                  <p class="mt-1 text-[11px] text-[#6b7280]">{{ formatDate(task.due_date) }}</p>

                  <div class="mt-2 flex gap-[3mm]">
                    <button
                      type="button"
                      class="rounded bg-[#dbeafe] px-2 py-1 text-[10px] font-semibold text-[#2563eb]"
                      @click="openEditTaskModal(task)"
                    >
                      Edit
                    </button>
                    <button
                      type="button"
                      class="rounded bg-[#fee2e2] px-2 py-1 text-[10px] font-semibold text-[#ef4444] disabled:opacity-60"
                      :disabled="deletingTaskId === task.id"
                      @click="deleteTask(task)"
                    >
                      Hapus
                    </button>
                  </div>
                </article>

                <p
                  v-if="(groupedTasks[categoryName] ?? []).length === 0"
                  class="rounded-lg border border-dashed border-[#d1d5db] p-2 text-center text-[11px] text-[#9ca3af]"
                >
                  No tasks
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

    <div
      v-if="isEditTaskOpen"
      class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4"
    >
      <div class="w-full max-w-lg rounded-xl bg-white p-5 text-[#333333] shadow-xl">
        <h3 class="text-lg font-bold text-[#cf73a4]">Edit Task</h3>

        <form class="mt-4 space-y-3" @submit.prevent="submitEditTask">
          <input
            v-model="editTaskForm.title"
            type="text"
            placeholder="Task title"
            class="w-full rounded-lg border border-[#dddddd] px-3 py-2 text-sm outline-none"
          />

          <textarea
            v-model="editTaskForm.description"
            rows="3"
            placeholder="Task description"
            class="w-full rounded-lg border border-[#dddddd] px-3 py-2 text-sm outline-none"
          />

          <div class="grid grid-cols-2 gap-3">
            <input
              v-model="editTaskForm.due_date"
              type="date"
              class="w-full rounded-lg border border-[#dddddd] px-3 py-2 text-sm outline-none"
            />

            <select
              v-model="editTaskForm.category_id"
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
              @click="isEditTaskOpen = false"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="rounded-lg bg-[#cf73a4] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
              :disabled="isSavingTaskEdit"
            >
              Update Task
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>
