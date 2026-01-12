<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class CustomAccountWidget extends Widget
{
    protected static string $view = 'vendor.filament.widgets.account-widget';
    
    protected int | string | array $columnSpan = 1;
}
