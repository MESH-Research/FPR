<?php
declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Submission;
use App\Models\User;
use App\Notifications\SubmissionStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\TestFactory;

class SubmissionStatusUpdatesTest extends TestCase
{
    use RefreshDatabase;
    use TestFactory;

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
                'status' => Submission::INITIALLY_SUBMITTED,
                'status_name' => 'INITIALLY_SUBMITTED',
                'status_change_comment' => '',
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
            'type' => 'submission.initially_submitted',
            'action' => 'Visit Pilcrow',
            'url' => '/',
            'body' => 'A submission status has been updated.',
            'subject' => '',
        ];
    }

    /**
     * @return void
     */
    public function testSubmissionStatusUpdatedNotificationForAnIndividualUser()
    {
        $user = User::factory()->create();
        $notification_data = $this->getSampleNotificationData($user);
        $user->notify(new SubmissionStatusUpdated($notification_data));
        $this->assertEquals(1, $user->notifications->count());
        $this->assertEquals("App\Notifications\SubmissionStatusUpdated", $user->notifications->first()->type);
        $this->assertEquals($notification_data, $user->notifications->first()->data);
    }

    /**
     * @return void
     */
    public function testSubmissionStatusUpdatedNotificationForMultipleUsers()
    {
        $users = User::factory()->count(4)->create();
        /** @var User $user  */
        $user = $users->first();

        $notification_data = $this->getSampleNotificationData($user);
        Notification::send($users, new SubmissionStatusUpdated($notification_data));
        $users->map(function ($user) use ($notification_data) {
            $this->assertEquals(1, $user->notifications->count());
            $this->assertEquals("App\Notifications\SubmissionStatusUpdated", $user->notifications->first()->type);
            $this->assertEquals($notification_data, $user->notifications->first()->data);
        });
    }

    /**
     * @return void
     */
    public function testMultipleSubmissionStatusUpdatedNotificationsForAnIndividualUser()
    {
        $user = User::factory()->create();
        $notification_data_1 = $this->getSampleNotificationData($user, 1002);
        $notification_data_2 = $this->getSampleNotificationData($user, 1003);
        $user->notify(new SubmissionStatusUpdated($notification_data_1));
        $user->notify(new SubmissionStatusUpdated($notification_data_2));
        $this->assertEquals(2, $user->notifications->count());
        $this->assertEquals("App\Notifications\SubmissionStatusUpdated", $user->notifications->first()->type);
        $this->assertEquals("App\Notifications\SubmissionStatusUpdated", $user->notifications->last()->type);
        $notification_1 = $user->notifications
            ->where('notifiable_type', "App\Models\User")
            ->where('notifiable_id', $user->id)
            ->where('data', $notification_data_1)
            ->first();
        $this->assertEquals($notification_data_1, $notification_1->data);
        $notification_2 = $user->notifications
            ->where('notifiable_type', "App\Models\User")
            ->where('notifiable_id', $user->id)
            ->where('data', $notification_data_2)
            ->first();
        $this->assertEquals($notification_data_2, $notification_2->data);
    }

    /**
     * @return void
     */
    public function testMultipleSubmissionStatusUpdatedNotificationsForMultipleUsers()
    {
        $users = User::factory()->count(4)->create();

        /** @var User $user  */
        $user = $users->first();

        $notification_data_1 = $this->getSampleNotificationData($user, 1004);
        $notification_data_2 = $this->getSampleNotificationData($user, 1005);
        Notification::send($users, new SubmissionStatusUpdated($notification_data_1));
        Notification::send($users, new SubmissionStatusUpdated($notification_data_2));
        $users->map(function ($user) use ($notification_data_1, $notification_data_2) {
            $this->assertEquals(2, $user->notifications->count());
            $this->assertEquals("App\Notifications\SubmissionStatusUpdated", $user->notifications->first()->type);
            $this->assertEquals("App\Notifications\SubmissionStatusUpdated", $user->notifications->last()->type);
            $notification_1 = $user->notifications
                ->where('notifiable_type', "App\Models\User")
                ->where('notifiable_id', $user->id)
                ->where('data', $notification_data_1)
                ->first();
            $this->assertEquals($notification_data_1, $notification_1->data);
            $notification_2 = $user->notifications
                ->where('notifiable_type', "App\Models\User")
                ->where('notifiable_id', $user->id)
                ->where('data', $notification_data_2)
                ->first();
            $this->assertEquals($notification_data_2, $notification_2->data);
        });
    }

    /**
     * @return void
     */
    public function testMarkingASubmissionStatusUpdatedNotificationAsReadForAnIndividualUser()
    {
        $user = User::factory()->create();
        $notification_data = $this->getSampleNotificationData($user, 1006);
        $user->notify(new SubmissionStatusUpdated($notification_data));
        $notification = $user->notifications->first();
        $notification->markAsRead();
        $this->assertEquals(0, $user->unreadNotifications->count());
    }

    /**
     * @return void
     */
    public function testMarkingMultipleSubmissionStatusUpdatedNotificationsAsReadForMultipleUsers()
    {
        $users = User::factory()->count(4)->create();

        /** @var User $user  */
        $user = $users->first();

        $notification_data_1 = $this->getSampleNotificationData($user, 1007);
        $notification_data_2 = $this->getSampleNotificationData($user, 1008);
        Notification::send($users, new SubmissionStatusUpdated($notification_data_1));
        Notification::send($users, new SubmissionStatusUpdated($notification_data_2));

        $users->map(function ($user) {
            $user->notifications->map(function ($notification) {
                $notification->markAsRead();
            });
            $this->assertEquals(0, $user->unreadNotifications->count());
        });
    }
}
