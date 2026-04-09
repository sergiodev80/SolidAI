# Plugin Traduccion - Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Crear un plugin Filament v5 que permita a traductores editar documentos traducidos por Azure con interfaz de 3 paneles (PDF original, editor Word, cambios+justificaciones).

**Architecture:** El plugin expone una ruta dinámica `/traduccion/{id_asignacion}` que carga datos de la asignación, descarga/procesa el PDF desde FTP via Azure, y renderiza una página fullscreen con 3 paneles. Control de acceso: traductores/revisores ven sus asignaciones, administradores ven todas.

**Tech Stack:** Laravel 13, Filament v5, OnlyOffice (editor), Azure Doc Intelligence + Translator, FTP (descarga), PDF.js (visor), Livewire (componentes interactivos).

---

## File Structure

### Core Plugin Files
- **`app/Filament/Plugins/Traduccion/Traduccion.php`** — Plugin principal que registra la página en Filament
- **`app/Filament/Plugins/Traduccion/Pages/TraduccionPage.php`** — Página Livewire que orquesta carga de datos y renderizado

### Services (Lógica reutilizable)
- **`app/Services/AzureTranslationService.php`** — Descarga FTP, llama Azure Doc Intelligence + Translator, genera Word
- **`app/Services/DocumentVersionService.php`** — Crea/maneja versiones locales, detecta cambios via diff
- **`app/Services/PermissionService.php`** — Valida acceso (traductor/revisor vs admin)

### Routes & Controllers
- **`app/Http/Controllers/TraduccionController.php`** — Controlador con acciones: show, guardar, comparar, enviar-revision
- **`routes/web.php`** — Registro de rutas `/traduccion/*`

### Views
- **`resources/views/filament/traduccion/traduccion-page.blade.php`** — Layout fullscreen 3 paneles
- **`resources/views/filament/traduccion/panel-original.blade.php`** — Visor PDF (PDF.js)
- **`resources/views/filament/traduccion/panel-traduccion.blade.php`** — OnlyOffice iframe
- **`resources/views/filament/traduccion/panel-cambios.blade.php`** — Lista cambios + inputs justificación

### Config
- **`config/traduccion.php`** — URLs Azure, credenciales, paths locales

---

## Tasks

### Task 1: Crear configuración y estructura base del plugin

**Files:**
- Create: `config/traduccion.php`
- Create: `app/Filament/Plugins/Traduccion/Traduccion.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`

- [ ] **Step 1: Crear archivo de configuración**

Create `config/traduccion.php`:

```php
<?php

return [
    // Azure
    'azure' => [
        'doc_intelligence' => [
            'endpoint' => env('AZURE_DOC_INTELLIGENCE_ENDPOINT'),
            'api_key' => env('AZURE_DOC_INTELLIGENCE_KEY'),
        ],
        'translator' => [
            'endpoint' => env('AZURE_TRANSLATOR_ENDPOINT'),
            'api_key' => env('AZURE_TRANSLATOR_KEY'),
            'region' => env('AZURE_TRANSLATOR_REGION', 'centralus'),
        ],
    ],

    // FTP
    'ftp' => [
        'host' => env('FTP_HOST'),
        'username' => env('FTP_USERNAME'),
        'password' => env('FTP_PASSWORD'),
        'port' => env('FTP_PORT', 21),
        'root' => env('FTP_ROOT', '/'),
    ],

    // Local storage
    'storage' => [
        'path' => 'archivos/traducciones',
        'max_versions' => 5,
    ],

    // OnlyOffice
    'onlyoffice' => [
        'url' => env('ONLYOFFICE_URL'),
        'jwt_secret' => env('ONLYOFFICE_JWT_SECRET'),
    ],
];
```

- [ ] **Step 2: Crear clase principal del plugin**

Create `app/Filament/Plugins/Traduccion/Traduccion.php`:

```php
<?php

namespace App\Filament\Plugins\Traduccion;

use Filament\Contracts\Plugin;
use Filament\Panel;

class Traduccion implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'traduccion';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            Pages\TraduccionPage::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
```

- [ ] **Step 3: Registrar plugin en AdminPanelProvider**

Modify `app/Providers/Filament/AdminPanelProvider.php` (buscar método `->plugins()`):

```php
->plugins([
    // ... plugins existentes ...
    \App\Filament\Plugins\Traduccion\Traduccion::make(),
])
```

- [ ] **Step 4: Crear directorio base del plugin**

Run:
```bash
mkdir -p app/Filament/Plugins/Traduccion/{Pages,Services}
mkdir -p resources/views/filament/traduccion
mkdir -p app/Services
mkdir -p app/Http/Controllers
```

- [ ] **Step 5: Commit**

```bash
git add config/traduccion.php app/Filament/Plugins/Traduccion/Traduccion.php app/Providers/Filament/AdminPanelProvider.php
git commit -m "feat: crear estructura base del plugin Traduccion"
```

---

### Task 2: Crear servicio de control de acceso (PermissionService)

**Files:**
- Create: `app/Services/PermissionService.php`

- [ ] **Step 1: Crear servicio de permisos**

Create `app/Services/PermissionService.php`:

```php
<?php

namespace App\Services;

use App\Models\PresupAdjAsignacion;
use Illuminate\Support\Facades\Auth;

class PermissionService
{
    /**
     * Verifica si el usuario puede acceder a una asignación
     * - Admin: puede ver todas
     * - Traductor/Revisor: solo ve sus asignaciones
     */
    public static function canAccessAsignacion(int $idAsignacion, ?string $login = null): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $login = $login ?? $user->login ?? null;
        if (!$login) {
            return false;
        }

        // Administrador ve todo
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return true;
        }

        // Traductor/Revisor: verificar que la asignación es suya
        $asignacion = PresupAdjAsignacion::find($idAsignacion);
        if (!$asignacion) {
            return false;
        }

        return $asignacion->login === $login;
    }

    /**
     * Obtiene el login del usuario autenticado
     */
    public static function getUserLogin(): ?string
    {
        $user = Auth::user();
        return $user?->login ?? $user?->email ?? null;
    }

    /**
     * Verifica si el usuario es administrador
     */
    public static function isAdmin(): bool
    {
        $user = Auth::user();
        return $user && ($user->hasRole('super_admin') || $user->hasRole('admin'));
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Services/PermissionService.php
git commit -m "feat: crear PermissionService para control de acceso"
```

