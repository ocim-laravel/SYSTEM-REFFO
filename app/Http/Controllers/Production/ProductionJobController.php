<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Production\AssignProductionRequest;
use App\Http\Requests\Production\HandoverRequest;
use App\Http\Requests\Production\RecordProgressRequest;
use App\Http\Requests\Production\RequestReprintRequest;
use App\Models\OrderItem;
use App\Models\PrintJob;
use App\Models\User;
use App\Services\Production\ProductionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionJobController extends Controller
{
    public function show(OrderItem $item): View
    {
        $job = PrintJob::query()
            ->with([
                'orderItem.order.customer',
                'assignee',
                'assigner',
                'operators.operator',
                'reprintRequests.requester',
                'reprintRequests.approver',
            ])
            ->where('order_item_id', $item->id)
            ->firstOrFail();

        $this->authorize('view', $job);

        $operators = User::permission('production.progress')->orderBy('name')->get();

        return view('production.show', [
            'item' => $item->loadMissing('order.customer'),
            'job' => $job,
            'operators' => $operators,
        ]);
    }

    public function assign(AssignProductionRequest $request, OrderItem $item, ProductionService $production): RedirectResponse
    {
        $job = $this->jobForItem($item);
        $this->authorize('assign', $job);

        $operator = User::query()->findOrFail($request->validated('operator_id'));
        $production->assign($job, $operator, $request->user());

        return redirect()->route('production.show', $item)->with('status', 'Pekerjaan berhasil dibagikan.');
    }

    public function claim(Request $request, OrderItem $item, ProductionService $production): RedirectResponse
    {
        $job = $this->jobForItem($item);
        $this->authorize('claim', $job);

        $production->claim($job, $request->user());

        return redirect()->route('production.show', $item)->with('status', 'Pekerjaan berhasil diambil.');
    }

    public function progress(RecordProgressRequest $request, OrderItem $item, ProductionService $production): RedirectResponse
    {
        $job = $this->jobForItem($item);
        $this->authorize('recordProgress', $job);

        $production->recordProgress($job, $request->validated(), $request->user());

        return redirect()->route('production.show', $item)->with('status', 'Progres berhasil dicatat.');
    }

    public function handover(HandoverRequest $request, OrderItem $item, ProductionService $production): RedirectResponse
    {
        $job = $this->jobForItem($item);
        $this->authorize('handover', $job);

        $operator = User::query()->findOrFail($request->validated('operator_id'));
        $production->handover($job, $operator, (string) $request->validated('reason'), $request->user());

        return redirect()->route('production.show', $item)->with('status', 'Handover berhasil dicatat.');
    }

    public function requestReprint(RequestReprintRequest $request, OrderItem $item, ProductionService $production): RedirectResponse
    {
        $job = $this->jobForItem($item);
        $this->authorize('requestReprint', $job);

        $production->requestReprint($job, $request->validated('qty'), (string) $request->validated('reason'), $request->user());

        return redirect()->route('production.show', $item)->with('status', 'Pengajuan cetak ulang berhasil dikirim.');
    }

    private function jobForItem(OrderItem $item): PrintJob
    {
        return PrintJob::query()
            ->with(['operators', 'orderItem'])
            ->where('order_item_id', $item->id)
            ->firstOrFail();
    }
}
