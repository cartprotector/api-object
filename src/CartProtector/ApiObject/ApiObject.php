<?php

namespace CartProtector\ApiObject;

class ApiObject implements \Serializable
{

    private static function convertApiData($apiData)
    {
        if (is_string($apiData)) {
            $apiData = @json_decode($apiData, true);
        }
        if (is_null($apiData)) {
            $apiData = [];
        }
        if ($apiData instanceof ApiObject) {
            $apiData = $apiData->export(false);
        }
        if (!is_array($apiData)) {
            throw new \Exception('ApiData must be an array, json string or null');
        }
        return $apiData;
    }

    private static function compress(array $data)
    {
        $tinyData = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (!empty($value)) {
                    $tinyData[$key] = $value;
                }
            } else {
                if ($value) {
                    $tinyData[$key] = $value;
                }
            }
        }
        return $tinyData;
    }

    protected static function importDatetime($datetime)
    {
        if ($datetime instanceof \DateTime) {
            return clone $datetime;
        }
        if ($datetime && is_int($datetime)) {
            $dt = new \DateTime();
            $dt->setTimestamp($datetime);
            return $dt;
        }
        if ($datetime && is_string($datetime)) {
            return new \DateTime($datetime);
        }
        return null;
    }

    protected static function exportDatetime(\DateTime $dateTime = null)
    {
        return $dateTime
            ? $dateTime->format('Y-m-d H:i:s')
            : null
        ;
    }

    protected function parseProperties(array $properties)
    {
        foreach ($properties as $property) {
            $method = 'set' . ucfirst($property);
            $this->$method($this->get($property));
        }
    }

    protected function exportProperties(array $properties, $compress = false)
    {
        $data = [];
        foreach ($properties as $property) {
            $data[$property] = $this->exportProperty($property, $compress);
        }
        return $data;
    }

    protected function exportProperty($property, $compress = false)
    {
        $method = 'export' . ucfirst($property);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        $method = 'get' . ucfirst($property);
        $value = $this->$method();
        if ($value instanceof ApiObject) {
            return $value->export($compress);
        }
        if ($value instanceof \DateTime) {
            return self::exportDatetime($value);
        }
        return $value;
    }



    private $apiData;

    public function __construct($apiData = null)
    {
        $this->load($apiData);
    }

    protected function load($apiData)
    {
        $this->apiData = self::convertApiData($apiData);
        $this->parse();
        $this->apiData = null;
    }

    protected function getPropertiesList()
    {
        return [];
    }

    protected function parse()
    {
        return $this->parseProperties(
            $this->getPropertiesList()
        );
    }

    protected function exportData($compress)
    {
        return $this->exportProperties(
            $this->getPropertiesList(),
            $compress
        );
    }

    /**
     * @param bool $compress
     * @return array
     */
    public function export($compress = false)
    {
        $data = $this->exportData($compress);
        if ($compress) {
            $data = self::compress($data);
        }
        return $data;
    }

    public function __toString()
    {
        return json_encode($this->export(true));
    }

    protected function getAll()
    {
        return $this->apiData;
    }

    protected function has($key)
    {
        return array_key_exists($key, $this->apiData);
    }

    protected function get($key, $default = null)
    {
        return array_key_exists($key, $this->apiData)
            ? $this->apiData[$key]
            : $default;
    }

    protected function getInt($key, $default = 0)
    {
        return (int)($this->get($key, $default));
    }

    protected function getIntMin($key, $default = 0, $min = 0)
    {
        $value = $this->get($key, null);
        if (is_null($value) || !is_numeric($value)) {
            return $default;
        }
        $value = (int)$value;
        return $value >= $min ? $value : $default;
    }

    protected function getFlag($key, $default = 0)
    {
        return $this->getInt($key, $default) ? 1 : 0;
    }

    protected function getListFlag($key)
    {
        return in_array($key, $this->apiData) ? 1 : 0;
    }

    protected function getString($key, $default = '')
    {
        $value = $this->get($key, null);
        if (is_null($value)) {
            return $default;
        }
        return (string)$value;
    }

    protected function getArray($key, $default = [])
    {
        $value = $this->get($key);
        return is_array($value) ? $value : $default;
    }

    public function serialize()
    {
        return \serialize($this->__toString());
    }

    public function unserialize($serialized)
    {
        $this->__construct(\unserialize($serialized));
    }


}