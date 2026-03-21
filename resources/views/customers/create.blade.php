@extends('layouts.app')

@section('title', 'Thêm khách hàng | ticketSP')

@section('body')
    <main class="shell stack">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">IT</div>
                <div>
                    <h1>Thêm khách hàng</h1>
                    <p>Tạo hồ sơ khách để chuẩn hóa thông tin liên hệ phục vụ đội support.</p>
                </div>
            </div>
        </header>

        @if ($errors->any())
            <div class="flash flash-error">{{ $errors->first() }}</div>
        @endif

        <section class="card panel stack">
            <div>
                <h2 class="section-title">Thông tin khách hàng</h2>
                <p class="section-copy">Tên là trường bắt buộc, các trường còn lại giúp support theo dõi liên hệ và license.</p>
            </div>

            <form method="POST" action="{{ route('customers.store') }}" class="stack">
                @include('customers.partials.form', [
                    'customer' => $customer,
                    'method' => 'POST',
                    'submitLabel' => 'Lưu khách hàng',
                ])
            </form>
        </section>
    </main>
@endsection
