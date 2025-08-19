<?php

namespace App\Support\Middleware;

use Closure;
use Illuminate\Support\Str;

class TransformRequestMiddleware
{
    /**
     * Liste des clés à ne pas transformer.
     */
    protected array $whitelistKeys = [
        'password_confirmation',
        'new_password_confirmation',
    ];

    /**
     * Handle the request and convert snake_case to camelCase.
     */
    public function handle($request, Closure $next)
    {
        if ($request->isJson()) {
            $data = $request->all();
            $transformedData = $this->transformToCamelCase($data);
            $request->replace($transformedData);
        }

        // Transform query parameters
        $queryParams = $request->query();
        $transformedQueryParams = $this->transformToCamelCase($queryParams);
        $request->query->replace($transformedQueryParams);

        return $next($request);
    }

    /**
     * Transform array keys to camelCase.
     */
    protected function transformToCamelCase(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            // Si la clé est dans la liste blanche, ne pas la transformer
            if (in_array($key, $this->whitelistKeys)) {
                $result[$key] = is_array($value) ? $this->transformToCamelCase($value) : $value;
            } else {
                $newKey = Str::camel($key);
                $result[$newKey] = is_array($value) ? $this->transformToCamelCase($value) : $value;
            }
        }

        return $result;
    }
}
