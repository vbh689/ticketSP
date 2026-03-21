@extends('layouts.app')

@section('title', 'Tạo Ticket | ticketSP')

@section('body')
    <main class="shell stack">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">IT</div>
                <div>
                    <h1>Tạo ticket mới</h1>
                    <p>Ghi nhận nhanh yêu cầu hỗ trợ và đưa ngay vào backlog chung.</p>
                </div>
            </div>

            <div class="nav">
                <a class="button button-muted" href="{{ route('tickets.index') }}">Quay lại backlog</a>
            </div>
        </header>

        @if ($errors->any())
            <div class="flash flash-error">{{ $errors->first() }}</div>
        @endif

        <section class="card panel stack">
            <div>
                <h2 class="section-title">Thông tin ticket</h2>
                <p class="section-copy">Ticket mới sẽ mặc định ở trạng thái Open và chưa có người phụ trách.</p>
            </div>

            <form method="POST" action="{{ route('tickets.store') }}" class="stack">
                @csrf

                <section class="card panel stack" style="padding: 20px;">
                    <div>
                        <h3 class="section-title" style="font-size: 1.1rem;">Khách hàng</h3>
                        <p class="section-copy">Gõ tên để tìm nhanh khách hàng đã có. Nếu không chọn khách có sẵn, hệ thống sẽ tạo khách mới từ thông tin bạn nhập.</p>
                    </div>

                    <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id') }}">

                    <div class="stack" style="gap: 12px;">
                        <label>
                            Tìm khách hàng
                            <input
                                type="text"
                                id="customer_search"
                                value=""
                                placeholder="Nhập tên khách hàng để tìm nhanh"
                                autocomplete="off"
                            >
                        </label>

                        <div id="customer_selected" class="helper" style="display: none;"></div>
                        <div id="customer_results" class="card" style="display: none; padding: 10px;"></div>

                        <div class="nav" id="customer_actions" style="display: none;">
                            <button type="button" class="button button-secondary" id="clear_customer_selection">Bỏ chọn khách hàng</button>
                        </div>
                    </div>

                    <div class="grid grid-2" id="customer_manual_fields">
                        <label>
                            Tên khách hàng
                            <input type="text" name="customer_name" value="{{ old('customer_name', old('requester_name')) }}" required>
                        </label>

                        <label>
                            Số điện thoại công ty
                            <input type="text" name="customer_phone" value="{{ old('customer_phone') }}">
                        </label>

                        <label>
                            Email công ty
                            <input type="email" name="customer_email" value="{{ old('customer_email') }}">
                        </label>

                        <label>
                            Số lượng license
                            <input type="number" min="0" step="1" name="customer_license_count" value="{{ old('customer_license_count') }}">
                        </label>

                        <label>
                            Nhân viên đại diện
                            <input type="text" name="customer_representative_name" value="{{ old('customer_representative_name') }}">
                        </label>

                        <label>
                            Điện thoại nhân viên đại diện
                            <input type="text" name="customer_representative_phone" value="{{ old('customer_representative_phone') }}">
                        </label>
                    </div>

                    <label>
                        Địa chỉ khách hàng
                        <textarea name="customer_address" id="customer_address">{{ old('customer_address') }}</textarea>
                    </label>

                    <label>
                        Ghi chú khách hàng
                        <textarea name="customer_notes" id="customer_notes">{{ old('customer_notes') }}</textarea>
                    </label>
                </section>

                <div class="grid grid-2">
                    Tiêu đề
                    <input type="text" name="title" value="{{ old('title') }}" required>
                </div>

                <div class="grid grid-2">
                    <label>
                        Loại ticket
                        <select name="category_id" required>
                            <option value="">Chọn loại ticket</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <label>
                    Mô tả chi tiết
                    <textarea name="description" required>{{ old('description') }}</textarea>
                </label>

                <div class="nav">
                    <button class="button-primary" type="submit">Lưu ticket</button>
                    <a class="button button-secondary" href="{{ route('tickets.index') }}">Hủy</a>
                </div>
            </form>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const customers = @json($customers);
            const searchInput = document.getElementById('customer_search');
            const customerIdInput = document.getElementById('customer_id');
            const results = document.getElementById('customer_results');
            const selected = document.getElementById('customer_selected');
            const actions = document.getElementById('customer_actions');
            const clearButton = document.getElementById('clear_customer_selection');
            const manualFields = Array.from(document.querySelectorAll('#customer_manual_fields input, #customer_address, #customer_notes'));
            const customerNameInput = document.querySelector('input[name="customer_name"]');

            const oldCustomerId = customerIdInput.value;
            let selectedCustomer = customers.find((customer) => String(customer.id) === String(oldCustomerId)) || null;

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const setManualFieldsDisabled = (disabled) => {
                manualFields.forEach((field) => {
                    field.disabled = disabled;
                });

                customerNameInput.required = !disabled;
            };

            const renderSelectedCustomer = () => {
                if (!selectedCustomer) {
                    selected.style.display = 'none';
                    selected.textContent = '';
                    actions.style.display = 'none';
                    customerIdInput.value = '';
                    setManualFieldsDisabled(false);

                    return;
                }

                const parts = [
                    selectedCustomer.representative_name ? `Dai dien: ${selectedCustomer.representative_name}` : null,
                    selectedCustomer.representative_phone ? `SDT dai dien: ${selectedCustomer.representative_phone}` : null,
                    selectedCustomer.phone ? `SDT cong ty: ${selectedCustomer.phone}` : null,
                    selectedCustomer.email ? `Email: ${selectedCustomer.email}` : null,
                    selectedCustomer.license_count !== null ? `License: ${selectedCustomer.license_count}` : null,
                ].filter(Boolean);

                selected.style.display = 'block';
                selected.textContent = `${selectedCustomer.name}${parts.length ? ' | ' + parts.join(' | ') : ''}`;
                actions.style.display = 'flex';
                customerIdInput.value = selectedCustomer.id;
                searchInput.value = selectedCustomer.name;
                results.style.display = 'none';
                results.innerHTML = '';
                setManualFieldsDisabled(true);
            };

            const renderResults = (matches) => {
                if (!matches.length || selectedCustomer) {
                    results.style.display = 'none';
                    results.innerHTML = '';

                    return;
                }

                results.innerHTML = matches.map((customer) => `
                    <button type="button" class="search-result" data-customer-id="${customer.id}">
                        <strong>${escapeHtml(customer.name)}</strong>
                        <span>${escapeHtml(customer.representative_name || customer.phone || customer.email || 'Chua co thong tin lien he')}</span>
                    </button>
                `).join('');
                results.style.display = 'grid';
                results.querySelectorAll('[data-customer-id]').forEach((button) => {
                    button.addEventListener('click', () => {
                        selectedCustomer = customers.find((customer) => String(customer.id) === button.dataset.customerId) || null;
                        renderSelectedCustomer();
                    });
                });
            };

            searchInput.addEventListener('input', () => {
                if (selectedCustomer) {
                    return;
                }

                const keyword = searchInput.value.trim().toLowerCase();

                if (!keyword) {
                    renderResults([]);

                    return;
                }

                const matches = customers
                    .filter((customer) => customer.name.toLowerCase().includes(keyword))
                    .slice(0, 8);

                renderResults(matches);
            });

            clearButton.addEventListener('click', () => {
                selectedCustomer = null;
                searchInput.value = '';
                renderSelectedCustomer();
                searchInput.focus();
            });

            document.addEventListener('click', (event) => {
                if (results.contains(event.target) || event.target === searchInput) {
                    return;
                }

                results.style.display = 'none';
            });

            renderSelectedCustomer();
        });
    </script>
@endsection
