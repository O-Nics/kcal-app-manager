<?php

namespace App\Support\Helpers;

use Illuminate\Support\Str;

class CharHelper
{
    public function snakeKeys(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $snakeKey = is_string($key) ? preg_replace('/^([0-9])/', '_$1', Str::snake($key)) : $key;            if (is_array($value)) {
                $out[$snakeKey] = $this->snakeKeys($value);
            } else {
                $out[$snakeKey] = $value;
            }
        }
        return $out;
    }
}
