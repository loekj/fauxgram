<?php
// app/src/Controller/AdminController.php
namespace Controller;
	use Silex\Application;
	use Symfony\Component\HttpFoundation\Request;

	class AdminController {
    public function phpmyadminAction(Request $request, Application $app) {
		return $app->redirect('/phpmyadmin/index.php');
	}	
}