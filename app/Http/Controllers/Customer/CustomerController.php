<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerRequest;
use App\Http\Requests\Customer\DeleteCustomerRequest;
use App\Models\Customer;
use App\Services\Customer\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Customer::class);

        return view('customers.index', [
            'customers' => Customer::query()->orderByDesc('id')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('customers.create');
    }

    public function store(CustomerRequest $request, CustomerService $service): RedirectResponse
    {
        $this->authorize('create', Customer::class);

        $customer = $service->create($request->validated(), $request->user(), $this->meta($request));

        return redirect()->route('customers.show', $customer)
            ->with('status', 'Pelanggan berhasil ditambahkan.');
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        return view('customers.show', ['customer' => $customer]);
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('customers.edit', ['customer' => $customer]);
    }

    public function update(CustomerRequest $request, Customer $customer, CustomerService $service): RedirectResponse
    {
        $this->authorize('update', $customer);

        $service->update($customer, $request->validated(), $request->user(), $this->meta($request));

        return redirect()->route('customers.show', $customer)
            ->with('status', 'Data pelanggan berhasil diperbarui.');
    }

    public function destroy(DeleteCustomerRequest $request, Customer $customer, CustomerService $service): RedirectResponse
    {
        $this->authorize('delete', $customer);

        $service->delete($customer, $request->validated('reason'), $request->user(), $this->meta($request));

        return redirect()->route('customers.index')
            ->with('status', 'Pelanggan berhasil dihapus.');
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
