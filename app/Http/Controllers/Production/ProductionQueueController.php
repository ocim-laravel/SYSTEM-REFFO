<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\PrintJob;
use App\Services\Production\ProductionService;
use Illuminate\View\View;

class ProductionQueueController extends Controller
{
    public function index(ProductionService $production): View
    {
        $this->authorize('viewAny', PrintJob::class);

        $queues = $production->queues();

        return view('production.queue', [
            'urgent' => $queues['urgent'],
            'h1' => $queues['h1'],
            'regular' => $queues['regular'],
        ]);
    }
}
