@extends('layouts.app')

@section('title', 'Thêm nhân viên | ticketSP')

@section('body')
    <main class="shell stack">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">IT</div>
                <div>
                    <h1>Thêm nhân viên</h1>
                    <p>Tạo hồ sơ liên hệ nội bộ cho thành viên support.</p>
                </div>
            </div>
        </header>

        @if ($errors->any())
            <div class="flash flash-error">{{ $errors->first() }}</div>
        @endif

        <section class="card panel stack">
            <div>
                <h2 class="section-title">Thông tin nhân viên</h2>
                <p class="section-copy">Lưu các trường cơ bản để dùng trong quản lý đội support.</p>
            </div>

            <form method="POST" action="{{ route('employees.store') }}" class="stack">
                @include('employees.partials.form', [
                    'employee' => $employee,
                    'contactMethods' => $contactMethods,
                    'method' => 'POST',
                    'submitLabel' => 'Lưu nhân viên',
                ])
            </form>
        </section>
    </main>
@endsection
