<?php
/*
# EMAIL HELPER
# by Danny Broadbent
*/
class EmailHelper extends Helper {
	private $body_html = NULL;
	private $body_html_header = NULL;
	private $body_html_footer = NULL;
	private $body_plain = NULL;
	private $body_plain_header = NULL;
	private $body_plain_footer = NULL;
	private $subject = NULL;
	private $from = NULL;
	private $reply = NULL;
	private $cc = NULL;
	private $bcc = NULL;
	private $to = NULL;
	private $template = NULL;
	private $contentType = 'text/plain';
	private $charset = 'UTF-8';
	
	public function __construct() {
		parent::__construct();

		$this->body_html_header = file_get_contents(EMAIL_DIR.'/global/header.html');
		$this->body_html_footer = file_get_contents(EMAIL_DIR.'/global/footer.html');
		$this->body_plain_header = file_get_contents(EMAIL_DIR.'/global/header.txt');
		$this->body_plain_footer = file_get_contents(EMAIL_DIR.'/global/footer.txt');
	}

	public function setHTMLBody($body) {
		$this->body_html = $body;
	}
	
	public function setHTMLBodyHeader($hbody) {
		$this->body_html_header = $hbody;
	}
	
	public function setHTMLBodyFooter($fbody) {
		$this->body_html_footer = $fbody;
	}
	
	public function setPlainBody($body) {
		$this->body_plain = $body;
	}
	
	public function setPlainBodyHeader($hbody) {
		$this->body_plain_header = $hbody;
	}
	
	public function setPlainBodyFooter($fbody) {
		$this->body_plain_footer = $fbody;
	}
	
	public function setSubject($subject) {
		$this->subject = $subject;
	}
	
	public function setFrom($from, $name=NULL) {
		if ($name!=NULL) {
			$this->from = $name.' <'.$from.'>';
		} else {
			$this->from = $from;
		}
	}

	public function setTemplate($tpl) {
		$this->template = $tpl;
	}
	
	public function setReplyTo($reply) {
		$this->reply = $reply;
	}
	
	public function setCC($cc) {
		$this->cc = $cc;
	}
	
	public function setBCC($bcc) {
		$this->bcc = $bcc;
	}
	
	public function setTo($to, $name=NULL) {
		if ($name!=NULL) {
			$this->to = $name.' <'.$to.'>';
		} else {
			$this->to = $to;
		}
	}

	public function getContent($type='html') {
		extract($this->getData());
		$site = $this->site;
		$user = $this->user;
		$main_url = $this->main_url;

		ob_start();
		echo ($type=='plain') ? ($this->body_plain_header!=NULL ? $this->body_plain_header:'') : ($this->body_html_header!=NULL ? $this->body_html_header:'');
		include(EMAIL_DIR.'/'.$this->template.'.'.($type=='plain'?'txt':'html'));
		echo ($type=='plain') ? ($this->body_plain_footer!=NULL ? $this->body_plain_footer:'') : ($this->body_html_footer!=NULL ? $this->body_html_footer:'');
		$content = ob_get_clean();

		preg_match_all("|{php:([^>].*):php}|U", $content, $contentArr, PREG_SET_ORDER);
		if (is_array($contentArr) && count($contentArr) > 0) {
			foreach ($contentArr as $php) {
				if (strpos($php[1],'$') !== false) {
					$content = str_replace($php[0], (eval('return $'.str_replace('$', '', $php[1]).';')), $content);
				}
			}
		}

		return $content;
	}
	
	public function email($to = NULL, $from = NULL, $subject = NULL, $body_html = NULL, $body_plain = NULL, $reply = NULL, $cc = NULL, $bcc = NULL) {
		// Check variables that have been set
		if ($to == NULL) $to = $this->to;
		if ($from == NULL) $from = $this->from;
		if ($subject == NULL) $subject = $this->subject;
		if ($body_html == NULL) $body_html = $this->body_html;
		if ($body_plain == NULL) $body_plain = $this->body_plain;
		if ($cc == NULL) $cc = $this->cc;
		if ($bcc == NULL) $bcc = $this->bcc;
		if ($reply == NULL) $reply = $this->reply;
		// If no subject title no subject
		if ($subject == NULL) {
			$subject = '(no subject)';
		}
		// Set Mime Boundary
		$semi_rand = md5(time());
		$mime_boundary = "==MULTIPART_BOUNDARY_$semi_rand";
		$mime_boundary_header = $mime_boundary;
		// Append body header and footer
		$notice_text = "This is a multi-part message in MIME format.";
		
		$message_plain = $this->getContent('plain');
		$message_html = $this->getContent('html');
		
		$body = $notice_text."\n".
		"--".$mime_boundary."\n".
		"Content-Type: text/plain; charset=us-ascii\n".
		"Content-Transfer-Encoding: 7bit\n".
		"\n".
		$message_plain."\n".
		"\n".
		"--".$mime_boundary."\n".
		"Content-Type: text/html; charset=us-ascii\n".
		"Content-Transfer-Encoding: 7bit\n".
		"\n".
		$message_html."\n".
		"\n".
		"--".$mime_boundary."--";
		
		// Set email headers
		$headers = 'From: '.$from."\r\n";
		if ($cc != NULL) {
			$headers.= 'Cc: '.$cc."\r\n";
		}
		if ($bcc != NULL) {
			$headers.= 'Bcc: '.$bcc."\r\n";
		}
		if ($reply != NULL) {
			$headers.= 'Reply-To: '.$reply."\r\n";
		}
		$headers.= "MIME-Version: 1.0"."\r\n";
		$headers.= "Content-Type: multipart/alternative;"."\r\n";
		$headers.= "	boundary=".$mime_boundary_header."\r\n";
		// Send email return true if sent or false if failed
		if (mail($to, $subject, $body, $headers)) {
			return true;
		} else {
			return false;
		}
	}
}