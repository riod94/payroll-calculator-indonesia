<?php

declare(strict_types=1);

namespace PayrollCalculator\Traits;

trait MapArrayObject
{
    public function set(string $name, mixed $value): mixed
    {
        $this->{$name} = $value;
        return $value;
    }

    public function get(string $name): mixed
    {
        return $this->{$name} ?? null;
    }

    public function toArray(): array
    {
        return array_values(get_object_vars($this));
    }

    public function keys(): array
    {
        return array_keys(get_object_vars($this));
    }

    public function values(): array
    {
        return array_values(get_object_vars($this));
    }

    public function toString(): string
    {
        return implode(' ', $this->values());
    }

    public function keyToString(): string
    {
        return implode(' ', $this->keys());
    }

    public function count(): int
    {
        return count($this->values());
    }

    public function sum(): float
    {
        return array_sum($this->values());
    }
}
