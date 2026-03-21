<?php

namespace App\Http\Controllers;

use App\Models\Tag;
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
            'is_manager' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'password' => [$employee ? 'nullable' : 'required', 'string', 'min:8'],
        ], [], [
            'email' => 'email',
            'username' => 'username',
            'name' => 'tên',
            'phone' => 'số điện thoại',
            'department' => 'phòng ban',
            'primary_contact_method' => 'phương thức liên lạc chính',
            'is_manager' => 'quyền quản lý',
            'is_active' => 'trạng thái hoạt động',
            'password' => 'mật khẩu',
        ]);

        $validated['is_manager'] = $request->boolean('is_manager');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['status'] = $validated['is_active'] ? 'active' : 'inactive';

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
        $contactMethods = Tag::query()
            ->forType(Tag::TYPE_CONTACT_METHOD)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        return $contactMethods !== [] ? $contactMethods : ['Email', 'Phone', 'Telegram', 'Zalo', 'Teams'];
    }
}
