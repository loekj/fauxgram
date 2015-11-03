<?php
# app/src/Controller/APIController.php
namespace APIEvent;
    use Symfony\Component\EventDispatcher\EventDispatcher;
    use Symfony\Component\EventDispatcher\Event;
    use APIException\APIException as APIException;
    use Exception;
    use APIEvent;
    use PDO;

class APIListener {

	private function _mailCommenters($email, $email_commenter, $email_owner, $content, $imgid){
		$subject = $email_commenter . " commented on a thread you follow!";
		$body = $email_commenter . " commented on " . $email_owner . "'s photo with ID " . $imgid .
			PHP_EOL . PHP_EOL . "Comment:" . PHP_EOL . "'" . $content . "'" . PHP_EOL;
		$headers = 'From: admin@fauxgram.com \r\n' .
    				'Reply-To: admin@fauxgram.com \r\n' .
    				'X-Mailer: PHP/' . phpversion();
		mail($email, $subject, $body, $headers);
	}

	private function _mailOwner($email_owner, $email_commenter, $content, $imgid){
		$subject = $email_commenter . " commented on your photo!";
		$body = $email_commenter . PHP_EOL . " commented on your photo with ID " . $imgid .
			PHP_EOL . PHP_EOL . "Comment:" . PHP_EOL . "'" . $content . "'" . PHP_EOL;
		$headers = 'From: admin@fauxgram.com r\n' .
    				'Reply-To: admin@fauxgram.com \r\n' .
    				'X-Mailer: PHP/' . phpversion();
		mail($email_owner, $subject, $body, $headers);
	}	

	public function onNewComment(EmailEvent $email_event_obj) {
		$email_owner = $email_event_obj->getEmailOwner();
		$email_array = $email_event_obj->getEmailArray();
		$email_commenter = $email_event_obj->getEmailCommenter();
		$content = $email_event_obj->getComment();
		$imgid = $email_event_obj->getImgId();

		$closure = function($email) use ($email_commenter, $email_owner, $content, $imgid) {
			return ($this->_mailCommenters($email, $email_commenter, $email_owner, $content, $imgid));
		};
		array_map($closure, $email_array);
		if ($email_owner != $email_commenter) {
			$this->_mailOwner($email_owner, $email_commenter, $content, $imgid);
		}
	}
}
?>