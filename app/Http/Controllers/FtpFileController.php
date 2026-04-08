<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Filesystem;

class FtpFileController extends Controller
{
    /**
     * Stream archivo desde FTP
     * GET /ftp-file/{filename}
     */
    public function stream(string $filename): Response
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
        $config = [
            'host' => config('filesystems.disks.presupuestos_ftp.host'),
            'username' => config('filesystems.disks.presupuestos_ftp.username'),
            'password' => config('filesystems.disks.presupuestos_ftp.password'),
            'port' => config('filesystems.disks.presupuestos_ftp.port', 21),
            'root' => config('filesystems.disks.presupuestos_ftp.root', '/'),
            'ssl' => config('filesystems.disks.presupuestos_ftp.ssl', false),
            'timeout' => 30,
            'utf8' => false,
            'passive' => true,
        ];

        $adapter = new FtpAdapter($config);
        return new Filesystem($adapter);
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
