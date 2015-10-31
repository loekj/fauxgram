<?php

use Silex\Application\TwigTrait;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

// $app->get('/', function() {
//     return new Response('WeasdasdasdSilex app');
// });
# Web routing
$app->get('/', 'Controller\PageController::indexAction');
$app->get('/register', 'Controller\AuthController::registerAction');
$app->get('/login', 'Controller\AuthController::loginAction');
$app->get('/logout', 'Controller\AuthController::logoutAction');

# Admin routing
#$app->get('/phpmyadmin', 'Controller\AdminController::phpmyadminAction');


## Error Handlers ##############################################################
#
$app->error(function (\Exception $e, $code) use ($app) {
	if ($code == 404) {
		$message = $app['twig']->render('404error.html.twig');
		break;
	} else {
		$message = 'Something went wrong...<br>';
	}
	if ($app['debug']) {
		$message .= '&emsp;Error Message:<br>&emsp;' . $e->getMessage();
	}
	return new Response($message, $code);
});
?>