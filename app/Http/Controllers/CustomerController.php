<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        return view('customers.index', [
            'customers' => Customer::query()->latest()->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('customers.create', [
            'customer' => new Customer,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = Customer::create($this->validatedData($request));

        return redirect()
            ->route('customers.index')
            ->with('status', "Đã thêm khách {$customer->name}.");
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validatedData($request));

        return redirect()
            ->route('customers.index')
            ->with('status', "Đã cập nhật khách {$customer->name}.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'representative_name' => ['nullable', 'string', 'max:255'],
            'representative_phone' => ['nullable', 'string', 'max:255'],
            'license_count' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ], [], [
            'name' => 'tên',
            'address' => 'địa chỉ',
            'phone' => 'điện thoại',
            'email' => 'email',
            'representative_name' => 'nhân viên đại diện',
            'representative_phone' => 'điện thoại nhân viên đại diện',
            'license_count' => 'số lượng license',
            'notes' => 'ghi chú',
        ]);
    }
}
