@extends('layouts.app')

@section('title', 'Chỉnh sửa khách hàng | ticketSP')

@section('body')
    <main class="shell stack">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">IT</div>
                <div>
                    <h1>Chỉnh sửa khách hàng</h1>
                    <p>Cập nhật hồ sơ liên hệ và license cho {{ $customer->name }}.</p>
                </div>
            </div>
        </header>

        @if ($errors->any())
            <div class="flash flash-error">{{ $errors->first() }}</div>
        @endif

        <section class="card panel stack">
            <div>
                <h2 class="section-title">Thông tin khách hàng</h2>
                <p class="section-copy">Giữ dữ liệu khách gọn và nhất quán để tra cứu nhanh khi tạo hoặc xử lý ticket.</p>
            </div>

            <form method="POST" action="{{ route('customers.update', $customer) }}" class="stack">
                @include('customers.partials.form', [
                    'customer' => $customer,
                    'method' => 'PATCH',
                    'submitLabel' => 'Lưu thay đổi',
                ])
            </form>
        </section>
    </main>
@endsection
