<?php
declare(strict_types=1);

namespace App\Listeners;

use App\Events\OverallCommentReplyAdded as EventsOverallCommentReplyAdded;
use App\Notifications\OverallCommentReplyAdded;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class NotifyUsersAboutOverallCommentReply extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Handle the event.
     *
     * @param \App\Events\OverallCommentReplyAdded $event
     * @return void
     */
    public function handle(EventsOverallCommentReplyAdded $event): void
    {
        $submission = $event->overall_comment->submission;
        $submitters = $submission->submitters()->get();
        $parent_commentor = $event->overall_comment->parent->createdBy()->get();
        $commentors = $event->overall_comment->parent->commentors()->get();
        $review_coordinators = $submission->reviewCoordinators()->get();
        $notification_data = [
            'submission' => [
                'id' => $submission->id,
                'title' => $submission->title,
            ],
            'commentor' => [
                'display_label' => $event->overall_comment->createdBy->displayLabel,
            ],
            'type' => 'submission.overall_comment_reply.added',
        ];
        $recipients = $submitters
            ->merge($commentors)
            ->merge($parent_commentor)
            ->merge($review_coordinators)
            ->unique()
            ->filter(function ($user) use ($event) {
                return $user->id !== $event->overall_comment->createdBy->id;
            });

        Notification::send($recipients, new OverallCommentReplyAdded($notification_data));
    }
}
