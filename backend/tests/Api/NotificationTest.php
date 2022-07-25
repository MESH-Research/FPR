<?php
declare(strict_types=1);

namespace Tests\Api;

use App\Models\Publication;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\SubmissionCreated;
use App\Notifications\SubmissionStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\ApiTestCase;

class NotificationTest extends ApiTestCase
{
    use RefreshDatabase;

    /**
     * @param User $user
     * @param int $submission_id
     * @return array
     */
    private function getSampleNotificationData(User $user, $submission_id = 1000)
    {
        return [
            'submission' => [
                'id' => $submission_id,
                'title' => 'Test Submission from PHPUnit',
            ],
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
            ],
            'publication' => [
                'id' => 1,
                'name' => 'Test Publication from PHPUnit',
            ],
            'type' => 'submission.created',
            'action' => 'Visit CCR',
            'url' => '/',
            'body' => 'A submission has been created.',
        ];
    }

    /**
     * @return void
     */
    public function testNotificationsCanOnlyBeQueriedForOneself()
    {
        /** @var User $user_1 */
        $user_1 = User::factory()->create();
        $user_2 = User::factory()->create();
        $this->actingAs($user_1);
        $notification_data = $this->getSampleNotificationData($user_1, 1009);
        $user_1->notify(new SubmissionCreated($notification_data));
        $user_2->notify(new SubmissionCreated($notification_data));
        $response = $this->graphQL(
            'query GetUsers {
                userSearch {
                    data {
                        id
                        notifications (first: 10, page: 1) {
                            data {
                                data {
                                    type
                                    user {
                                        id
                                    }
                                    submission {
                                        id
                                    }
                                }
                            }
                        }
                    }
                }
            }'
        );
        $expected_data = [
            'data' => [
                'userSearch' => [
                    'data' => [
                        [
                            'id' => (string)$user_1->id,
                            'notifications' => [
                                'data' => [
                                    [
                                        'data' => [
                                            'type' => 'submission.created',
                                            'user' => [
                                                'id' => (string)$user_1->id,
                                            ],
                                            'submission' => [
                                                'id' => '1009',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'id' => (string)$user_2->id,
                            'notifications' => [
                                'data' => [ ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $response->assertJson($expected_data);
    }

    /**
     * @return void
     */
    public function testMarkNotificationReadViaGraphqlEndpoint()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);
        $notification_data = $this->getSampleNotificationData($user, 1010);
        $user->notify(new SubmissionCreated($notification_data));
        $this->graphQL(
            'mutation MarkNotificationRead ($notification_id: ID!) {
                markNotificationRead(id: $notification_id) {
                  read_at
                }
            }',
            [
                'notification_id' => $user->notifications->first()->id,
            ]
        );
        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    /**
     * @return void
     */
    public function testMarkNotificationReadViaGraphqlEndpointIsProhibitedAsAnotherUser()
    {
        /** @var User $user_1 */
        $user_1 = User::factory()->create();
        $user_2 = User::factory()->create();
        $this->actingAs($user_1);
        $notification_data = $this->getSampleNotificationData($user_2, 1011);
        $user_2->notify(new SubmissionCreated($notification_data));
        $this->graphQL(
            'mutation MarkNotificationRead ($notification_id: ID!) {
                markNotificationRead(id: $notification_id) {
                  read_at
                }
            }',
            [
                'notification_id' => $user_2->notifications->first()->id,
            ]
        );
        $this->assertEquals(1, $user_2->unreadNotifications()->count());
    }

    /**
     * @return void
     */
    public function testMarkAllNotificationsReadViaGraphqlEndpoint()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);
        $notification_data_1 = $this->getSampleNotificationData($user, 1012);
        $user->notify(new SubmissionCreated($notification_data_1));
        $notification_data_2 = $this->getSampleNotificationData($user, 1013);
        $user->notify(new SubmissionCreated($notification_data_2));
        $response = $this->graphQL(
            'mutation MarkAllNotificationsRead {
                markAllNotificationsRead
            }'
        );
        $expected_response = [
            'markAllNotificationsRead' => 2,
        ];
        $response->assertJsonPath('data', $expected_response);
        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    /**
     * @return void
     */
    public function testMarkAllNotificationsReadViaGraphqlEndpointIsProhibitedAsAnotherUser()
    {
        /** @var User $user_1 */
        $user_1 = User::factory()->create();
        $user_2 = User::factory()->create();
        $this->actingAs($user_1);
        $notification_data_1 = $this->getSampleNotificationData($user_2, 1014);
        $user_2->notify(new SubmissionCreated($notification_data_1));
        $notification_data_2 = $this->getSampleNotificationData($user_2, 1015);
        $user_2->notify(new SubmissionCreated($notification_data_2));
        $response = $this->graphQL(
            'mutation MarkAllNotificationsRead {
                markAllNotificationsRead
            }'
        );
        $expected_response = [
            'markAllNotificationsRead' => 0,
        ];
        $response->assertJsonPath('data', $expected_response);
        $this->assertEquals(2, $user_2->unreadNotifications()->count());
    }

    /**
     * @return array
     */
    public function provideAllSubmissionStates()
    {
        return [
            'INITIALLY_SUBMITTED' => [ 1 ],
            'AWAITING_RESUBMISSION' => [ 2 ],
            'RESUBMITTED' => [ 3 ],
            'AWAITING_REVIEW' => [ 4 ],
            'REJECTED' => [ 5 ],
            'ACCEPTED_AS_FINAL' => [ 6 ],
            'EXPIRED' => [ 7 ],
            'UNDER_REVIEW' => [ 8 ],
            'AWAITING_DECISION' => [ 9 ],
            'AWAITING_REVISION' => [ 10 ],
            'ARCHIVED' => [ 11 ],
            'DELETED' => [ 12 ],
        ];
    }

    /**
     * @dataProvider provideAllSubmissionStates
     * @param int $state
     * @return void
     */
    public function testThatNotificationsAreSentUponSubmissionStatusUpdates($state)
    {
        Notification::fake();
        $this->beAppAdmin();
        $submitter = User::factory()->create();
        $reviewer = User::factory()->create();
        $review_coordinator = User::factory()->create();
        $editor = User::factory()->create();
        $submission = Submission::factory()
            ->for(Publication::factory()
                ->hasAttached($editor, [], 'editors')
                ->create())
            ->hasAttached($submitter, [], 'submitters')
            ->hasAttached($reviewer, [], 'reviewers')
            ->hasAttached($review_coordinator, [], 'reviewCoordinators')
            ->create();
        $submission->status = $state;
        $submission->save();
        Notification::assertSentTo([$submitter], SubmissionStatusUpdated::class);
    }
}
