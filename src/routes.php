<?php

use Silex\Application\TwigTrait;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

# Controller routing
$app->get('/hello', 'Controller\PageController::helloAction');
$app->get('/', 'Controller\PageController::indexAction');
$app->get('/user', 'Controller\PageController::userAction');


## Error Handlers ##############################################################
#
$app->error(function (\Exception $e, $code) use ($app) {
	switch ($code) {
		case 404:
			$message = $app['twig']->render('404error.html.twig');
			break;
		default:
			$message = 'We are sorry, but something went terribly wrong.';
	}
	if ($app['debug']) {
		$message .= ' Error Message: ' . $e->getMessage();
	}
	return new Response($message, $code);
});
?>