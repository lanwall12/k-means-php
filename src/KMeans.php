<?php
require_once('Cluster.php');
require_once('DataPoint.php');
require_once('Dimension.php');

/**
 * An implementation of k-means clustering
 * @author Lance Wall <elemdubya@gmail.com>
 */
class KMeans {

    private $clusters = array();
    private $dimensions = array();
    private $data = array();
    private $initialized = false;
    private $init_method = 'random';
    private $converged = false;

    /**
     * Constructor
     * @param   array   $dimensions     The list of dimensions (keys for data points, can be strings or Dimension objects)
     * @param   mixed   $clusters       The number of clusters to create or an array of preexisting clusters
     * @param   array   $data           The data points
     * @param   string  $init_method    Which initialization method to perform ('random'|'partition')
     */
    public function __construct(array $dimensions=null, $clusters=null, array $data=null, $init_method=null) {
        $this->set_dimensions($dimensions);
        if (is_array($clusters)) {
            $this->set_clusters($clusters);
            $this->init_method = false;
        } else {
            $this->set_k($clusters);
            $this->init_method = $init_method ? $init_method : 'random';
        }
        $this->set_data($data);
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
        $str = 'KMeans object';
        $str .= "\n  # of clusters: ".count($this->clusters);
        $str .= "\n  dimensions: ";
        foreach ($this->dimensions as $dim)
            $str .= $dim->name.', ';
        $str .= "\n  # of data points: ".count($this->data);
        return $str."\n";
    }

    /**
     * Create the clusters
     * @param   int $new_k  The number of clusters to create
     */
    private function set_k($new_k) {
        if (is_int($new_k)) {
            $this->clusters = array();
            foreach (range(1, $new_k) as $id)
                $this->clusters[] = new Cluster($id, $this->dimensions);
        }
    }

    /**
     * Use some existing clusters
     * @param   array   $clusters   The existing clusters to use (Cluster objects)
     */
    private function set_clusters(array $clusters) {
        foreach ($clusters as $id => $cluster) {
            if (!$cluster instanceof Cluster) {
                $this->clusters = array();
                return false;
            }
        }
        $this->clusters = $clusters;
    }

    /**
     * Set the dimensions to use
     * @param   array   $new_dims   The dimensions to use (strings)
     */
    private function set_dimensions(array $new_dims) {
        $new_dims = array_unique($new_dims);
        if (!$this->initialized) {
            foreach ($new_dims as $dim) {
                if (!($dim instanceof Dimension) && !is_scalar($dim)) {
                    $this->dimensions = array();
                    return false;
                }
                $this->dimensions[] = is_scalar($dim) ? new Dimension($dim) : $dim;
            }
        }
    }

    /**
     * Set the data points to cluster
     * @param   array   $new_data   Array of data points (keys = 'name','values'; 'values' is 'dimension' => val)
     */
    private function set_data(array $new_data) {
        if (empty($this->dimensions))
            return;
        // get rid of any malformed data points
        $filtered_data = array_filter($new_data, function($val) {
            if (!is_array($val) || empty($val))
                return false;
            if (!array_key_exists('values', $val))
                return false;
            foreach ($this->dimensions as $dim) {
                if (!array_key_exists($dim->name, $val['values']))
                    return false;
            }
            return true;
        });
        if (!empty($filtered_data)) {
            foreach ($filtered_data as $id => $point)
                $this->data[] = new DataPoint($point['values'], isset($point['name']) ? $point['name'] : "Data point {$id}");
        }
    }

    /**
     * The initialization step (set up the initial cluster means).
     * Depends on the value of $this->init_method.
     * @return  bool    Whether the initialization was successful
     */
    public function initialize() {
        // make sure all the vars are set
        if (empty($this->clusters) || empty($this->dimensions) || empty($this->data))
            return $this->initialized = false;
        if ($this->init_method === 'random' && count($this->data) >= count($this->clusters)) {
            // pick k random data points, use them as the means
            $i = 0;
            foreach (array_rand($this->data, count($this->clusters)) as $index) {
                if (!$this->clusters[$i]->set_mean(clone $this->data[$index]))
                    return $this->initialized = false;
                $i++;
            }
            return $this->initialized = true;
        } elseif ($this->init_method === false) {
            // the clusters are already set, don't change them
            foreach ($this->clusters as $cluster) {
                if (!$cluster->mean)
                    return $this->initialized = false;
            }
            return $this->initialized = true;
        } else {
            // randomly assign points, calculate means
            foreach ($this->data as $point)
                $this->clusters[array_rand($this->clusters)]->assign_point($point);
            return $this->initialized = $this->update_means();
        }
    }

    /**
     * Perform the algorithm.
     * Assign each data point to its nearest cluster.
     * Update cluster means.
     * Repeat until convergence.
     * @return  bool    Whether the algorithm was successfully performed
     */
    public function solve() {
        if (!$this->initialized)
            return false;
        while (!$this->converged) {
            foreach ($this->clusters as $id => &$cluster) {
                $cluster->clear_data();
            }
            unset($cluster);
            // perform an iteration of the algoritm
            if (!$this->assign_points())
                return false;
            if (!$this->update_means())
                return false;
            // check for convergence
            $this->converged = array_reduce($this->clusters, function($prev, $c) {
                return $prev && $c->converged;
            }, true);
        }
        return $this->converged;
    }

    /**
     * The assignment step (assign each data point to its nearest cluster)
     * @return  bool    Whether the points were successfully assigned
     */
    private function assign_points() {
        foreach ($this->data as $id => $point) {
            if (($nearest_cluster = self::find_nearest_cluster($point, $this->clusters, $this->dimensions)) === false) {
                return $this->initialized = false;
            }
            $this->clusters[$nearest_cluster]->assign_point($point);
        }
        return true;
    }

    /**
     * The update step (recalculate the means)
     * @return  bool    Whether the means were successfully recalculated
     */
    private function update_means() {
        foreach ($this->clusters as &$cluster) {
            if ($cluster->update_mean() === false)
                return $this->initialized = false;
        }
        unset($cluster);
        return true;
    }

    /**
     * Find the euclidean distance between the point and each cluster
     * Return the id of the nearest cluster to the point
     * @param   DataPoint   $point      The point to be assigned
     * @param   array       $clusters   The existing clusters
     * @param   array       $dimensions The dimensions to use for calculating the distance
     * @return  int         $cluster_id The id of the nearest cluster (or false on failure)
     */
    private static function find_nearest_cluster(DataPoint $point, array $clusters, array $dimensions) {
        $cluster_id = false;
        $min_distance = INF;
        // find distance to each cluster
        foreach ($clusters as $id => $cluster) {
            $squared_distance = 0;
            // get squared euclidean distance for this cluster
            foreach ($dimensions as $dim) {
                if ($point->get_dimension($dim) === false || $cluster->get_dimension($dim) === false)
                    return false;
                $squared_distance += pow($point->get_dimension($dim) - $cluster->get_dimension($dim), 2);
            }
            // see if this cluster is the closest
            if ($squared_distance < $min_distance) {
                $min_distance = $squared_distance;
                $cluster_id = $id;
            }
        }
        return $cluster_id;
    }
}
