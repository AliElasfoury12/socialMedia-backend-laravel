<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Jobs\DeleteImagesJob;
use App\Models\ProfilePic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{

    private ImagesController $imagesController;

    public function __construct() {
        $this->imagesController = new ImagesController;
    }

    public function index()
    {
        $users = User::paginate(15,['id','name','email','img'])->all();
        return response()->json(
            UserResource::collection($users),
         200);
    }

    public function show(User $user)
    {
        $user->load(['follows'])->loadCount(['followers', 'followings']);
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'img' => $user->img,
            'followers_count' => $user->followers_count,
            'followings_count' => $user->followings_count,
            'follows' => count($user->follows)
        ],200);
    }

    public function update(Request $request,User $user)
    {
        Gate::authorize('update', $user);

        $valdated = $request->validate([
            'password' => "required|current_password",
            'name' => 'required|string|min:3|max:50',
            'email' =>'required|email',
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
       
       $posts = PostController::posts()
       ->where('user_id',$id)
       ->latest()->paginate(10);

       $lastPage = $posts->lastPage();
       $page = $posts ->currentPage();
       $posts = PostResource::collection($posts);

        return response()->json(
            compact(['posts', 'page', 'lastPage'])
        ,200);
    }

    public function changrProfilePic (Request $request, User $user) {
        $valdated = $request->validate([
            'img' => 'required|image'
        ]);

        $imageName = $this->imagesController->storeImage($request->img, 'profile/');
        $valdated['img'] = $imageName ;
        
        $user->update($valdated);

        ProfilePic::create([
            'user_id' => $user->id,
            'img' => $imageName
        ]);

        return response()->json([
            'message' => 'image updated successfully',
            'user' => new UserResource( $user)
        ],200);
    }
}