<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;

class DashboardController extends Controller
{
    public function index()
    {
        $activeProjectsCount = Project::where('status', 'active')->count();

        $incompleteTasksCount = Task::whereHas('category', function ($query) {
            $query->where('name', '!=', 'DONE');
        })->count();

        $upcomingTasks = Task::with(['project', 'category'])
            ->whereHas('category', function ($query) {
                $query->where('name', '!=', 'DONE');
            })
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        return response()->json([
            'message' => 'Dashboard data retrieved successfully.',
            'data' => [
                'active_projects_count' => $activeProjectsCount,
                'incomplete_tasks_count' => $incompleteTasksCount,
                'upcoming_tasks' => $upcomingTasks,
            ],
        ]);
    }
}
