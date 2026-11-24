<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Contracts\PackageUsageCheck;
use App\Modules\Catalog\Exceptions\PackageInUseException;
use App\Modules\Catalog\Models\VoucherPackage;
use App\Modules\Shared\Services\ActivityLogger;

/**
 * Menghapus paket hanya jika tidak ada modul yang masih memakainya.
 */
class DeletePackage
{
    /**
     * @param  iterable<PackageUsageCheck>  $usageChecks
     */
    public function __construct(
        private readonly ActivityLogger $logger,
        private readonly iterable $usageChecks,
    ) {}

    /**
     * @throws PackageInUseException
     */
    public function __invoke(VoucherPackage $package): void
    {
        foreach ($this->usageChecks as $check) {
            if ($reason = $check->usageReason($package->id)) {
                throw PackageInUseException::because($package->name, $reason);
            }
        }

        $this->logger->log('package.deleted', null, ['id' => $package->id, 'name' => $package->name]);

        $package->delete();
    }
}
