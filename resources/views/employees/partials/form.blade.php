@csrf

@if ($method === 'PATCH')
    @method('PATCH')
@endif

<div class="grid grid-2">
    <label>
        Email
        <input type="email" name="email" value="{{ old('email', $employee->email) }}" required>
    </label>

    <label>
        Username
        <input type="text" name="username" value="{{ old('username', $employee->username) }}" required>
    </label>

    <label>
        Tên
        <input type="text" name="name" value="{{ old('name', $employee->name) }}" required>
    </label>

    <label>
        Số điện thoại
        <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}">
    </label>

    <label>
        Phòng ban
        <input type="text" name="department" value="{{ old('department', $employee->department) }}">
    </label>

    <label>
        Phương thức liên lạc chính
        <select name="primary_contact_method">
            <option value="">Chọn phương thức</option>
            @foreach ($contactMethods as $contactMethod)
                <option value="{{ $contactMethod }}" @selected(old('primary_contact_method', $employee->primary_contact_method) === $contactMethod)>{{ $contactMethod }}</option>
            @endforeach
        </select>
    </label>

    <label>
        Trạng thái
        <select name="status">
            <option value="active" @selected(old('status', $employee->status ?: 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $employee->status) === 'inactive')>Inactive</option>
        </select>
    </label>

    <label>
        {{ $employee->exists ? 'Mật khẩu mới' : 'Mật khẩu' }}
        <input type="password" name="password" {{ $employee->exists ? '' : 'required' }}>
    </label>
</div>

<div class="nav">
    <button class="button-primary" type="submit">{{ $submitLabel }}</button>
    <a class="button button-muted" href="{{ route('employees.index') }}">Quay lại danh sách</a>
</div>
