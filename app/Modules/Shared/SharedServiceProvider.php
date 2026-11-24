<?php

namespace App\Modules\Shared;

/**
 * Shared kernel: logger aktivitas, setting, dan dasar modul.
 * Shared tidak boleh bergantung pada modul lain.
 */
class SharedServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }
}
