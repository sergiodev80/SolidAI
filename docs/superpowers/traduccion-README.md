# Plugin Traduccion - Guía de Implementación

## Overview

El plugin Traduccion permite a traductores editar documentos traducidos automáticamente por Azure con una interfaz de 3 paneles.

## Arquitectura

- **Página Livewire**: `TraduccionPage.php` - Punto de entrada
- **Servicios**:
  - `AzureTranslationService`: Descarga FTP + procesamiento Azure
  - `DocumentVersionService`: Versionado y detección de cambios
  - `PermissionService`: Control de acceso
  - `OnlyOfficeService`: JWT para editor
- **Controlador**: `TraduccionController` - Lógica de rutas
- **Vistas**: 3 paneles (original, traducción, cambios)

## Estado Actual

### Implementado
- ✅ Estructura base del plugin
- ✅ Servicios de FTP, Azure, versionado
- ✅ Control de acceso
- ✅ Controlador y rutas
- ✅ Página Livewire
- ✅ Vistas Blade (esqueleto)
- ✅ Tests básicos

### Pendiente (Futuro)
- 🔄 Integración completa PDF.js (panel izquierdo)
- 🔄 Integración OnlyOffice (panel central)
- 🔄 Webhooks de OnlyOffice para guardar
- 🔄 Rendering de cambios en panel derecho
- 🔄 Inputs de justificación
- 🔄 Plugin revisor (para revisores)

## Variables de Entorno

Completar en `.env`:
```env
AZURE_DOC_INTELLIGENCE_ENDPOINT=...
AZURE_DOC_INTELLIGENCE_KEY=...
AZURE_TRANSLATOR_ENDPOINT=...
AZURE_TRANSLATOR_KEY=...
AZURE_TRANSLATOR_REGION=...
FTP_HOST=...
FTP_USERNAME=...
FTP_PASSWORD=...
ONLYOFFICE_URL=...
ONLYOFFICE_JWT_SECRET=...
```

## Testing

```bash
php artisan test tests/Feature/TraduccionControllerTest.php
```

## Próximas Tareas

1. Integración PDF.js para visor de PDF original
2. Integración OnlyOffice con callbacks
3. Rendering de cambios y justificaciones
4. Plugin revisor
