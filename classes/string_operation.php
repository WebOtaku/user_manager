<?php

namespace block_user_manager;

class string_operation
{
    public static function first_substr_in_strarr(string $substr, array $strings) {
        foreach ($strings as $key => $string) {
            if (strpos($string, $substr) !== false) {
                return $key;
            }
        }

        return -1;
    }

    public static function first_in_strarr_substr_of_str(array $substrs, string $string) {
        foreach ($substrs as $key => $substr) {
            if (strpos($string, $substr) !== false) {
                return $key;
            }
        }

        return -1;
    }

    public static function remove_prefix(string $str, string $prefix): string {
        return (stripos($str, $prefix) === 0)? substr($str, strlen($prefix)) : $str;
    }

    public static function capitalize_first_letter_cyrillic(string $str): string {
        $f_letter = substr($str, 0, 2);
        $f_letter = mb_convert_case($f_letter, MB_CASE_UPPER);
        return $f_letter . substr($str, 2);
    }
}