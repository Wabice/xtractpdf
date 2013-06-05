<?php

namespace XtractPDF\Library;

/**
 * Abstract Model Class
 */
abstract class Model
{
    public function __get($item)
    {
        if ($item{0} != '_') {
            return $this->$item;
        }
    }

    // --------------------------------------------------------------

    public function __isset($item)
    {
        return ($item{0} != '_') ? isset($this->$item) : false;
    }

    // --------------------------------------------------------------

    public function toArray()
    {
        $arr = array();

        foreach(get_object_vars($this) as $k => $v) {
            if ($k{0} != '_') {
                $arr[$k] = $v;
            }
        }

        return $arr;
    }    
}

/* EOF: Model.php */