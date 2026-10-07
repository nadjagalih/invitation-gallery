<?php

namespace Database\Factories;

use App\Models\TemplateCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Template>
 */
class TemplateFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));
        $price = fake()->numberBetween(15, 50) * 10_000;

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'template_category_id' => TemplateCategory::factory(),
            'current_version' => 1,
            'price' => $price,
            'promo_price' => (int) round($price * 0.45 / 1000) * 1000,
            'badges' => ['Gratis Template Story IG'],
            'thumbnails' => [],
            'supported_features' => ['love_story', 'gallery', 'gift', 'gift_confirmation', 'rsvp', 'wishes', 'music'],
            'description' => fake()->sentence(12),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Slug yang benar-benar punya folder view, sehingga undangan yang memakainya
     * bisa dirender. Wajib dipakai test yang memanggil halaman publik.
     */
    public function renderable(): static
    {
        return $this->state(fn () => [
            'name' => 'Elegant Botanical',
            'slug' => 'elegant-botanical',
        ]);
    }

    public function slug(string $slug): static
    {
        return $this->state(fn () => ['slug' => $slug]);
    }

    public function withoutPromo(): static
    {
        return $this->state(fn () => ['promo_price' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
