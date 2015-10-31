<?php
# app/src/Controller/APIController.php
namespace Controller;
    use Silex\Application;
    use Symfony\Component\HttpFoundation\Request;
    require_once 'APIHandler.php';
    class APIController extends APIHandler {
        //protected $User;

        public function __construct($request, $origin) {
            parent::__construct($request);

            // // Abstracted out for example
            // $APIKey = new Models\APIKey();
            // $User = new Models\User();

            // if (!array_key_exists('apiKey', $this->request)) {
            //     throw new Exception('No API Key provided');
            // } else if (!$APIKey->verifyKey($this->request['apiKey'], $origin)) {
            //     throw new Exception('Invalid API Key');
            // } else if (array_key_exists('token', $this->request) &&
            //      !$User->get('token', $this->request['token'])) {

            //     throw new Exception('Invalid User Token');
            // }

            // $this->User = $User;
        }

        /**
         * Example of an Endpoint
         */
         protected function example() {
            if ($this->method == 'GET') {
                //return "Your name is " . $this->User->name;
                return "Your name is MARK";
            } else {
                return "Only accepts GET requests";
            }
         }
     }
?>