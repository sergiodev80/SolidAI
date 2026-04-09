# Especificación: Plugin Traduccion

**Fecha**: 2026-04-08  
**Estado**: En diseño  
**Autor**: SolidAI Translation System

---

## 1. Resumen Ejecutivo

El plugin **Traduccion** proporciona una interfaz fullscreen de 3 paneles para que traductores editen y justifiquen cambios en documentos traducidos automáticamente por Azure (Doc Intelligence + Translator).

**Ruta**: `/traduccion/{id_asignacion}`

**Flujo**:
1. Descargar PDF desde FTP
2. Procesar con Azure (Doc Intelligence → Translator → Word)
3. Mostrar interfaz de 3 paneles
4. Traductor edita libremente
5. Sistema detecta cambios (diff)
6. Traductor justifica cada cambio
7. Enviar para revisión → Estado = "En Revisión"

---

## 2. Requisitos Funcionales

### 2.1 Autenticación y Acceso

- **Ruta protegida**: Solo usuarios autenticados pueden acceder
- **Validación de permisos**: Solo el traductor asignado (login en `PresupAdjAsignacion`) puede acceder a su asignación
- **Cambio de estado automático**: Al cargar la página, cambiar estado de "Asignado" a "En Traducción"

### 2.2 Descarga y Procesamiento Inicial

**Entrada**: `id_asignacion` (ID de la asignación en BD)

**Datos obtenidos de BD**:
- `PresupAdjAsignacion`:
  - `id_asignacion`
  - `id_adjun` (referencia al documento)
  - `login` (traductor asignado)
  - `id_idiom_original` (idioma fuente)
  - `id_idiom` (idioma destino)
  - `pag_inicio` / `pag_fin` (páginas asignadas)
  - `estado` → actualizar a "En Traducción"

- `PresupAdj`:
  - `nombre_archivo` (ej: "contrato_servicios.pdf")
  - Path o ubicación en FTP

**Procesamiento**:

1. **Descargar desde FTP**: Obtener PDF del servidor externo según configuración FTP
2. **Azure Doc Intelligence**: Extraer texto, estructura y layout del PDF
3. **Azure Translator**: Traducir contenido extraído de `id_idiom_original` a `id_idiom`
4. **Convertir a Word**: Generar documento `.docx` con contenido traducido
5. **Guardar V1**: Persistir como `documento_V1.docx` en:
   ```
   app/public/archivos/traducciones/{id_asignacion}/documento_V1.docx
   ```
6. **Crear metadata**: Archivo `metadata.json` con datos de la asignación

**Errores**:
- Si FTP falla: Mostrar error y permitir reintentar
- Si Azure falla: Mostrar error específico (Doc Intelligence o Translator)
- Si conversión a Word falla: Notificar al usuario

### 2.3 Interfaz de 3 Paneles (Fullscreen)

**Layout**:
```
┌─ HEADER (Filament header mínimo) ──────────────────────────────┐
├────────────────────────────────────────────────────────────────┤
│  ┌─ PANEL IZQUIERDO ─┬─ PANEL CENTRAL ─────┬─ PANEL DERECHO ─┐│
│  │  Original (PDF)   │  Editor OnlyOffice   │  Hojas + Cambios││
│  │  35% ancho        │  50% ancho           │  15% ancho      ││
│  └───────────────────┴──────────────────────┴──────────────────┘│
│  ┌─ FOOTER: Botones de acción ──────────────────────────────────┐│
│  │ [Guardar] [Justificar Cambios] [Enviar para Revisión]       ││
│  └──────────────────────────────────────────────────────────────┘│
└────────────────────────────────────────────────────────────────┘
```

#### 2.3.1 Panel Izquierdo - Documento Original

- **Contenido**: Visor PDF del documento original (antes de traducir)
- **Modo**: Read-only
- **Herramientas**:
  - Zoom in/out
  - Navegación de páginas
  - Número de página actual
- **Indicador de páginas asignadas**: Mostrar en header del panel "Páginas asignadas: 1-50"
- **Visualización**: Solo las páginas asignadas pueden estar resaltadas o marcadas (opcional)

#### 2.3.2 Panel Central - Editor de Traducción

