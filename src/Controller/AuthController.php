<?php
// app/src/Controller/AuthController.php
namespace Controller;
	use Silex\Application;
	use Symfony\Component\HttpFoundation\Request;
	use Exception;

	class AuthController {
	    public function registerAction(Request $request, Application $app) {
			if ($_SERVER['REQUEST_METHOD'] != 'POST') {
				throw new Exception("API endpoint does not support non-POST requests!");
			}
		    $params = $request->query->all();
		    $required = array(
		    	'email',
		    	'password',
		    	'fname',
		    	'lname'
		    	);
		    $missing = array_diff_key(array_flip($required), $params);
		    echo print_r($missing);
		    if (count($missing) != 0) {
		    	throw new Exception("Passed argument keys not valid. Must be email, password, fname, lname");
		    }
		    // if (!array_key_exists ( 'email', $params )) {
		    // 	throw Exception("Passed argument keys not valid. Must be email, pwd, fname, lname")
		    // }

// $app['db']->insert('users', array(
// 	'email' => 'blasdceh@stxcanford.edu',
// 	'password' => $app['security.encoder.digest']->encodePassword('banaassan', ''),
// 	'fname' => 'Blexxxh',
// 	'lname' => 'Baxxxxrk',
// 	'roles' => 'ROLE_USER'
// 	));		    
		}
	    public function loginAction(Request $request, Application $app) {
	    	return $app->redirect('/index.php');
		}
		public function logoutAction(Request $request, Application $app) {
	    	return $app->redirect('/hello.php');
		}		
}