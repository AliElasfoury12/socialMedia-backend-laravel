<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LikeNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $title = '';
    public $post = [];

    public $user = [];
    public function __construct($title, $post, $user)
    {
        $this->title = $title;
        $this->post = $post;
        $this->user = $user;
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $notification = [
            'id' => $notifiable->id,
            'title' => $this->title,
            'post' => $this->post,
            'user' => $this->user,
            'created_at' => now(),
        ];
        return new BroadcastMessage($notification);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['broadcast', 'database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        
        return [
            'title' => $this->title,
            'post' => $this->post,
            'user' => $this->user,
            'created_at' => now()
        ];
        
    }
}
