<?php
#ini_set("file_uploads", "On")
require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

# Set to false in production
$app['debug'] = true;

# One thread only. So no multiple request handling here.
$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

# Set middleware
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));


require_once __DIR__ . '/../src/routes.php';

$app->run();
?>