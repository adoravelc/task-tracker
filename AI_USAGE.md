# AI_USAGE.md

## AI Assistant

- Tool: GitHub Copilot
- Model: GPT-5.3-Codex
- Scope: Fullstack development for `project-tracker` (Laravel 12 API + Vue 3 TypeScript)

## Prompt Summary

Berikut ringkasan prompt utama yang digunakan selama development:

1. Membuat Authentication API di Laravel Sanctum:
   - `login` dengan validasi email/password, `Auth::attempt`, generate PAT token.
   - `logout` dengan revoke token aktif.
   - route `logout` wajib `auth:sanctum`.

2. Melengkapi `ProjectController`:
   - `index` dengan search/filter.
   - `store` + `update` dengan validasi.
   - `created_by` otomatis dari user login.
   - `show` menampilkan project beserta task.

3. Melengkapi `TaskController`:
   - `index` dengan search/filter.
   - `store`, `show`, `update`, `destroy`.
   - soft delete + isi `deleted_by` sebelum delete.

4. Membuat `DashboardController` statistik:
   - total project aktif,
   - total task belum done,
   - task terdekat deadline,
   - distribusi task per kategori.

5. Frontend Vue 3 + TypeScript:
   - Pinia auth store,
   - router guard (redirect login/protected routes),
   - Login page,
   - Main layout + sidebar,
   - Dashboard, Project, Task global, Project detail (kanban + modal + drag/drop).

6. Iterasi UI/UX:
   - penyesuaian tema warna,
   - perbaikan spacing antar kotak,
   - pewarnaan badge kategori (highlight/stabilo),
   - polishing tabel, card, dan layout.

7. Data seeding dan dokumentasi:
   - seeder admin, kategori, project, dan task,
   - pembuatan `README.md` root sesuai ketentuan.

## MCP / Context Used

Konteks yang digunakan oleh agent:

- Workspace lokal:
  - `backend/` (Laravel 12 API)
  - `frontend/` (Vue 3 + TypeScript)
- Repository context:
  - Owner: `adoravelc`
  - Repo: `project-tracker`
  - Branch: `main`
- Editor context:
  - file aktif dan selection dari VS Code.
- Terminal context:
  - hasil command (contoh: `migrate:fresh --seed`, `npm run build`).
- Attachment context:
  - screenshot UI dan file lampiran dari user.

## Tools Used

Tools yang digunakan selama pengerjaan (utama):

- `read_file` -> membaca source code/migration/seeder/view.
- `apply_patch` -> edit file existing secara terarah.
- `create_file` -> membuat file baru (contoh: `README.md`, `AI_USAGE.md`).
- `get_errors` -> validasi error setelah perubahan.
- `run_in_terminal` -> menjalankan command validasi/manual bila diperlukan.
- `list_dir` -> verifikasi struktur folder.
- `multi_tool_use.parallel` -> menjalankan beberapa operasi read/edit paralel saat aman.

## Output Artifacts

Dokumen/hasil yang wajib dan dihasilkan:

- `README.md` di root project (instalasi, menjalankan aplikasi, testing).
- `AI_USAGE.md` di root project (prompt, MCP/context, tools).

## Notes

- Perubahan dilakukan iteratif berdasarkan feedback user sampai memenuhi kebutuhan backend, frontend, UI spacing, seeding, dan dokumentasi.
- Jika ada requirement baru, dokumen ini dapat diperbarui sebagai log penggunaan AI lanjutan.