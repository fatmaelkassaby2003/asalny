@php
    $user = filament()->auth()->user();
@endphp

<div style="height: 120px; display: flex; align-items: center; justify-content: center;">
    <x-filament-widgets::widget style="height: 100%; width: 100%;">
        <x-filament::section style="height: 100%; display: flex; align-items: center; justify-content: center;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 1.5rem;">
                <form action="{{ filament()->getLogoutUrl() }}" method="post">
                    @csrf
                    <x-filament::button
                        color="gray"
                        icon="heroicon-m-arrow-left-on-rectangle"
                        type="submit"
                        size="sm"
                    >
                        تسجيل الخروج
                    </x-filament::button>
                </form>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="text-align: right;">
                        <h2 style="font-size: 1.1rem; font-weight: 700; margin: 0;">مرحباً</h2>
                        <p style="font-size: 0.75rem; color: #6b7280; margin: 0;">{{ filament()->getUserName($user) }}</p>
                    </div>
                    <x-filament-panels::avatar.user :user="$user" size="md" />
                </div>
            </div>
        </x-filament::section>
    </x-filament-widgets::widget>
</div>
