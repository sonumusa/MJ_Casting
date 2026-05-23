<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class SyncStatusController extends Controller
{
    public function index(): View
    {
        $localStatus = [
            'last_sync' => null,
            'pending_changes' => 0,
            'sync_enabled' => true,
        ];

        return view('pages.sync.index', compact('localStatus'));
    }
}
