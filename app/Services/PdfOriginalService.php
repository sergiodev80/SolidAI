<?php

namespace App\Services;

use App\Models\PresupAdj;
use App\Models\PresupAdjAsignacion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Filesystem;

class PdfOriginalService
{
    /**
     * Obtiene o descarga el PDF original
     * Retorna el path local del archivo
     */
    public function obtenerPdfOriginal(PresupAdjAsignacion $asignacion): ?string
    {
        $adjunto = $asignacion->adjunto;
        $presupuesto = $adjunto->presupuesto;

        if (!$presupuesto) {
            return null;
        }

        $idPresupuesto = $presupuesto->id_pres;
        $idDocumento = $adjunto->id_adjun;
        $nombreArchivo = $adjunto->nombre_archivo;

        // Directorio local
        $directorioLocal = "archivos/originales/{$idPresupuesto}/{$idDocumento}";
        $rutaLocal = public_path($directorioLocal);

        // Verificar si el archivo ya existe localmente
        if (is_dir($rutaLocal)) {
            $archivos = glob("{$rutaLocal}/*");
            foreach ($archivos as $archivo) {
                if (is_file($archivo) && preg_match('/\.(pdf|jpg|jpeg|png|doc|docx)$/i', $archivo)) {
                    return "/{$directorioLocal}/" . basename($archivo);
                }
            }
        }

        // Si no existe, descargar desde FTP
        return $this->descargarDelFtp($asignacion, $rutaLocal, $nombreArchivo, $directorioLocal);
    }

    /**
     * Descarga el PDF desde FTP y lo guarda localmente
     */
    private function descargarDelFtp(PresupAdjAsignacion $asignacion, string $rutaLocal, string $nombreArchivo, string $directorioLocal): ?string
    {
        try {
            // Obtener ruta en FTP
            $ftpRoot = config('filesystems.disks.presupuestos_ftp.root', '/');
            $rutaFtp = trim($ftpRoot, '/') . '/' . trim($nombreArchivo, '/');

            // Conectar a FTP
            $filesystem = $this->getFtpFilesystem();

            if (!$filesystem->fileExists($rutaFtp)) {
                Log::warning("PDF no encontrado en FTP: {$rutaFtp}");
                return null;
            }

            // Crear directorio local si no existe
            if (!is_dir($rutaLocal)) {
                mkdir($rutaLocal, 0755, true);
            }

            // Descargar archivo
            $contenido = $filesystem->read($rutaFtp);

            // Guardar localmente
            $nombreLocal = 'documento_original.' . pathinfo($nombreArchivo, PATHINFO_EXTENSION);
            $rutaCompleta = $rutaLocal . '/' . $nombreLocal;

            file_put_contents($rutaCompleta, $contenido);

            Log::info("PDF descargado desde FTP: {$rutaFtp} -> {$rutaCompleta}");

            return "/{$directorioLocal}/{$nombreLocal}";
        } catch (\Exception $e) {
            Log::error("Error al descargar PDF desde FTP", [
                'id_asignacion' => $asignacion->id,
                'nombre_archivo' => $nombreArchivo,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Obtiene instancia de Filesystem conectada a FTP
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
}
