<?php

namespace App\Http\Controllers\Finishing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finishing\AssignFinishingRequest;
use App\Http\Requests\Finishing\CompleteReworkRequest;
use App\Http\Requests\Finishing\PackRequest;
use App\Http\Requests\Finishing\RecordFinishingProgressRequest;
use App\Http\Requests\Finishing\RecordQcRequest;
use App\Models\FinishingJob;
use App\Models\OrderItem;
use App\Models\Packing;
use App\Models\Rework;
use App\Models\User;
use App\Services\Production\FinishingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinishingJobController extends Controller
{
    public function show(OrderItem $item, FinishingService $finishing): View
    {
        $job = $this->findJob($item);
        $this->authorize($job === null ? 'viewAny' : 'view', $job ?? FinishingJob::class);

        return view('finishing.show', [
            'item' => $item->loadMissing('order.customer'),
            'job' => $job,
            'reworks' => $item->reworks()->with(['qcRecord.checker', 'doneBy'])->latest('id')->get(),
            'packings' => $item->packings()->with('packer')->latest('id')->get(),
            'history' => $finishing->history($item),
            'operators' => User::permission('finishing.claim')->orderBy('name')->get(),
        ]);
    }

    public function assign(AssignFinishingRequest $request, OrderItem $item, FinishingService $finishing): RedirectResponse
    {
        $job = $this->jobForItem($item);
        $this->authorize('assign', $job);
        $operator = User::query()->findOrFail($request->validated('operator_id'));
        $finishing->assign($job, $operator, $request->user());

        return redirect()->route('finishing.show', $item)->with('status', 'Pekerjaan finishing berhasil dibagikan.');
    }

    public function claim(Request $request, OrderItem $item, FinishingService $finishing): RedirectResponse
    {
        $job = $this->jobForItem($item);
        $this->authorize('claim', $job);
        $finishing->claim($job, $request->user());

        return redirect()->route('finishing.show', $item)->with('status', 'Pekerjaan finishing berhasil diambil.');
    }

    public function progress(
        RecordFinishingProgressRequest $request,
        OrderItem $item,
        FinishingService $finishing,
    ): RedirectResponse {
        $job = $this->jobForItem($item);
        $this->authorize('recordProgress', $job);
        $finishing->recordProgress($job, $request->validated(), $request->user());

        return redirect()->route('finishing.show', $item)->with('status', 'Progres finishing berhasil dicatat.');
    }

    public function qc(RecordQcRequest $request, OrderItem $item, FinishingService $finishing): RedirectResponse
    {
        $job = $this->jobForItem($item);
        $this->authorize('recordQc', $job);
        $data = $request->validated();
        $finishing->recordQc(
            $job,
            $data['result'],
            $data['notes'] ?? null,
            $request->user(),
            $data['redo_stage'] ?? null,
        );

        return redirect()->route('finishing.show', $item)->with('status', 'Hasil QC berhasil dicatat.');
    }

    public function startRework(Request $request, OrderItem $item, Rework $rework, FinishingService $finishing): RedirectResponse
    {
        abort_unless($rework->order_item_id === $item->id, 404);
        $this->authorize('manageRework', $rework);
        $finishing->startRework($rework, $request->user());

        return redirect()->route('finishing.show', $item)->with('status', 'Rework dimulai.');
    }

    public function completeRework(
        CompleteReworkRequest $request,
        OrderItem $item,
        Rework $rework,
        FinishingService $finishing,
    ): RedirectResponse {
        abort_unless($rework->order_item_id === $item->id, 404);
        $this->authorize('manageRework', $rework);
        $finishing->completeRework($rework, $request->user(), $request->validated('note'));

        return redirect()->route('finishing.show', $item)->with('status', 'Rework selesai, QC dapat diulang.');
    }

    public function startPacking(Request $request, OrderItem $item, FinishingService $finishing): RedirectResponse
    {
        $this->authorize('pack', $this->jobForItem($item));
        $finishing->startPacking($item, $request->user());

        return redirect()->route('finishing.show', $item)->with('status', 'Packing dimulai.');
    }

    public function completePacking(
        PackRequest $request,
        OrderItem $item,
        Packing $packing,
        FinishingService $finishing,
    ): RedirectResponse {
        abort_unless($packing->order_item_id === $item->id, 404);
        $this->authorize('completePacking', $packing);
        $finishing->completePacking($packing, $request->validated('note'), $request->user());

        return redirect()->route('finishing.show', $item)->with('status', 'Packing selesai.');
    }

    private function findJob(OrderItem $item): ?FinishingJob
    {
        return FinishingJob::query()
            ->with(['assignee', 'assigner', 'qcRecords.checker', 'reworks.doneBy', 'packing.packer'])
            ->where('order_item_id', $item->id)
            ->first();
    }

    private function jobForItem(OrderItem $item): FinishingJob
    {
        return $this->findJob($item) ?? abort(404);
    }
}
