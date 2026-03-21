<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Laravel\Scout\Contracts\UpdatesIndexSettings;
use Laravel\Scout\EngineManager;

class SearchMaintenanceController extends Controller
{
    public function __construct(
        private readonly EngineManager $engineManager,
    ) {
    }

    public function rebuild(): RedirectResponse
    {
        if (config('scout.driver') !== 'meilisearch') {
            return back()->withErrors([
                'search_rebuild' => 'Meilisearch chưa được bật trong môi trường hiện tại.',
            ]);
        }

        $this->syncConfiguredIndexSettings();
        $this->rebuildModelIndex(Customer::class);
        $this->rebuildModelIndex(Ticket::class);

        return back()->with('status', 'Đã rebuild lại index tìm kiếm.');
    }

    protected function syncConfiguredIndexSettings(): void
    {
        $engine = $this->engineManager->engine(config('scout.driver'));

        if (! $engine instanceof UpdatesIndexSettings) {
            return;
        }

        foreach ((array) config('scout.meilisearch.index-settings', []) as $index => $settings) {
            if (! is_array($settings)) {
                $settings = [];
            }

            $engine->updateIndexSettings($index, $settings);
        }
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model&\Laravel\Scout\Searchable>  $modelClass
     */
    protected function rebuildModelIndex(string $modelClass): void
    {
        $modelClass::removeAllFromSearch();
        $modelClass::makeAllSearchable();
    }
}
