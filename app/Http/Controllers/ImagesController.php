<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Post;
use App\Models\PostImg;
use App\Models\ProfilePic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ImagesController extends Controller
{
    public function storePostImages ($images, $postId) 
    {
        foreach ($images as $image) {
            $imageName = $this->storeImage($image,'posts/');
            PostImg::create([
                'post_id' => $postId,
                'img' => $imageName
            ]);
        }    
    }

    public function deletePostImages (Request $request, Post $post) 
    {
        if($post->user_id != $request->user()->id) {
            return response()->json([
                'error' => 'unauthorized',
            ],401);
        }

        $validator = Validator::make($request->all(), ['images' => "required|array"]);
        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors(),
            ],422);
        }
        
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

    public function storeImage ($img, string $path): string 
    {
        $storage = Storage::disk('public');
        $imageName = Str::random(32). '.' . $img->getClientOriginalExtension();
        $storage->put($path . $imageName, file_get_contents($img));

        return $imageName;
    }

    public function changrProfilePic (Request $request, User $user) {
        $valdated = $request->validate([
            'img' => 'required|image'
        ]);

        $imageName = $this->storeImage($request->img, 'profile/');
        $valdated['img'] = $imageName ;
        
        $user->update($valdated);

        ProfilePic::create([
            'user_id' => $user->id,
            'img' => $imageName
        ]);

        return response()->json([
            'message' => 'image updated successfully',
            'user' => new UserResource($user)
        ],200);
    }

}
