<?php

namespace Database\Seeders;

use App\Models\TemplateCategory;
use Illuminate\Database\Seeder;

class TemplateCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Art Adat', 'slug' => 'art-adat'],
            ['name' => 'Art Non Adat', 'slug' => 'art-non-adat'],
            ['name' => 'Tema Exclusive', 'slug' => 'tema-exclusive'],
            ['name' => 'Story IG', 'slug' => 'story-ig'],
        ];

        foreach ($categories as $index => $category) {
            TemplateCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category + ['sort_order' => $index, 'is_active' => true],
            );
        }
    }
}
