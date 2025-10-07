<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UsersProfileImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UsersProfileImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->isValid($request,[
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048'
        ] );

        $imageController = new ImagesController();
        $url = $imageController->storeImage($request->file('image'),'profile/');
        $user_id = auth()->id();

        DB::statement('CALL UpdateUserProfileImage(?,?)', [$user_id, $url]);
        
        return $this->response([
            'message' => 'Profile Image Updated Successfully',
            'profile_image_url' => $url
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(UsersProfileImage $usersProfileImage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UsersProfileImage $usersProfileImage)
    {
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UsersProfileImage $usersProfileImage)
    {
        //
    }

}
