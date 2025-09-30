<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationErrorException;
use App\Http\Resources\PostResource;
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
        // $result = $this->posts()->orderByDesc('id')->cursorPaginate(10);  
        // $result = $result->toArray();
        // $posts = $result['data'];
        // $nextCursor = $result['next_cursor']; 
        // $posts = $this->formatResponse($posts);

        $postsRepository = new PostRepository;
        $posts = $postsRepository->getPosts(99, 10, $request->cursor);
        $nextCursor = $postsRepository->get_next_cursor();

        return response()->json([
            'posts' => $posts,
            'nextCursor' => $nextCursor
        ]); 
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
                $post['shared_post'] = null;
    
        }
        return $posts;
    }

    public function show(Post $post)
    {
        return response()->json($post->content);
    }

    public function store(Request $request)
    {
        $content = $request['content'] ?? '';
        $images = $request->allFiles();

        $data = $this->valdaitePost($request,$content,$images);

        $post = Post::create($data);

        $this->storeImages($images,$post->id);
       
        return response()->json([
            'message' => 'post created Successfully',
            'post' => $post
        ]);
    }

    public function update(Request $request,int $post_id)
    {
        $content = $request['content'] ?? '';
        $images = $request->allFiles();

        $data = $this->valdaitePost($request,$content,$images);

        Post::where('id',$post_id)->update($data);

        $postImages = $this->storeImages($images,$post_id);

        return response()->json([
            'message' => 'Post Updated successfully',
            'post' => [
                'content' => $content,
                'post_imgs' => $postImages
            ]
        ]);
    }

    public function destroy(Request $request,int $post_id)
    {
        $isSucces = Post::where('id', $post_id)
        ->where('user_id', $request->jwt_user->id)
        ->delete();

        if (!$isSucces) 
            throw new ValidationErrorException(['message' => 'Something Went Wrong']);

        DeleteImagesJob::dispatchAfterResponse($post_id, 'posts');

        return response()->json([
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
            'user_id' => $request->jwt_user->id
        ];
    }


    public function sharePost (Request $request, int $sharedPostId) 
    {
        $content = $request['content'] ?? '';

        $data = [
            'content' => $content,
            'user_id' => $request->jwt_user->id
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
        $auth = $request->jwt_user;

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
