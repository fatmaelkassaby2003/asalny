<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AsalnyWelcomeWidget extends Widget
{
    protected static string $view = 'filament.widgets.asalny-welcome-widget';
    
    protected int | string | array $columnSpan = 1;
}
