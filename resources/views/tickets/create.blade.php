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
                    <div class="stack" style="gap: 12px;">
                        <input type="hidden" name="category_name" id="category_name" value="{{ old('category_name') }}">

                        <label>
                            Loại ticket
                            <input
                                type="text"
                                id="category_search"
                                value="{{ old('category_name') }}"
                                placeholder="Gõ để chọn nhanh hoặc tạo mới loại ticket"
                                autocomplete="off"
                                required
                            >
                            <span class="inline-note">Ví dụ gõ `phan` sẽ hiện `Phần mềm`, `Phần cứng`. Nếu không có loại phù hợp, hệ thống sẽ tự tạo loại mới theo nội dung bạn nhập.</span>
                        </label>

                        <div id="category_selected" class="helper" style="display: none;"></div>
                        <div id="category_results" class="card" style="display: none; padding: 10px;"></div>

                        <div class="nav" id="category_actions" style="display: none;">
                            <button type="button" class="button button-secondary" id="clear_category_selection">Bỏ chọn loại ticket</button>
                        </div>
                    </div>

                    <div class="stack" style="gap: 12px;">
                        <input type="hidden" name="requester_contact_method" id="requester_contact_method" value="{{ old('requester_contact_method') }}">

                        <label>
                            Phương thức liên hệ
                            <input
                                type="text"
                                id="contact_method_search"
                                value="{{ old('requester_contact_method') }}"
                                placeholder="Gõ để chọn nhanh hoặc tạo mới phương thức liên hệ"
                                autocomplete="off"
                            >
                            <span class="inline-note">Ví dụ gõ `tele` sẽ hiện `Telegram`. Nếu không có phương thức phù hợp, hệ thống sẽ tự tạo tag mới theo nội dung bạn nhập.</span>
                        </label>

                        <div id="contact_method_selected" class="helper" style="display: none;"></div>
                        <div id="contact_method_results" class="card" style="display: none; padding: 10px;"></div>

                        <div class="nav" id="contact_method_actions" style="display: none;">
                            <button type="button" class="button button-secondary" id="clear_contact_method_selection">Bỏ chọn phương thức</button>
                        </div>
                    </div>
                </div>

                <label>
                    Ưu tiên
                    <select
                        name="priority"
                        id="priority"
                    >
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority }}" @selected(old('priority', \App\Models\Ticket::PRIORITY_NORMAL) === $priority)>{{ $priority }}</option>
                        @endforeach
                    </select>
                    <span class="inline-note">Ưu tiên được cố định theo ba mức: Thấp, Bình thường, Cao. Mặc định là Bình thường.</span>
                </label>

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

    @php
        $categorySuggestions = $categories
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->values();
        $contactMethodSuggestions = $contactMethods
            ->map(fn ($contactMethod) => [
                'id' => $contactMethod->id,
                'name' => $contactMethod->name,
            ])
            ->values();

        $initialSelectedCustomer = $selectedCustomer
            ? [
                'id' => $selectedCustomer->id,
                'name' => $selectedCustomer->name,
                'selected_label' => $selectedCustomer->name,
                'contact_preview' => collect([
                    $selectedCustomer->representative_name ? "Đại diện: {$selectedCustomer->representative_name}" : null,
                    $selectedCustomer->phone ? "SĐT: {$selectedCustomer->phone}" : null,
                    $selectedCustomer->email ? "Email: {$selectedCustomer->email}" : null,
                ])->filter()->implode(' | '),
                'license_preview' => $selectedCustomer->license_count ? (string) $selectedCustomer->license_count : null,
            ]
            : null;
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const categorySuggestions = @js($categorySuggestions);
            const contactMethodSuggestions = @js($contactMethodSuggestions);
            const initialSelectedCustomer = @js($initialSelectedCustomer);
            const customerSearchUrl = @js(route('customers.search'));
            const searchInput = document.getElementById('customer_search');
            const customerIdInput = document.getElementById('customer_id');
            const customerNameInput = document.getElementById('customer_name');
            const customerContactPreview = document.getElementById('customer_contact_preview');
            const customerLicensePreview = document.getElementById('customer_license_preview');
            const results = document.getElementById('customer_results');
            const selected = document.getElementById('customer_selected');
            const actions = document.getElementById('customer_actions');
            const clearButton = document.getElementById('clear_customer_selection');
            const categorySearchInput = document.getElementById('category_search');
            const categoryNameInput = document.getElementById('category_name');
            const categoryResults = document.getElementById('category_results');
            const categorySelected = document.getElementById('category_selected');
            const categoryActions = document.getElementById('category_actions');
            const clearCategoryButton = document.getElementById('clear_category_selection');
            const contactMethodSearchInput = document.getElementById('contact_method_search');
            const contactMethodInput = document.getElementById('requester_contact_method');
            const contactMethodResults = document.getElementById('contact_method_results');
            const contactMethodSelected = document.getElementById('contact_method_selected');
            const contactMethodActions = document.getElementById('contact_method_actions');
            const clearContactMethodButton = document.getElementById('clear_contact_method_selection');
            let searchController = null;
            let searchTimer = null;
            let selectedCategory = null;
            let selectedContactMethod = null;

            const normalizeText = (value) => value
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/đ/g, 'd')
                .replace(/Đ/g, 'D')
                .toLowerCase()
                .trim();

            const setPreviewValue = (element, value) => {
                if (!element) {
                    return;
                }

                element.value = value;
            };

            const oldCustomerId = customerIdInput.value;
            let selectedCustomer = oldCustomerId ? initialSelectedCustomer : null;
            const initialCategoryValue = categoryNameInput.value.trim();
            const initialContactMethodValue = contactMethodInput.value.trim();

            if (initialCategoryValue) {
                const matchedCategory = categorySuggestions.find((category) => normalizeText(category.name) === normalizeText(initialCategoryValue));

                if (matchedCategory) {
                    selectedCategory = matchedCategory;
                }
            }

            if (initialContactMethodValue) {
                const matchedContactMethod = contactMethodSuggestions.find((contactMethod) => normalizeText(contactMethod.name) === normalizeText(initialContactMethodValue));

                if (matchedContactMethod) {
                    selectedContactMethod = matchedContactMethod;
                }
            }

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

            const renderCategoryResults = (matches) => {
                if (!matches.length || selectedCategory) {
                    categoryResults.style.display = 'none';
                    categoryResults.innerHTML = '';

                    return;
                }

                categoryResults.innerHTML = matches.map((category, index) => `
                    <button type="button" class="search-result" data-category-index="${index}">
                        <strong>${category.name}</strong>
                        <span>Loại ticket đã có</span>
                    </button>
                `).join('');
                categoryResults.style.display = 'grid';

                categoryResults.querySelectorAll('[data-category-index]').forEach((button, index) => {
                    button.addEventListener('click', () => {
                        selectedCategory = matches[index];
                        renderSelectedCategory();
                    });
                });
            };

            const renderSelectedCategory = () => {
                if (!selectedCategory) {
                    categorySelected.style.display = 'block';
                    categorySelected.textContent = categorySearchInput.value.trim()
                        ? 'Sẽ tạo loại ticket mới theo nội dung bạn nhập nếu chưa có loại trùng.'
                        : 'Gõ để tìm nhanh loại ticket đã có hoặc nhập tên mới.';
                    categoryActions.style.display = 'none';
                    categoryResults.style.display = 'none';
                    categoryResults.innerHTML = '';
                    categoryNameInput.value = categorySearchInput.value.trim();

                    return;
                }

                categorySelected.style.display = 'block';
                categorySelected.textContent = `Đã chọn loại ticket: ${selectedCategory.name}`;
                categoryActions.style.display = 'flex';
                categorySearchInput.value = selectedCategory.name;
                categoryNameInput.value = selectedCategory.name;
                categoryResults.style.display = 'none';
                categoryResults.innerHTML = '';
            };

            const renderContactMethodResults = (matches) => {
                if (!matches.length || selectedContactMethod) {
                    contactMethodResults.style.display = 'none';
                    contactMethodResults.innerHTML = '';

                    return;
                }

                contactMethodResults.innerHTML = matches.map((contactMethod, index) => `
                    <button type="button" class="search-result" data-contact-method-index="${index}">
                        <strong>${contactMethod.name}</strong>
                        <span>Phương thức liên hệ đã có</span>
                    </button>
                `).join('');
                contactMethodResults.style.display = 'grid';

                contactMethodResults.querySelectorAll('[data-contact-method-index]').forEach((button, index) => {
                    button.addEventListener('click', () => {
                        selectedContactMethod = matches[index];
                        renderSelectedContactMethod();
                    });
                });
            };

            const renderSelectedContactMethod = () => {
                if (!selectedContactMethod) {
                    contactMethodSelected.style.display = 'block';
                    contactMethodSelected.textContent = contactMethodSearchInput.value.trim()
                        ? 'Sẽ tạo phương thức liên hệ mới theo nội dung bạn nhập nếu chưa có tag trùng.'
                        : 'Gõ để tìm nhanh phương thức liên hệ đã có hoặc nhập tên mới.';
                    contactMethodActions.style.display = 'none';
                    contactMethodResults.style.display = 'none';
                    contactMethodResults.innerHTML = '';
                    contactMethodInput.value = contactMethodSearchInput.value.trim();

                    return;
                }

                contactMethodSelected.style.display = 'block';
                contactMethodSelected.textContent = `Đã chọn phương thức liên hệ: ${selectedContactMethod.name}`;
                contactMethodActions.style.display = 'flex';
                contactMethodSearchInput.value = selectedContactMethod.name;
                contactMethodInput.value = selectedContactMethod.name;
                contactMethodResults.style.display = 'none';
                contactMethodResults.innerHTML = '';
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

            categorySearchInput.addEventListener('input', () => {
                const keyword = categorySearchInput.value.trim();

                if (selectedCategory && normalizeText(keyword) !== normalizeText(selectedCategory.name)) {
                    selectedCategory = null;
                }

                categoryNameInput.value = keyword;

                if (!keyword) {
                    renderCategoryResults([]);
                    renderSelectedCategory();

                    return;
                }

                const normalizedKeyword = normalizeText(keyword);
                const matches = categorySuggestions
                    .filter((category) => normalizeText(category.name).includes(normalizedKeyword))
                    .slice(0, 6);

                renderSelectedCategory();
                renderCategoryResults(matches);
            });

            contactMethodSearchInput.addEventListener('input', () => {
                const keyword = contactMethodSearchInput.value.trim();

                if (selectedContactMethod && normalizeText(keyword) !== normalizeText(selectedContactMethod.name)) {
                    selectedContactMethod = null;
                }

                contactMethodInput.value = keyword;

                if (!keyword) {
                    renderContactMethodResults([]);
                    renderSelectedContactMethod();

                    return;
                }

                const normalizedKeyword = normalizeText(keyword);
                const matches = contactMethodSuggestions
                    .filter((contactMethod) => normalizeText(contactMethod.name).includes(normalizedKeyword))
                    .slice(0, 6);

                renderSelectedContactMethod();
                renderContactMethodResults(matches);
            });

            clearButton.addEventListener('click', () => {
                selectedCustomer = null;
                searchInput.value = '';
                customerNameInput.value = '';
                renderSelectedCustomer();
                searchInput.focus();
            });

            clearCategoryButton.addEventListener('click', () => {
                selectedCategory = null;
                categorySearchInput.value = '';
                categoryNameInput.value = '';
                renderSelectedCategory();
                categorySearchInput.focus();
            });

            clearContactMethodButton.addEventListener('click', () => {
                selectedContactMethod = null;
                contactMethodSearchInput.value = '';
                contactMethodInput.value = '';
                renderSelectedContactMethod();
                contactMethodSearchInput.focus();
            });

            document.addEventListener('click', (event) => {
                if (results.contains(event.target) || event.target === searchInput) {
                    return;
                }

                results.style.display = 'none';

                if (categoryResults.contains(event.target) || event.target === categorySearchInput) {
                    return;
                }

                categoryResults.style.display = 'none';

                if (contactMethodResults.contains(event.target) || event.target === contactMethodSearchInput) {
                    return;
                }

                contactMethodResults.style.display = 'none';
            });

            renderSelectedCustomer();
            renderSelectedCategory();
            renderSelectedContactMethod();
        });
    </script>
@endsection
