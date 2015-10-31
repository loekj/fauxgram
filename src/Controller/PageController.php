<?php
// app/src/Controller/PageController.php
namespace Controller;
	use Silex\Application;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpFoundation\Response;

	class PageController {
	    public function indexAction(Request $request, Application $app) {
	        return $app['twig']->render('index.html.twig', array(
	        	'last_email' => $app['session']->get('_security.last_email'),
	        ));
	    }
	}