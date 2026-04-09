<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Smalot\PdfParser\Parser;

class FtpFileController extends Controller
{
    /**
     * Stream archivo desde FTP
     * GET /ftp-file/{filename}
     */
    public function stream(string $filename): StreamedResponse
    {
        try {
            $filesystem = $this->getFtpFilesystem();

            if (!$filesystem->fileExists($filename)) {
                abort(404, 'Archivo no encontrado en FTP');
            }

            $stream = $filesystem->readStream($filename);
            $mimeType = $this->getMimeType($filename);

            return response()->stream(
                fn () => fpassthru($stream),
                200,
                [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'inline; filename="' . basename($filename) . '"',
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error al descargar archivo FTP', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Error al descargar el archivo');
        }
    }

    /**
     * Obtiene la instancia de Filesystem conectada a FTP
     */
    private function getFtpFilesystem(): Filesystem
    {
        $options = FtpConnectionOptions::fromArray([
            'host' => config('filesystems.disks.presupuestos_ftp.host'),
            'username' => config('filesystems.disks.presupuestos_ftp.username'),
            'password' => config('filesystems.disks.presupuestos_ftp.password'),
            'port' => (int) config('filesystems.disks.presupuestos_ftp.port', 21),
            'root' => config('filesystems.disks.presupuestos_ftp.root', '/'),
            'ssl' => config('filesystems.disks.presupuestos_ftp.ssl', false),
            'timeout' => 30,
            'utf8' => false,
            'passive' => true,
        ]);

        $adapter = new FtpAdapter($options);
        return new Filesystem($adapter);
    }

    /**
     * Obtiene el número de páginas de un PDF
     */
    public function getPdfPageCount(string $filename): int
    {
        try {
            $filesystem = $this->getFtpFilesystem();

            if (!$filesystem->fileExists($filename)) {
                return 0;
            }

            $content = $filesystem->read($filename);

            // Guardar temporalmente en /tmp
            $tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
            file_put_contents($tempFile, $content);

            $parser = new Parser();
            $pdf = $parser->parseFile($tempFile);
            $pages = $pdf->getPages();

            @unlink($tempFile);

            return count($pages);
        } catch (\Exception $e) {
            Log::error('Error al obtener páginas del PDF', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Obtiene PDF como blob (para PDF.js)
     */
    public function getPdfBlob(string $filename)
    {
        try {
            $filesystem = $this->getFtpFilesystem();

            if (!$filesystem->fileExists($filename)) {
                abort(404, 'Archivo no encontrado en FTP');
            }

            $content = $filesystem->read($filename);

            return response($content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Length', strlen($content))
                ->header('Cache-Control', 'public, max-age=3600')
                ->header('Pragma', 'public');
        } catch (\Exception $e) {
            Log::error('Error al obtener PDF como blob', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Error al descargar el archivo');
        }
    }

    /**
     * Determina el MIME type según la extensión
     */
    private function getMimeType(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc' => 'application/msword',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'ppt' => 'application/vnd.ms-powerpoint',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
            'rar' => 'application/vnd.rar',
            default => 'application/octet-stream',
        };
    }
}
