<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

class SearchMaintenanceController extends Controller
{
    public function rebuild(): RedirectResponse
    {
        if (config('scout.driver') !== 'meilisearch') {
            return back()->withErrors([
                'search_rebuild' => 'Meilisearch chưa được bật trong môi trường hiện tại.',
            ]);
        }

        Artisan::call('scout:sync-index-settings');
        Artisan::call('scout:flush', ['model' => Customer::class]);
        Artisan::call('scout:flush', ['model' => Ticket::class]);
        Artisan::call('scout:import', ['model' => Customer::class]);
        Artisan::call('scout:import', ['model' => Ticket::class]);

        return back()->with('status', 'Đã rebuild lại index tìm kiếm.');
    }
}
