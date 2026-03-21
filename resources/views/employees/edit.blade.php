@extends('layouts.app')

@section('title', 'Chỉnh sửa nhân viên | ticketSP')

@section('body')
    <main class="shell stack">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">IT</div>
                <div>
                    <h1>Chỉnh sửa nhân viên</h1>
                    <p>Cập nhật hồ sơ liên hệ cho {{ $employee->display_name }}.</p>
                </div>
            </div>
        </header>

        @if ($errors->any())
            <div class="flash flash-error">{{ $errors->first() }}</div>
        @endif

        <section class="card panel stack">
            <div>
                <h2 class="section-title">Thông tin nhân viên</h2>
                <p class="section-copy">Giữ dữ liệu gọn và dễ tra cứu trong nội bộ.</p>
            </div>

            <form method="POST" action="{{ route('employees.update', $employee) }}" class="stack">
                @include('employees.partials.form', [
                    'employee' => $employee,
                    'contactMethods' => $contactMethods,
                    'method' => 'PATCH',
                    'submitLabel' => 'Lưu thay đổi',
                ])
            </form>
        </section>
    </main>
@endsection