- **Contenido**: Documento Word (.docx) traducido, cargado en OnlyOffice
- **Modo**: **EDITABLE**
- **Funcionalidad**:
  - El traductor edita libremente el contenido
  - Cambios de formato (negrita, cursiva, etc.) permitidos
  - Save automático o manual (con botón "Guardar")
- **Integración OnlyOffice**:
  - Usar JWT para modo editable
  - Documento servido desde `app/public/archivos/traducciones/{id_asignacion}/documento_V1.docx`
- **Indicador visual**: 
  - Mostrar "Páginas asignadas: 1-50" en el header del panel
  - Número de versión actual (ej: "V1")

#### 2.3.3 Panel Derecho - Hojas Asignadas y Cambios

**Sección 1: Páginas Asignadas**
```
┌─────────────────────────────────────┐
│ Páginas Asignadas                   │
├─────────────────────────────────────┤
│ Inicio: 1                           │
│ Fin: 50                             │
│ Total: 50 páginas                   │
└─────────────────────────────────────┘
```

**Sección 2: Cambios Detectados** (después de comparar versiones)
```
┌─────────────────────────────────────┐
│ Cambios Detectados (V1 → V2)        │
├─────────────────────────────────────┤
│ [Cambio 1]                          │
│ Original:  "palabra antigua"         │
│ Nueva:     "palabra nueva"           │
│ Posición:  Pág. 5, Párrafo 2        │
│ Justificación:                      │
│ ┌─────────────────────────────────┐ │
│ │ Texto explicativo del cambio... │ │
│ └─────────────────────────────────┘ │
│                                     │
│ [Cambio 2]                          │
│ Original:  "frase original"         │
│ Nueva:     "frase nueva"            │
│ Posición:  Pág. 10, Párrafo 1       │
│ Justificación:                      │
│ ┌─────────────────────────────────┐ │
│ │ Mejor adaptación al contexto    │ │
│ └─────────────────────────────────┘ │
│                                     │
│ ... (más cambios) ...               │
└─────────────────────────────────────┘
```

**Características**:
- Lista scrollable de cambios
- Cada cambio muestra: original, nueva, posición en documento
- Input textarea para justificación
- Marcado visual de cambios justificados vs. pendientes

### 2.4 Flujo de Edición y Versionado

**Paso 1: Guardar cambios**
- Usuario edita en OnlyOffice (Panel Central)
- Click "Guardar" → Se persiste documento con cambios
- Sistema detecta cambios desde última versión
- Crear nueva versión: `documento_V2.docx`

**Paso 2: Comparar versiones**
- Comparar `documento_V1.docx` vs `documento_V2.docx`
- Detectar cambios a nivel:
  - **Palabra**: Token-level diff
  - **Línea**: Line-level diff
  - **Párrafo**: Paragraph-level diff
- Generar archivo `cambios_V1_V2.json`

**Paso 3: Mostrar cambios en Panel Derecho**
- Renderizar lista de cambios
- Cada cambio tiene input para justificación
- Usuario completa justificaciones

**Paso 4: Guardar justificaciones**
- Al rellenar/editar justificaciones, guardar en `cambios_V1_V2.json`
- Marcar cambios como "justificado" / "pendiente"

**Paso 5: Repetir si edita nuevamente**
- Si usuario vuelve a editar → Guardar → `documento_V3.docx`
- Comparar `documento_V2.docx` vs `documento_V3.docx`
- Mostrar solo cambios nuevos (V2 → V3)

### 2.5 Envío para Revisión

**Botón**: "Enviar para Revisión"

**Validaciones**:
- Todos los cambios tienen justificación (pendiente de confirmar si es obligatorio)
- Documento tiene al menos V1

**Acciones**:
1. Cambiar estado en BD: `PresupAdjAsignacion.estado` → "En Revisión"
2. Finalizar versionado: Marcar documento final (última versión)
3. Crear notificación para revisor (trabajo futuro)
4. Redirigir a página de confirmación o volver a lista de asignaciones

---

## 3. Almacenamiento en Filesystem

```
app/public/archivos/traducciones/
└── {id_asignacion}/
    ├── documento_V1.docx
    ├── documento_V2.docx
    ├── documento_V3.docx
    ├── cambios_V1_V2.json
    ├── cambios_V2_V3.json
    └── metadata.json
```

