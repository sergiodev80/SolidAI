# Diseño: Previsualización de documentos FTP en AsignarPage

**Fecha:** 2026-04-08  
**Contexto:** SolidAI — módulo de asignación de presupuestos

---

## Resumen

Al hacer clic en el nombre de un documento en `AsignarPage`, se abre un modal Filament que previsualiza el archivo. Los archivos viven en un servidor FTP externo (un solo directorio raíz). Se soportan PDF, imágenes (JPG, PNG, etc.) y Word (DOC, DOCX).

---

## Configuración `.env`

```
FTP_HOST=
FTP_USERNAME=
FTP_PASSWORD=
FTP_PORT=21

ONLYOFFICE_URL=https://docs.tuservidor.com
ONLYOFFICE_JWT_SECRET=
```

---

## Arquitectura

```
Click en nombre de documento (blade)
  → Filament Action "previsualizarAction" (AsignarPage.php)
    → Modal con vista blade
      → según extensión del archivo:
          PDF / imagen  → ruta proxy /ftp-file/{filename} → stream desde FTP → <iframe> o <img>
          DOC / DOCX    → OnlyOffice Document Server iframe
                           (OnlyOffice llama de vuelta al proxy para obtener el archivo)
```

---

## Componentes

### 1. Ruta proxy `GET /ftp-file/{filename}`
- Middleware `auth` (no expone archivos sin autenticación)
- Se conecta al FTP con credenciales del `.env`
- Descarga el archivo y lo streamea con el `Content-Type` correcto
- Usada tanto por el cliente (PDF/imágenes) como por OnlyOffice (Word)

### 2. Acción Filament `previsualizarAction`
- Se agrega a `AsignarPage.php`
- Abre un modal con la vista blade de previsualización
- Pasa `filename` y `tipo` (pdf, image, word) como argumentos

### 3. Vista blade del modal
- **PDF:** `<iframe src="/ftp-file/{filename}">` — renderizado nativo del navegador
- **Imagen:** `<img src="/ftp-file/{filename}">` — visualización directa
- **Word:** `<iframe src="{ONLYOFFICE_URL}/web-apps/apps/api/documents/api.js config...">` — editor OnlyOffice en modo solo lectura

### 4. Integración OnlyOffice
- El Document Server recibe un config JSON con:
  - `fileUrl` apuntando a la ruta proxy del proyecto
  - `documentType: "word"`, `mode: "view"`
  - JWT firmado con `ONLYOFFICE_JWT_SECRET`
- Se genera en el backend antes de renderizar el modal

---

## Decisiones clave

| Decisión | Razón |
|---|---|
| Proxy en Laravel, no URL FTP directa | Credenciales FTP nunca expuestas al cliente |
| OnlyOffice para Word en lugar de LibreOffice | Ya está en el stack del proyecto |
| OnlyOffice en modo `view` | Solo previsualización, no edición desde este modal |
| Un solo directorio FTP | Sin estructura de carpetas por presupuesto |

---

## Fuera de alcance

- Subida de archivos al FTP desde la app
- Edición de documentos Word (solo lectura)
- Caché local de archivos FTP
