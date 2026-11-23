<?php

namespace App\Enums;

enum BatchSource: string
{
    case Generate = 'generate';
    case Import = 'import';

    public function label(): string
    {
        return match ($this) {
            self::Generate => 'Generate',
            self::Import => 'Import CSV',
        };
    }
}
