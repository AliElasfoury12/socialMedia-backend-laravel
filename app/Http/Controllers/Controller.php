<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class Controller
{
    public function storeImage ($img, $path) {
        $storage = Storage::disk('public');
        $imageName = Str::random(32). '.' . $img->getClientOriginalExtension();
        $storage->put( $path . $imageName, file_get_contents($img));

        return $imageName;
    }
}
