# Traducción Plugin - Quick Start Guide

## ✅ What's Ready Now

The **Traducción Plugin** is fully implemented and ready to use:

### Working Features

1. **3-Panel Translation Interface**
   - Left: PDF viewer with zoom and page controls (35% width)
   - Center: OnlyOffice document editor (50% width)
   - Right: Change tracking and metadata (15% width)
   - Access: `/admin/traduccion/{id_asignacion}`

2. **Automatic Document Processing**
   - Download original from FTP
   - Smart conversion: pdf2docx for text PDFs, Azure Doc Intelligence for scanned
   - Automatic translation to target language
   - UTF-8 character encoding fixed ✅

3. **Document Versioning**
   - V1: Initial AI-translated document
   - V2, V3, etc: Translator edits create new versions
   - Version files stored in `/public/archivos/traducciones/{id_presupuesto}/{id_asignacion}/`

4. **User Access Control**
   - Translators see only their assigned documents
   - Admins can view all documents
   - Permission validation on every access

5. **OnlyOffice Integration**
   - JWT-authenticated document editing
   - Callback handling for save events
   - State change tracking (Asignado → En Traducción → En Revisión)

---

## 🔧 Setup Instructions

### 1. Configure Environment Variables

Edit `.env` and set:

```env
# Azure Services (required for translation)
AZURE_DOC_INTELLIGENCE_ENDPOINT=https://YOUR-RESOURCE.cognitiveservices.azure.com/
AZURE_DOC_INTELLIGENCE_KEY=YOUR_API_KEY
AZURE_TRANSLATOR_ENDPOINT=https://api.cognitive.microsofttranslator.com
AZURE_TRANSLATOR_KEY=YOUR_API_KEY
AZURE_TRANSLATOR_REGION=eastus

# OnlyOffice (required for editing)
ONLYOFFICE_URL=http://172.17.0.1:8000  # Your OnlyOffice server URL
ONLYOFFICE_JWT_SECRET=generate-random-secret-here

# FTP (required to get original documents)
FTP_HOST=your-ftp-server.com
FTP_USERNAME=ftp_user
FTP_PASSWORD=ftp_password
FTP_PORT=21
PRESUP_FTP_ROOT=/path/to/documents
```

### 2. Create Required Directories

```bash
mkdir -p public/archivos/originales
mkdir -p public/archivos/traduccion-ai
mkdir -p public/archivos/traducciones
chmod 755 public/archivos
```

### 3. Install Dependencies (if needed)

```bash
# Python for pdf2docx
pip install pdf2docx

# Or install via apt (Linux)
apt-get install python3-pip && pip3 install pdf2docx
```

### 4. Database Setup

The plugin expects these columns in `presup_adj_asignaciones` table:
- `id_idiom_original` — Source language ID
- `id_idiom` — Target language ID

These are already in production database. No new migrations needed.

---

## 🚀 Usage

### Accessing a Translation

1. **Login** to Filament admin panel
2. **Navigate** to `/admin/traduccion/{id_asignacion}`
3. **View** the 3-panel interface
4. **Click** "🤖 Traducir con IA" button to start
5. **Wait** for document to be processed and translated
6. **Edit** the translated document in OnlyOffice
7. **Track** changes in the right panel

### Example URL
```
http://localhost:8000/admin/traduccion/42
```

Replace `42` with the actual `id_asignacion`.

---

## 📋 Translation Pipeline

```
Click "Traducir con IA"
        ↓
Fetch original PDF from FTP
        ↓
Convert to DOCX (pdf2docx or Doc Intelligence)
        ↓
Translate with Azure Translator
        ↓
Save as documento_V1.docx
        ↓
Load in OnlyOffice editor
        ↓
Translator edits freely
        ↓
Changes tracked for V2, V3, etc.
```

---

## 🔍 Monitoring & Debugging

### Check Logs

```bash
# View translation requests
tail -f storage/logs/laravel.log | grep -i "traduccion\|traducir"

# Watch for Azure errors
tail -f storage/logs/laravel.log | grep -i "azure"

# Monitor OnlyOffice
tail -f storage/logs/laravel.log | grep -i "onlyoffice"
```

### Test the API Endpoint

```bash
# Requires authentication
curl -X POST \
  http://localhost:8000/admin/traduccion/traducir-ai/42 \
  -H "X-CSRF-TOKEN: $(grep csrf-token .env)" \
  -H "Content-Type: application/json" \
  -d '{"targetLanguage":"es"}'

# Response:
{
  "success": true,
  "message": "Documento traducido exitosamente",
  "documentoV1": "/archivos/traducciones/123/42/documento_V1.docx"
}
```

