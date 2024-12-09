<?php
declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Role;
use App\Models\Submission;
use App\Models\SubmissionInvitation;
use App\Models\User;
use Tests\TestCase;
use Tests\TestFactory;

class InvitationsTest extends TestCase
{
    use TestFactory;

    /**
     * @return void
     */
    public function testSubmissionUsersReceiveNotificationsUponAcceptedReviewerInvitations()
    {
        $this->beAppAdmin();
        $submitter = User::factory()->create();
        $reviewer = User::factory()->create();
        $review_coordinator = User::factory()->create();
        $submission = Submission::factory()
            ->hasAttached($submitter, [], 'submitters')
            ->hasAttached($reviewer, [], 'reviewers')
            ->hasAttached($review_coordinator, [], 'reviewCoordinators')
            ->create();
        $invite = SubmissionInvitation::create([
            'submission_id' => $submission->id,
            'role_id' => Role::REVIEWER_ROLE_ID,
            'email' => 'bob1@msu.edu',
        ]);
        $invite->inviteReviewer();
        $details = [
            'name' => '',
            'username' => 'bob1',
            'password' => 'rLT2ovkZkMby5UpwiQkFBeS9',
        ];
        $invite->acceptInvite($details);
        $this->assertEquals(1, $submitter->notifications->count());
        $this->assertEquals(1, $reviewer->notifications->count());
        $this->assertEquals(1, $review_coordinator->notifications->count());
    }

    /**
     * @return void
     */
    public function testSubmissionUsersReceiveNotificationsUponAcceptedReviewCoordinatorInvitations()
    {
        $this->beAppAdmin();
        $submitter = User::factory()->create();
        $reviewer = User::factory()->create();
        $review_coordinator = User::factory()->create();
        $submission = Submission::factory()
            ->hasAttached($submitter, [], 'submitters')
            ->hasAttached($reviewer, [], 'reviewers')
            ->hasAttached($review_coordinator, [], 'reviewCoordinators')
            ->create();
        $invite = SubmissionInvitation::create([
            'submission_id' => $submission->id,
            'role_id' => Role::REVIEW_COORDINATOR_ROLE_ID,
            'email' => 'bob2@msu.edu',
        ]);
        $invite->inviteReviewCoordinator();
        $details = [
            'name' => '',
            'username' => 'bob2',
            'password' => 'aYUB1IYUadd38fl9mxAVv2',
        ];
        $invite->acceptInvite($details);
        $this->assertEquals(1, $submitter->notifications->count());
        $this->assertEquals(1, $reviewer->notifications->count());
        $this->assertEquals(1, $review_coordinator->notifications->count());
    }
}
