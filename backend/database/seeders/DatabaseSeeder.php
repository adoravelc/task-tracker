<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Category;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'Admin Energeek',
            'email' => 'admin@energeek.id',
            'password' => Hash::make('admPa$$Energeek'),
            'is_admin' => true,
        ]);

        $categories = [
            'TODO',
            'IN PROGRESS',
            'TESTING',
            'DONE',
            'PENDING'
        ];

        foreach ($categories as $category) {
            Category::create(['name' => $category]);
        }
    }
}
