<?php

namespace App\Http\Controllers\Delivery;

use App\Enums\DeliveryStatus;
use App\Enums\ItemStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Delivery\AssignCrewRequest;
use App\Http\Requests\Delivery\RecordDeliveryProgressRequest;
use App\Http\Requests\Delivery\StoreDeliveryRequest;
use App\Http\Requests\Delivery\UploadDeliveryProofRequest;
use App\Models\Delivery;
use App\Models\DeliveryProof;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Delivery\DeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Delivery::class);
        $filter = $request->string('filter')->toString();

        $deliveries = Delivery::query()
            ->with(['order.customer', 'scheduler', 'items.orderItem'])
            ->when($filter === 'today', fn ($q) => $q->whereDate('scheduled_at', today()->toDateString()))
            ->when($filter === 'upcoming', fn ($q) => $q
                ->where('scheduled_at', '>=', now())
                ->where('status', '!=', DeliveryStatus::Completed))
            ->when($filter === 'completed', fn ($q) => $q->where('status', DeliveryStatus::Completed))
            ->latest('scheduled_at')
            ->paginate(20)
            ->withQueryString();

        return view('delivery.index', ['deliveries' => $deliveries, 'filter' => $filter]);
    }

    public function create(): View
    {
        $this->authorize('create', Delivery::class);

        return view('delivery.create', [
            'orders' => Order::query()
                ->where('status', '!=', OrderStatus::Cancelled)
                ->whereHas('items', fn ($q) => $q->whereIn('status', [ItemStatus::Printed, ItemStatus::Packed]))
                ->with('customer')
                ->latest('id')
                ->get(),
            'items' => OrderItem::query()
                ->whereIn('status', [ItemStatus::Printed, ItemStatus::Packed])
                ->with('order.customer')
                ->orderBy('order_id')
                ->orderBy('seq')
                ->get(),
            'crews' => User::permission('deliveries.field')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreDeliveryRequest $request, DeliveryService $service): RedirectResponse
    {
        $delivery = $service->schedule($request->validated(), $request->user());

        return redirect()->route('deliveries.show', $delivery)
            ->with('status', 'Jadwal pekerjaan lapangan berhasil dibuat.');
    }

    public function show(Delivery $delivery): View
    {
        $this->authorize('view', $delivery);

        return view('delivery.show', [
            'delivery' => $delivery->load([
                'order.customer',
                'items.orderItem',
                'proofs.uploader',
                'proofs.confirmer',
                'scheduler',
                'completer',
            ]),
            'crews' => User::permission('deliveries.field')->orderBy('name')->get(),
            'chargedFee' => app(DeliveryService::class)->chargedFee($delivery->order),
        ]);
    }

    public function assignCrew(
        AssignCrewRequest $request,
        Delivery $delivery,
        DeliveryService $service,
    ): RedirectResponse {
        $service->assignCrew($delivery, $request->validated('crew_ids'), $request->user());

        return redirect()->route('deliveries.show', $delivery)
            ->with('status', 'Petugas lapangan berhasil ditugaskan.');
    }

    public function progress(
        RecordDeliveryProgressRequest $request,
        Delivery $delivery,
        DeliveryService $service,
    ): RedirectResponse {
        $service->recordProgress(
            $delivery,
            $request->validated('stage'),
            $request->user(),
            $request->validated('note'),
        );

        return redirect()->route('deliveries.show', $delivery)
            ->with('status', 'Progres pekerjaan lapangan berhasil dicatat.');
    }

    public function proof(
        UploadDeliveryProofRequest $request,
        Delivery $delivery,
        DeliveryService $service,
    ): RedirectResponse {
        $service->attachProof(
            $delivery,
            $request->file('photo'),
            $request->validated('note'),
            $request->user(),
        );

        return redirect()->route('deliveries.show', $delivery)
            ->with('status', 'Foto bukti berhasil diunggah dan dikonfirmasi.');
    }

    public function complete(Request $request, Delivery $delivery, DeliveryService $service): RedirectResponse
    {
        $this->authorize('complete', $delivery);
        $service->complete($delivery, $request->user());

        return redirect()->route('deliveries.show', $delivery)
            ->with('status', 'Pekerjaan lapangan selesai; item ditandai completed.');
    }

    public function print(Delivery $delivery): View
    {
        $this->authorize('print', $delivery);

        return view('delivery.print', [
            'delivery' => $delivery->load([
                'order.customer',
                'items.orderItem',
                'proofs.uploader',
                'scheduler',
                'completer',
            ]),
            'chargedFee' => app(DeliveryService::class)->chargedFee($delivery->order),
        ]);
    }

    public function downloadProof(DeliveryProof $proof): StreamedResponse
    {
        $this->authorize('downloadProof', $proof);

        return Storage::disk('local')->download(
            $proof->photo_path,
            'bukti-'.$proof->delivery_id.'-'.$proof->id.'.'.pathinfo($proof->photo_path, PATHINFO_EXTENSION),
        );
    }
}
