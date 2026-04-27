<?php

use App\Models\ActivityLog;
use App\Models\Notification;


function logActivity($action, $model, $modelId, $desc)
{
    try {
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'description' => $desc,
        ]);
    } catch (\Exception $e) {
        // biar gak crash
    }
}

function sendNotification($userId, $title, $message)
{
    Notification::create([
        'user_id' => $userId,
        'title' => $title,
        'message' => $message,
        'is_read' => false
    ]);
}

function calculateTaskProgress($tasks)
{
    $total = $tasks->count();
    $done = $tasks->where('status', 'approved')->count();

    return $total ? round(($done/$total)*100) : 0;
}