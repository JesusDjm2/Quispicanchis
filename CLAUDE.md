# PEL Quispicanchi al 2036

Sistema de **indicadores educativos** para la provincia de Quispicanchi (Cusco, Perú), construido para el contrato del proyecto "PEL Quispicanchi al 2036" entre **Edutalento** (LA COMITENTE) y la entidad contratante. Centraliza datos dispersos de UGEL/ESCALE/INEI/MIDIS y genera reportes oficiales (Excel y Word) con la progresión histórica 2022-2026 de cada indicador, distrito por distrito.

> Nota legal: casi todos los archivos del código llevan el docblock *"Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Cláusula Octava del contrato."* — mantenlo al crear archivos nuevos del dominio del negocio.

## Para qué sirve (resumen funcional)

1. **Carga datos** de indicadores educativos/sociales por distrito y año (2022-2026), desde Excel/CSV o formulario manual, con su fuente oficial (UGEL, ESCALE, INEI, MIDIS).
2. **Carga el padrón ESCALE de Instituciones Educativas** (censo anual: alumnos, docentes, secciones por IE y nivel/modalidad), para estadísticas y demografía a nivel de institución, no solo de distrito.
3. **Visualiza** todo en un panel admin (Filament) con stats y gráficos (Chart.js): completitud de datos, tendencias provinciales, comparativos por distrito, alumnos por nivel/gestión.
4. **Exporta** reportes oficiales en Excel y Word (.docx) con la estructura exigida por el contrato (tabla de progresión histórica + leyenda de fuente).

## Stack

- Laravel 12 (PHP 8.2+), MySQL (dev: XAMPP, BD `quispicanchis`, ver `.env`).
- **Filament 3** como panel admin (único frontend; no hay SPA/API pública).
- `maatwebsite/excel` para import/export de Excel/CSV.
- `phpoffice/phpword` para generar el reporte Word.
- Acceso de un solo usuario: `App\Http\Middleware\AutoLoginAdmin` autentica automáticamente sin pantalla de login (uso unipersonal, por diseño, no un bug).

## Modelo de datos

### Núcleo: indicadores por distrito/año

- **District** (`districts`): los **10 distritos** atendidos por la UGEL Quispicanchi (`DistrictSeeder`). *Lucre y Oropesa pertenecen políticamente a la provincia pero los atiende otra UGEL y no aparecen en los censos ESCALE de UGEL Quispicanchi — se excluyen a propósito.*
- **Indicator** (`indicators`): catálogo de indicadores (Matrícula Escolar, logros de aprendizaje, deserción, docentes, pobreza, etc.) — `IndicatorSeeder`.
- **DataRecord** (`data_records`): el valor de un `Indicator` para un `District` en un `year` (2022-2026, ver `DataRecord::MIN_YEAR`/`MAX_YEAR`), con su `source` (`DataRecord::SOURCES`). Único por `(district_id, indicator_id, year)`.

### Censo ESCALE por institución (alumnos/docentes/secciones)

- **EducationalInstitution** (`educational_institutions`): una IE física, agrupada por `local_code` (Código de local de ESCALE) cuando existe; si no, por nombre+distrito+centro poblado. Pertenece a un `District`.
- **InstitutionLevelCensus** (`institution_level_census`): el censo de un nivel/modalidad de esa IE (Inicial, Primaria, Secundaria, Básica Especial, etc.) en un `census_year`: `students`, `teachers`, `sections`. Único por `(modular_code, census_year)`. Celdas no reportadas quedan `NULL` (no `0`), para no distorsionar promedios/ratios.

## Imports / Exports (`app/Imports`, `app/Exports`, `app/Services`)

| Clase | Qué hace |
|---|---|
| `DataRecordsImport` | Excel genérico con cabecera `Distrito\|Indicador\|Unidad\|Año\|Valor\|Fuente`, una fila = un `DataRecord`. |
| `MatriculaConsolidadaImport` | Excel consolidado de matrícula UGEL/ESCALE: una fila por IE, **suma** por distrito+año hacia un `Indicator` elegido en el formulario. Detecta la fila de cabecera real buscando la columna "Distrito" (tolera filas de título antes). |
| `EscaleInstitutionsImport` | Padrón ESCALE "Instituciones": una fila por IE x Nivel/Modalidad. Filtra solo provincia Quispicanchi, agrupa niveles bajo la misma IE, año de censo se indica en el formulario (el archivo no lo trae). |
| `app/Imports/Concerns/ResolvesDistricts.php` | Trait compartido: resuelve el nombre de distrito de una fila contra los `District` existentes, tolerando tildes y errores de tipeo (Levenshtein ≤2). Lo usan `MatriculaConsolidadaImport` y `EscaleInstitutionsImport`. |
| `DataRecordsExport` | Export Excel de todos los `DataRecord`. |
| `ExportService` | Genera el reporte Word oficial por indicador: tabla de progresión histórica 2022-2026 por distrito + leyenda "Fuente: UGEL Quispicanchi / ESCALE \| Elaboración: Edutalento". El gráfico de tendencia queda como placeholder de texto (se pega manualmente desde el widget Chart.js). |

## Panel Filament (`app/Filament`)

- **Resources**: `DistrictResource`, `IndicatorResource`, `DataRecordResource` (con acciones de import Excel/matrícula y export Excel/Word en `ListDataRecords`), `EducationalInstitutionResource` (con acción "Importar censo ESCALE" y un RelationManager `LevelCensusesRelationManager` para ver/editar los niveles censados de cada IE).
- **Widgets** (auto-descubiertos por `AdminPanelProvider`, no requieren registro manual): `DataOverviewStats`, `IndicatorCompletenessTable`, `IndicatorTrendChart`, `DistrictComparisonChart` (indicadores genéricos) + `InstitutionCensusOverviewStats`, `StudentsByDistrictChart`, `StudentsByLevelChart`, `ManagementTypeChart` (censo ESCALE por institución).
- Color primario del panel: ámbar (`#d97706` en los gráficos, consistente en todos los widgets).

## Convenciones a seguir

- Docblocks y mensajes de UI en **español**, con tono formal de informe institucional.
- Todo archivo de dominio (modelos, imports, exports, resources, widgets) lleva el docblock legal de propiedad de Edutalento.
- Períodos y enums de negocio (años 2022-2026, las 4 fuentes UGEL/ESCALE/INEI/MIDIS, los 10 distritos) son **requisitos contractuales**, no detalles técnicos — no los cambies sin confirmar con el usuario.
- Antes de extender un importador, revisa si el trait `ResolvesDistricts` ya cubre lo que necesitas en vez de duplicar lógica de resolución de distrito.

## Comandos útiles

```bash
php artisan migrate          # aplicar migraciones (MySQL en XAMPP debe estar corriendo)
php artisan db:seed          # distritos + indicadores + admin por defecto
composer dev                 # serve + queue + logs + vite, todo junto
```
