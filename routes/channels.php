<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('employee.{id}', function ($user, $id) {
    \Log::info('=== CHANNEL AUTH CALLBACK ===', [
        'user_class' => get_class($user),
        'user_id'    => (string) $user->id,
        'param_id'   => (string) $id,
        'match'      => (string) $user->id === (string) $id,
    ]);

    return true; // ✅ sementara return true dulu untuk test
});