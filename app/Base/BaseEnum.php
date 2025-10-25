<?php

namespace App\Base;

abstract class BaseEnum
{
    /**
     * Get all constant values defined in the child class.
     */
    public static function values(): array
    {
        $reflection = new \ReflectionClass(static::class);
        return array_values($reflection->getConstants());
    }

    /**
     * Get all constant names (keys).
     */
    public static function keys(): array
    {
        $reflection = new \ReflectionClass(static::class);
        return array_keys($reflection->getConstants());
    }

    /**
     * Get all constants as key-value pairs.
     */
    public static function all(): array
    {
        $reflection = new \ReflectionClass(static::class);
        return $reflection->getConstants();
    }

    /**
     * Validate if a value exists in the enum.
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, static::values(), true);
    }
}
