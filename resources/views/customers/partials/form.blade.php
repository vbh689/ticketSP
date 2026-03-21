@csrf

@if ($method === 'PATCH')
    @method('PATCH')
@endif

<div class="grid grid-2">
    <label>
        Tên (*)
        <input type="text" name="name" value="{{ old('name', $customer->name) }}" required>
    </label>

    <label>
        Điện thoại
        <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}">
    </label>

    <label>
        Email
        <input type="email" name="email" value="{{ old('email', $customer->email) }}">
    </label>

    <label>
        Số lượng license
        <input type="number" min="0" step="1" name="license_count" value="{{ old('license_count', $customer->license_count) }}">
    </label>

    <label>
        Nhân viên đại diện
        <input type="text" name="representative_name" value="{{ old('representative_name', $customer->representative_name) }}">
    </label>

    <label>
        Điện thoại nhân viên đại diện
        <input type="text" name="representative_phone" value="{{ old('representative_phone', $customer->representative_phone) }}">
    </label>
</div>

<label>
    Địa chỉ
    <textarea name="address">{{ old('address', $customer->address) }}</textarea>
</label>

<label>
    Ghi chú
    <textarea name="notes">{{ old('notes', $customer->notes) }}</textarea>
</label>

<div class="nav">
    <button class="button-primary" type="submit">{{ $submitLabel }}</button>
    <a class="button button-muted" href="{{ route('customers.index') }}">Quay lại</a>
</div>
