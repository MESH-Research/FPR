<?php
declare(strict_types=1);

namespace App\Listeners;

use App\Events\InlineCommentAdded as EventsInlineCommentAdded;
use App\Notifications\InlineCommentAdded;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class NotifyUsersAboutInlineComment extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Handle the event.
     *
     * @param \App\Events\EventsInlineCommentAdded $event
     * @return void
     */
    public function handle(EventsInlineCommentAdded $event): void
    {
        $submission = $event->inline_comment->submission;
        $submitters = $submission->submitters()->get();
        $review_coordinators = $submission->reviewCoordinators()->get();
        $notification_data = [
            'submission' => [
                'id' => $submission->id,
                'title' => $submission->title,
            ],
            'commentor' => [
                'display_label' => $event->inline_comment->createdBy->displayLabel,
            ],
            'type' => 'submission.inline_comment.added',
        ];
        $recipients = $submitters
            ->merge($review_coordinators)
            ->unique()
            ->filter(function ($user) use ($event) {
                return $user->id !== $event->inline_comment->createdBy->id;
            });

        Notification::send($recipients, new InlineCommentAdded($notification_data));
    }
}
