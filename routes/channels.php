<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel("Post", function ($user) {
    return $user;
});

Broadcast::channel('notifications.{id}', function ($user, $id) 
{
    return (int) $user->id === (int) $id;
});

Broadcast::channel('like', fn ($user) => $user);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});


Broadcast::channel('testchannel.user.{id}', function ($user, $id) {
    return  $user->id == $id;
});

Broadcast::channel('privateTestchannel', function ($user) {
    return $user;
}); 