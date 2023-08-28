<?php

if (!function_exists('clsdir')) {
    /** Clear given directory directory */
    function clsdir($dirname)
    {
        $files = glob($dirname . '/*');
        foreach ($files as $file) {
            if (is_dir($file)) {
                clsdir($file);
                rmdir($file);
            }
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}

if (!function_exists('clrmdir')) {
    /** Remove directory */
    function clrmdir($dirname)
    {
        if (is_dir($dirname)) {
            clsdir($dirname);
            rmdir($dirname);
        }
    }
}


if (!function_exists('getterArray')) {
    /** Get key's value from nested array. 
     * If have no key array, return empty array instead exception */
    function getterArray($array, ...$keys)
    {
        return getterArrayProperties($array, $keys);
    }
}


if (!function_exists('getterArrayProperties')) {
    function getterArrayProperties($array, $keys)
    {
        try {
            if (empty($keys)) {
                return $array;
            }
            $key = $keys[0];
            unset($keys[0]);
            $keys = array_merge([], $keys);
            if (isset($array[$key])) {
                if (!empty($keys)) {
                    return getterArrayProperties($array[$key], $keys);
                } else {
                    return $array[$key];
                }
            } else {
                return [];
            }
        } catch (\Throwable $th) {
            dd($th->getMessage());
        }
    }
}

if (!function_exists('callerOfArray')) {
    /** Call if array have callable value and set to array */
    function callerOfArray($array)
    {
        foreach ($array as $key => $value) {
            if (is_callable($value)) {
                $result = $value();
                if (is_array($result)) {
                    $array[$key] = callerOfArray($result);
                } else {
                    $array[$key] = $result;
                }
            } else {
                if (is_array($value)) {
                    $array[$key] = callerOfArray($value);
                }
            }
        }
        return $array;
    }
}


if (!function_exists('getter')) {
    function getter($array, $key)
    {
        return is_array($array) && isset($array[$key]) ? $array[$key] : null;
    }
}
