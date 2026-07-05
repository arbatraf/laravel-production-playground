<?php

namespace App\Enums;

enum CompanyType: string
{
    case Customer = 'customer';
    case Vendor = 'vendor';
    case Partner = 'partner';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Vendor => 'Vendor',
            self::Partner => 'Partner',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
