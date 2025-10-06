<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class NotificationsController extends Controller
{
    private function notifications() 
    {
        $notifications = DB::table('notifications')
        ->where('notifiable_id', auth()->id());
        return $notifications;
    }

    public function index () 
    {
        $notifications = $this->notifications()->latest()
        ->cursorPaginate(5, ['id', 'data', 'read_at','created_at']);

        $next_cursor = $notifications->nextCursor()?->encode();
        $notifications = $this->format_notifications($notifications);

        return $this->response([
            'notifications' => $notifications,
            'nextCursor' => $next_cursor
        ]);
    }

    private function format_notifications ($notifications) 
    {
        $notifications = $notifications->items();

        foreach ($notifications as &$notification) {
            $notification->data = json_decode($notification->data);
            foreach ($notification->data as $key => $value) {
                $notification->$key = $value;
            }
            unset($notification->data);
        }

        return $notifications;
    }

    public function getNotificationsCount ()  
    {
        $notificationsCount = $this->notifications()
        ->where('seen',false)->count();

        return $this->response([
            'notifications_count' => $notificationsCount,
        ]);
    }

    public function markAsRead (string $id) 
    {
        $this->notifications()->where('id', $id)
        ->update(['read_at' => now()]);

        return $this->response([
            'message' => 'notifiction read Successfully'
        ]);
    }

    public function markAllAsRead () 
    {
        $this->notifications()->update(['read_at' => now()]);

        return $this->response([
            'message' => 'notifictions read Successfully',
        ]);
    }

    public function seen () 
    {
        $this->notifications()->update(['seen' => true]);
        return $this->response([
            'message' => 'notifiction seen Successfully'
        ]);
    }

    public function notificationsPost (int $postId, int $commentId) 
    {
        $postController = new PostController;

        $post = $postController->posts()->where('id', $postId); 

        if($commentId) $post = $post->with(['comments' => fn($query) => $query->where('id', $commentId)]);
        
        $post = $post->first();

        $post = $postController->formatResponse([$post->toArray()])[0];

        if($commentId) {
            $post->comment = $post->comments[0];
            unset($post->comments);
        }

        return $this->response([
            'post' => $post
        ]);
    }
}
