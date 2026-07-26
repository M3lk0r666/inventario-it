# Diagrama Entidad-Relación — Inventario TI

Generado en Fase 1. Las tablas de framework (users, cache, jobs, permisos Spatie, activity_log) se omiten del diagrama salvo `users` donde participa en relaciones de negocio.

```mermaid
erDiagram
    %% ===== Catálogos =====
    departments ||--o{ employees : "tiene"
    locations ||--o{ employees : "ubica"
    locations ||--o{ assets : "ubica"
    locations ||--o{ consumables : "almacena"
    manufacturers ||--o{ asset_models : "fabrica"
    asset_types ||--o{ asset_models : "clasifica"
    asset_types ||--o{ assets : "clasifica"
    asset_statuses ||--o{ assets : "estado"
    asset_models ||--o{ assets : "modelo"
    suppliers ||--o{ assets : "vende"
    suppliers ||--o{ licenses : "vende"
    suppliers ||--o{ consumables : "surte"
    license_types ||--o{ licenses : "tipo"
    problem_categories ||--o{ problems : "categoriza"

    %% ===== Personas =====
    users ||--o| employees : "cuenta de acceso"
    employees ||--o{ employee_accounts : "cuentas corporativas"

    %% ===== Activos y asignaciones =====
    assets ||--o{ assignments : "histórico"
    employees ||--o{ assignments : "recibe"
    employees ||--o{ responsive_letters : "firma"
    responsive_letters ||--o{ assignments : "ampara"
    users ||--o{ assignments : "registra"

    %% ===== Consumibles =====
    consumables ||--o{ consumable_movements : "kardex"
    employees ||--o{ consumable_movements : "destinatario"
    users ||--o{ consumable_movements : "registra"

    %% ===== Licencias =====
    licenses ||--o{ license_assignments : "asientos"
    assets ||--o{ license_assignments : "instalada en (morph)"
    employees ||--o{ license_assignments : "asignada a (morph)"

    %% ===== Soporte =====
    assets ||--o{ problems : "incidencias"
    problems ||--o{ problem_notes : "seguimiento"
    users ||--o{ problems : "crea / atiende"
    users ||--o{ problem_notes : "escribe"

    %% ===== Herramientas =====
    users ||--o{ reminders : "autor"
    kb_categories ||--o{ kb_articles : "agrupa"
    users ||--o{ kb_articles : "autor"

    %% ===== Transversales =====
    assets ||--o{ attachments : "adjuntos (morph)"
    problems ||--o{ attachments : "adjuntos (morph)"
    licenses ||--o{ attachments : "adjuntos (morph)"

    departments { bigint id PK; string name UK; string code }
    locations { bigint id PK; string name UK; string address }
    manufacturers { bigint id PK; string name UK }
    asset_types { bigint id PK; string name UK; string slug UK; json spec_fields }
    asset_statuses { bigint id PK; string name UK; string slug UK; bool is_assignable }
    asset_models { bigint id PK; string name; bigint manufacturer_id FK; bigint asset_type_id FK }
    suppliers { bigint id PK; string name UK; string rfc; string email }
    license_types { bigint id PK; string name UK }
    problem_categories { bigint id PK; string name UK }

    employees { bigint id PK; string employee_number UK; string name; enum status; bigint user_id FK }
    employee_accounts { bigint id PK; bigint employee_id FK; enum account_type; string identifier; enum status }

    assets { bigint id PK; string asset_tag UK; string serial_number; json specs; date warranty_expires_at; decimal purchase_cost }
    assignments { bigint id PK; bigint asset_id FK; bigint employee_id FK; bigint responsive_letter_id FK; date assigned_at; date returned_at }
    responsive_letters { bigint id PK; string folio UK; bigint employee_id FK; enum status; string pdf_path }

    consumables { bigint id PK; string name; uint stock; uint min_stock }
    consumable_movements { bigint id PK; bigint consumable_id FK; enum type; uint quantity; datetime moved_at }

    licenses { bigint id PK; string software_name; uint seats; date expires_at }
    license_assignments { bigint id PK; bigint license_id FK; string assignable_type; bigint assignable_id; date released_at }

    problems { bigint id PK; bigint asset_id FK; enum priority; enum status; decimal cost }
    problem_notes { bigint id PK; bigint problem_id FK; longtext body }

    reminders { bigint id PK; datetime starts_at; datetime ends_at; enum visibility }
    kb_categories { bigint id PK; string name UK; string slug UK }
    kb_articles { bigint id PK; string slug UK; longtext body; bool is_published }
    attachments { bigint id PK; string attachable_type; bigint attachable_id; string file_path }
    settings { bigint id PK; string key UK; text value }
```

## Convenciones

- Toda entidad de negocio importante usa **SoftDeletes** (los kardex/notas/adjuntos no, por ser registros históricos inmutables).
- Auditoría con **spatie/activitylog** vía trait `App\Models\Concerns\TracksActivity` en: Asset, Employee, EmployeeAccount, Assignment, ResponsiveLetter, Consumable, License, Problem, Supplier, KbArticle.
- `assets.specs` (JSON) guarda campos dinámicos por tipo; la **definición** de esos campos vive en `asset_types.spec_fields`.
- Asignación activa = `assignments.returned_at IS NULL`. Asiento de licencia en uso = `license_assignments.released_at IS NULL`.
- `license_assignments.assignable` y `attachments.attachable` son polimórficos.
