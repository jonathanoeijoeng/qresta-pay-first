<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// 1. Untuk Order Sent (Dapur)
Broadcast::channel('order-sent-branch.{branchId}', function ($user, $branchId) {
    return (int) $user->branch_id === (int) $branchId;
});

// 2. Untuk Order Completed (Kasir)
Broadcast::channel('order-completed-branch.{branchId}', function ($user, $branchId) {
    return (int) $user->branch_id === (int) $branchId;
});
