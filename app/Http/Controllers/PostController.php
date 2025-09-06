<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Jobs\DeleteImagesJob;
use App\Jobs\SendLikeNotifiction;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use stdClass;

class PostController extends Controller
{
    private ImagesController $imagesController;

    public function __construct() {
        $this->imagesController = new ImagesController;
    }

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

    public function index()
    {
        $result = $this->posts()->latest()->cursorPaginate(10);  
        $result = $result->toArray();
        $posts = $result['data'];
        $nextCursor = $result['next_cursor']; 
        $posts = $this->formatResponse($posts);
        return response()->json(compact('posts', 'nextCursor')); 
    }

    public function formatResponse (array $posts): array 
    {
        foreach ($posts as &$post) {
            if (strlen($post['content']) > 80 ) 
                $post['content'] = substr($post['content'],0,80).'...';
            
            $post['is_liked_by_auth_user'] = $post['is_liked_by_auth_user'] ? true : false;
            $post['user']['is_auth_user_follows'] = $post['user']['is_auth_user_follows'] ? true : false;
            
            if($post['shared_post']) {
                $post['shared_post'] = $post['shared_post'][0];
                $post['shared_post']['user']['is_auth_user_follows'] = $post['shared_post']['user']['is_auth_user_follows'] ? true : false;
            }else 
                $post['shared_post'] = new stdClass;
    
        }
        return $posts;
    }

   
    public function show(Post $post)
    {
        return response()->json($post->content);
    }

    public function store(Request $request)
    {
        if(!$request['content'] && !$request->allFiles() ){
            return response()->json([
                'message' => 'you must add post content or one image' 
            ], 422);
        }

        $data = [
            'content' => $request['content'] ?? '',
            'user_id' => $request->user()->id
        ];

        $post = Post::create($data);
        $post->post_imgs = $this->storeImages($request, $post);
       
        return response()->json([
            'message' => 'post created Successfully',
            'post' => $post
        ]);
    }

    private function storeImages (Request $request,Post $post): array
    {
        $images = $request->allFiles();
        if($images) $images = $this->imagesController->storePostImages($images, $post->id);
        return $images;
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $data = [
            'content' => $request['content'] ?? '',
            'user_id' => $request->user()->id
        ];

        $post->update($data);
        $postImages = $this->storeImages($request, $post);

        return response()->json([
            'message' => 'Post Updated successfully',
            'post' => [
                'content' => $post->content,
                'post_imgs' => $postImages
            ]
        ]);
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        DeleteImagesJob::dispatchSync($post->id, 'posts');

        $post->delete();

        return response()->json([
            'message' => 'post deleted successfully'
        ]);
    }

    public function sharePost (Request $request, Post $post) 
    {
        $sharedPostId = $post->id;

        $data = [
            'content' => $request['content'] ?? '',
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
        ->where('post','like', "%$search%")
        ->latest()->paginate(10,['id','post','created_at']);

        return response()->json(
            PostResource::collection($posts)
        ,200);
    }
}
