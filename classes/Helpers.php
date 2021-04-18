<?php

namespace Flynsarmy\ContentBlocks\Classes;

class Helpers
{
    public static function base64EncodeUrl(string $string): string
    {
        return str_replace(['+','/','='], ['-','_',''], base64_encode($string));
    }

    public static function base64DecodeUrl(string $string): string
    {
        return base64_decode(str_replace(['-','_'], ['+','/'], $string));
    }
}
