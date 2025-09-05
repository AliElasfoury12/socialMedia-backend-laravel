<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Jobs\DeleteImagesJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    private PostController $postController;

    public function __construct () {
        $this->postController = new PostController();
    }
    public function index()
    {
        $users = User::paginate(15,['id','name','email','img'])->all();
        return response()->json(
            UserResource::collection($users),
         200);
    }

    public function show(string $userId)
    {
        $user = User::where('id', $userId)
        ->with(['follows'])
        ->withCount(['followers', 'followings'])->get();

        $user = $user->toArray()[0];
        $user['follows'] = count($user['follows']) == 0 ? false : true;  
        unset($user['email']);

        return response()->json(compact('user'));
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('update', $user);

        $valdated = $request->validate([
            'name' => 'string|min:1|max:100',
            'email' =>'email|max:100',
            'password' => "current_password|max:100"
        ]);

        $emailTaken = $user->where('email', $request->email)
        ->where('id',"!=",$user->id)->first();

        if($emailTaken){
            return response([
                'errors' =>[
                    'email' => ['Email is taken']
                ] 
            ],422);
        }

       $user->update($valdated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => new UserResource( $user )
        ],200);
    }

    public function destroy(User $user, Request $request)
    {
        Gate::authorize('delete', $user);

        $request->validate([
            'password' => "required|current_password"
        ]);

        if($user->img){
            DeleteImagesJob::dispatchSync($user->id, 'profile');
        } 
       
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
            'user' => new UserResource($user)
        ]);
    }

    public function searchUsers($search) {
        $users = User::where('name','like', '%'. $search .'%')
       ->paginate(6,['id','name','img'])->all();

        return response()->json(
            $users
        ,200);
    }

    public function userPosts ($id) {
       
       $posts = $this->postController->posts()
       ->where('user_id',$id)
       ->latest()->paginate(10);

       $lastPage = $posts->lastPage();
       $page = $posts ->currentPage();
       $posts = $posts->toArray()['data'];
       $posts = $this->postController->formatResponse($posts);

        return response()->json(
            compact(['posts', 'page', 'lastPage'])
        );
    }

    public function follow (User $user, Request $request) 
    {
        $authId = $request->user()->id;

        if($user->id == $authId){
            return response()->json(
                ['error' => "can't follow your self" ]
            ,422);
        }

        $message = '';

        $isFollowing = DB::table('followers')
        ->where('user_id', $user->id)
        ->where('follower_id', $authId)->first();

        if ($isFollowing) {
            $user->followers()->detach($authId);
            $message = 'UnFollow';
            
        }else{
            $user->followers()->attach($authId);
            $message = 'Follow';
        }

        return response()->json([
            'message' => $message,
            'follows' => $user->follows->count() ? true : false
        ]);
    }
}