---

### Task 3: Crear servicio de descarga FTP y procesamiento Azure

**Files:**
- Create: `app/Services/AzureTranslationService.php`

- [ ] **Step 1: Crear servicio Azure**

Create `app/Services/AzureTranslationService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use Exception;

class AzureTranslationService
{
    private string $ftpHost;
    private string $ftpUsername;
    private string $ftpPassword;
    private int $ftpPort;
    private string $docIntelligenceEndpoint;
    private string $docIntelligenceKey;
    private string $translatorEndpoint;
    private string $translatorKey;
    private string $translatorRegion;

    public function __construct()
    {
        $this->ftpHost = config('traduccion.ftp.host');
        $this->ftpUsername = config('traduccion.ftp.username');
        $this->ftpPassword = config('traduccion.ftp.password');
        $this->ftpPort = config('traduccion.ftp.port', 21);

        $this->docIntelligenceEndpoint = config('traduccion.azure.doc_intelligence.endpoint');
        $this->docIntelligenceKey = config('traduccion.azure.doc_intelligence.api_key');
        $this->translatorEndpoint = config('traduccion.azure.translator.endpoint');
        $this->translatorKey = config('traduccion.azure.translator.api_key');
        $this->translatorRegion = config('traduccion.azure.translator.region', 'centralus');
    }

    /**
     * Descarga archivo desde FTP
     * @param string $remoteFilename Ruta del archivo en FTP
     * @return string Ruta local temporal del archivo descargado
     */
    public function downloadFromFtp(string $remoteFilename): string
    {
        try {
            $connection = ftp_connect($this->ftpHost, $this->ftpPort, 30);
            if (!$connection) {
                throw new Exception('No se pudo conectar al servidor FTP');
            }

            $login = ftp_login($connection, $this->ftpUsername, $this->ftpPassword);
            if (!$login) {
                throw new Exception('Fallo la autenticación FTP');
            }

            ftp_pasv($connection, true);

            $localPath = storage_path('app/temp/' . basename($remoteFilename));
            $dir = dirname($localPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $downloaded = ftp_get($connection, $localPath, $remoteFilename, FTP_BINARY);
            ftp_close($connection);

            if (!$downloaded) {
                throw new Exception('No se pudo descargar el archivo desde FTP');
            }

            return $localPath;
        } catch (Exception $e) {
            throw new Exception('Error descargando archivo FTP: ' . $e->getMessage());
        }
    }

    /**
     * Extrae texto del PDF usando Azure Doc Intelligence
     * @param string $pdfPath Ruta local del PDF
     * @return string Texto extraído
     */
    public function extractTextFromPdf(string $pdfPath): string
    {
        try {
            $fileContents = file_get_contents($pdfPath);

            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->docIntelligenceKey,
                'Content-Type' => 'application/octet-stream',
            ])->post(
                $this->docIntelligenceEndpoint . '/documentintelligence:analyze?api-version=2024-02-29-preview&model-id=prebuilt-read',
                $fileContents
            );

            if (!$response->successful()) {
                throw new Exception('Error en Doc Intelligence: ' . $response->body());
            }

            $operationLocation = $response->header('Operation-Location');
            if (!$operationLocation) {
                throw new Exception('No se recibió Operation-Location de Doc Intelligence');
            }

            // Poll hasta que esté ready
            $extractedText = $this->pollDocIntelligenceResult($operationLocation);

            return $extractedText;
        } catch (Exception $e) {
            throw new Exception('Error extrayendo texto con Doc Intelligence: ' . $e->getMessage());
        }
    }

    /**
     * Poll de resultados de Doc Intelligence
     */
    private function pollDocIntelligenceResult(string $operationLocation, int $maxAttempts = 60): string
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->docIntelligenceKey,
            ])->get($operationLocation);

            if ($response->json('status') === 'succeeded') {
                $pages = $response->json('analyzeResult.pages', []);
                $extractedText = '';
                foreach ($pages as $page) {
                    if (isset($page['lines'])) {
                        foreach ($page['lines'] as $line) {
                            $extractedText .= ($line['content'] ?? '') . "\n";
                        }
                    }
                }
                return $extractedText;
            }

            if ($response->json('status') === 'failed') {
                throw new Exception('Doc Intelligence falló: ' . json_encode($response->json('analyzeResult.errors')));
            }

            sleep(1); // Esperar 1 segundo antes de reintentar
        }

        throw new Exception('Timeout esperando resultado de Doc Intelligence');
    }

    /**
     * Traduce texto usando Azure Translator
     * @param string $text Texto a traducir
     * @param string $targetLanguage Código del idioma destino (ej: 'es', 'en')
     * @return string Texto traducido
     */
    public function translateText(string $text, string $targetLanguage): string
    {
        try {
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->translatorKey,
                'Ocp-Apim-Subscription-Region' => $this->translatorRegion,
            ])->post(
                $this->translatorEndpoint . '/translate?api-version=3.0&targetLanguage=' . urlencode($targetLanguage),
                [['Text' => $text]]
            );

            if (!$response->successful()) {
                throw new Exception('Error en Azure Translator: ' . $response->body());
            }

            return $response->json('0.translations.0.text', '');
        } catch (Exception $e) {
            throw new Exception('Error traduciendo con Azure Translator: ' . $e->getMessage());
        }
    }

    /**
     * Convierte texto traducido a documento Word
     * @param string $content Contenido traducido
     * @param string $filename Nombre del archivo Word a generar
     * @return string Ruta del archivo Word generado
     */
    public function createWordDocument(string $content, string $filename): string
    {
        try {
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();

            // Dividir contenido en párrafos
            $paragraphs = array_filter(explode("\n", $content));
            foreach ($paragraphs as $paragraph) {
                if (!empty(trim($paragraph))) {
                    $section->addText($paragraph);
                }
            }

            $outputPath = storage_path('app/temp/' . $filename);
            $dir = dirname($outputPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $phpWord->save($outputPath);

            return $outputPath;
        } catch (Exception $e) {
            throw new Exception('Error creando documento Word: ' . $e->getMessage());
        }
    }

    /**
     * Orquesta todo el flujo: descarga FTP → Doc Intelligence → Translator → Word
     */
    public function processDocumentFullFlow(
        string $ftpPath,
        string $sourceLanguageCode,
        string $targetLanguageCode,
        string $outputFilename
    ): string {
        // 1. Descargar desde FTP
        $localPdfPath = $this->downloadFromFtp($ftpPath);

        try {
            // 2. Extraer texto con Doc Intelligence
            $extractedText = $this->extractTextFromPdf($localPdfPath);

            // 3. Traducir con Azure Translator
            $translatedText = $this->translateText($extractedText, $targetLanguageCode);

            // 4. Crear documento Word
            $wordPath = $this->createWordDocument($translatedText, $outputFilename);

            // Limpiar PDF temporal
            @unlink($localPdfPath);

            return $wordPath;
        } catch (Exception $e) {
            @unlink($localPdfPath);
            throw $e;
        }
    }
}
```

