<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SubmissionFile;
use Illuminate\Database\Seeder;

class SubmissionFileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->callOnce(SubmissionSeeder::class);

        SubmissionFile::factory()->count(2)->create([
            'submission_id' => 100,
        ]);
        SubmissionFile::factory()->count(1)->create([
            'submission_id' => 101,
        ]);
    }
}
