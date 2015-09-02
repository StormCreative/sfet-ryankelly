<?php
/*
# EMAIL HELPER
# by Danny Broadbent
*/
require_once(SYS_PATH.'/plugins/dompdf/dompdf_config.inc.php');

class PDFHelper extends Helper {
	private $dompdf = NULL;
	private $html = NULL;
	private $template = NULL;
	private $output_file = NULL;
	private $header = NULL;
	private $footer = NULL;

	public function __construct() {
		parent::__construct();

		$this->dompdf = new DOMPDF();
	}

	public function setOutputFile($file) {
		$this->output_file = PUB_DIR.'/pdfs'.$file;
	}

	public function setTemplate($tpl) {
		$this->template = $tpl;
	}

	public function getContent() {
		extract($this->getData());
		$site = $this->site;
		$user = $this->user;
		$main_url = $this->main_url;

		ob_start();
		echo '<html>';
		echo '<style>';
		require_once(CSS_PATH.'/form-pdf.css');
		echo '</style>';
		echo '<body>';
		echo ($this->header!=NULL ? $this->header:'');
		include(PDF_DIR.'/'.$this->template.'.html');
		echo ($this->footer!=NULL ? $this->footer:'');
		echo '</body>';
		echo '</html>';
		$content = ob_get_clean();

		preg_match_all("|{php:([^>].*):php}|U", $content, $contentArr, PREG_SET_ORDER);
		if (is_array($contentArr) && count($contentArr) > 0) {
			foreach ($contentArr as $php) {
				if (strpos($php[1],'$') !== false) {
					$content = str_replace($php[0], (eval('return $'.str_replace('$', '', $php[1]).';')), $content);
				}
			}
		}

		$this->dompdf->load_html($content);
	}

	public function generate() {
		$this->dompdf->set_paper('letter', 'portrait');

		$this->getContent();

		$this->dompdf->render();

		file_put_contents($this->output_file, $this->dompdf->output());
	}
}