### 3.1 Estructura de `metadata.json`

```json
{
  "id_asignacion": 123,
  "id_adjun": 456,
  "id_presup": 789,
  "nombre_archivo_original": "contrato_servicios.pdf",
  "traductor": "jdoe",
  "id_idiom_original": 1,
  "id_idiom_destino": 2,
  "pag_inicio": 1,
  "pag_fin": 50,
  "fecha_inicio": "2026-04-08T10:00:00Z",
  "fecha_actualizacion": "2026-04-08T14:30:00Z",
  "version_actual": "V3"
}
```

### 3.2 Estructura de `cambios_Vn_Vm.json`

```json
{
  "comparacion": "V1_V2",
  "cambios": [
    {
      "id": "cambio_1",
      "tipo": "palabra",
      "original": "palabra antigua",
      "nueva": "palabra nueva",
      "posicion": {
        "pagina": 5,
        "parrafo": 2,
        "linea": 3
      },
      "contexto_original": "Lorem ipsum palabra antigua dolor sit amet...",
      "contexto_nuevo": "Lorem ipsum palabra nueva dolor sit amet...",
      "justificacion": "Mejor precisión según contexto técnico",
      "estado": "justificado"
    },
    {
      "id": "cambio_2",
      "tipo": "parrafo",
      "original": "Párrafo original completo...",
      "nueva": "Párrafo nuevo completo...",
      "posicion": {
        "pagina": 10,
        "parrafo": 1,
        "linea": null
      },
      "justificacion": "",
      "estado": "pendiente"
    }
  ],
  "estadisticas": {
    "total_cambios": 42,
    "palabras_cambiadas": 35,
    "lineas_cambiadas": 5,
    "parrafos_cambiados": 2,
    "justificadas": 40,
    "pendientes": 2
  },
  "fecha_comparacion": "2026-04-08T14:35:00Z",
  "traductor": "jdoe"
}
```

---

## 4. Componentes a Implementar

### 4.1 Backend - PHP/Laravel

**Controlador Principal**: `TraduccionController.php`
- GET `/traduccion/{id_asignacion}` → Mostrar página
- POST `/traduccion/{id_asignacion}/guardar` → Guardar documento
- POST `/traduccion/{id_asignacion}/comparar` → Comparar versiones
- POST `/traduccion/{id_asignacion}/justificar` → Guardar justificaciones
- POST `/traduccion/{id_asignacion}/enviar-revision` → Cambiar estado

**Servicio**: `AzureTranslationService.php`
- `downloadFromFtp($documento)`: Descargar PDF
- `extractText($pdfPath, $language)`: Doc Intelligence
- `translate($text, $sourceLang, $targetLang)`: Azure Translator
- `convertToWord($content, $metadata)`: Crear .docx

**Servicio**: `DocumentVersionService.php`
- `createVersion($id_asignacion, $content)`: Crear nueva versión
- `getLatestVersion($id_asignacion)`: Obtener última versión
- `compareVersions($versionA, $versionB)`: Hacer diff
- `detectChanges($oldDoc, $newDoc)`: Detectar cambios (palabra, línea, párrafo)
- `saveChanges($id_asignacion, $changes)`: Persistir cambios en JSON

**Modelos**: Posiblemente extender `PresupAdjAsignacion` con métodos helper

### 4.2 Frontend - Blade/Livewire

**Vista principal**: `traduccion-page.blade.php`
- Layout fullscreen sin sidebar
- 3 paneles con grillas CSS/flexbox
- Botones de acción (footer)

**Vista - Panel Izquierdo**: `panel-original.blade.php`
- Visor PDF (PDF.js o similar)
- Indicador de páginas asignadas

**Vista - Panel Central**: `panel-traduccion.blade.php`
- Integración OnlyOffice
- Documento editable
- Número de versión

**Vista - Panel Derecho**: `panel-cambios.blade.php`
- Lista de cambios
- Inputs de justificación
- Estadísticas

### 4.3 Rutas

