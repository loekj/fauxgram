<?php
namespace APIException;
    use \Exception;

    /** 
    *   Message and code are not optionional
    */
    class APIException extends Exception {
        public function __construct($message, $code, Exception $previous = null) {
            parent::__construct($message, $code, $previous);
        }
    }
?>