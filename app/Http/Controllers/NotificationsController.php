<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

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

        return response()->json([
            'notifications' => $notifications->items(),
            'nextCursor' => $notifications->nextCursor()?->encode() ?? null
        ]);
    }

    public function getNotificationsCount ()  
    {
        $notificationsCount = $this->notifications()
        ->where('seen',false)->count();

        return response()->json([
            'notifications_count' => $notificationsCount,
        ]);
    }

    public function markAsRead (string $id) 
    {
        $this->notifications()->where('id', $id)
        ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'notifiction read Successfully'
        ]);
    }

    public function markAllAsRead (Request $request) 
    {
        $this->notifications()->update(['read_at' => now()]);

        return response()->json([
            'message' => 'notifictions read Successfully',
        ]);
    }

    public function seen () 
    {
        $this->notifications()->update(['seen' => true]);
        return response()->json([
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
            $post['comment'] = $post['comments'][0];
            unset($post['comments']);
        }

        return response()->json([
            'post' => $post
        ]);
    }
}
