{{--
    Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
--}}
<x-filament-panels::page class="fi-dashboard-page">
    <x-filament::section
        heading="Indicadores PEL Quispicanchi al 2036"
        description="Progresión histórica 2022-2026 por distrito, consolidado provincial (incluye Lucre y Oropesa)."
    >
        <x-filament-widgets::widgets
            :columns="$this->getColumns()"
            :widgets="$this->getIndicatorWidgets()"
        />
    </x-filament::section>

    <x-filament::section
        heading="Censo ESCALE de Instituciones Educativas"
        description="Última carga del padrón de IIEE censadas por la UGEL Quispicanchi (10 distritos)."
    >
        <x-filament-widgets::widgets
            :columns="$this->getColumns()"
            :widgets="$this->getCensusWidgets()"
        />
    </x-filament::section>
</x-filament-panels::page>
