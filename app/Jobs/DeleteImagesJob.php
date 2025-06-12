<?php

namespace App\Jobs;

use App\Models\PostImg;
use App\Models\ProfilePic;
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
    public function __construct($id, $type)
    {
      $this->id = $id;  
      $this->type = $type; 
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

        }elseif($this->type === 'profile'){
            $imgs = ProfilePic::where('user_id', $this->id)->pluck('img');

            foreach ($imgs as $img) {
                Storage::disk('public')->delete("profile/$img");
            }
        }

    }
}
