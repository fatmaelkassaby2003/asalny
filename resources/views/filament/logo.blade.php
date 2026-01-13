@php
    $isLoginPage = request()->routeIs('filament.admin.auth.login') || !auth()->check();
@endphp

@if($isLoginPage)
    {{-- Login Page: Only Logo Image --}}
    <img src="{{ asset('images/WhatsApp Image 2025-11-13 at 3.19.03 AM 1.png') }}" alt="Asalny Logo" style="height: 60px; width: auto;">
@else
    {{-- Dashboard Sidebar: Only Asalny Text (No Logo Image) --}}
    <div style="font-size: 28px; font-weight: 800; letter-spacing: 1px; direction: ltr;">
        <span style="color: #3b82f6;">As</span><span style="color: #ffffff;">alny</span>
    </div>
@endif
