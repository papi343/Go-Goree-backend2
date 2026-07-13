<?php

use Illuminate\Support\Facades\Broadcast;

// Canal privé par utilisateur (les IDs sont des UUID → comparaison en chaîne).
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});
