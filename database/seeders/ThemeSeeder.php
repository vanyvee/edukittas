<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Safe to re-run: upserts on `code`. Add a theme = add a row here AND a
// [data-theme="<code>"] block in App.vue.
class ThemeSeeder extends Seeder
{
    public const THEMES = [
        ['code' => 'default', 'name' => 'Onion & Milky',  'description' => 'Default purple and soft milky neutrals'],
        ['code' => 'theme1',  'name' => 'Goal Mastery',   'description' => 'Navy and clear sky blue'],
        ['code' => 'theme2',  'name' => 'Royal Mint',     'description' => 'Royal purple and mint'],
        ['code' => 'theme3',  'name' => 'Nanocodes',      'description' => 'Violet, deep purple and off-white'],
        ['code' => 'theme4',  'name' => 'Blue Serenity',  'description' => 'Indigo and soft periwinkle'],
        ['code' => 'theme5',  'name' => 'Ocean Breeze',   'description' => 'Blue, cyan and violet'],
        ['code' => 'theme6',  'name' => 'Electric Teal',  'description' => 'Electric aqua and deep charcoal'],
    ];

    public function run(): void
    {
        $now = now();
        $rows = [];
        foreach (self::THEMES as $i => $t) {
            $rows[] = $t + ['is_active' => true, 'sort' => $i, 'created_at' => $now, 'updated_at' => $now];
        }
        DB::table('themes')->upsert($rows, ['code'], ['name', 'description', 'is_active', 'sort', 'updated_at']);
    }
}
