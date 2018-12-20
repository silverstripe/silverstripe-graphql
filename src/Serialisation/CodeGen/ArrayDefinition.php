<?php

namespace SilverStripe\GraphQL\Serialisation\CodeGen;

class ArrayDefinition implements CodeString
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var int
     */
    protected $tabLevel;

    /**
     * @var bool
     */
    protected $stripNull = true;

    /**
     * ArrayDefinition constructor.
     * @param $array
     * @param int $tabLevel
     */
    public function __construct($array, $tabLevel = 1)
    {
        $this->data = $array;
        $this->tabLevel = $tabLevel;
    }

    /**
     * return string
     */
    public function __toString()
    {
        $values = [];
        $outerTab = str_repeat("\t", $this->tabLevel);
        $innerTab = str_repeat("\t", $this->tabLevel + 1);
        foreach ($this->data as $key => $val) {
            if ($this->getStripNullValues() && $val === null) {
                continue;
            }
            $code = null;
            if ($val instanceof CodeString) {
                $code = (string) $val;
            } else if (is_object($val)) {
                $serialised = serialize($val);
                $code = sprintf('unserialize(%s)', var_export($serialised, true));
            } else {
                $code = var_export($val, true);
            }
            $values[] = sprintf('%s\'%s\' => %s,', $innerTab, $key, $code);
        }
        $arr = implode('', [
            "\n",
            implode("\n", $values),
            "\n",
            $outerTab
        ]);
        $ret = sprintf('[%s]', $arr);

        return $ret;
    }

    /**
     * @param $bool
     * @return $this
     */
    public function setStripNullValues($bool)
    {
        $this->stripNull = (bool) $bool;

        return $this;
    }

    /**
     * @return bool
     */
    public function getStripNullValues()
    {
        return $this->stripNull;
    }
}