- [ ] **Step 2: Installar dependencias necesarias**

Run:
```bash
composer require phpoffice/phpword
```

- [ ] **Step 3: Commit**

```bash
git add app/Services/AzureTranslationService.php composer.json composer.lock
git commit -m "feat: crear AzureTranslationService para FTP y Azure"
```

---

### Task 4: Crear servicio de versionado y detección de cambios

**Files:**
- Create: `app/Services/DocumentVersionService.php`

- [ ] **Step 1: Crear servicio de versionado**

Create `app/Services/DocumentVersionService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Exception;

class DocumentVersionService
{
    private string $basePath;
    private int $maxVersions;

    public function __construct()
    {
        $this->basePath = config('traduccion.storage.path');
        $this->maxVersions = config('traduccion.storage.max_versions', 5);
    }

    /**
     * Obtiene la ruta base para una asignación
     */
    public function getAsignacionPath(int $idAsignacion): string
    {
        return public_path($this->basePath . '/' . $idAsignacion);
    }

    /**
     * Crea la carpeta de una asignación
     */
    public function createAsignacionFolder(int $idAsignacion): void
    {
        $path = $this->getAsignacionPath($idAsignacion);
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Obtiene la versión más reciente de un documento
     */
    public function getLatestVersion(int $idAsignacion): ?int
    {
        $path = $this->getAsignacionPath($idAsignacion);
        if (!is_dir($path)) {
            return null;
        }

        $files = glob($path . '/documento_V*.docx');
        if (empty($files)) {
            return null;
        }

        // Obtener el número máximo de versión
        $versions = [];
        foreach ($files as $file) {
            if (preg_match('/documento_V(\d+)\.docx/', basename($file), $matches)) {
                $versions[] = (int)$matches[1];
            }
        }

        return !empty($versions) ? max($versions) : null;
    }

    /**
     * Obtiene la ruta de un archivo de versión específica
     */
    public function getVersionPath(int $idAsignacion, int $version): string
    {
        return $this->getAsignacionPath($idAsignacion) . '/documento_V' . $version . '.docx';
    }

    /**
     * Guarda una nueva versión del documento
     * @param int $idAsignacion ID de la asignación
     * @param string $sourcePath Ruta del archivo a guardar
     * @return int Número de versión creada
     */
    public function saveNewVersion(int $idAsignacion, string $sourcePath): int
    {
        $this->createAsignacionFolder($idAsignacion);

        $latestVersion = $this->getLatestVersion($idAsignacion) ?? 0;
        $newVersion = $latestVersion + 1;

        $destinationPath = $this->getVersionPath($idAsignacion, $newVersion);
        copy($sourcePath, $destinationPath);

        // Limpiar versiones antiguas si excede máximo
        $this->cleanOldVersions($idAsignacion);

        return $newVersion;
    }

    /**
     * Obtiene el contenido de texto de un documento Word
     */
    public function getDocumentContent(string $docxPath): string
    {
        try {
            if (!file_exists($docxPath)) {
                throw new Exception('Archivo no encontrado: ' . $docxPath);
            }

            $phpWord = IOFactory::load($docxPath);
            $fullText = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $fullText .= $element->getText() . "\n";
                    }
                }
            }

            return $fullText;
        } catch (Exception $e) {
            throw new Exception('Error leyendo documento Word: ' . $e->getMessage());
        }
    }

    /**
     * Compara dos versiones y detecta cambios
     * Devuelve array de cambios detectados
     */
    public function compareVersions(int $idAsignacion, int $fromVersion, int $toVersion): array
    {
        $fromPath = $this->getVersionPath($idAsignacion, $fromVersion);
        $toPath = $this->getVersionPath($idAsignacion, $toVersion);

        if (!file_exists($fromPath) || !file_exists($toPath)) {
            throw new Exception('Una o ambas versiones no existen');
        }

        $fromContent = $this->getDocumentContent($fromPath);
        $toContent = $this->getDocumentContent($toPath);

        // Detectar cambios a nivel palabra, línea y párrafo
        return $this->detectChanges($fromContent, $toContent, $fromVersion, $toVersion);
    }

    /**
     * Detecta cambios entre dos textos
     */
    private function detectChanges(string $fromText, string $toText, int $fromVersion, int $toVersion): array
    {
        $changes = [];

        // Split por párrafos
        $fromParagraphs = array_filter(explode("\n", $fromText));
        $toParagraphs = array_filter(explode("\n", $toText));

        $maxParagraphs = max(count($fromParagraphs), count($toParagraphs));

        for ($i = 0; $i < $maxParagraphs; $i++) {
            $fromPara = trim($fromParagraphs[$i] ?? '');
            $toPara = trim($toParagraphs[$i] ?? '');

            if ($fromPara !== $toPara) {
                // Párrafo cambió - detectar cambios a nivel palabra y línea
                $changeId = uniqid('cambio_');
                $changes[] = [
                    'id' => $changeId,
                    'tipo' => 'parrafo',
                    'original' => $fromPara,
                    'nueva' => $toPara,
                    'posicion' => [
                        'pagina' => ceil(($i + 1) / 50), // Estimación
                        'parrafo' => $i + 1,
                    ],
                    'justificacion' => '',
                    'estado' => 'pendiente',
                ];

                // Detectar cambios a nivel palabra dentro del párrafo
                $this->detectWordLevelChanges($fromPara, $toPara, $i, $changes);
            }
        }

        return $changes;
    }

    /**
     * Detecta cambios a nivel palabra
     */
    private function detectWordLevelChanges(string $fromPara, string $toPara, int $paragraphIndex, array &$changes): void
    {
        $fromWords = str_word_count($fromPara, 1);
        $toWords = str_word_count($toPara, 1);

        // Simple word-by-word comparison
        $maxWords = max(count($fromWords), count($toWords));
        for ($i = 0; $i < $maxWords; $i++) {
            $fromWord = $fromWords[$i] ?? '';
            $toWord = $toWords[$i] ?? '';

            if ($fromWord !== $toWord && !empty($fromWord) && !empty($toWord)) {
                $changeId = uniqid('cambio_');
                $changes[] = [
                    'id' => $changeId,
                    'tipo' => 'palabra',
                    'original' => $fromWord,
                    'nueva' => $toWord,
                    'posicion' => [
                        'pagina' => ceil(($paragraphIndex + 1) / 50),
                        'parrafo' => $paragraphIndex + 1,
                        'palabra' => $i + 1,
                    ],
                    'justificacion' => '',
                    'estado' => 'pendiente',
                ];
            }
        }
    }

    /**
     * Guarda cambios en archivo JSON
     */
    public function saveChanges(int $idAsignacion, int $fromVersion, int $toVersion, array $changes, string $usuario = ''): void
    {
        $changesPath = $this->getAsignacionPath($idAsignacion) . '/cambios_V' . $fromVersion . '_V' . $toVersion . '.json';

        $data = [
            'comparacion' => 'V' . $fromVersion . '_V' . $toVersion,
            'cambios' => $changes,
            'estadisticas' => $this->calculateStats($changes),
            'fecha_comparacion' => now()->toIso8601String(),
            'usuario' => $usuario,
        ];

        file_put_contents($changesPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Calcula estadísticas de cambios
     */
    private function calculateStats(array $changes): array
    {
        $stats = [
            'total_cambios' => count($changes),
            'palabras_cambiadas' => 0,
            'lineas_cambiadas' => 0,
            'parrafos_cambiados' => 0,
            'justificadas' => 0,
            'pendientes' => 0,
        ];

        foreach ($changes as $change) {
            if ($change['tipo'] === 'palabra') {
                $stats['palabras_cambiadas']++;
            } elseif ($change['tipo'] === 'linea') {
                $stats['lineas_cambiadas']++;
            } elseif ($change['tipo'] === 'parrafo') {
                $stats['parrafos_cambiados']++;
            }

            if ($change['estado'] === 'justificado') {
                $stats['justificadas']++;
            } else {
                $stats['pendientes']++;
            }
        }

        return $stats;
    }

    /**
     * Limpia versiones antiguas
     */
    private function cleanOldVersions(int $idAsignacion): void
    {
        $path = $this->getAsignacionPath($idAsignacion);
        $files = glob($path . '/documento_V*.docx');

        if (count($files) > $this->maxVersions) {
            // Obtener versiones ordenadas
            $versions = [];
            foreach ($files as $file) {
                if (preg_match('/documento_V(\d+)\.docx/', basename($file), $matches)) {
                    $versions[(int)$matches[1]] = $file;
                }
            }

            // Eliminar las versiones más antiguas
            ksort($versions);
            $versionsToDelete = array_slice($versions, 0, count($versions) - $this->maxVersions);
            foreach ($versionsToDelete as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Guarda metadata de la asignación
     */
    public function saveMetadata(int $idAsignacion, array $metadata): void
    {
        $metadataPath = $this->getAsignacionPath($idAsignacion) . '/metadata.json';
        file_put_contents($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Obtiene metadata de la asignación
     */
    public function getMetadata(int $idAsignacion): ?array
    {
        $metadataPath = $this->getAsignacionPath($idAsignacion) . '/metadata.json';
        if (!file_exists($metadataPath)) {
            return null;
        }
        return json_decode(file_get_contents($metadataPath), true);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Services/DocumentVersionService.php
git commit -m "feat: crear DocumentVersionService para versionado y detección de cambios"
```

