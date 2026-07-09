<?php

namespace App\LineWatt\Access;

final class LineWattRole
{
    public const SUPER_ADMIN = 'super_admin';
    public const ADMIN = 'admin';
    public const LIBRARIAN = 'librarian';
    public const LIBRARY_PUBLISHER = 'library_publisher';
    public const LIBRARY_CHAMPION = 'library_champion';
    public const SUBSCRIBER = 'subscriber';
    public const GUEST = 'guest';
    public const PARTNER_ADMIN = 'partner_admin';
    public const PARTNER_USER = 'partner_user';

    /**
     * @return array<int,string>
     */
    public static function platformRoles(): array
    {
        return [
            self::SUPER_ADMIN,
            self::ADMIN,
            self::LIBRARIAN,
            self::LIBRARY_PUBLISHER,
        ];
    }

    /**
     * @return array<int,string>
     */
    public static function partnerRoles(): array
    {
        return [
            self::PARTNER_ADMIN,
            self::PARTNER_USER,
        ];
    }

    public static function label(?string $role): string
    {
        return match ($role) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::LIBRARIAN => 'Librarian',
            self::LIBRARY_PUBLISHER => 'Library Publisher',
            self::LIBRARY_CHAMPION => 'Library Champion',
            self::SUBSCRIBER => 'Subscriber',
            self::PARTNER_ADMIN => 'Manufacturer Admin',
            self::PARTNER_USER => 'Manufacturer User',
            default => 'Guest',
        };
    }
}
