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
        Quyền
        <select name="is_manager">
            <option value="0" @selected(! old('is_manager', $employee->is_manager))>Nhân viên</option>
            <option value="1" @selected((bool) old('is_manager', $employee->is_manager))>Manager</option>
        </select>
    </label>

    <label>
        Trạng thái hoạt động
        <select name="is_active">
            <option value="1" @selected((bool) old('is_active', $employee->exists ? $employee->is_active : true))>Đang làm việc</option>
            <option value="0" @selected(! (bool) old('is_active', $employee->exists ? $employee->is_active : true))>Đã nghỉ</option>
        </select>
    </label>

    <label>
        {{ $employee->exists ? 'Mật khẩu mới' : 'Mật khẩu' }}
        <input type="password" name="password" {{ $employee->exists ? '' : 'required' }}>
    </label>
</div>

<div class="nav">
    <button class="button-primary" type="submit">{{ $submitLabel }}</button>
    <a class="button button-muted" href="{{ route('employees.index') }}">Quay lại</a>
</div>
