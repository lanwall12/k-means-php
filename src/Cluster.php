<?php

/**
 * An implementation of a cluster for k-means clustering
 * @author Lance Wall <elemdubya@gmail.com>
 */
class Cluster {

    private $name;
    private $dimensions = array();
    private $mean;
    private $data = array();
    private $converged = false;

    /**
     * Constructor
     * @param   mixed   $name       Name of this cluster (int | string)
     * @param   array   $dimensions An array of dimension names (or Dimension objects)
     * @param   mixed   $mean       An initial mean for this cluster (DataPoint | array of string (dimension) => float)
     */
    public function __construct($name, array $dimensions=array(), array $mean=array()) {
        $this->set_name($name);
        $this->set_dimensions($dimensions);
        if (!empty($mean)) {
            if (is_array($mean))
                $mean = new DataPoint($mean);
            $this->set_mean($mean);
        }
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
        $str = 'Cluster object, id: '.$this->name;
        $str .= "\n  # of data points: ".count($this->data);
        if (!empty($this->mean)) {
            $str .= "\n  mean: ";
            foreach ($this->mean->values as $dim => $val)
                $str .= $dim.' = '.$val.', ';
        }
        return $str."\n";
    }

    /**
     * Set the name for this cluster
     * @param   string  $new_name   The new name to use for this cluster
     */
    public function set_name($new_name) {
        if (is_scalar($new_name))
            $this->name = $new_name;
    }

    /**
     * Set the dimensions for this cluster
     * @param   array   $new_dims   The new dimensions to use for this cluster (strings | Dimension objects)
     */
    public function set_dimensions(array $new_dims) {
        $this->mean = null;
        $this->data = array();
        foreach ($new_dims as $dim)
            $this->dimensions[] = is_scalar($dim) ? new Dimension($dim) : $dim;
    }

    /**
     * Set the initial mean for this cluster
     * @param   DataPoint   $mean   The mean to use for this cluster
     * @param   bool                Whether the mean was successfully set
     */
    public function set_mean(DataPoint $mean) {
        foreach ($this->dimensions as $dim) {
            if ($mean->get_dimension($dim) === false)
                return false;
        }
        $this->data = array();
        $this->mean = $mean;
        $this->mean->set_name("Cluster {$this->name} mean");
        return true;
    }

    /**
     * Remove all data points from this cluster
     */
    public function clear_data() {
        $this->data = array();
    }

    /**
     * Assign a data point to this cluster
     * @param   DataPoint   $point  The data point to assign
     * @param   bool                Whether the data point was successfully assigned
     */
    public function assign_point(DataPoint $point) {
        foreach ($this->dimensions as $dim) {
            if ($point->get_dimension($dim) === false)
                return false;
        }
        $this->data[] = $point;
        return true;
    }

    /**
     * Get the value of the mean for a specific dimension
     * @param   Dimension   $dim    The dimension for which a value will be retrieved
     * @return  float               The value of the dimension (or false on failure)
     */
    public function get_dimension(Dimension $dim) {
        if (empty($this->mean))
            return false;
        return $this->mean->get_dimension($dim);
    }

    /**
     * Recalculate the mean for this cluster
     * @return  bool    Whether the mean was successfully recalculated
     */
    public function update_mean() {
        if (empty($this->data))
            return $this->converged = true;
        $new_mean = array();
        $converged = true;
        // get the average for each dimension and check if it has converged
        foreach ($this->dimensions as $dim) {
            $dim_mean = array_sum(array_map(function($p) use ($dim) { return $p->get_dimension($dim); }, $this->data))/count($this->data);
            $converged = $converged && $dim_mean == $this->get_dimension($dim);
            $new_mean[$dim->name] = $dim_mean;
        }
        // if we haven't converged, update the mean
        if (!$converged)
            $this->mean = new DataPoint($new_mean, "Cluster {$this->name} mean");
        $this->converged = $converged;
        return true;
    }
}
