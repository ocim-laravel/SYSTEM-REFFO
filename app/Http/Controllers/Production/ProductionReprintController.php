<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Production\ApproveReprintRequest;
use App\Models\ReprintRequest;
use App\Services\Production\ProductionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionReprintController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewReprints', ReprintRequest::class);

        $reprints = ReprintRequest::query()
            ->with(['printJob.orderItem.order.customer', 'requester', 'approver'])
            ->latest()
            ->get();

        return view('production.reprints', [
            'reprints' => $reprints,
        ]);
    }

    public function approve(ApproveReprintRequest $request, ReprintRequest $reprintRequest, ProductionService $production): RedirectResponse
    {
        $this->authorize('approveReprint', $reprintRequest);

        $production->approveReprint($reprintRequest, $request->user(), $request->validated('actual_qty'));

        return redirect()->route('production.reprints.index')->with('status', 'Cetak ulang disetujui.');
    }

    public function reject(Request $request, ReprintRequest $reprintRequest, ProductionService $production): RedirectResponse
    {
        $this->authorize('approveReprint', $reprintRequest);

        $production->rejectReprint($reprintRequest, $request->user());

        return redirect()->route('production.reprints.index')->with('status', 'Cetak ulang ditolak.');
    }
}
