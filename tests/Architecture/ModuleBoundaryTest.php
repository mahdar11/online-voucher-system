<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Menjaga aturan modular monolith:
 *
 * 1. Arah ketergantungan satu arah (tanpa lingkaran):
 *    Shared ← Identity ← Catalog ← Inventory ← Ordering ← Payment
 * 2. Modul lain hanya boleh memakai bagian publik sebuah modul:
 *    Contracts, Data, Enums, Events. Shared adalah shared kernel, jadi seluruhnya publik.
 * 3. Modul tidak boleh bergantung pada lapisan aplikasi (App\Http, App\Providers).
 */
class ModuleBoundaryTest extends TestCase
{
    /**
     * Modul => modul yang boleh dipakai.
     */
    private const ALLOWED_DEPENDENCIES = [
        'Shared' => [],
        'Identity' => ['Shared'],
        'Catalog' => ['Shared', 'Identity'],
        'Inventory' => ['Shared', 'Identity', 'Catalog'],
        'Ordering' => ['Shared', 'Identity', 'Catalog', 'Inventory'],
        'Payment' => ['Shared', 'Identity', 'Catalog', 'Inventory', 'Ordering'],
    ];

    private const PUBLIC_NAMESPACES = ['Contracts', 'Data', 'Enums', 'Events'];

    private const SHARED_KERNEL = 'Shared';

    public function test_every_module_folder_is_declared(): void
    {
        $folders = array_map('basename', glob($this->modulesPath().'/*', GLOB_ONLYDIR));

        $this->assertEqualsCanonicalizing(array_keys(self::ALLOWED_DEPENDENCIES), $folders);
    }

    public function test_every_module_provider_is_registered(): void
    {
        $providers = (string) file_get_contents(dirname(__DIR__, 2).'/bootstrap/providers.php');

        foreach (array_keys(self::ALLOWED_DEPENDENCIES) as $module) {
            $this->assertStringContainsString("App\\Modules\\{$module}\\{$module}ServiceProvider", $providers);
        }
    }

    public function test_modules_respect_dependency_direction_and_public_api(): void
    {
        $violations = [];

        foreach (array_keys(self::ALLOWED_DEPENDENCIES) as $module) {
            foreach ($this->phpFiles($this->modulesPath().'/'.$module) as $file) {
                foreach ($this->violationsIn($module, (string) file_get_contents($file)) as $violation) {
                    $violations[] = $this->relative($file).': '.$violation;
                }
            }
        }

        $this->assertSame([], $violations, "Pelanggaran batas modul:\n".implode("\n", $violations));
    }

    public function test_no_code_left_in_old_flat_namespaces(): void
    {
        $root = dirname(__DIR__, 2);
        $leftovers = [];

        foreach (['app', 'routes', 'database', 'config', 'tests'] as $dir) {
            foreach ($this->phpFiles($root.'/'.$dir) as $file) {
                if (preg_match('/App\\\\(Models|Enums|Services|Actions)\\\\/', (string) file_get_contents($file))) {
                    $leftovers[] = $this->relative($file);
                }
            }
        }

        $this->assertSame([], array_values(array_diff($leftovers, [$this->relative(__FILE__)])));
    }

    /**
     * Memastikan pemindai benar-benar menangkap pelanggaran (agar test di atas tidak lolos palsu).
     */
    public function test_scanner_detects_violations(): void
    {
        $code = <<<'PHP'
            use App\Modules\Inventory\Models\Voucher;          // internal modul lain
            use App\Modules\Ordering\Contracts\Something;      // arah terbalik
            use App\Http\Middleware\HandleInertiaRequests;     // lapisan aplikasi
            use App\Modules\Catalog\Contracts\PackageCatalog;  // boleh
            use App\Modules\Shared\Services\ActivityLogger;    // boleh (shared kernel)
            PHP;

        $violations = $this->violationsIn('Inventory', str_replace('Inventory\\Models', 'Ordering\\Models', $code));

        $this->assertCount(3, $violations);
        $this->assertSame([], $this->violationsIn('Ordering', 'use App\Modules\Catalog\Contracts\PackageCatalog;'));
    }

    /**
     * @return list<string>
     */
    private function violationsIn(string $module, string $code): array
    {
        $violations = [];

        if (preg_match_all('/App\\\\(Http|Providers)\\\\[\w\\\\]+/', $code, $shell)) {
            foreach (array_unique($shell[0]) as $reference) {
                $violations[] = "{$module} memakai lapisan aplikasi {$reference}";
            }
        }

        preg_match_all('/App\\\\Modules\\\\(\w+)\\\\(\w+)/', $code, $matches, PREG_SET_ORDER);

        foreach ($matches as [$reference, $target, $section]) {
            if ($target === $module) {
                continue;
            }

            if (! in_array($target, self::ALLOWED_DEPENDENCIES[$module] ?? [], true)) {
                $violations[] = "{$module} tidak boleh bergantung pada {$target} ({$reference})";

                continue;
            }

            if ($target !== self::SHARED_KERNEL && ! in_array($section, self::PUBLIC_NAMESPACES, true)) {
                $violations[] = "{$module} memakai bagian internal {$target}: {$reference}";
            }
        }

        return array_values(array_unique($violations));
    }

    private function modulesPath(): string
    {
        return dirname(__DIR__, 2).'/app/Modules';
    }

    /**
     * @return list<string>
     */
    private function phpFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function relative(string $path): string
    {
        return str_replace(dirname(__DIR__, 2).'/', '', $path);
    }
}
