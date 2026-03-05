<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $deadline = trim((string) $request->query('deadline', ''));

        $projects = Project::query()
            ->withCount('tasks')
            ->when($search !== '', function ($query) use ($search) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
            })
            ->when(in_array($status, ['active', 'archived'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($deadline !== '', function ($query) use ($deadline) {
                $query->whereHas('tasks', function ($taskQuery) use ($deadline) {
                    $taskQuery->whereDate('due_date', '<=', $deadline);
                });
            })
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Projects retrieved successfully.',
            'data' => $projects,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'status' => ['required', 'in:active,archived'],
        ]);

        $project = Project::create([
            ...$validated,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Project created successfully.',
            'data' => $project,
        ], 201);
    }

    public function show(Project $project)
    {
        $project->load('tasks.category');

        return response()->json([
            'message' => 'Project retrieved successfully.',
            'data' => $project,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'status' => ['required', 'in:active,archived'],
        ]);

        $project->update($validated);

        return response()->json([
            'message' => 'Project updated successfully.',
            'data' => $project,
        ]);
    }
}