---

### Task 5: Crear controlador de traducción

**Files:**
- Create: `app/Http/Controllers/TraduccionController.php`

- [ ] **Step 1: Crear controlador**

Create `app/Http/Controllers/TraduccionController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\PresupAdjAsignacion;
use App\Services\PermissionService;
use App\Services\AzureTranslationService;
use App\Services\DocumentVersionService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class TraduccionController extends Controller
{
    private PermissionService $permissionService;
    private AzureTranslationService $azureService;
    private DocumentVersionService $versionService;

    public function __construct(
        PermissionService $permissionService,
        AzureTranslationService $azureService,
        DocumentVersionService $versionService
    ) {
        $this->permissionService = $permissionService;
        $this->azureService = $azureService;
        $this->versionService = $versionService;
    }

    /**
     * Mostrar página de traducción
     */
    public function show(int $id_asignacion): View
    {
        // Validar acceso
        if (!$this->permissionService->canAccessAsignacion($id_asignacion)) {
            abort(403, 'No tienes permiso para acceder a esta asignación');
        }

        // Obtener asignación
        $asignacion = PresupAdjAsignacion::findOrFail($id_asignacion);

        // Obtener documento adjunto
        $documento = $asignacion->adjunto;
        if (!$documento) {
            abort(404, 'Documento no encontrado');
        }

        // Crear carpeta si no existe
        $this->versionService->createAsignacionFolder($id_asignacion);

        // Obtener última versión disponible
        $latestVersion = $this->versionService->getLatestVersion($id_asignacion);

        // Si no existe V1, procesar con Azure
        if (!$latestVersion) {
            try {
                // Obtener idiomas del ERP
                $sourceLanguage = $this->getLanguageCode($asignacion->id_idiom_original);
                $targetLanguage = $this->getLanguageCode($asignacion->id_idiom);

                // Procesar documento
                $wordPath = $this->azureService->processDocumentFullFlow(
                    $documento->nombre_archivo,
                    $sourceLanguage,
                    $targetLanguage,
                    'documento_V1.docx'
                );

                // Guardar como V1
                $this->versionService->saveNewVersion($id_asignacion, $wordPath);

                // Guardar metadata
                $this->versionService->saveMetadata($id_asignacion, [
                    'id_asignacion' => $id_asignacion,
                    'id_adjun' => $documento->id_adjun,
                    'nombre_archivo_original' => $documento->nombre_archivo,
                    'traductor' => $asignacion->login,
                    'id_idiom_original' => $asignacion->id_idiom_original,
                    'id_idiom_destino' => $asignacion->id_idiom,
                    'pag_inicio' => $asignacion->pag_inicio,
                    'pag_fin' => $asignacion->pag_fin,
                    'fecha_inicio' => now()->toIso8601String(),
                    'version_actual' => 'V1',
                ]);

                $latestVersion = 1;
            } catch (\Exception $e) {
                abort(500, 'Error procesando documento: ' . $e->getMessage());
            }
        }

        // Actualizar estado a "En Traducción"
        if ($asignacion->estado === 'Asignado') {
            $asignacion->update(['estado' => 'En Traducción']);
        }

        return view('filament.traduccion.traduccion-page', [
            'asignacion' => $asignacion,
            'documento' => $documento,
            'latestVersion' => $latestVersion,
            'onlyofficeUrl' => config('traduccion.onlyoffice.url'),
        ]);
    }

    /**
     * Obtiene código de idioma para Azure Translator
     */
    private function getLanguageCode(int $idIdiom): string
    {
        $languageMap = [
            1 => 'en',  // Inglés
            2 => 'es',  // Español
            3 => 'fr',  // Francés
            4 => 'de',  // Alemán
            5 => 'pt',  // Portugués
            // Agregar más idiomas según necesidad
        ];

        return $languageMap[$idIdiom] ?? 'en';
    }

    /**
     * Guardar documento editado
     */
    public function guardar(Request $request, int $id_asignacion)
    {
        if (!$this->permissionService->canAccessAsignacion($id_asignacion)) {
            abort(403);
        }

        $asignacion = PresupAdjAsignacion::findOrFail($id_asignacion);

        try {
            // Obtener documento editado desde OnlyOffice (vía webhook o POST)
            // Por ahora, asumimos que OnlyOffice lo maneja automáticamente
            // En versión completa, implementar callback de OnlyOffice

            return response()->json(['success' => true, 'message' => 'Documento guardado']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Comparar versiones y detectar cambios
     */
    public function comparar(Request $request, int $id_asignacion)
    {
        if (!$this->permissionService->canAccessAsignacion($id_asignacion)) {
            abort(403);
        }

        try {
            $fromVersion = $request->input('from_version', 1);
            $toVersion = $request->input('to_version');

            if (!$toVersion) {
                $toVersion = $this->versionService->getLatestVersion($id_asignacion);
            }

            if (!$fromVersion || !$toVersion || $fromVersion >= $toVersion) {
                return response()->json(['error' => 'Versiones inválidas'], 400);
            }

            // Comparar versiones
            $changes = $this->versionService->compareVersions($id_asignacion, $fromVersion, $toVersion);

            // Guardar cambios
            $usuario = PermissionService::getUserLogin();
            $this->versionService->saveChanges($id_asignacion, $fromVersion, $toVersion, $changes, $usuario);

            return response()->json([
                'success' => true,
                'changes' => $changes,
                'count' => count($changes),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Enviar para revisión
     */
    public function enviarRevision(Request $request, int $id_asignacion): RedirectResponse
    {
        if (!$this->permissionService->canAccessAsignacion($id_asignacion)) {
            abort(403);
        }

        $asignacion = PresupAdjAsignacion::findOrFail($id_asignacion);

        try {
            // Cambiar estado a "En Revisión"
            $asignacion->update(['estado' => 'En Revisión']);

            return redirect()
                ->back()
                ->with('success', 'Documento enviado para revisión');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error enviando para revisión: ' . $e->getMessage());
        }
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Controllers/TraduccionController.php
git commit -m "feat: crear TraduccionController para gestionar traducción"
```

