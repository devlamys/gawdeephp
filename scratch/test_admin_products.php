<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['view'] = 'products';
$_GET['edit'] = 'new';
define('GAWDEE_ROOT', __DIR__ . '/..');
require __DIR__ . '/../includes/data.php';
require __DIR__ . '/../admin/partials/products.php';
echo "\nTEST PASSED FOR edit=new\n";

$_GET['edit'] = 'gawdee-gir-cow-a2-ghee-500-ml';
require __DIR__ . '/../admin/partials/products.php';
echo "\nTEST PASSED FOR edit=gawdee-gir-cow-a2-ghee-500-ml\n";
