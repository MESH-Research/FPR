<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\OverallComment;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class OverallCommentSeeder extends Seeder
{
    /**
     * Run the seeder
     *
     * @return void
     */
    public function run()
    {
        $this->callOnce(SubmissionSeeder::class);

        $this->create(100);
        $this->create(100, 1);
        $this->create(100, 8);
        $this->create(100, 1, 1);
    }

    /**
     * Run the database seeds.
     *
     * @param int $submissionId
     * @param int $replies
     * @param int $userId
     * @return void
     */
    protected function create($submissionId, $replies = 0, $userId = null)
    {
        $userIds = User::all()->pluck('id');
        if ($userId === null) {
            $userId = $userIds->except(1)->random(); // Ensure that the comment is not created by the application admin
        }

        $parent = OverallComment::factory()->create([
            'submission_id' => $submissionId,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        // Seed replies
        if ($replies > 0) {
            $comments = collect([$parent]);

            for ($i = 0; $i < $replies; $i++) {
                $comments->push($this->createCommentReply(false, $userIds->random(), $parent, $comments->random()));
            }
        }
    }

    /**
     * @param bool $is_inline
     * @param \App\Models\User $userId
     * @param \App\Models\InlineComment|\App\Models\OverallComment $parent
     * @param \App\Models\InlineComment|\App\Models\OverallComment $reply_to
     * @return \App\Models\InlineComment|\App\Models\OverallComment
     */
    private function createCommentReply($is_inline, $userId, $parent, $reply_to)
    {
        $faker = Faker::create();
        $time = Carbon::parse($reply_to->created_at);
        $datetime = $faker->dateTimeBetween($time, Carbon::now());

        return OverallComment::factory()->create([
            'submission_id' => 100,
            'parent_id' => $parent->id,
            'reply_to_id' => $reply_to->id,
            'created_at' => $datetime,
            'updated_at' => $datetime,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }
}
