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
     *
     * Deploys run this on every boot, so an already-seeded database is left
     * alone rather than collecting a second set of demo orders.
     */
    public function run(): void
    {
        if (User::query()->exists()) {
            return;
        }

        User::factory()->create([
            'name' => 'Ops Operator',
            'email' => 'operator@ecomdrive.test',
        ]);

        $this->call([
            ProductSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
