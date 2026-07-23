<?php

namespace App\LegalGovernance\Support;

final class CanonicalJson
{
    public static function encode(array $value): string
    {
        $normalised = self::sort($value);

        return json_encode($normalised, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private static function sort(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(self::sort(...), $value);
        }
        ksort($value, SORT_STRING);

        return array_map(self::sort(...), $value);
    }
}
