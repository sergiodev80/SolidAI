<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Log;

class PluginVersion extends Model
{
    protected $fillable = [
        'plugin_slug',
        'plugin_name',
        'version',
        'version_type',
        'description',
        'changelog',
        'github_repository',
        'github_tag',
        'github_release_url',
        'file_path',
        'file_size',
        'file_hash',
        'previous_version',
        'created_by',
        'notes',
        'is_active',
        'is_stable',
        'released_at',
    ];

    protected $casts = [
        'released_at' => 'datetime',
        'is_active' => 'boolean',
        'is_stable' => 'boolean',
    ];

    /**
     * Obtiene las versiones de un plugin
     */
    public static function forPlugin(string $pluginSlug)
    {
        return static::where('plugin_slug', $pluginSlug)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Obtiene la versión activa de un plugin
     */
    public static function activeVersion(string $pluginSlug)
    {
        return static::where('plugin_slug', $pluginSlug)
            ->where('is_active', true)
            ->latest('created_at')
            ->first();
    }

    /**
     * Obtiene la versión estable más reciente
     */
    public static function latestStable(string $pluginSlug)
    {
        return static::where('plugin_slug', $pluginSlug)
            ->where('is_stable', true)
            ->latest('released_at')
            ->first();
    }

    /**
     * Formatea el tipo de versión
     */
    protected function versionType(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => match($value) {
                'major' => 'Mayor',
                'minor' => 'Menor',
                'patch' => 'Parche',
                default => $value,
            }
        );
    }
}
