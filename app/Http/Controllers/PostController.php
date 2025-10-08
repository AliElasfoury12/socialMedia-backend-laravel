<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationErrorException;
use App\Jobs\DeleteImagesJob;
use App\Jobs\SendLikeNotifiction;
use App\Models\Post;
use App\Repositories\PostRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    public function posts () 
    {
        $posts = Post::select(['id','user_id','content','created_at'])
        ->with([
            'user' ,
            'isLikedByAuthUser',
            'postImgs',
            'sharedPost'
        ])->withCount(['likes','comments']);
    
        return $posts;
    }

    public function index(Request $request)
    {
        // $posts = $this->posts()->orderByDesc('id')->cursorPaginate(10);  
        // $nextCursor = $posts->nextCursor()?->encode(); 
        // $posts = $this->formatResponse($posts->toArray()['data']);

        $postsRepository = new PostRepository;
        $posts = $postsRepository->getPosts($request->user()->id,10,$request->cursor);
        $nextCursor = $postsRepository->get_next_cursor();

        return $this->response([
            'posts' => $posts,
            'nextCursor' => $nextCursor
        ]); 
    }

    public function formatResponse (array $posts): array
    {
        foreach ($posts as &$post) {
            $post = (object) $post;

            if (strlen($post->content) > 80 ) 
                $post->content = substr($post->content,0,80).'...';
            
            if(isset($post->is_liked_by_auth_user))
                $post->is_liked_by_auth_user = $post->is_liked_by_auth_user ? true : false;
            
            $post->user = (object) $post->user;
            $post->user->is_auth_user_follows = $post->user->is_auth_user_follows ? true : false;
            
            if(isset($post->shared_post) && $post->shared_post) 
                $post->shared_post = $this->formatResponse($post->shared_post)[0];
            else if(isset($post->shared_post))
                $post->shared_post = null;    
        }
        return $posts;
    }

    public function show(Post $post)
    {
        return $this->response(['content' => $post->content]);
    }

    public function store(Request $request)
    {
        $content = $request['content'] ?? '';
        $images = $request->allFiles();

        $data = $this->valdaitePost($request,$content,$images);

        $post = Post::create($data);

        $post->post_imgs =  $this->storeImages($images,$post->id);
        
        return $this->response([
            'message' => 'post created Successfully',
            'post' => $post
        ]);
    }

    public function update(Request $request,int $post_id)
    {
        $content = $request['content'] ?? '';
        $images = $request->allFiles();

        $data = $this->valdaitePost($request,$content,$images);

        $is_updated = Post::where('id',$post_id)
        ->where('user_id', $data['user_id'])
        ->update(['content' => $data['content']]);

        if(!$is_updated) 
            throw new ValidationErrorException(['message' => 'Something Went Wrong']);

        $postImages = $this->storeImages($images,$post_id);

        return $this->response([
            'message' => 'Post Updated successfully',
            'post' => [
                'content' => $content,
                'post_imgs' => $postImages
            ]
        ]);
    }

    public function destroy(Request $request,int $post_id)
    {
        $is_deleted = Post::where('id', $post_id)
        ->where('user_id', $request->user()->id)
        ->delete();

        if (!$is_deleted) 
            throw new ValidationErrorException(['message' => 'Something Went Wrong']);

        DeleteImagesJob::dispatchAfterResponse($post_id, 'posts');

        return $this->response([
            'message' => 'post deleted successfully'
        ]);
    }

    private function storeImages (array $images,string $post_id): array
    {
        if($images) {
            $imageController  = new ImagesController;
            $images = $imageController->storePostImages($images,$post_id);
        }
        return $images;
    }

    private function valdaitePost (Request $request, string $content, array $images) 
    {
        if(!$content && !$images){
            throw new ValidationErrorException([
                'message' => 'you must add post content or one image' 
            ]);
        }

        return [
            'content' => $content,
            'user_id' => $request->user()->id
        ];

    }

    public function sharePost (Request $request, int $sharedPostId) 
    {
        $content = $request['content'] ?? '';

        $data = [
            'content' => $content,
            'user_id' => $request->user()->id
        ];
       
        $post = Post::create($data);

        $post->sharedPost()->attach($sharedPostId);

        return response()->json([
            'message' => 'Post Shared Successfully',
            'post' => $post,
        ]);
    }

    public function like(Request $request, Post $post) 
    {
        $message = '';
        $auth = $request->user();

        $exists = DB::table('likes')
        ->where('user_id', $auth->id)
        ->where('post_id',$post->id)->first();

        if ($exists) {
            $post->likes()->detach($auth->id);
            $message = 'Unliked';
        }else{
            $post->likes()->attach($auth->id);
            $message = 'Liked';
            SendLikeNotifiction::dispatchAfterResponse($post->id, $auth);
        }

        $likesCount = DB::table('likes')->where('post_id',$post->id)->count();
        
        return response()->json([
            'message' => $message,
            'likesCount' => $likesCount ?? 0
        ]);
    }
    
    public function searchPosts(string $search) 
    {
        $posts = $this->posts()
        ->where('content','like', "%$search%")
        ->latest()->cursorPaginate(5,['id','content','created_at']);

        $nextCursor = $posts->nextCursor()?->encode();
        $posts = $this->formatResponse($posts->items());

        return response()->json([
            'posts' => $posts,
            'nextCursor' => $nextCursor
        ]);
    }
}
