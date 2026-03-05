<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/utils/axios'

type ProjectStatus = 'active' | 'archived'

interface Project {
  id: number
  name: string
  description: string
  status: ProjectStatus
  created_at: string
  tasks_count: number
}

const router = useRouter()

const projects = ref<Project[]>([])
const search = ref('')
const selectedStatus = ref('')
const selectedDeadline = ref('')
const isLoading = ref(false)
const isSubmitting = ref<number | null>(null)
const isSavingForm = ref(false)
const errorMessage = ref('')
const isFormModalOpen = ref(false)
let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null

const formMode = ref<'create' | 'edit'>('create')
const editingId = ref<number | null>(null)
const form = ref({
  name: '',
  description: '',
  status: 'active' as ProjectStatus,
})

const hasProjects = computed(() => projects.value.length > 0)

const formTitle = computed(() => (formMode.value === 'create' ? 'Tambah Project' : 'Edit Project'))

const submitLabel = computed(() => (formMode.value === 'create' ? 'Create Project' : 'Update Project'))

const resetForm = () => {
  formMode.value = 'create'
  editingId.value = null
  form.value = {
    name: '',
    description: '',
    status: 'active',
  }
}

const openCreateModal = () => {
  resetForm()
  isFormModalOpen.value = true
}

const closeFormModal = () => {
  isFormModalOpen.value = false
}

const fetchProjects = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const response = await api.get<{ data: Project[] }>('/projects', {
      params: {
        search: search.value.trim() || undefined,
        status: selectedStatus.value || undefined,
        deadline: selectedDeadline.value || undefined,
      },
    })

    projects.value = response.data.data
  } catch {
    errorMessage.value = 'Failed to load projects.'
  } finally {
    isLoading.value = false
  }
}


const toggleStatus = async (project: Project) => {
  const nextStatus: ProjectStatus = project.status === 'active' ? 'archived' : 'active'
  isSubmitting.value = project.id

  try {
    await api.put(`/projects/${project.id}`, {
      name: project.name,
      description: project.description,
      status: nextStatus,
    })

    project.status = nextStatus
  } catch {
    errorMessage.value = 'Failed to update project status.'
  } finally {
    isSubmitting.value = null
  }
}

const submitProject = async () => {
  if (!form.value.name.trim() || !form.value.description.trim()) {
    errorMessage.value = 'Name and description are required.'
    return
  }

  isSavingForm.value = true
  errorMessage.value = ''

  try {
    if (formMode.value === 'create') {
      await api.post('/projects', {
        name: form.value.name.trim(),
        description: form.value.description.trim(),
        status: form.value.status,
      })
    } else if (editingId.value) {
      await api.put(`/projects/${editingId.value}`, {
        name: form.value.name.trim(),
        description: form.value.description.trim(),
        status: form.value.status,
      })
    }

    resetForm()
    closeFormModal()
    await fetchProjects()
  } catch {
    errorMessage.value = 'Failed to save project.'
  } finally {
    isSavingForm.value = false
  }
}

const startEdit = (project: Project) => {
  formMode.value = 'edit'
  editingId.value = project.id
  form.value = {
    name: project.name,
    description: project.description,
    status: project.status,
  }
  isFormModalOpen.value = true
}

const goToDetail = (projectId: number) => {
  router.push(`/projects/${projectId}`)
}

const formatDate = (value: string) => {
  return new Date(value).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

onMounted(async () => {
  await fetchProjects()
})

watch(search, () => {
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
  }

  searchDebounceTimer = setTimeout(() => {
    fetchProjects()
  }, 300)
})
</script>

