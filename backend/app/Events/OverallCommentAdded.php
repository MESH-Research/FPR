<?php
declare(strict_types=1);

namespace App\Events;

use App\Models\OverallComment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OverallCommentAdded
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @var \App\Models\OverallComment $overall_comment
     */
    public $overall_comment;

    /**
     * @param \App\Models\OverallComment $overall_comment
     */
    public function __construct(OverallComment $overall_comment)
    {
        $this->overall_comment = $overall_comment;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
