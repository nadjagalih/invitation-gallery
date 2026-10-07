<?php

namespace Database\Seeders;

use App\Models\Template;
use App\Models\TemplateCategory;
use Illuminate\Database\Seeder;

/**
 * Satu template dulu sampai polanya mantap. `slug` harus punya folder view
 * yang benar-benar ada di resources/views/invitations/{slug}/v{n}/.
 */
class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $category = TemplateCategory::where('slug', 'art-non-adat')->firstOrFail();

        Template::updateOrCreate(
            ['slug' => 'elegant-botanical'],
            [
                'name' => 'Elegant Botanical',
                'template_category_id' => $category->id,
                'current_version' => 1,
                'price' => 350_000,
                'promo_price' => 149_000,
                'badges' => ['Gratis Template Story IG'],
                'thumbnails' => [],
                'supported_features' => [
                    'love_story',
                    'gallery',
                    'gift',
                    'gift_confirmation',
                    'rsvp',
                    'wishes',
                    'music',
                ],
                'description' => 'Desain lembut bernuansa terracotta dan krem dengan aksen botani, cocok untuk resepsi indoor maupun outdoor.',
                'is_active' => true,
                'sort_order' => 0,
            ],
        );
    }
}
