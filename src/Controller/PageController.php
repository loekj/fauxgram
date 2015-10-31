<?php
// app/src/Controller/PageController.php
namespace Controller;
	use Silex\Application;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpFoundation\Response;

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
	    public function registerAction(Request $request, Application $app) {
	    	
	  //   	$dbhost = 'localhsost';
			// $dbuser = 'root';
			// $dbpass = 'behance';
			// $db = 'fauxgram';
			// $mysqli = new mysqli($dbhost, $dbuser, $dbpass, $db);

			// if( $mysqli->connect_errno ) {
			// 	$message = $app['twig']->render('404error.html.twig');
			// 	return new Response($message, $mysqli->connect_errno);
			// }

			#$pwd = 'test1';
			#$pwd_insert = $app['security.encoder.digest']->encodePassword($pwd, '');
			#$sql = "INSERT INTO users (email, password, fname, lname, roles) VALUES ('ljanssen@stanford.edu', 'bnaan', 'Loek', 'Janssen', 'ROLE_USER');";

			// if(! $mysqli->query($sql)) {
			// 	$message = $app['twig']->render('404error.html.twig');
			// 	return new Response($message, $mysqli->connect_errno);
			// }
			// $mysqli->close();
			#return $app->redirect('/index.php');
		}
}