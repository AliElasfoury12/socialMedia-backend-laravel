<?php

namespace App\Jobs;

use App\Models\PostImg;
use App\Models\UsersProfileImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class DeleteImagesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public $id;
    public $type;
    private array $images;
    public function __construct($id, $type, array $images)
    {
        $this->id = $id;  
        $this->type = $type; 
        $this->images = $images;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if($this->type === 'posts'){
            $imgs = PostImg::where('post_id', $this->id)->pluck('img');

            if($imgs){
                foreach ($imgs as $img) {
                    Storage::disk('public')->delete("posts/$img");
                }
            }

        }
        
        if($this->type === 'profile'){
            foreach ($this->images as $image) {
                Storage::disk('public')->delete("profile/$image");
            }
        }

    }
}
