<?php
namespace Controller;
	use Silex\Application;
	use Symfony\Component\HttpFoundation\Request;

	class PageController {
    public function indexAction(Request $request, Application $app) {
        return $app['twig']->render('index.html.twig', array());
    }
    public function helloAction(Request $request, Application $app) {
        return $app['twig']->render('hello.html.twig', array());
    }    
    public function userAction() {
	    # Database dummy
		$images = array(
			'0001' => array(
				'name' => 'Flower'
			),
			'0002' => array(
				'name' => 'Butterfly'
			),
		);
		return json_encode($images);
	}
}