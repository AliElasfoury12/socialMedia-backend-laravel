<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Jobs\DeleteImagesJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function show(string $userId)
    {
        $user = User::select(['id','name', 'profile_image_id'])
        ->where('id', $userId)
        ->with(['isAuthUserFollows','profilePic'])
        ->withCount(['followers', 'followings'])->get();

        $user = $user->toArray()[0];
        $user['is_auth_user_follows'] = count($user['is_auth_user_follows']) == 0 ? false : true;  
        unset($user->profile_image_id);

        return $this->response(['user' => $user]);
    }

    public function update(Request $request)
    {
        $auth_user = $request->user();

        $data = $this->isValid($request, [
            'name' => 'required|string|min:1|max:200',
            'email' => "required|email|unique:users,email,{$auth_user->id}|max:200",
            'password' => "required|current_password|max:200"
        ]);
        
       $auth_user->update($data);
       $auth_user = $auth_user->only(['id', 'name', 'email']);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $auth_user
        ]);
    }

    public function destroy(Request $request)
    {
        $this->isValid($request, [
            'password' => "required|current_password|max:200"
        ]);

        $user = $request->user();

        if($user->profile_image_id){
            DeleteImagesJob::dispatchSync($user->id, 'profile');
        } 
       
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
            'user' => $user->only(['id', 'name', 'email'])
        ]);
    }

    public function searchUsers(string $search) 
    {
        $users = User::where('name','like', "%$search%")
        ->with(['profilePic'])
        ->cursorPaginate(6,['id','name', 'profile_image_id']);

        return response()->json([
            'users' => $users->items(),
            'nextCursor' => $users->nextCursor()?->encode()
        ]);
    }

    public function userPosts (int $id) 
    {
       $postController = new PostController();
       $posts = $postController->posts()->where('user_id',$id)
       ->latest()->paginate(10);

       $lastPage = $posts->lastPage();
       $page = $posts ->currentPage();
       $posts = $posts->toArray()['data'];
       $posts = $postController->formatResponse($posts);

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