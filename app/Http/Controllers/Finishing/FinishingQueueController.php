<?php

namespace App\Http\Controllers\Finishing;

use App\Http\Controllers\Controller;
use App\Models\FinishingJob;
use App\Models\OrderItem;
use App\Services\Production\FinishingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinishingQueueController extends Controller
{
    public function index(FinishingService $finishing): View
    {
        $this->authorize('viewAny', FinishingJob::class);

        return view('finishing.queue', [
            'items' => $finishing->queue(),
        ]);
    }

    public function intake(Request $request, OrderItem $item, FinishingService $finishing): RedirectResponse
    {
        $this->authorize('start', FinishingJob::class);
        $finishing->intake($item, $request->user());

        return redirect()->route('finishing.show', $item)
            ->with('status', 'Item masuk antrean finishing.');
    }
}
