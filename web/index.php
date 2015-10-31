<?php
#ini_set("file_uploads", "On")
require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

# Set to false in production
$app['debug'] = true;

// # One thread only. So no multiple request handling here.
// $filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
// if (php_sapi_name() === 'cli-server' && is_file($filename)) {
//     return false;
// }

$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\SecurityServiceProvider(), array(
    'security.firewalls' => array(
        'public' => array('pattern' => '^/*'), // for now set public to all!
        'default' => array(
            'pattern' => '^.*$',
            'anonymous' => true, // Needed as the login path is under the secured area
            'form' => array('login_path' => '/login', 'check_path' => '/login_check'),
            'logout' => array('logout_path' => '/logout'), // url to call for logging out
            'users' => $app->share(function() use ($app) {
                // Specific class App\User\UserProvider is described below
                return new Connection\User\UserProvider($app['db']);
            }),
        ),
    ),
    'security.access_rules' => array(
        // You can rename ROLE_USER as you wish
        array('^/.+$', 'ROLE_USER'),
        array('^/user$', ''), // This url is available as anonymous user
    )
));

# Set middleware
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));



# set up providers
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver' => 'pdo_mysql',
        'dbhost' => 'localhost',
        'dbname' => 'fauxgram',
        'user' => 'root',
        'password' => 'behance',
    ),
));
// $app['db']->insert('users', array(
// 	'email' => 'blasdceh@stxcanford.edu',
// 	'password' => $app['security.encoder.digest']->encodePassword('banaassan', ''),
// 	'fname' => 'Blexxxh',
// 	'lname' => 'Baxxxxrk',
// 	'roles' => 'ROLE_USER'
// 	));
require_once __DIR__ . '/../src/routes.php';

$app->run();
?>