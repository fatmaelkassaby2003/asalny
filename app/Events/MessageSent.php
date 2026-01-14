<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Support both Message model and array for testing
        $chatId = is_array($this->message) ? 1 : $this->message->chat_id;
        
        return [
            new PrivateChannel('chat.' . $chatId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        // If it's an array (test data), return it directly
        if (is_array($this->message)) {
            return $this->message;
        }
        
        // Otherwise, return Message model data
        return [
            'id' => $this->message->id,
            'chat_id' => $this->message->chat_id,
            'sender_id' => $this->message->sender_id,
            'message' => $this->message->message,
            'is_read' => $this->message->is_read,
            'created_at' => $this->message->created_at->format('Y-m-d H:i:s'),
            'sender' => [
                'id' => $this->message->sender->id,
                'name' => $this->message->sender->name,
            ],
        ];
    }
}