---

### Task 6: Registrar rutas de traducción

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Agregar rutas**

Modify `routes/web.php` - agregar después de las rutas existentes:

```php
Route::middleware(['auth'])->group(function () {
    // Rutas existentes...

    // Rutas de Traducción
    Route::get('/traduccion/{id_asignacion}', [\App\Http\Controllers\TraduccionController::class, 'show'])
        ->name('traduccion.show');

    Route::post('/traduccion/{id_asignacion}/guardar', [\App\Http\Controllers\TraduccionController::class, 'guardar'])
        ->name('traduccion.guardar');

    Route::post('/traduccion/{id_asignacion}/comparar', [\App\Http\Controllers\TraduccionController::class, 'comparar'])
        ->name('traduccion.comparar');

    Route::post('/traduccion/{id_asignacion}/enviar-revision', [\App\Http\Controllers\TraduccionController::class, 'enviarRevision'])
        ->name('traduccion.enviar-revision');
});
```

- [ ] **Step 2: Commit**

```bash
git add routes/web.php
git commit -m "feat: registrar rutas de traducción"
```

---

### Task 7: Crear página Livewire TraduccionPage (Filament)

**Files:**
- Create: `app/Filament/Plugins/Traduccion/Pages/TraduccionPage.php`

- [ ] **Step 1: Crear página Livewire**

