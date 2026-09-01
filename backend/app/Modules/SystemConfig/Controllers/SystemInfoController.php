<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;

class SystemInfoController extends BaseController
{
    public function info() {
        return $this->success([
            'system' => [
                'os' => PHP_OS,
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
                'timezone' => config('app.timezone'),
                'env' => config('app.env'),
            ],
            'database' => [
                'connection' => config('database.default'),
                'host' => config('database.connections.mysql.host'),
                'database' => config('database.connections.mysql.database'),
            ],
            'server' => [
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
            ],
            'time' => now()->toDateTimeString(),
        ]);
    }
}
