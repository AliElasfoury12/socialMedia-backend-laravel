<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Jobs\DeleteImagesJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    private PostController $postController;

    public function __construct () {
        $this->postController = new PostController();
    }
    public function index()
    {
        $users = User::paginate(15,['id','name','email']);
        return response()->json(UserResource::collection($users));
    }

    public function show(string $userId)
    {
        $user = User::select(['id','name', 'profile_image_id'])->where('id', $userId)
        ->with(['isAuthUserFollows','profilePic:id,url'])
        ->withCount(['followers', 'followings'])->get();

        $user = $user->toArray()[0];
        $user['is_auth_user_follows'] = count($user['is_auth_user_follows']) == 0 ? false : true;  
        unset($user->profile_image_id);

        return response()->json(compact('user'));
    }

    public function update(Request $request)
    {
        $data = $request->all();
        $auth_user = $request->user();

        $validator = Validator::make($data, [
            'name' => 'required|string|min:1|max:200',
            'email' => "required|email|unique:users,email,{$auth_user->id}|max:200",
            'password' => "required|current_password|max:200"
        ]);

        if($validator->fails())
            return response()->json(['errors' => $validator->messages()], 422);
        
       
       $auth_user->update($data);
       $auth_user = $auth_user->only(['id', 'name', 'email']);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $auth_user
        ]);
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
       ->paginate(6,['id','name','img']);

        return response()->json(
            $users
        ,200);
    }

    public function userPosts (int $id) {
       
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