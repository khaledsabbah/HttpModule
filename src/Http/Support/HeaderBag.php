<?php
namespace Idaratech\Integrations\Http\Support;

class HeaderBag
{
    public static function merge(array ...$sets): array
    {
        $result = [];
        foreach ($sets as $set) {
            foreach ($set as $k => $v) {
                $result[self::normalize($k)] = $v;
            }
        }
        return $result;
    }

    public static function normalize(string $key): string
    {
        $key = strtolower($key);
        return implode('-', array_map(fn($p) => ucfirst($p), explode('-', $key)));
    }
}
