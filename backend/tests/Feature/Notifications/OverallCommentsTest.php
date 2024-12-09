<?php
declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\OverallComment;
use App\Models\User;
use Tests\TestCase;
use Tests\TestFactory;

class OverallCommentsTest extends TestCase
{
    use TestFactory;

    private function createSubmissionWithOverallCommentThread()
    {
        $submission = $this->createSubmissionWithAllRoles();

        // Create comment participants
        $commentor = User::factory()->create();
        $commentor_reply = User::factory()->create();
        $commentor_reply_to_reply = User::factory()->create();
        $commentor_elsewhere = User::factory()->create();

        // Assign comment participants as submission reviewers
        $submission->reviewers()->attach([
            $commentor->id,
            $commentor_reply->id,
            $commentor_reply_to_reply->id,
            $commentor_elsewhere->id,
        ]);

        // Make comments
        OverallComment::factory()->create([
            'submission_id' => $submission->id,
            'content' => 'This is some content for an overall comment created by PHPUnit.',
            'created_by' => $commentor_elsewhere->id,
            'updated_by' => $commentor_elsewhere->id,
            'parent_id' => null,
            'reply_to_id' => null,
        ]);
        $comment_parent = OverallComment::factory()->create([
            'submission_id' => $submission->id,
            'content' => 'This is some content for an overall comment created by PHPUnit.',
            'created_by' => $commentor->id,
            'updated_by' => $commentor->id,
            'parent_id' => null,
            'reply_to_id' => null,
        ]);
        $comment_reply = OverallComment::factory()->create([
            'submission_id' => $submission->id,
            'content' => 'This is some content for an overall comment reply created by PHPUnit.',
            'created_by' => $commentor_reply->id,
            'updated_by' => $commentor_reply->id,
            'parent_id' => $comment_parent->id,
            'reply_to_id' => $comment_parent->id,
        ]);
        OverallComment::factory()->create([
            'submission_id' => $submission->id,
            'content' => 'This is some content for an overall comment reply to a reply created by PHPUnit.',
            'created_by' => $commentor_reply_to_reply->id,
            'updated_by' => $commentor_reply_to_reply->id,
            'parent_id' => $comment_parent->id,
            'reply_to_id' => $comment_reply->id,
        ]);

        return $submission;
    }

    /**
     * @return void
     */
    public function testUsersReceiveNotificationsForNewOverallComments()
    {
        $submission = $this->createSubmissionWithOverallCommentThread();
        $comments = $submission->overallCommentsWithReplies();
        $this->assertEquals(4, $comments->count());

        // Submitter
        // Gets all 4 notifications
        $submitter = $submission->submitters()->first();
        $this->assertEquals(2, $this->getOverallCommentNotificationCount($submitter));
        $this->assertEquals(2, $this->getOverallCommentReplyNotificationCount($submitter));

        // Uninvolved First Reviewer
        // Gets 0 notifications
        $reviewer1 = $submission->reviewers()->first();
        $this->assertEquals(0, $this->getOverallCommentNotificationCount($reviewer1));
        $this->assertEquals(0, $this->getOverallCommentReplyNotificationCount($reviewer1));

        // Overall Commentor
        // Gets 2 notifications for all replies
        $reviewer2 = $submission->reviewers()->get()->slice(1, 1)->first();
        $this->assertEquals(0, $this->getOverallCommentNotificationCount($reviewer2));
        $this->assertEquals(2, $this->getOverallCommentReplyNotificationCount($reviewer2));

        // Overall Comment Replier
        // Gets 1 notification for reply to reply
        $reviewer3 = $submission->reviewers()->get()->slice(2, 1)->first();
        $this->assertEquals(0, $this->getOverallCommentNotificationCount($reviewer3));
        $this->assertEquals(1, $this->getOverallCommentReplyNotificationCount($reviewer3));

        // Overall Comment Reply Replier
        // Gets 0 notifications
        $reviewer4 = $submission->reviewers()->get()->slice(3, 1)->first();
        $this->assertEquals(0, $this->getOverallCommentNotificationCount($reviewer4));
        $this->assertEquals(0, $this->getOverallCommentReplyNotificationCount($reviewer4));

        // Separate overall Commentor
        // Gets 0 notifications
        $reviewer5 = $submission->reviewers()->first();
        $this->assertEquals(0, $this->getOverallCommentNotificationCount($reviewer5));
        $this->assertEquals(0, $this->getOverallCommentReplyNotificationCount($reviewer5));

        // Coordinator
        // Gets all 4 notifications
        $coordinator = $submission->reviewCoordinators()->first();
        $this->assertEquals(2, $this->getOverallCommentNotificationCount($coordinator));
        $this->assertEquals(2, $this->getOverallCommentReplyNotificationCount($coordinator));

        // Editor
        // Gets 0 notifications
        $editor = $submission->publication->editors()->first();
        $this->assertEquals(0, $this->getOverallCommentNotificationCount($editor));
        $this->assertEquals(0, $this->getOverallCommentReplyNotificationCount($editor));

        // Publication Admins
        // Gets 0 notifications
        $admin = $submission->publication->publicationAdmins()->first();
        $this->assertEquals(0, $this->getOverallCommentNotificationCount($admin));
        $this->assertEquals(0, $this->getOverallCommentReplyNotificationCount($admin));
    }

    /**
     * @param User $user
     * @return int
     */
    private function getOverallCommentNotificationCount($user)
    {
        $type = 'App\Notifications\OverallCommentAdded';

        return $user->notifications()->where('type', $type)->get()->count();
    }

    /**
     * @param User $user
     * @return int
     */
    private function getOverallCommentReplyNotificationCount($user)
    {
        $type = 'App\Notifications\OverallCommentReplyAdded';

        return $user->notifications()->where('type', $type)->get()->count();
    }
}
