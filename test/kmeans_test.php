<?php
require(__DIR__.'/../src/KMeans.php');

$k = 3;
$dimensions = array('linkedin.com', 'bright.com', 'job', 'company', 'x', 'y');

// Clustering with no prior data
$data = array(
    array('name' => 'linkedin.com/job/1?x=1&y=2', 'values' => array('linkedin.com' => 100, 'bright.com' => 0, 'job' => 10, 'company' => 0, 'x' => 1, 'y' => 1)),
    array('name' => 'linkedin.com/job/1?x=2&y=2', 'values' => array('linkedin.com' => 100, 'bright.com' => 0, 'job' => 10, 'company' => 0, 'x' => 1, 'y' => 1)),
    array('name' => 'linkedin.com/company/1?x=1&y=2', 'values' => array('linkedin.com' => 100, 'bright.com' => 0, 'job' => 0, 'company' => 10, 'x' => 1, 'y' => 1)),
    array('name' => 'linkedin.com/job/1?x=1&y=2', 'values' => array('linkedin.com' => 100, 'bright.com' => 0, 'job' => 10, 'company' => 0, 'x' => 1, 'y' => 1)),
    array('name' => 'linkedin.com/company/1?x=1&y=2', 'values' => array('linkedin.com' => 100, 'bright.com' => 0, 'job' => 0, 'company' => 10, 'x' => 1, 'y' => 1)),
    array('name' => 'linkedin.com/job/1?x=1&y=2', 'values' => array('linkedin.com' => 100, 'bright.com' => 0, 'job' => 10, 'company' => 0, 'x' => 1, 'y' => 1)),
    array('name' => 'linkedin.com/job/1?x=1&y=2', 'values' => array('linkedin.com' => 100, 'bright.com' => 0, 'job' => 10, 'company' => 0, 'x' => 1, 'y' => 1)),
    array('name' => 'linkedin.com/company/1?x=1&y=2', 'values' => array('linkedin.com' => 100, 'bright.com' => 0, 'job' => 0, 'company' => 10, 'x' => 1, 'y' => 1)),
    array('name' => 'linkedin.com/job/1?x=1&y=2', 'values' => array('linkedin.com' => 100, 'bright.com' => 0, 'job' => 10, 'company' => 0, 'x' => 1, 'y' => 1)),
    array('name' => 'linkedin.com/job/1?x=1&y=2', 'values' => array('linkedin.com' => 100, 'bright.com' => 0, 'job' => 10, 'company' => 0, 'x' => 1, 'y' => 1)),
    array('name' => 'bright.com/job/1?x=1&y=2', 'values' => array('linkedin.com' => 0, 'bright.com' => 100, 'job' => 10, 'company' => 0, 'x' => 1, 'y' => 1)),
);
$kmeans = new KMeans($dimensions, $k, $data);
solve($kmeans);

// Clustering with prior data
$mean1 = array('linkedin.com' => 100, 'bright.com' => 0, 'job' => 10, 'company' => 0, 'x' => 1, 'y' => 1);
$mean2 = array('linkedin.com' => 0, 'bright.com' => 100, 'job' => 10, 'company' => 0, 'x' => 1, 'y' => 1);
$mean3 = array('linkedin.com' => 100, 'bright.com' => 0, 'job' => 0, 'company' => 10, 'x' => 1, 'y' => 1);
$clusters = array(
    'linkedin.com/job' => new Cluster('linkedin.com/job', $dimensions, $mean1),
    'bright.com/job' => new Cluster('bright.com/job', $dimensions, $mean2),
    'linkedin.com/company' => new Cluster('linkedin.com/company', $dimensions, $mean3),
);
$new_point = array('name' => 'linkedin.com/job/2?x=1&y=2', 'values' => array('linkedin.com' => 80, 'bright.com' => 10, 'job' => 10, 'company' => 0, 'x' => 1, 'y' => 1));
$kmeans = new KMeans($dimensions, $clusters, array($new_point));
solve($kmeans);

// Solve and print the biggest cluster
function solve($kmeans) {
    echo "\n".'Initializing: ';
    echo ($kmeans->initialize() ? 'success' : 'fail')."\n";
    echo 'Solving: ';
    echo ($kmeans->solve() ? 'success' : 'fail')."\n\n";
    echo $kmeans;

    $biggest_cluster = false;
    foreach ($kmeans->clusters as $id => $cluster) {
        echo $cluster;
        if ($biggest_cluster === false || count($cluster->data) > count($kmeans->clusters[$biggest_cluster]->data))
            $biggest_cluster = $id;
    }
    echo "\nBiggest cluster = {$kmeans->clusters[$biggest_cluster]->name}\n";
    foreach ($kmeans->clusters[$biggest_cluster]->data as $point)
        echo $point;
}
