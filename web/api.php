<?php

use Controller\APIController;

require_once __DIR__.'/../vendor/autoload.php';
// $app = new Silex\Application();

// # Set to false in production
// $app['debug'] = true;


// $app->register(new Silex\Provider\SessionServiceProvider());
// $app->register(new Silex\Provider\SecurityServiceProvider(), array(
//     'security.firewalls' => array(
//         'public' => array('pattern' => '^/*'), // for now set public to all!
//         'default' => array(
//             'pattern' => '^.*$',
//             'anonymous' => true, // Needed as the login path is under the secured area
//             'form' => array('login_path' => '/', 'check_path' => '/login_check'),
//             'logout' => array('logout_path' => '/logout'), // url to call for logging out
//             'users' => $app->share(function() use ($app) {
//                 // Specific class App\User\UserProvider is described below
//                 return new Connection\User\UserProvider($app['db']);
//             }),
//         ),
//     ),
//     'security.access_rules' => array(
//         // You can rename ROLE_USER as you wish
//         array('^/.+$', 'ROLE_USER'),
//         array('^/user$', ''), // This url is available as anonymous user
//     )
// ));

// // # Set middleware
// // $app->register(new Silex\Provider\TwigServiceProvider(), array(
// //     'twig.path' => __DIR__.'/../views',
// // ));


// # set up providers
// $app->register(new Silex\Provider\DoctrineServiceProvider(), array(
//     'db.options' => array(
//         'driver' => 'pdo_mysql',
//         'dbhost' => 'localhost',
//         'dbname' => 'fauxgram',
//         'user' => 'root',
//         'password' => 'behance',
//     ),
// ));

//require_once __DIR__ . '/../src/routes.php';

// Requests from the same server don't have a HTTP_ORIGIN header
if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

try {
    $API = new APIController($_REQUEST['request'], $_SERVER['HTTP_ORIGIN']);
    echo $API->processAPI();
} catch (Exception $e) {
    echo json_encode(Array('error' => $e->getMessage()));
}
#$app->run();
?>