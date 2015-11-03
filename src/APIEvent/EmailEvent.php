<?php
namespace APIEvent;
    use Symfony\Component\EventDispatcher\Event;

	class EmailEvent extends Event {
		protected $email_owner = Null;
		protected $email_array = Null;
		protected $comment = Null;
		protected $imgid = Null;
		protected $email_commenter = Null;

		public function __construct($email_owner, $email_commenter, $email_array, $comment, $imgid) {
			$this->email_array = $email_array;
			$this->email_owner = $email_owner;
			$this->email_commenter = $email_commenter;
			$this->comment = $comment;
			$this->imgid = $imgid;

		}

		/*
		* Getter methods
		*/
		public function getEmailArray() {
			return $this->email_array;
		}

		public function getEmailOwner() {
			return $this->email_owner;
		}

		public function getEmailCommenter() {
			return $this->email_commenter;
		}

		public function getComment() {
			return $this->comment;
		}

		public function getImgId() {
			return $this->imgid;
		}
	}
?>