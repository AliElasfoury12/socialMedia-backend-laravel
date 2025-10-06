<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Post;
use App\Models\PostImg;
use App\Models\ProfilePic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImagesController extends Controller
{
    public function storePostImages (array $images, int $postId): array 
    {
        foreach ($images as &$image) {
            $imageName = $this->storeImage($image,'posts/');
            $image = PostImg::create([
                'post_id' => $postId,
                'img' => $imageName
            ]);
            $image = ['id' => $image->id, 'img' => $image->img];
        }  
        return array_values($images);  
    }

    public function deletePostImages (Request $request) 
    {
        $this->isValid($request,['images' => "required|array"]);
        
        $imagesIds = [];

        foreach ($request->images as $image) {
            Storage::disk('public')->delete('posts/'.$image['img']);
            $imagesIds[] = $image['id'];
        }

        PostImg::whereIn('id', $imagesIds)->delete();

        return $this->response([
            'message' => 'Images Deleted successfully',
            'images' => $request->images
        ]);
    }

    public function storeImage ($img, string $path): string 
    {
        $storage = Storage::disk('public');
        $imageName = Str::random(32). '.' . $img->getClientOriginalExtension();
        $storage->put($path . $imageName, file_get_contents($img));

        return $imageName;
    }
}
