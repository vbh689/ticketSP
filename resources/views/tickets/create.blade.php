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
                        <p class="section-copy">Gõ tên để tìm nhanh khách hàng đã có qua search service. Nếu không chọn khách có sẵn, hệ thống sẽ tạo mới khách chỉ với tên để bạn bổ sung sau.</p>
                    </div>

                    <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id') }}">

                    <div class="stack" style="gap: 12px;">
                        <label>
                            Tìm khách hàng
                            <input
                                type="text"
                                id="customer_search"
                                value="{{ old('customer_name', $selectedCustomer?->name) }}"
                                placeholder="Nhập tên khách hàng để tìm nhanh hoặc tạo mới"
                                autocomplete="off"
                            >
                        </label>

                        <div id="customer_selected" class="helper" style="display: none;"></div>
                        <div id="customer_results" class="card" style="display: none; padding: 10px;"></div>

                        <div class="nav" id="customer_actions" style="display: none;">
                            <button type="button" class="button button-secondary" id="clear_customer_selection">Bỏ chọn khách hàng</button>
                        </div>
                    </div>

                    <label>
                        Tên khách hàng
                        <input
                            type="text"
                            name="customer_name"
                            id="customer_name"
                            value="{{ old('customer_name', old('requester_name', $selectedCustomer?->name)) }}"
                            required
                        >
                        <span class="inline-note">Chưa chọn khách có sẵn thì tên này sẽ được dùng để tạo hồ sơ khách mới.</span>
                    </label>

                    <!-- <div class="grid grid-2">
                        <label>
                            Thông tin liên hệ hiện có
                            <input type="text" id="customer_contact_preview" value="" disabled>
                        </label>

                        <label>
                            Số lượng license hiện có
                            <input type="text" id="customer_license_preview" value="" disabled>
                        </label>
                    </div> -->
                </section>

                <label>
                    Tiêu đề
                    <input type="text" name="title" value="{{ old('title') }}" required>
                </label>

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
            const customerSearchUrl = @json(route('customers.search'));
            const initialSelectedCustomer = @json($selectedCustomer);
            const searchInput = document.getElementById('customer_search');
            const customerIdInput = document.getElementById('customer_id');
            const customerNameInput = document.getElementById('customer_name');
            const customerContactPreview = document.getElementById('customer_contact_preview');
            const customerLicensePreview = document.getElementById('customer_license_preview');
            const results = document.getElementById('customer_results');
            const selected = document.getElementById('customer_selected');
            const actions = document.getElementById('customer_actions');
            const clearButton = document.getElementById('clear_customer_selection');
            let searchController = null;
            let searchTimer = null;

            const setPreviewValue = (element, value) => {
                if (!element) {
                    return;
                }

                element.value = value;
            };

            const oldCustomerId = customerIdInput.value;
            let selectedCustomer = oldCustomerId ? initialSelectedCustomer : null;

            const setCustomerStateDisabled = (disabled) => {
                customerNameInput.disabled = disabled;
                customerNameInput.classList.toggle('input-disabled', disabled);
                customerContactPreview?.classList.toggle('input-disabled', disabled);
                customerLicensePreview?.classList.toggle('input-disabled', disabled);
                customerNameInput.required = !disabled;
            };

            const renderResults = (matches) => {
                if (!matches.length || selectedCustomer) {
                    results.style.display = 'none';
                    results.innerHTML = '';

                    return;
                }

                results.innerHTML = matches.map((customer) => `
                    <button type="button" class="search-result" data-customer-id="${customer.id}">
                        <strong>${customer.name_html}</strong>
                        <span>${customer.contact_html}</span>
                    </button>
                `).join('');
                results.style.display = 'grid';
                results.querySelectorAll('[data-customer-id]').forEach((button, index) => {
                    button.addEventListener('click', () => {
                        selectedCustomer = matches[index];
                        renderSelectedCustomer();
                    });
                });
            };

            const renderSelectedCustomer = () => {
                if (!selectedCustomer) {
                    selected.style.display = 'none';
                    selected.textContent = '';
                    actions.style.display = 'none';
                    customerIdInput.value = '';
                    setPreviewValue(customerContactPreview, '');
                    setPreviewValue(customerLicensePreview, '');
                    setCustomerStateDisabled(false);

                    return;
                }

                selected.style.display = 'block';
                selected.textContent = selectedCustomer.selected_label || selectedCustomer.name;
                actions.style.display = 'flex';
                customerIdInput.value = selectedCustomer.id;
                searchInput.value = selectedCustomer.name;
                customerNameInput.value = selectedCustomer.name;
                setPreviewValue(customerContactPreview, selectedCustomer.contact_preview || 'Chưa có');
                setPreviewValue(customerLicensePreview, selectedCustomer.license_preview || 'Chưa cập nhật');
                results.style.display = 'none';
                results.innerHTML = '';
                setCustomerStateDisabled(true);
            };

            searchInput.addEventListener('input', () => {
                if (selectedCustomer) {
                    return;
                }

                const keyword = searchInput.value.trim();
                customerNameInput.value = searchInput.value;

                if (!keyword) {
                    renderResults([]);

                    return;
                }

                window.clearTimeout(searchTimer);

                searchTimer = window.setTimeout(async () => {
                    if (searchController) {
                        searchController.abort();
                    }

                    searchController = new AbortController();

                    try {
                        const response = await fetch(`${customerSearchUrl}?q=${encodeURIComponent(keyword)}`, {
                            headers: {
                                'Accept': 'application/json',
                            },
                            signal: searchController.signal,
                        });

                        if (!response.ok) {
                            throw new Error('Customer search request failed');
                        }

                        const payload = await response.json();
                        renderResults(payload.data || []);
                    } catch (error) {
                        if (error.name === 'AbortError') {
                            return;
                        }

                        renderResults([]);
                    }
                }, 180);
            });

            clearButton.addEventListener('click', () => {
                selectedCustomer = null;
                searchInput.value = '';
                customerNameInput.value = '';
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
