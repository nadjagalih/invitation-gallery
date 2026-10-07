<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Template;
use App\Models\TemplateCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatalogPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function katalog_menampilkan_template_aktif_dengan_demo_dan_harga(): void
    {
        $category = TemplateCategory::factory()->create([
            'name' => 'Art Adat',
            'slug' => 'art-adat',
        ]);
        $template = Template::factory()
            ->renderable()
            ->for($category, 'category')
            ->create([
                'name' => 'Elegant Botanical',
                'price' => 350_000,
                'promo_price' => 149_000,
            ]);
        $demo = Invitation::factory()->for($template)->preview()->create();
        $template->update(['demo_invitation_id' => $demo->getKey()]);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('Elegant Botanical')
            ->assertSee('Rp149.000')
            ->assertSee(route('template.demo', $template), false);
    }

    #[Test]
    public function katalog_dapat_disaring_berdasarkan_kategori(): void
    {
        $category = TemplateCategory::factory()->create([
            'name' => 'Art Adat',
            'slug' => 'art-adat',
        ]);
        Template::factory()->renderable()->for($category, 'category')->create([
            'name' => 'Tema Terpilih',
        ]);
        Template::factory()->create([
            'name' => 'Tema Lain',
            'slug' => 'tema-lain',
        ]);

        $this->get(route('catalog.index', ['category' => 'art-adat']))
            ->assertOk()
            ->assertSee('Tema Terpilih')
            ->assertDontSee('Tema Lain');
    }
}
