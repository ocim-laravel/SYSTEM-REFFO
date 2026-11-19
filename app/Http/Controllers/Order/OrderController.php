<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CancelOrderRequest;
use App\Http\Requests\Order\OrderFilterRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(OrderFilterRequest $request): View
    {
        $this->authorize('viewAny', Order::class);

        $filters = $request->validated();
        $query = Order::query()->with('customer');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return view('orders.index', [
            'orders' => $query->orderByDesc('id')->paginate(20)->withQueryString(),
            'filters' => $filters,
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Order::class);

        return view('orders.create', [
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreOrderRequest $request, OrderService $service): RedirectResponse
    {
        $this->authorize('create', Order::class);

        $order = $service->create($request->validated(), $request->user(), $this->meta($request));

        return redirect()->route('orders.show', $order)
            ->with('status', 'Order berhasil dibuat dan menunggu validasi.');
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load(['customer', 'items', 'items.statusHistories.changedBy', 'creator', 'validator', 'cancellation.decidedBy']);

        return view('orders.show', ['order' => $order]);
    }

    public function validate(Request $request, Order $order, OrderService $service): RedirectResponse
    {
        $this->authorize('validate', $order);

        $service->validate($order, $request->user(), $this->meta($request));

        return redirect()->route('orders.show', $order)
            ->with('status', 'Order berhasil divalidasi dan item masuk antrean desain.');
    }

    public function cancel(Order $order): View
    {
        $this->authorize('cancel', $order);

        return view('orders.cancel', ['order' => $order->load('customer')]);
    }

    public function cancelStore(CancelOrderRequest $request, Order $order, OrderService $service): RedirectResponse
    {
        $this->authorize('cancel', $order);

        $service->cancel(
            $order,
            $request->validated('reason'),
            $request->validated('actual_cost'),
            $request->validated('settlement_amount'),
            $request->validated('settlement_note'),
            $request->user(),
            $this->meta($request),
        );

        return redirect()->route('orders.show', $order)
            ->with('status', 'Order berhasil dibatalkan.');
    }

    /**
     * @return array{ip: string|null, user_agent: string|null}
     */
    private function meta(Request $request): array
    {
        return [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }
}
