<?php
declare(strict_types=1);

namespace App\Listeners;

use App\Events\InlineCommentReplyAdded as EventsInlineCommentReplyAdded;
use App\Notifications\InlineCommentReplyAdded;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class NotifyUsersAboutInlineCommentReply extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Handle the event.
     *
     * @param \App\Events\InlineCommentReplyAdded $event
     * @return void
     */
    public function handle(EventsInlineCommentReplyAdded $event): void
    {
        $submission = $event->inline_comment->submission;
        $submitters = $submission->submitters()->get();
        $parent_commentor = $event->inline_comment->parent->createdBy()->get();
        $commentors = $event->inline_comment->parent->commentors()->get();
        $review_coordinators = $submission->reviewCoordinators()->get();
        $notification_data = [
            'submission' => [
                'id' => $submission->id,
                'title' => $submission->title,
            ],
            'commentor' => [
                'display_label' => $event->inline_comment->createdBy->displayLabel,
            ],
            'type' => 'submission.inline_comment_reply.added',
        ];
        $recipients = $submitters
            ->merge($commentors)
            ->merge($parent_commentor)
            ->merge($review_coordinators)
            ->unique()
            ->filter(function ($user) use ($event) {
                return $user->id !== $event->inline_comment->createdBy->id;
            });

        Notification::send($recipients, new InlineCommentReplyAdded($notification_data));
    }
}
