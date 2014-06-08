<?php

/**
 * An implementation of a data point for k-means clustering
 * @author Lance Wall <elemdubya@gmail.com>
 */
class DataPoint {

    private $name;
    private $dimensions = array();
    private $values = array();

    /**
     * Constructor
     * @param   array   $data   The values for this data point (dimension => value)
     */
    public function __construct(array $data, $name='') {
        $this->set_dimensions(array_keys($data));
        $this->set_values($data);
        $this->set_name($name);
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
     * Human readable representation of this instance
     * @return  string
     */
    public function __toString() {
        $str = 'DataPoint object, id: '.$this->name;
        $str .= "\n  values: ";
        foreach ($this->values as $dim => $val)
            $str .= $dim.' = '.$val.', ';
        return $str."\n";
    }

    /**
     * Set the dimensions for this data point
     * @param   array   $new_dims   The new dimensions to use for this data point (strings | Dimension objects)
     */
    public function set_dimensions(array $new_dims) {
        foreach ($new_dims as $dim)
            $this->dimensions[] = is_scalar($dim) ? new Dimension($dim) : $dim;
    }

    /**
     * Set the values to use for this data point
     * @param   array   $data   The values ('dimension' => value)
     * @return  bool            Whether the values were successfully set
     */
    public function set_values(array $data) {
        foreach ($this->dimensions as $dim) {
            if (!array_key_exists($dim->name, $data) || !is_numeric($data[$dim->name])) {
                return false;
            }
        }
        $this->values = $data;
        return true;
    }

    /**
     * Set the name for this data point
     * @param   string  $new_name   The new name to use for this data point
     */
    public function set_name($new_name) {
        if (is_scalar($new_name))
            $this->name = $new_name;
    }

    /**
     * Get the value of this data point for a specific dimension
     * @param   Dimension   $dim    The dimension for which a value will be retrieved
     * @return  float               The value for the dimension (or false on failure)
     */
    public function get_dimension(Dimension $dim) {
        if (!in_array($dim, $this->dimensions))
            return false;
        return $this->values[$dim->name];
    }
}
