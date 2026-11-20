<?php

namespace App\Http\Controllers\Design;

use App\Enums\ItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Design\ApproveDesignRequest;
use App\Http\Requests\Design\ChangeRequestRequest;
use App\Http\Requests\Design\ReviseDesignRequest;
use App\Http\Requests\Design\SubmitDesignRequest;
use App\Http\Requests\Design\UploadDesignRequest;
use App\Models\ChangeRequest;
use App\Models\DesignFile;
use App\Models\DesignJob;
use App\Models\OrderItem;
use App\Services\Design\DesignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Antrean desain (MOD-03, PH-03, AC-03-1) — read-only pada sesi ini.
 */
class DesignController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', OrderItem::class);

        // Prioritas enum tidak terurut leksikografis (urgent < h1 < normal),
        // sehingga dipakai CASE; deadline lalu id sebagai tie-breaker.
        $items = OrderItem::query()
            ->where('status', ItemStatus::QueuedDesign)
            ->with(['order.customer'])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'h1' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
            ->orderBy('deadline_at')
            ->orderBy('id')
            ->get();

        return view('design.queue', ['items' => $items]);
    }

    public function claim(Request $request, OrderItem $item, DesignService $service): RedirectResponse
    {
        $this->authorize('claim', $item);

        $service->claim($item, $request->user());

        return redirect()->route('design.queue')
            ->with('status', 'Tugas desain berhasil diambil.');
    }

    public function uploadForm(OrderItem $item, DesignService $service): View
    {
        $this->authorize('view', $item);

        $job = DesignJob::query()->where('order_item_id', $item->id)->latest('id')->firstOrFail();
        $files = $job->files()->orderBy('version')->get();
        $revisions = $job->revisions()->with('requester')->orderBy('cycle_no')->get();
        $approvals = $job->approvals()->with(['approver', 'designFile'])->orderBy('approved_at')->get();
        $changeRequests = $item->changeRequests()
            ->with(['approvedBy', 'designFile'])
            ->latest()
            ->get();

        return view('design.upload', [
            'item' => $item,
            'job' => $job,
            'files' => $files,
            'revisions' => $revisions,
            'approvals' => $approvals,
            'changeRequests' => $changeRequests,
            'history' => $service->history($job),
        ]);
    }

    public function upload(UploadDesignRequest $request, OrderItem $item, DesignService $service): RedirectResponse
    {
        $this->authorize('upload', $item);

        $job = DesignJob::query()->where('order_item_id', $item->id)->latest('id')->firstOrFail();

        $service->upload($job, $request->file('file'), $request->user());

        return redirect()->route('design.upload', $item)
            ->with('status', 'File desain berhasil diunggah.');
    }

    public function revise(ReviseDesignRequest $request, OrderItem $item, DesignService $service): RedirectResponse
    {
        $this->authorize('revise', $item);

        $job = DesignJob::query()->where('order_item_id', $item->id)->latest('id')->firstOrFail();

        $service->requestRevision($job, $request->validated('note'), $request->user());

        return redirect()->route('design.upload', $item)
            ->with('status', 'Permintaan revisi berhasil dicatat.');
    }

    public function submit(SubmitDesignRequest $request, OrderItem $item, DesignService $service): RedirectResponse
    {
        $this->authorize('submit', $item);

        $job = DesignJob::query()->where('order_item_id', $item->id)->latest('id')->firstOrFail();

        $service->submitForApproval($job, $request->user());

        return redirect()->route('design.upload', $item)
            ->with('status', 'Desain berhasil diajukan untuk ACC.');
    }

    public function approve(ApproveDesignRequest $request, OrderItem $item, DesignService $service): RedirectResponse
    {
        $this->authorize('approve', $item);

        $job = DesignJob::query()->where('order_item_id', $item->id)->latest('id')->firstOrFail();

        $service->approve(
            $job,
            $request->user(),
            (string) ($request->validated('method') ?? 'internal'),
            $request->validated('note'),
            $request->validated('design_file_id'),
        );

        return redirect()->route('design.upload', $item)
            ->with('status', 'Desain berhasil di-ACC.');
    }

    public function handoff(Request $request, OrderItem $item, DesignService $service): RedirectResponse
    {
        $this->authorize('handoff', $item);

        $service->handoffToProduction($item, $request->user());

        return redirect()->route('design.upload', $item)
            ->with('status', 'Desain diserahkan ke produksi.');
    }

    public function changeRequest(ChangeRequestRequest $request, OrderItem $item, DesignService $service): RedirectResponse
    {
        $this->authorize('changeRequest', $item);

        $service->requestChangeRequest(
            $item,
            $request->user(),
            (string) $request->validated('reason'),
            $request->validated('cost_impact'),
            $request->validated('deadline_impact'),
            $request->validated('design_file_id'),
        );

        return redirect()->route('design.upload', $item)
            ->with('status', 'Change Request berhasil diajukan.');
    }

    public function approveChangeRequest(Request $request, ChangeRequest $changeRequest, DesignService $service): RedirectResponse
    {
        $this->authorize('approve', $changeRequest);

        $service->approveChangeRequest($changeRequest, $request->user());

        $changeRequest->loadMissing('orderItem');

        return redirect()->route('design.upload', $changeRequest->orderItem)
            ->with('status', 'Change Request disetujui.');
    }

    public function rejectChangeRequest(Request $request, ChangeRequest $changeRequest, DesignService $service): RedirectResponse
    {
        $this->authorize('approve', $changeRequest);

        $service->rejectChangeRequest($changeRequest, $request->user());

        $changeRequest->loadMissing('orderItem');

        return redirect()->route('design.upload', $changeRequest->orderItem)
            ->with('status', 'Change Request ditolak.');
    }

    public function download(DesignFile $file): StreamedResponse
    {
        $this->authorize('view', $file);

        return Storage::disk('local')->download($file->path);
    }
}
