<?php

namespace Database\Seeders;

use App\Models\ProhibitedItem;
use App\Models\ProhibitedItemsVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Standard courier-industry prohibited-items list (not sourced from a
 * specific docs file — no concrete list was specified, so this follows
 * common Indian intercity courier restrictions).
 */
class ProhibitedItemSeeder extends Seeder
{
    private const ITEMS = [
        ['name' => 'Petrol, diesel, kerosene', 'category' => 'hazardous', 'description' => 'Flammable liquids of any kind.'],
        ['name' => 'Gas cylinders and aerosol cans', 'category' => 'hazardous', 'description' => 'Pressurised or compressed gas containers.'],
        ['name' => 'Fireworks and explosives', 'category' => 'hazardous', 'description' => 'Any explosive or combustible material.'],
        ['name' => 'Industrial chemicals and acids', 'category' => 'hazardous', 'description' => 'Corrosive or toxic chemical substances.'],
        ['name' => 'Firearms and ammunition', 'category' => 'weapons', 'description' => 'Guns, ammunition, and firearm parts.'],
        ['name' => 'Knives, swords and bladed weapons', 'category' => 'weapons', 'description' => 'Sharp-edged weapons not intended as tools.'],
        ['name' => 'Cash and currency notes', 'category' => 'valuables', 'description' => 'Indian or foreign currency in any form.'],
        ['name' => 'Gold, silver and bullion', 'category' => 'valuables', 'description' => 'Precious metals in bulk or raw form.'],
        ['name' => 'Fresh food and perishables', 'category' => 'perishables', 'description' => 'Fruits, vegetables, dairy, or items requiring refrigeration.'],
        ['name' => 'Meat and seafood', 'category' => 'perishables', 'description' => 'Fresh, frozen, or raw meat and seafood products.'],
        ['name' => 'Narcotics and controlled substances', 'category' => 'restricted_substances', 'description' => 'Any drug or substance banned under Indian law.'],
        ['name' => 'Alcohol without valid permit', 'category' => 'restricted_substances', 'description' => 'Alcoholic beverages shipped without required licensing.'],
        ['name' => 'Live animals, birds and insects', 'category' => 'live_organisms', 'description' => 'Any living creature.'],
        ['name' => 'Plants with soil', 'category' => 'live_organisms', 'description' => 'Live plants shipped with soil or growing medium.'],
        ['name' => 'Human remains or body parts', 'category' => 'other', 'description' => 'Prohibited under courier and transport regulations.'],
        ['name' => 'Counterfeit goods', 'category' => 'other', 'description' => 'Items infringing on trademarks, copyrights, or patents.'],
    ];

    public function run(): void
    {
        foreach (self::ITEMS as $item) {
            ProhibitedItem::query()->updateOrCreate(
                ['name' => $item['name']],
                ['category' => $item['category'], 'description' => $item['description'], 'is_active' => true],
            );
        }

        $latestVersion = ProhibitedItemsVersion::query()->orderByDesc('published_at')->first();

        if ($latestVersion === null) {
            ProhibitedItemsVersion::create([
                'version_label' => 'v1',
                'content' => self::ITEMS,
                'published_at' => Carbon::now()->subDay(),
            ]);
        }
    }
}