```php
// routes/web.php (agregar)
Route::middleware(['auth'])->group(function () {
    Route::get('/traduccion/{id_asignacion}', [TraduccionController::class, 'show'])
        ->name('traduccion.show');
    Route::post('/traduccion/{id_asignacion}/guardar', [TraduccionController::class, 'guardar'])
        ->name('traduccion.guardar');
    Route::post('/traduccion/{id_asignacion}/comparar', [TraduccionController::class, 'comparar'])
        ->name('traduccion.comparar');
    Route::post('/traduccion/{id_asignacion}/justificar', [TraduccionController::class, 'justificar'])
        ->name('traduccion.justificar');
    Route::post('/traduccion/{id_asignacion}/enviar-revision', [TraduccionController::class, 'enviarRevision'])
        ->name('traduccion.enviar-revision');
});
```

---

## 5. Flujo Técnico Detallado

```
1. Usuario accede a /traduccion/{id_asignacion}
   ↓
2. Validar: ¿User == traductor asignado?
   ↓
3. Obtener datos de BD (PresupAdjAsignacion + PresupAdj)
   ↓
4. ¿Existe V1 en filesystem?
   ├─ NO: Descargar FTP → Azure → Guardar V1
   └─ SÍ: Usar V1 existente
   ↓
5. Actualizar estado → "En Traducción"
   ↓
6. Renderizar vista con 3 paneles
   - Panel izq: PDF original (read-only)
   - Panel central: OnlyOffice con V1 (editable)
   - Panel derech: Páginas asignadas (sin cambios aún)
   ↓
7. Usuario edita en OnlyOffice
   ↓
8. Click "Guardar"
   ↓
9. Crear V2.docx
   ↓
10. Comparar V1 vs V2 → Detectar cambios
    ↓
11. Guardar cambios en cambios_V1_V2.json
    ↓
12. Mostrar cambios en Panel Derecho
    ↓
13. Usuario completa justificaciones
    ↓
14. (Opcional) Usuario edita de nuevo → V3, etc.
    ↓
15. Click "Enviar para Revisión"
    ↓
16. Validar cambios justificados
    ↓
17. Cambiar estado BD → "En Revisión"
    ↓
18. Redirigir a confirmación
```

---

## 6. Limitaciones y Consideraciones

### 6.1 OnlyOffice

- **Limitación**: No se puede mostrar solo páginas específicas dentro del editor
- **Solución**: Mostrar indicador visual en header: "Páginas asignadas: 1-50"
- **Editor integrado**: Usar iframe con JWT para modo editable

### 6.2 Tamaño de Documentos

- **Impacto**: Descarga, procesamiento Azure, comparación de documentos
- **Considerar**: Tiempos de Azure (puede tardar minutos en documentos grandes)

### 6.3 Detección de Cambios

- **Herramienta**: Comparación de Word/texto a múltiples niveles
- **Opciones**:
  - Librería PHP: `phpword` para parsear .docx
  - Librería diff: `jfcherng/php-diff` o `sebastianbergmann/diff`
  - Opción Python: Servicio externo para diff avanzado
- **Desempeño**: Optimizar para documentos de 50 páginas

### 6.4 Manejo de Errores

- **FTP fail**: Reintentar o mostrar error
- **Azure fail**: Notificar y permitir reintentar
- **OnlyOffice offline**: Fallback a editor básico (TBD)
- **Cambios no guardados**: Advertencia antes de salir

---

## 7. Estimaciones y Riesgos

| Aspecto | Riesgo | Mitigación |
|--------|--------|-----------|
| Azure latencia | Documentos de 50 páginas pueden tardar | Procesar async + notificación |
| Diff complejo | Detectar cambios exactos es difícil | Usar librería sólida de diff |
| OnlyOffice JWT | Configuración delicada | Reutilizar config existente |
| Almacenamiento | Múltiples versiones ocupan espacio | Política de limpieza (últimas 5) |

---

## 8. Trabajo Futuro

1. **Revisor**: Plugin/interfaz para revisor (similar pero read-only + aceptar/rechazar)
2. **Notificaciones**: Alertar al revisor cuando se envía para revisión
3. **Historial**: Interfaz para ver historial de versiones
4. **Glosario**: Diccionario de términos específicos del dominio
5. **Comentarios**: Permitir comentarios entre traductor y revisor
6. **Asincronía**: Procesar Azure en background con WebSockets

---

## 9. Aprobaciones y Cambios

| Fecha | Autor | Cambio |
|-------|-------|--------|
| 2026-04-08 | SolidAI | Especificación inicial |