Create `app/Filament/Plugins/Traduccion/Pages/TraduccionPage.php`:

```php
<?php

namespace App\Filament\Plugins\Traduccion\Pages;

use App\Models\PresupAdjAsignacion;
use App\Services\PermissionService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Route;

class TraduccionPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.traduccion.traduccion-page';
    protected static ?string $title = 'Traducción';
    protected static ?string $slug = 'traduccion/{id_asignacion}';

    public ?PresupAdjAsignacion $asignacion = null;
    public ?int $idAsignacion = null;
    public ?int $latestVersion = null;

    public static function getNavigationGroup(): ?string
    {
        return 'Traducción';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // No mostrar en menú - acceder via ruta dinámica
    }

    public function mount(int $id_asignacion): void
    {
        // Validar acceso
        $permissionService = app(PermissionService::class);
        if (!$permissionService->canAccessAsignacion($id_asignacion)) {
            abort(403, 'No tienes permiso para acceder a esta asignación');
        }

        // Obtener asignación
        $this->asignacion = PresupAdjAsignacion::findOrFail($id_asignacion);
        $this->idAsignacion = $id_asignacion;

        // Obtener última versión (será obtenida en la vista)
    }

    public function getTitle(): string
    {
        return $this->asignacion ? 'Traducción - ' . $this->asignacion->adjunto?->nombre_archivo : 'Traducción';
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Filament/Plugins/Traduccion/Pages/TraduccionPage.php
git commit -m "feat: crear página Livewire TraduccionPage"
```

---

### Task 8: Crear vistas Blade (layout y paneles)

**Files:**
- Create: `resources/views/filament/traduccion/traduccion-page.blade.php`
- Create: `resources/views/filament/traduccion/panel-original.blade.php`
- Create: `resources/views/filament/traduccion/panel-traduccion.blade.php`
- Create: `resources/views/filament/traduccion/panel-cambios.blade.php`

- [ ] **Step 1: Crear vista principal (layout 3 paneles)**

Create `resources/views/filament/traduccion/traduccion-page.blade.php`:

```blade
<x-filament-panels::page>

<div class="traduccion-container" style="display: flex; flex-direction: column; height: 100vh; overflow: hidden;">

    {{-- Header con info de asignación --}}
    <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0;">
            Traducción — {{ $asignacion->adjunto->nombre_archivo ?? '' }}
        </h1>
        <p style="color: #6b7280; margin: 0.5rem 0 0 0; font-size: 0.875rem;">
            Páginas asignadas: {{ $asignacion->pag_inicio }} - {{ $asignacion->pag_fin }}
            · Estado: <span style="color: #2563eb; font-weight: 600;">{{ $asignacion->estado }}</span>
        </p>
    </div>

    {{-- 3 Paneles --}}
    <div class="paneles-container" style="display: flex; gap: 0; flex: 1; overflow: hidden;">

        {{-- Panel Izquierdo: PDF Original --}}
        <div style="flex: 0 0 35%; border-right: 1px solid #e5e7eb; overflow-y: auto; padding: 1rem;">
            <div style="margin-bottom: 1rem;">
                <h2 style="font-size: 0.875rem; font-weight: 600; color: #374151; margin: 0 0 1rem 0;">
                    ORIGINAL ({{ $asignacion->adjunto->nombre_archivo ?? '' }})
                </h2>
            </div>
            @include('filament.traduccion.panel-original', [
                'asignacion' => $asignacion,
                'documento' => $documento ?? null
            ])
        </div>

        {{-- Panel Central: Editor OnlyOffice --}}
        <div style="flex: 0 0 50%; border-right: 1px solid #e5e7eb; overflow-y: auto;">
            <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb;">
                <h2 style="font-size: 0.875rem; font-weight: 600; color: #374151; margin: 0;">
                    TRADUCCIÓN (V{{ $latestVersion ?? 1 }})
                </h2>
            </div>
            @include('filament.traduccion.panel-traduccion', [
                'asignacion' => $asignacion,
                'idAsignacion' => $idAsignacion,
                'latestVersion' => $latestVersion ?? 1,
                'onlyofficeUrl' => $onlyofficeUrl ?? ''
            ])
        </div>

        {{-- Panel Derecho: Cambios y Justificaciones --}}
        <div style="flex: 0 0 15%; overflow-y: auto; padding: 1rem; background: #fafafa;">
            <div style="margin-bottom: 1rem;">
                <h2 style="font-size: 0.875rem; font-weight: 600; color: #374151; margin: 0 0 1rem 0;">
                    CAMBIOS
                </h2>
            </div>
            @include('filament.traduccion.panel-cambios', [
                'asignacion' => $asignacion,
                'idAsignacion' => $idAsignacion,
            ])
        </div>

    </div>

    {{-- Footer: Botones de acción --}}
    <div style="padding: 1rem; border-top: 1px solid #e5e7eb; background: #f9fafb; display: flex; gap: 0.5rem;">
        <button
            class="fi-btn fi-btn-size-md fi-rounded-md fi-btn-color-primary"
            onclick="guardarDocumento()"
            style="padding: 0.5rem 1rem; background: #2563eb; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 500;"
        >
            Guardar
        </button>

        <button
            class="fi-btn fi-btn-size-md fi-rounded-md fi-btn-color-info"
            onclick="compararVersiones()"
            style="padding: 0.5rem 1rem; background: #0891b2; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 500;"
        >
            Justificar Cambios
        </button>

        <button
            class="fi-btn fi-btn-size-md fi-rounded-md fi-btn-color-success"
            onclick="enviarParaRevision()"
            style="padding: 0.5rem 1rem; background: #16a34a; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 500;"
        >
            Enviar para Revisión
        </button>
    </div>

</div>

<script>
function guardarDocumento() {
    alert('Guardar documento (implementar integración OnlyOffice)');
}

function compararVersiones() {
    fetch('{{ route("traduccion.comparar", ["id_asignacion" => $idAsignacion]) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            from_version: 1,
            to_version: {{ $latestVersion ?? 1 }}
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Se encontraron ' + data.count + ' cambios');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => console.error('Error:', error));
}

function enviarParaRevision() {
    if (confirm('¿Enviar este documento para revisión?')) {
        fetch('{{ route("traduccion.enviar-revision", ["id_asignacion" => $idAsignacion]) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        })
        .then(response => {
            if (response.ok) {
                alert('Documento enviado para revisión');
                location.reload();
            } else {
                alert('Error enviando documento');
            }
        })
        .catch(error => console.error('Error:', error));
    }
}
</script>

<style>
.traduccion-container {
    height: calc(100vh - 4rem);
}

.paneles-container {
    background: white;
}
</style>

</x-filament-panels::page>
```

