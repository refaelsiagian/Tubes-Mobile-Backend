<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating 20 users...');
        
        // Buat 20 user menggunakan factory
        User::factory(20)->create();
        
        $this->command->info('Users have been created!');
    }
}