<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommentResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\PostResource;
use App\Jobs\DeleteOldNotificationsJob;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

class NotificationsController extends Controller
{
    private function notifications() {
        $notifications = DB::table('notifications')
        ->where('notifiable_id', auth()->id());
        return $notifications;
    }

    public function index () {
        $notifications = $this->notifications()->latest()->paginate(10);

        $notificationsCount = $notifications
        ->where('seen',false)->count();

        DeleteOldNotificationsJob::dispatchAfterResponse();

        return response()->json([
           'notifications' => NotificationResource::collection($notifications),
            'notifications_count' => $notificationsCount,
        ],200
        );
    }

    public function markAsRead ($id) {
        $user = auth()->user();
        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->update(['read_at' => now()]);

        return response()->json([
            'message' => 'notifiction read Successfully'
        ]);
    }

    public function markAllAsRead () {
        $user = auth()->user();
        $user->unreadNotifications->markAsRead();

        $notifications = $this->notifications()->latest()->paginate(10);

        return response()->json([
            'message' => 'notifictions read Successfully',
            'notifications' => NotificationResource::collection($notifications),
        ]);
    }

    public function seen () {
        $this->notifications()->update(['seen' => true]);
        return response()->json([
            'message' => 'notifiction seen Successfully'
        ]);
    }

    public function notificationsPost (Post $post, $comment_id) {
        $post->load(['user:id,name,img','likes:user_id,post_id','postImgs:id,post_id,img']);
        $post->loadCount(['likes','comments']);

        if($comment_id) {
            $comment = Comment::find($comment_id);
            $comment->load(['user:id,name,img']);
        }

        return response()->json([
                'post' => new PostResource($post),
                'comment' => $comment_id ? new CommentResource($comment) : []
            ], 200);
        }
}
