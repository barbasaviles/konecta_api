<?php
namespace Framework;

abstract class filters
{
    public static function filterInt($int, array $options = ['default' => null]) {
        return filter_var($int, FILTER_VALIDATE_INT, ['options' => $options]);
    }

    public static function filterString($string, int $size = null) {
        $text = filter_var($string, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        if ($size) {
            $text = mb_strlen($text) > $size ? mb_substr($text, 0, $size) : $text;
        }

        return $text ? pg_escape_string($text) : null;
    }

    public static function filterArrayPath(string $path, array $array) {
        $parts = explode('.', $path);
        foreach ($parts as $part) {
            if (!is_array($array) || !isset($array[$part])) {
                $array = null;
                break;
            }
            $array = $array[$part];
        }
        return $array;
    }

}