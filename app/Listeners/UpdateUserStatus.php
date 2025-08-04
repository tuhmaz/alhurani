<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Models\VisitorTracking;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UpdateUserStatus
{
    public function handleLogin(Login $event)
    {
        $user = $event->user;
        $user->status = 'online';
        $user->last_seen = now();
        $user->save();

        // تحديث سجل الزيارة مع إضافة عنوان IP
        $ipAddress = request()->ip() ?? '127.0.0.1';
        
        VisitorTracking::updateOrCreate(
            ['user_id' => $user->id],
            [
                'ip_address' => $ipAddress,
                'user_agent' => request()->userAgent() ?? 'Unknown',
                'last_activity' => now()
            ]
        );
    }

    public function handleLogout(Logout $event)
    {
        $user = $event->user;
        if ($user) {
            $user->status = 'offline';
            $user->last_seen = now();
            $user->save();
        }
    }
}