- [ ] **Step 2: Crear vista panel original (PDF)**

Create `resources/views/filament/traduccion/panel-original.blade.php`:

```blade
<div id="pdf-viewer">
    <p style="color: #6b7280; font-size: 0.875rem;">
        PDF Viewer (PDF.js)
    </p>
    <p style="color: #9ca3af; font-size: 0.875rem;">
        El visor PDF se cargará aquí. Integracion con PDF.js o similar.
    </p>
    {{-- 
        TODO: Implementar PDF.js viewer
        - Cargar PDF desde FTP path
        - Mostrar solo páginas {{ $asignacion->pag_inicio }}-{{ $asignacion->pag_fin }}
        - Permitir zoom y scroll
    --}}
</div>
```

- [ ] **Step 3: Crear vista panel traducción (OnlyOffice)**

Create `resources/views/filament/traduccion/panel-traduccion.blade.php`:

```blade
<div id="onlyoffice-editor" style="width: 100%; height: 100%; background: white;">
    {{-- OnlyOffice iframe se cargará aquí --}}
    <p style="color: #6b7280; font-size: 0.875rem; padding: 1rem;">
        Editor OnlyOffice
    </p>
    <p style="color: #9ca3af; font-size: 0.875rem; padding: 0 1rem;">
        El documento editable se cargará en OnlyOffice con JWT.
    </p>
    {{--
        TODO: Implementar integración OnlyOffice
        - Generar JWT con credenciales
        - Cargar documento_V{{ $latestVersion }}.docx
        - Permitir edición
        - Guardar cambios via webhook
    --}}
</div>
```

- [ ] **Step 4: Crear vista panel cambios**

Create `resources/views/filament/traduccion/panel-cambios.blade.php`:

```blade
<div id="cambios-panel">
    <div style="margin-bottom: 1rem;">
        <p style="color: #6b7280; font-size: 0.75rem; margin: 0;">
            Páginas
        </p>
        <p style="color: #374151; font-size: 0.875rem; font-weight: 600; margin: 0.5rem 0 0 0;">
            {{ $asignacion->pag_inicio }} - {{ $asignacion->pag_fin }}
        </p>
    </div>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 1rem 0;">

    <div id="cambios-lista" style="font-size: 0.75rem;">
        <p style="color: #9ca3af; text-align: center; padding: 1rem 0;">
            Sin cambios aún
        </p>
        {{--
            TODO: Mostrar cambios detectados
            - Listar cada cambio (palabra, línea, párrafo)
            - Mostrar original → nueva
            - Input para justificación
            - Marcar como justificado cuando complete
        --}}
    </div>
</div>
```

- [ ] **Step 5: Commit**

```bash
git add resources/views/filament/traduccion/
git commit -m "feat: crear vistas Blade para layout 3 paneles"
```

---

### Task 9: Crear helper para JWT de OnlyOffice

**Files:**
- Create: `app/Services/OnlyOfficeService.php`

- [ ] **Step 1: Crear servicio OnlyOffice**

Create `app/Services/OnlyOfficeService.php`:

```php
<?php

namespace App\Services;

use Firebase\JWT\JWT;

class OnlyOfficeService
{
    /**
     * Genera JWT para autentificación con OnlyOffice
     */
    public static function generateJwt(array $payload): string
    {
        $secret = config('traduccion.onlyoffice.jwt_secret');

        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Construye configuración para OnlyOffice
     */
    public static function getEditorConfig(
        string $documentUrl,
        string $documentTitle,
        string $userId,
        string $userName,
        bool $canEdit = true
    ): array {
        $config = [
            'document' => [
                'fileType' => 'docx',
                'key' => uniqid() . time(),
                'title' => $documentTitle,
                'url' => $documentUrl,
            ],
            'documentType' => 'text',
            'editorConfig' => [
                'mode' => $canEdit ? 'edit' : 'view',
                'callbackUrl' => route('onlyoffice.callback'),
                'user' => [
                    'id' => $userId,
                    'name' => $userName,
                ],
            ],
        ];

        // Generar JWT del documento
        $config['token'] = self::generateJwt($config);

        return $config;
    }
}
```

