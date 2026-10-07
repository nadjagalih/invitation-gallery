<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@lensaku.test'],
            [
                'name' => 'Administrator',
                'password' => 'password',
                'is_admin' => true,
                'email_verified_at' => now(),
            ],
        );

        $this->call([
            TemplateCategorySeeder::class,
            TemplateSeeder::class,
            DemoInvitationSeeder::class,
        ]);
    }
}
