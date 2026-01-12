@php
    $user = filament()->auth()->user();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem;">
            <!-- Left: Logout Button -->
            <form action="{{ filament()->getLogoutUrl() }}" method="post" style="margin: 0;">
                @csrf
                <x-filament::button
                    color="gray"
                    icon="heroicon-m-arrow-left-on-rectangle"
                    icon-position="before"
                    type="submit"
                    size="sm"
                >
                    تسجيل الخروج
                </x-filament::button>
            </form>
            
            <!-- Right: User Info -->
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="text-align: right;">
                    <h2 style="font-size: 1.1rem; font-weight: 700; margin: 0; color: #1f2937;">مرحباً</h2>
                    <p style="font-size: 0.8rem; color: #6b7280; margin: 0;">{{ filament()->getUserName($user) }}</p>
                </div>
                <x-filament-panels::avatar.user :user="$user" size="lg" />
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
