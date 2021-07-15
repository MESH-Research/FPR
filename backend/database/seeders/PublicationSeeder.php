<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Publication;
use App\Models\User;
use Illuminate\Database\Seeder;

class PublicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Publication::factory()->hasAttached(
            User::where('username', 'applicationAdminUser')->firstOrFail(),
            [
                'role_id' => 2,
            ]
        )
        ->create([
            'id' => 1,
            'name' => 'CCR Test Publication 1',
        ]);
    }
}
