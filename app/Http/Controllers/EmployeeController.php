<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        return view('employees.index', [
            'employees' => User::query()->latest()->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('employees.create', [
            'employee' => new User(),
            'contactMethods' => $this->contactMethods(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = User::create($this->validatedData($request));

        return redirect()
            ->route('employees.index')
            ->with('status', "Đã thêm nhân viên {$employee->display_name}.");
    }

    public function edit(User $employee): View
    {
        return view('employees.edit', [
            'employee' => $employee,
            'contactMethods' => $this->contactMethods(),
        ]);
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $employee->update($this->validatedData($request, $employee));

        return redirect()
            ->route('employees.index')
            ->with('status', "Đã cập nhật nhân viên {$employee->display_name}.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?User $employee = null): array
    {
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($employee),
            ],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($employee),
            ],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'primary_contact_method' => ['nullable', 'string', Rule::in($this->contactMethods())],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => [$employee ? 'nullable' : 'required', 'string', 'min:8'],
        ], [], [
            'email' => 'email',
            'username' => 'username',
            'name' => 'tên',
            'phone' => 'số điện thoại',
            'department' => 'phòng ban',
            'primary_contact_method' => 'phương thức liên lạc chính',
            'status' => 'trạng thái',
            'password' => 'mật khẩu',
        ]);

        if (! ($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    private function contactMethods(): array
    {
        return ['Email', 'Phone', 'Telegram', 'Zalo', 'Teams'];
    }
}
