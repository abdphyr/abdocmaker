<?php

namespace Abd\Docmaker\Traits;


trait ParseValueTrait
{
    protected $obj = "object";
    protected $num = "integer";
    protected $arr = "array";
    protected $str = "string";
    protected $boo = "boolean";

    public function getType($value)
    {
        if ($this->maybeObject($value)) return $this->obj;
        if (is_numeric($value)) return $this->num;
        if (is_bool($value)) return $this->boo;
        if (is_string($value)) return $this->str;
        if (is_array($value)) return $this->arr;
        if (is_null($value)) return null;
    }

    public function parser($data)
    {
        switch ($this->getType($data)) {
            case $this->obj:
                $obj["type"] = $this->obj;
                $obj['properties'] = [];
                foreach ($data as $key => $value) {
                    $obj['properties'][$key] = $this->parser($value);
                }
                return $obj;
                break;
            case $this->arr;
                $arr["type"] = $this->arr;
                $arr['items'] = [];
                if (!empty($data)) {
                    $arr['items'] = $this->parser($data[0]);
                } else {
                    $arr['items'] = $this->parser(null);
                }
                return $arr;
                break;
            case $this->num:
                return [
                    "type" => $this->num,
                    "example" => $data
                ];
                break;
            case $this->str:
                return [
                    "type" => $this->str,
                    "example" => $data
                ];
                break;
            case $this->boo:
                return [
                    "type" => $this->boo,
                    "example" => $data
                ];
                break;
            case null:
                return [
                    "type" => null,
                    "example" => null
                ];
                break;
        }
    }

    public function maybeObject($array)
    {
        if (!is_array($array)) return false;
        foreach ($array as $key => $value) {
            if (is_string($key)) return true;
        }
        return false;
    }
}
