<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});


Broadcast::channel('test-channel', function () {
    return true;  // Cualquiera puede escuchar este canal público
});

Broadcast::channel('admin.notifications', function ($user) {
    return $user->isAdmin();  // ✅ usa la relación
});

// Canal privado del case manager — solo el propio case manager puede suscribirse
Broadcast::channel('case-manager.{caseManagerId}', function ($user, $caseManagerId) {
    return (int) $user->id === (int) $caseManagerId;
});
