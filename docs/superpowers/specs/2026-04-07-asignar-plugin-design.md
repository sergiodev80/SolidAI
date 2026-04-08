# Plugin Asignar — Diseño

**Fecha:** 2026-04-07  
**Estado:** Aprobado

---

## Resumen

Plugin Filament v5 independiente que permite asignar traductores y revisores a los documentos de un presupuesto. Accesible vía URL `/asignar/{id_presup}` y desde el menú lateral del panel.

---

## Arquitectura

### Estructura de carpetas

```
app/Filament/Plugins/Asignar/
├── Asignar.php                  # Plugin principal
└── Pages/
    └── AsignarPage.php          # Página Filament (recibe {id_presup})
```

### Namespace

`App\Filament\Plugins\Asignar`

### Modelos involucrados

| Modelo | Tabla | BD | Descripción |
|---|---|---|---|
| `Presupuesto` | `presupuestos` | erp | Ya existe |
| `PresupAdj` | `presup_adj` | erp | Ya existe — documentos del presupuesto |
| `SeccUser` | `secc_users` | erp | Ya existe — traductores/revisores |
| `PresupAdjAsignacion` | `presup_adj_asignaciones` | erp | **Nuevo** |

---

## Base de datos

### Nueva tabla: `presup_adj_asignaciones` (BD: `erp`)

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT PK AI | Primary key |
| `id_adjun` | BIGINT | FK → `presup_adj.id_adjun` |
| `login` | VARCHAR | FK → `secc_users.login` |
| `rol` | ENUM | `'traductor'` \| `'revisor'` |
| `pag_inicio` | INT | Página de inicio del rango |
| `pag_fin` | INT | Página de fin del rango |
| `estado` | ENUM | `'Asignado'` \| `'En Traducción'` \| `'En Revisión'` \| `'Aceptado'` \| `'Impreso'` \| `'Entregado'` |
| `created_at` | TIMESTAMP | Fecha de asignación |

- Sin `updated_at`
- Índice: `(id_adjun, rol)`
- La migración usará `DB::connection('erp')` o `Schema::connection('erp')`

### Modelo `PresupAdjAsignacion`

```php
protected $connection = 'erp';
protected $table = 'presup_adj_asignaciones';
protected $primaryKey = 'id';
public $timestamps = false; // solo created_at manual
```

---

## UI — Página principal (`AsignarPage`)

- Página Filament independiente (`extends Page`) en el menú lateral, grupo "Asignar"
- Ruta: `/asignar/{id_presup}`
- Carga el `Presupuesto` y sus `PresupAdj` con sus `asignaciones`
- Muestra cabecera con ID y nombre del presupuesto

### Cards de documentos

Una card por cada documento en `presup_adj` del presupuesto:

- **Cabecera de card:** nombre del archivo (extraído del blob `adjun_adjun`) + botón **"+ Asignar"**
- **Borde izquierdo de color:**
  - Azul: todos los documentos con al menos un traductor y un revisor
  - Amarillo: parcialmente asignado
  - Rojo: sin asignaciones
- **Cuerpo de card:** dos columnas — Traductores | Revisores
- Cada asignación muestra: `Nombre · p.X–Y` + badge de estado + botón **✕**
- El botón ✕ elimina la asignación con delete directo (sin soft delete)
- Si no hay asignaciones: texto "sin asignar" en gris

### Extracción del nombre del archivo

El nombre se extrae del blob `adjun_adjun`. La lógica de extracción se encapsula en un método del modelo `PresupAdj` (`getNombreAttribute()` o similar).

---

## UI — Modal de asignación

Se abre al hacer clic en "+ Asignar" de cualquier card. Implementado con `Action` de Filament v5 (`Action::make()->form()->action()`).

### Campos del modal

| Campo | Tipo | Detalle |
|---|---|---|
| Rol | Toggle (2 opciones) | Traductor / Revisor |
| Usuario | Select con búsqueda | Sobre `secc_users.name`, valor es `login` |
| Página inicio | Número | Mínimo 1 |
| Página fin | Número | ≥ página inicio |

### Comportamiento al guardar

1. Inserta en `presup_adj_asignaciones` con `estado = 'Asignado'` y `created_at = now()`
2. Cierra el modal
3. Refresca la card del documento correspondiente (Livewire `$refresh` o `dispatch`)

---

## Registro en AdminPanelProvider

```php
->plugins([
    \App\Filament\Plugins\Asignar\Asignar::make(),
])
```

---

## Fuera de alcance (este sprint)

- Cambio de estado de las asignaciones (flujo posterior)
- Edición de una asignación existente (solo crear y eliminar)
- Notificaciones a traductores/revisores