- [ ] **Step 2: Instalar librería JWT**

Run:
```bash
composer require firebase/php-jwt
```

- [ ] **Step 3: Commit**

```bash
git add app/Services/OnlyOfficeService.php composer.json composer.lock
git commit -m "feat: crear OnlyOfficeService para JWT"
```

---

### Task 10: Agregar env variables y finales

**Files:**
- Modify: `.env.example`

- [ ] **Step 1: Agregar variables de entorno**

Modify `.env.example` - agregar al final:

```env
# Traducción - Azure
AZURE_DOC_INTELLIGENCE_ENDPOINT=https://<your-region>.api.cognitive.microsoft.com/
AZURE_DOC_INTELLIGENCE_KEY=your-key-here
AZURE_TRANSLATOR_ENDPOINT=https://api.cognitive.microsofttranslator.com/
AZURE_TRANSLATOR_KEY=your-key-here
AZURE_TRANSLATOR_REGION=centralus

# Traducción - FTP
FTP_HOST=ftp.example.com
FTP_USERNAME=ftpuser
FTP_PASSWORD=ftppass
FTP_PORT=21
FTP_ROOT=/

# Traducción - OnlyOffice
ONLYOFFICE_URL=http://localhost:8080
ONLYOFFICE_JWT_SECRET=your-jwt-secret
```

- [ ] **Step 2: Commit**

```bash
git add .env.example
git commit -m "feat: agregar variables de entorno para Traducción"
```

---

### Task 11: Crear tests básicos

**Files:**
- Create: `tests/Feature/TraduccionControllerTest.php`

- [ ] **Step 1: Crear tests**

Create `tests/Feature/TraduccionControllerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\PresupAdjAsignacion;
use App\Models\PresupAdj;
use App\Models\SeccUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraduccionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function traduccion_page_requiere_autenticacion()
    {
        $response = $this->get('/traduccion/1');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function usuario_puede_ver_su_asignacion()
    {
        $usuario = SeccUser::factory()->create();
        $documento = PresupAdj::factory()->create();
        $asignacion = PresupAdjAsignacion::factory()->create([
            'login' => $usuario->login,
            'id_adjun' => $documento->id_adjun,
        ]);

        $response = $this->actingAs($usuario)->get("/traduccion/{$asignacion->id}");
        $response->assertOk();
    }

    /** @test */
    public function usuario_no_puede_ver_asignacion_de_otro()
    {
        $usuario1 = SeccUser::factory()->create();
        $usuario2 = SeccUser::factory()->create();
        $documento = PresupAdj::factory()->create();
        $asignacion = PresupAdjAsignacion::factory()->create([
            'login' => $usuario1->login,
            'id_adjun' => $documento->id_adjun,
        ]);

        $response = $this->actingAs($usuario2)->get("/traduccion/{$asignacion->id}");
        $response->assertForbidden();
    }

    /** @test */
    public function admin_puede_ver_cualquier_asignacion()
    {
        $admin = SeccUser::factory()->create();
        $admin->assignRole('admin');

        $usuario = SeccUser::factory()->create();
        $documento = PresupAdj::factory()->create();
        $asignacion = PresupAdjAsignacion::factory()->create([
            'login' => $usuario->login,
            'id_adjun' => $documento->id_adjun,
        ]);

        $response = $this->actingAs($admin)->get("/traduccion/{$asignacion->id}");
        $response->assertOk();
    }

    /** @test */
    public function estado_cambia_a_en_traduccion()
    {
        $usuario = SeccUser::factory()->create();
        $documento = PresupAdj::factory()->create();
        $asignacion = PresupAdjAsignacion::factory()->create([
            'login' => $usuario->login,
            'id_adjun' => $documento->id_adjun,
            'estado' => 'Asignado',
        ]);

        $this->actingAs($usuario)->get("/traduccion/{$asignacion->id}");

        $asignacion->refresh();
        $this->assertEquals('En Traducción', $asignacion->estado);
    }
}
```

- [ ] **Step 2: Ejecutar tests**

Run:
```bash
php artisan test tests/Feature/TraduccionControllerTest.php
```

Expected: All tests pass

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/TraduccionControllerTest.php
git commit -m "test: agregar tests para TraduccionController"
```

---

### Task 12: Documentación y finalización

**Files:**
- Create: `docs/superpowers/traduccion-README.md`

- [ ] **Step 1: Crear README del plugin**

Create `docs/superpowers/traduccion-README.md`:

```markdown
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
```

- [ ] **Step 2: Commit final**

```bash
git add docs/superpowers/traduccion-README.md
git commit -m "docs: agregar README del plugin Traduccion"
```

- [ ] **Step 3: Verify all files created**

Run:
```bash
git log --oneline -15
```

Expected: 12 commits desde task 1

---

## Summary

**Plan Complete!** El plugin Traduccion está estructurado y listo para desarrollo iterativo.

### What's Built
- 3 servicios robustos (Azure, Versionado, Permisos)
- Rutas y controlador completo
- Página Livewire integrada con Filament v5
- Tests de acceso y permisos
- Vistas Blade con estructura de 3 paneles

### What's Next (Trabajo futuro)
1. Integración PDF.js (panel izquierdo - visor)
2. Integración OnlyOffice (panel central - editor)
3. Rendering dinámico de cambios (panel derecho)
4. Webhooks OnlyOffice para callbacks
5. Plugin revisor (segunda persona del flujo)

