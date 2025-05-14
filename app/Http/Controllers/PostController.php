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
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public static function posts () 
    {
        $posts = Post::select(['id','user_id','content','created_at'])
        ->with([
            'user' ,
            'isLiked',
            'postImgs',
            'sharedPost'
        ])->withCount(['likes','comments']);
    
        return $posts;
    }

    public function index()
    {
        $posts = $this->posts()
        ->latest()->paginate(10);   

        $posts = PostResource::collection($posts);
        return response()->json( compact('posts') ,200);  
    }

    public function show(Post $post)
    {
        return response()->json($post->post, 200);
    }

    public function store(Request $request)
    {
        $data = [
            'content' => $request->content,
            'user_id' => $request->user()->id
        ];
       
        $post = Post::create($data);

        $images = $request->allFiles();
        if($images) $this->storePostImages($images, $post->id);
       
        $post->load(['user','postImgs']);
        unset($post->updated_at);

        return response()->json([
            'message' => 'post created Successfully',
            'post' => $post
        ], 200);

    }

    private function storePostImages ($images, $postId) 
    {
        foreach ($images as $image) {
            $imageName = $this->storeImage($image,'posts/');
            PostImg::create([
                'post_id' => $postId,
                'img' => $imageName
            ]);
        }    
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $data = [
            'content' => $request->content,
            'user_id' => $request->user()->id
        ];

        $post->update($data);

        $images = $request->allFiles();
        if($images) $this->storePostImages($images, $post->id);

        $post->load(['postImgs']);

        return response([
            'message' => 'Post Updated successfully',
            'post' => [
                'content' => $post->content,
                'post_imgs' => $post->postImgs ?? []
            ],
        ], 200);
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

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

    public function deleteImages (Request $request, Post $post) 
    {
        $validator = Validator::make($request->all(), ['images' => "required|array"]);
        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors(),
            ],422);
        }
        
        $this->authorize('delete', $post);

        $imagesIds = [];

        foreach ($request->images as $image) {
            Storage::disk('public')->delete('posts/'.$image['img']);
            $imagesIds[] = $image['id'];
        }

        PostImg::whereIn('id', $imagesIds)->delete();

        return response()->json([
            'message' => 'Images Deleted successfully',
            'images' => $request->images
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
