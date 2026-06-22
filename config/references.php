<?php

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Catalogo de referencias bibliograficas en formato APA 7.a edicion para cada
 * fuente oficial admitida por el contrato (ver App\Models\DataRecord::SOURCES).
 * Estas citas son fijas por contrato y no se editan desde el panel Filament;
 * un cambio aqui aplica de inmediato a todos los reportes Word generados.
 */
return [

    'UGEL' => 'Unidad de Gestión Educativa Local Quispicanchi. (2026). '
        .'Padrón de instituciones educativas y registros de matrícula escolar '
        .'[Base de datos]. UGEL Quispicanchi.',

    'ESCALE' => 'Ministerio de Educación del Perú. (2026). '
        .'Estadística de la Calidad Educativa (ESCALE) [Base de datos]. '
        .'http://escale.minedu.gob.pe',

    'INEI' => 'Instituto Nacional de Estadística e Informática. (2026). '
        .'Sistema de Información Regional para la Toma de Decisiones [Base de datos]. '
        .'https://www.inei.gob.pe',

    'MIDIS' => 'Ministerio de Desarrollo e Inclusión Social. (2026). '
        .'Sistema de Focalización de Hogares (SISFOH) [Base de datos]. '
        .'https://www.midis.gob.pe',

    'UGEL QUISPICANCHI / ESCALE' => [
        'UGEL',
        'ESCALE',
    ],

];
