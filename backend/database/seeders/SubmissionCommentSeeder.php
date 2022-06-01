<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\InlineComment;
use App\Models\OverallComment;
use App\Models\StyleCriteria;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class SubmissionCommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @param int $reply_count
     * @param int $reply_reply_count
     * @return void
     */
    public function run($reply_count = 0, $reply_reply_count = 0)
    {
        $userIds = User::all()->pluck('id');
        $userId = $userIds->random();
        $style_criterias = StyleCriteria::inRandomOrder()
            ->limit(rand(1, 4))
            ->get()
            ->toArray();

        $inline_parent = InlineComment::factory()->create([
            'submission_id' => 100,
            'created_by' => $userId,
            'updated_by' => $userId,
            'style_criteria' => $style_criterias,
        ]);
        $overall_parent = OverallComment::factory()->create([
            'submission_id' => 100,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        // Replies
        if ($reply_count > 0) {
            // Seed inline comment replies
            for ($i = $reply_count; $i > 0; $i--) {
                $inline_reply = $this->createCommentReply(true, $userIds->random(), $inline_parent, $inline_parent);
            }

            // Seed replies to inline comment replies
            for ($i = $reply_reply_count; $i > 0; $i--) {
                $this->createCommentReply(true, $userIds->random(), $inline_parent, $inline_reply);
            }

            // Seed overall comment replies
            for ($i = $reply_count; $i > 0; $i--) {
                $overall_reply = $this->createCommentReply(false, $userIds->random(), $overall_parent, $overall_parent);
            }

            // Seed replies to overall comment replies
            for ($i = $reply_reply_count; $i > 0; $i--) {
                $this->createCommentReply(false, $userIds->random(), $overall_parent, $overall_reply);
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
        $datetime = $faker->dateTimeBetween($time, $time->addHours(24));
        $data = [
            'submission_id' => 100,
            'parent_id' => $parent->id,
            'reply_to_id' => $reply_to->id,
            'created_at' => $datetime,
            'updated_at' => $datetime,
            'created_by' => $userId,
            'updated_by' => $userId,
        ];

        return $is_inline
            ? InlineComment::factory()->create($data)
            : OverallComment::factory()->create($data);
    }
}
