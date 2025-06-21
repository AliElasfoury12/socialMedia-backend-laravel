<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Jobs\DeleteImagesJob;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    private ImagesController $imagesController;

    public function __construct() {
        $this->imagesController = new ImagesController;
    }

    public static function posts () 
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
        $posts = $this->posts()->latest()->paginate(10);   
        $posts = $posts->toArray()['data'];
        $posts = $this->formatResponse($posts);
        return response()->json(compact('posts')); 
    }

    private function formatResponse (array $posts) 
    {
        foreach ($posts as &$post) {
            $post['is_liked_by_auth_user'] = $post['is_liked_by_auth_user'] ? true : false;
            $post['user']['isAuthFollows'] = $post['user']['isAuthFollows'] ? true : false;
            $post['shared_post'] = $post['shared_post'] ? $post['shared_post'][0] : [];
        }
        return $posts;
    }

    public function show(Post $post)
    {
        return response()->json($post->content);
    }

    public function store(Request $request)
    {
        $data = [
            'content' => $request->content,
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
            'content' => $request->content,
            'user_id' => $request->user()->id
        ];

        $post->update($data);

        $this->storeImages($request, $post);

        $post->load(['postImgs']);

        return response()->json([
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

    public function sharePost (Request $request) 
    {
        $sharedPostId = $request->shared_post_id;
        if(!is_numeric($sharedPostId)){
            return response()->json([
                'message' => 'Post Not Found',
            ], 404);
        }

        $isPostExsists = Post::find($sharedPostId);
        if(!$isPostExsists){
            return response()->json([
                'message' => 'Post Not Found',
            ], 404);
        }

        $data = [
            'content' => $request->content,
            'user_id' => $request->user()->id
        ];
       
        $post = Post::create($data);

        $post->sharedPost()->attach($sharedPostId);

        return response()->json([
            'message' => 'Post Shared Successfully',
            'post' => $post,
        ], 200);
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