### Verify Services

```bash
# Check Azure connection
php artisan tinker
>>> app('App\Services\AzureDocumentTranslationService')->translateDocument('/path/to/test.docx', 'es');

# Check FTP connection
>>> app('App\Services\PdfOriginalService')->obtenerPdfOriginal($asignacion);
```

---

## 📦 File Structure

### Generated Files (Auto-created)

```
public/archivos/
├── originales/
│   └── 123/456/documento_original.pdf   (Downloaded from FTP)
├── traduccion-ai/
│   └── 123/456/documento_ai.docx        (Translated version)
└── traducciones/
    └── 123/42/
        ├── documento_V1.docx            (Copy for translator)
        ├── documento_V2.docx            (After edits)
        ├── cambios_V1_V2.json           (Change tracking)
        └── metadata.json                (Assignment metadata)
```

### Plugin Code

```
app/Filament/Plugins/Traduccion/
├── Traduccion.php                       (Plugin registration)
└── Pages/TraduccionPage.php             (Page controller)

app/Services/
├── DocumentConversionService.php        (PDF → DOCX)
├── AzureDocumentTranslationService.php  (Translation API)
├── TraduccionAiService.php              (Orchestration)
├── OnlyOfficeService.php                (Editor integration)
├── DocumentVersioningService.php        (Version management)
└── PdfOriginalService.php               (FTP download)

app/Http/Controllers/
├── TraduccionAiController.php           (API endpoint)
└── OnlyOfficeCallbackController.php     (Save callbacks)

resources/views/filament/traduccion/
└── traduccion-fullscreen.blade.php      (3-panel UI)
```

---

## ⚠️ Important Notes

### Language Code Mapping

The plugin maps idiom IDs to language codes:
```
1 → es (Spanish)
2 → en (English)
3 → pt (Portuguese)
4 → fr (French)
5 → de (German)
6 → it (Italian)
7 → ja (Japanese)
8 → zh (Chinese)
9 → ru (Russian)
10 → ar (Arabic)
```

See `TraduccionPage.php::getLanguageCode()` for the mapping.

### Permissions

Users see their own translations:
- `PresupAdjAsignacion.login` must match authenticated user
- Admins (role = admin) see all translations
- Permission check in `PermissionService`

### Document Size Limits

- Azure Document Translation: Up to 40 MB per document
- pdf2docx: Works with most PDFs (text and scanned)
- OnlyOffice: Handles files up to max configured

---

## 🐛 Troubleshooting

### "Error al traducir documento con IA"

**Cause**: Translation pipeline failed

**Debug**:
1. Check logs: `tail storage/logs/laravel.log`
2. Verify Azure credentials: `echo $AZURE_TRANSLATOR_KEY`
3. Test PDF exists: `ls public/archivos/originales/{id_presupuesto}/{id_documento}/`
4. Check FTP access if downloading

**Fix**:
- Verify all Azure environment variables are set
- Test FTP connection separately
- Ensure Python pdf2docx is installed: `which pdf2docx`

### "OnlyOffice API no cargó"

**Cause**: OnlyOffice server not accessible

**Debug**:
1. Check OnlyOffice URL: `echo $ONLYOFFICE_URL`
2. Test connectivity: `curl $ONLYOFFICE_URL/web-apps/apps/api/documents/api.js`
3. Verify JWT secret is set: `echo $ONLYOFFICE_JWT_SECRET`

**Fix**:
- Update `ONLYOFFICE_URL` to correct server
- Restart OnlyOffice service
- Check network/firewall rules

### "No se puede leer archivo PDF"

**Cause**: FTP download failed or file not found

**Debug**:
1. Check FTP credentials
2. Verify file path in FTP: `{FTP_ROOT}/{presupuesto_id}/{documento_id}/documento.*`

**Fix**:
- Verify FTP credentials in .env
- Check file exists on FTP server
- Check directory permissions

---

## 📞 Support

For issues, check:
1. `TRADUCCION_PLUGIN_STATUS.md` — Full technical documentation
2. `storage/logs/laravel.log` — Application logs
3. OnlyOffice server logs — Editor errors
4. Azure Portal — Service quotas and errors

---

**Last Updated**: 2026-04-08  
**Version**: 1.0.0  
**Status**: ✅ Production Ready
