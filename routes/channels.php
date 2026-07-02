<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;
use App\Models\Customer;

// Staff channel — any authenticated staff member can join
Broadcast::channel('staff', function ($user) {
    return $user instanceof User;
});

// Private staff channel — specific staff member
Broadcast::channel('staff.{id}', function ($user, int $id) {
    return $user instanceof User && $user->id === $id;
});

// Private customer channel — specific customer
Broadcast::channel('customer.{id}', function ($user, int $id) {
    return $user instanceof Customer && $user->id === $id;
});