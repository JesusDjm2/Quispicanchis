{{--
    Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.

    Copia de `filament-widgets::table-widget` que agrega al pie la leyenda
    obligatoria "Elaboración: Edutalento" exigida por el contrato.
--}}
<x-filament-widgets::widget class="fi-wi-table">
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\Widgets\View\WidgetsRenderHook::TABLE_WIDGET_START, scopes: static::class) }}

    {{ $this->table }}

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\Widgets\View\WidgetsRenderHook::TABLE_WIDGET_END, scopes: static::class) }}

    @include('filament.widgets.partials.legend')
</x-filament-widgets::widget>
