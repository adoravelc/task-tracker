<?php

namespace App\Http\Controllers;

use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'Categories retrieved successfully.',
            'data' => Category::query()->orderBy('name')->get(),
        ]);
    }
}