<template>
  <section class="min-h-[calc(100vh-3rem)] bg-[#333333] p-8 text-white">
    <div class="mx-auto flex max-w-7xl flex-col" style="row-gap: 6mm;">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <h1 class="text-2xl font-bold text-[#cf73a4]">Project</h1>

        <div class="flex w-full flex-col gap-x-[3mm] gap-y-[6mm] md:w-auto md:flex-row">
          <form class="flex w-full max-w-md gap-x-[3mm] gap-y-[6mm]" @submit.prevent="fetchProjects">
            <input
              v-model="search"
              type="text"
              placeholder="Cari nama project..."
              class="w-full rounded-lg border border-white/20 bg-white px-3 py-2 text-sm text-[#333333] outline-none"
            />
            <button
              type="submit"
              class="rounded-lg bg-[#cf73a4] px-4 py-2 text-sm font-semibold text-white"
            >
              Search
            </button>
          </form>

          <select
            v-model="selectedStatus"
            class="rounded-lg border border-white/20 bg-white px-3 py-2 text-sm text-[#333333] outline-none"
            @change="fetchProjects"
          >
            <option value="">Semua Jenis</option>
            <option value="active">Active</option>
            <option value="archived">Archived</option>
          </select>

          <input
            v-model="selectedDeadline"
            type="date"
            class="rounded-lg border border-white/20 bg-white px-3 py-2 text-sm text-[#333333] outline-none"
            @change="fetchProjects"
          />

          <button
            type="button"
            class="rounded-lg bg-[#cf73a4] px-4 py-2 text-sm font-semibold text-white"
            @click="openCreateModal"
          >
            + Tambah Project
          </button>
        </div>
      </div>

      <p v-if="errorMessage" class="rounded-lg bg-white/10 px-4 py-2 text-sm">{{ errorMessage }}</p>
      <p v-if="isLoading" class="text-sm text-white/80">Loading projects...</p>

      <div v-else class="overflow-hidden rounded-xl border border-white/10 bg-white shadow-lg">
        <table class="min-w-full text-left text-sm text-[#333333]">
          <thead class="bg-[#f3f3f3] text-xs uppercase tracking-wide text-[#555555]">
            <tr>
              <th class="px-4 py-3">Nama</th>
              <th class="px-4 py-3">Deskripsi</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3">Total Task</th>
              <th class="px-4 py-3">Tanggal Dibuat</th>
              <th class="px-4 py-3">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!hasProjects">
              <td colspan="6" class="px-4 py-6 text-center text-[#666666]">No projects found.</td>
            </tr>

            <tr v-for="project in projects" :key="project.id" class="border-t border-[#eeeeee]">
              <td class="px-4 py-3 font-semibold">{{ project.name }}</td>
              <td class="px-4 py-3 text-[#666666]">{{ project.description }}</td>
              <td class="px-4 py-3">
                <span
                  class="rounded-full px-2 py-1 text-xs font-semibold"
                  :class="
                    project.status === 'active'
                      ? 'bg-green-100 text-green-700'
                      : 'bg-gray-200 text-gray-700'
                  "
                >
                  {{ project.status }}
                </span>
              </td>
              <td class="px-4 py-3">{{ project.tasks_count }}</td>
              <td class="px-4 py-3">{{ formatDate(project.created_at) }}</td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-x-[3mm] gap-y-[6mm]">
                  <button
                    type="button"
                    class="rounded-lg border border-[#cf73a4] px-3 py-1.5 text-xs font-semibold text-[#cf73a4]"
                    @click="goToDetail(project.id)"
                  >
                    Detail
                  </button>

                  <button
                    type="button"
                    class="rounded-lg border border-[#cf73a4] px-3 py-1.5 text-xs font-semibold text-[#cf73a4]"
                    @click="startEdit(project)"
                  >
                    Edit
                  </button>

                  <button
                    type="button"
                    class="rounded-lg bg-[#cf73a4] px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-60"
                    :disabled="isSubmitting === project.id"
                    @click="toggleStatus(project)"
                  >
                    {{ project.status === 'active' ? 'Hapus' : 'Aktifkan' }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="isFormModalOpen"
        class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4"
      >
        <div class="w-full max-w-xl rounded-xl bg-white p-5 text-[#333333] shadow-xl">
          <h2 class="text-base font-bold text-[#cf73a4]">{{ formTitle }}</h2>

          <form class="mt-3 grid gap-3 md:grid-cols-2" @submit.prevent="submitProject">
            <input
              v-model="form.name"
              type="text"
              placeholder="Nama project"
              class="rounded-lg border border-[#dddddd] px-3 py-2 text-sm outline-none"
            />

            <select
              v-model="form.status"
              class="rounded-lg border border-[#dddddd] px-3 py-2 text-sm outline-none"
            >
              <option value="active">active</option>
              <option value="archived">archived</option>
            </select>

            <textarea
              v-model="form.description"
              rows="3"
              placeholder="Deskripsi project"
              class="rounded-lg border border-[#dddddd] px-3 py-2 text-sm outline-none md:col-span-2"
            />

            <div class="flex gap-2 md:col-span-2 md:justify-end">
              <button
                type="button"
                class="rounded-lg border border-[#cf73a4] px-4 py-2 text-sm font-semibold text-[#cf73a4]"
                @click="closeFormModal"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="rounded-lg bg-[#cf73a4] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                :disabled="isSavingForm"
              >
                {{ submitLabel }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</template>
