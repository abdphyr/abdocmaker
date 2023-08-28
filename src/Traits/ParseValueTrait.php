<?php

namespace Abd\Docmaker\Traits;


trait ParseValueTrait
{
    const OBJ = "object";
    const NUM = "integer";
    const ARR = "array";
    const STR = "string";
    const BOO = "boolean";

    public function getType($value)
    {
        if ($this->maybeObject($value)) return self::OBJ;
        if (is_numeric($value)) return self::NUM;
        if (is_bool($value)) return self::BOO;
        if (is_string($value)) return self::STR;
        if (is_array($value)) return self::ARR;
        if (is_null($value)) return null;
    }

    public function parser($data)
    {
        switch ($this->getType($data)) {
            case self::OBJ:
                $obj["type"] = self::OBJ;
                $obj['properties'] = [];
                foreach ($data as $key => $value) {
                    $obj['properties'][$key] = $this->parser($value);
                }
                return $obj;
                break;
            case self::ARR:
                $arr["type"] = self::ARR;
                $arr['items'] = [];
                if (!empty($data)) {
                    $arr['items'] = $this->parser($data[0]);
                } else {
                    $arr['items'] = $this->parser(null);
                }
                return $arr;
                break;
            case self::NUM:
                return [
                    "type" => self::NUM,
                    "example" => $data
                ];
                break;
            case self::STR:
                return [
                    "type" => self::STR,
                    "example" => $data
                ];
                break;
            case self::BOO:
                return [
                    "type" => self::BOO,
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
