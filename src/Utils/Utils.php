<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\Request;

class Utils
{
    public static function serializeRequestData(Request $request): array
    {
        return json_decode($request->getContent(), true);
    }
}