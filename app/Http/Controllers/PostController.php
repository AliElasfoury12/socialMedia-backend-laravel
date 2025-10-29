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
        $posts = $this->formatResponse($posts);
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
              
            if (strlen($post->content) > 80) 
                $post->content = substr($post->content,0,80).'...';
            
            if(property_exists($post,"is_liked_by_auth_user"))
                $post->is_liked_by_auth_user = $post->is_liked_by_auth_user ? true : false;
            
            $post->user = (object) $post->user;

            if(property_exists($post->user,"is_auth_user_follows"))
                $post->user->is_auth_user_follows = $post->user->is_auth_user_follows ? true : false;

            if($post->user->profile_pic == null) $post->user->profile_pic = ['url' => null];
            
            if(isset($post?->shared_post[0]['id'])) $post->shared_post = $post?->shared_post[0];
            
            if(isset($post?->shared_post['id'])) 
                $post->shared_post = $this->formatResponse([$post->shared_post])[0];
            else if(isset($post?->shared_post))
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
        
        if(!$content && !$images){
            throw new ValidationErrorException([
                'message' => 'you must add post content or one image' 
            ]);
        }

        $data = [
            'content' => $content,
            'user_id' => $request->user()->id
        ];

        $post = Post::create($data);

        $post->post_imgs = $this->storeImages($images,$post->id);
        
        return $this->response([
            'message' => 'post created Successfully',
            'post' => $post
        ]);
    }

    public function update(Request $request,int $post_id)
    {
        $content = $request['content'] ?? '';
        $images = $request->allFiles();

        if(!$content && !$images && !isset($request['to_delete_images'])){
            throw new ValidationErrorException([
                'message' => 'you must add post content or one image' 
            ]);
        }

        if($content) {
            $is_updated = Post::where('id',$post_id)
            ->where('user_id', $request->user()->id)
            ->update(['content' => $content]);

            if(!$is_updated) 
                throw new ValidationErrorException(['message' => 'Something Went Wrong']);
        }

        $postImages = $this->storeImages($images,$post_id);

        $is_success = false;
        if($request['to_delete_images']){
            $image_controller = new ImagesController;
            $is_success = $image_controller->deletePostImages($request);
        }

        $delete_images_message = $is_success? 'images deleted successfully': null;

        return $this->response([
            'message' => 'Post Updated successfully',
            'to_delete_images_message' => $delete_images_message,
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

    private function storeImages (array|null $images,string $post_id): array
    {
        if($images) {
            $imageController  = new ImagesController;
            $images = $imageController->storePostImages($images,$post_id);
        }
        return $images ?? [];
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
