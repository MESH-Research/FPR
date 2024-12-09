<?php
declare(strict_types=1);

namespace App\Events;

use App\Models\InlineComment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InlineCommentReplyAdded
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @var \App\Models\InlineComment $inline_comment
     */
    public $inline_comment;

    /**
     * @param \App\Models\InlineComment $inline_comment
     */
    public function __construct(InlineComment $inline_comment)
    {
        $this->inline_comment = $inline_comment;
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
