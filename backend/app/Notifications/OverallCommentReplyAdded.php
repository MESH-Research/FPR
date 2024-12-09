<?php
declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OverallCommentReplyAdded extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var array
     */
    private $data;

    /**
     * Create a new notification instance.
     *
     * @param array $overall_comment
     */
    public function __construct($overall_comment)
    {
        $this->data = $overall_comment;
        $this->after_commit = true;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'submission' => [
                'id' => $this->data['submission']['id'],
                'title' => $this->data['submission']['title'],
            ],
            'commentor' => [
                'display_label' => $this->data['commentor']['display_label'],
            ],
            'type' => $this->data['type'],
        ];
    }
}
