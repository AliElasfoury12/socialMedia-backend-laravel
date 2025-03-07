<?php

namespace App\Http\Controllers;

use App\Events\PostEvent;
use App\Events\PrivateEvent;
use App\Events\testingEvent;
use App\Http\Resources\PostResource;
use App\Jobs\DeleteImagesJob;
use App\Models\Post;
use App\Models\PostImg;
use Illuminate\Http\Request;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public static function posts () 
    {
        $posts = Post::with([
            'user.follows:id',
            'isLiked',
            'postImgs',
            'sharedPost'
            ])->withCount(['likes','comments']);
    
        return $posts;
    }

    //index
    public  function index( )
    {
        $posts = $this->posts()
        //->orderBy('likes_count','DESC')
        //->orderBy('comments_count','DESC') 
        ->latest()->paginate(10);   

       // $posts = PostResource::collection($posts);

        //$lastPage = $posts->lastPage();

    
        return response()->json( ['posts' => $posts->items()] ,200);
    
       // broadcast(new PostEvent($posts))->toOthers();
       //broadcast(new testingEvent('message', 111)) ;
       //broadcast(new PrivateEvent($posts))->toOthers();     
    }

    //show
    public function show(Post $post)
    {
        return response()->json($post->post, 200);
    }

    //store
    public function store(Request $request)
    {
        $data = [
            'post' => $request->post,
            'user_id' => $request->user()->id
        ];
       
        $post = Post::create($data);

        if ($request->hasFile('img0')) {
            $imgs = collect($request)->filter(
                fn($value, $key) => str_contains($key, 'img')
            );

            $this->storePostImages($imgs, $post->id);
        }

        $post->load(['user:id,name,img','postImgs:id,post_id,img']);

        return response()->json([
            'message' => 'post created Successfully',
            'post' => new PostResource($post),
        ], 200);

    }

    private function storePostImages ($imgs, $postId) {
        foreach ($imgs as $img) {
            $imageName = $this->storeImage($img,'posts/');
            PostImg::create([
                'post_id' => $postId,
                'img' => $imageName
            ]);
        }    
    }

    //update
    public function update(Request $request, Post $post)
    {
        Gate::authorize('update', $post);

        $data = [
            'post' => $request->post,
            'user_id' => $request->user()->id
        ];

        $post->update($data);

        if ($request->hasFile('img0')) {
            $imgs = collect($request)->filter(fn($value, $key) => str_contains($key, 'img'));
            $this->storePostImages($imgs, $post->id);
        }

        $post->load( ['postImgs:post_id,img']);

        return response([
            'message' => 'Post Updated successfully',
            'post' => [
                'post' => $post->post,
                'post_imgs' => $post->postImgs ?? []
            ],
        ], 200);
    }

    //destroy
    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);

        DeleteImagesJob::dispatchSync($post->id, 'posts');

        $post->delete();

        return response()->json([
            'message' => 'post deleted successfully',
        ]);
    }

    //search in posts
    public function searchPosts($search) {
        $posts = $this->posts()
        ->where('post','like', '%'. $search .'%')
        ->latest()->paginate(10,['id','post','created_at']);

        return response()->json(
            PostResource::collection($posts)
        ,200);
    }

    //delete image
    public function deleteImg (PostImg $postImg) {

        $post = Post::find($postImg->post_id);
      
        Gate::authorize('delete', $post );

        Storage::disk('public')->delete('posts/'.$postImg->img);

        $postImg->delete();

        return response()->json([
            'message' => 'Image Deleted successfully',
        ],200);

    }

    //Share Post
    public function sharePost (Request $request) 
    {
        $data = [
            'post' => $request->post,
            'user_id' => $request->user()->id
        ];
       
        $post = Post::create($data);

        $post->sharedPost()->attach($request->shared_post_id);

        $post->load(['user','sharedPost.user']);

        return response()->json([
            'message' => 'Post Shared Successfully',
            'post' => $post,
        ], 200);
    }
}
