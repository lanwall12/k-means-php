<?php

/**
 * An implementation of a dimension for k-means clustering
 * @author Lance Wall <elemdubya@gmail.com>
 */
class Dimension {

    private $name;
    private $description;

    /**
     * Constructor
     * @param   string  $name           The name of this dimension
     * @param   string  $description    A description for this dimension
     */
    public function __construct($name, $description='') {
        $this->name = $name;
        $this->description = $description;
    }

    /**
     * Magic getter
     * @param   string  $var_name   Attribute to receive
     */
    public function __get($var_name) {
        if (isset($this->{$var_name}))
            return $this->{$var_name};
        return null;
    }

    /**
     * Magic setter
     * @param   string  $var_name   The attribute to set
     * @param   mixed   $value      The new value for the attribute
     */
    public function __set($var_name, $value) {
        if (isset($var_name) && is_string($value))
            $this->{$var_name} = $value;
    }

    /**
     * Human readable representation of this instance
     * @return  string
     */
    public function __toString() {
        $str = 'Dimension object, id: '.$this->name;
        $str .= "\n  description: {$this->description}\n";
        return $str;
    }
}
