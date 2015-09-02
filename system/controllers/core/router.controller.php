<?php
/*
# ROUTER CONTROLLER
# by Danny Broadbent
*/
class RouterController extends SystemController {
	protected $routes = array();
	protected $admin_routes = array();
	
	public function __construct() {
		parent::__construct();

		// Generate custom routes
		$jsonRoutes = json_decode(file_get_contents(SYS_PATH.'/routes.json'), true);
		foreach ($jsonRoutes as $path => $controller) {
			if (strlen((string)trim($path))>0 && strlen((string)trim($controller))>0) {
				$vals = array();
				$pathCheck = explode('/', $path);
				$route='';
				$posCount = 1;
				foreach ($pathCheck as $val) {
					if (strpos($val,'{') !== false && strpos($val,'}') !== false) {
						$route.='\/([_a-zA-Z0-9- ]+)';
						$vals[] = array(
							'key' => str_replace('{', '', str_replace('}', '', $val)),
							'pos' => $posCount
						);
					} else
						$route.= '\/'.$val;
					$posCount++;
				}
				$this->routes[(string)$route] = array(
					'controller' => $controller,
					'values' => $vals
				);
			}
		}

		// Generate custom admin routes
		$jsonAdminRoutes = json_decode(file_get_contents(DIR_ADMIN.'/routes.json'), true);
		foreach ($jsonAdminRoutes as $path => $controller) {
			if (strlen((string)trim($path))>0 && strlen((string)trim($controller))>0) {
				$vals = array();
				$pathCheck = explode('/', $path);
				$route='';
				$posCount = 2;
				foreach ($pathCheck as $val) {
					if (strpos($val,'{') !== false && strpos($val,'}') !== false) {
						$route.='\/([_a-zA-Z0-9- ]+)';
						$vals[] = array(
							'key' => str_replace('{', '', str_replace('}', '', $val)),
							'pos' => $posCount
						);
					} else
						$route.= '\/'.$val;
					$posCount++;
				}
				$this->admin_routes[(string)$route] = array(
					'controller' => $controller,
					'values' => $vals
				);
			}
		}
	}
	
	public function loadhtc() {
		$cssPath = CSS_PATH;
		if ($this->isAdminPath) $cssPath = CSS_ADMIN;
		header("Content-type: text/x-component");
		$include = $cssPath.'/'.preg_replace("/^\/".CSS_URL."\/(.*).htc\$/", "$1.htc", $this->urlPass);
		require_once($include);
		ob_end_flush();
		exit;
	}
	
	public function loadjs() {
		$main_url = $this->main_url;
		$jsPath = JS_PATH;
		if ($this->isAdminPath) $jsPath = JS_ADMIN;
		if (isset($this->_get['module'])) $module = $this->_get['module'];
		if (preg_match("/^\/".JS_URL."\/global.js$/", $this->urlPass) && file_exists($jsPath.'/global.js')) {
			$include = $jsPath.'/'.preg_replace("/^\/".JS_URL."\/(.*).js\$/", "$1.php", $this->urlPass);
		} else {
			$include = $jsPath.'/'.preg_replace("/^\/".JS_URL."\/(.*).js\$/", "$1.js", $this->urlPass);
		}
		ob_start();
		include($include);
		$getJSData = ob_get_clean();
		preg_match_all("|{php:([^>].*):php}|U", $getJSData, $getJSDataArr, PREG_SET_ORDER);
		if (is_array($getJSDataArr) && count($getJSDataArr) > 0) {
			foreach ($getJSDataArr as $php) {
				if (strpos($php[1],'$') !== false) {
					$getJSData= str_replace($php[0], (eval('return $'.str_replace('$', '', $php[1]).';')), $getJSData);
				}
			}
		}
		header("Content-type: application/x-javascript");
		$version = floatval('1.'.filemtime($include));
		if (isset($this->_server["HTTP_IF_NONE_MATCH"]) && $this->_server["HTTP_IF_NONE_MATCH"] == $version) {
			header("HTTP/1.0 304 Not Modified");
			exit;
		}
		header("ETag: ".$version);
		ob_start("ob_gzhandler");
		// Remove comments
		$getJSData = ($this->ENV=='LIVE') ? preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $getJSData) : $getJSData;
		// Remove space after colons
		$getJSData = ($this->ENV=='LIVE') ? str_replace(': ', ':', $getJSData) : $getJSData;
		// Remove whitespace and line breaks
		//$getJSData = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '   ', '    '), '', $getJSData);
		$getJSData = ($this->ENV=='LIVE') ? str_replace(array("\t", '  ', '   ', '    '), '', $getJSData) : $getJSData;
		echo $getJSData;
		ob_end_flush();
		exit;
	}
	
	public function loadcss() {
		$cssPath = CSS_PATH;
		if ($this->isAdminPath) $cssPath = CSS_ADMIN;
		header("Content-type: text/css");
		$include = $cssPath.'/'.preg_replace("/^\/".CSS_URL."\/(.*).css\$/", "$1.css", $this->urlPass);
		if (file_exists($include)) {
			$version = floatval('1.'.filemtime($include));
			if (isset($this->_server["HTTP_IF_NONE_MATCH"]) && $this->_server["HTTP_IF_NONE_MATCH"] == $version) {
				header("HTTP/1.0 304 Not Modified");
				exit;
			}

			ob_start();
			include($include);
			$cssData = ob_get_clean();
			// Remove comments
			$cssData = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $cssData);
			// Remove space after colons
			$cssData = str_replace(': ', ':', $cssData);
			// Remove whitespace
			$cssData = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '   ', '    '), '', $cssData);
			$cssData = str_replace(' {','{', $cssData);
			$cssData = str_replace(', ',',', $cssData);

			header("ETag: ".$version);
			ob_start("ob_gzhandler");
			echo $cssData;
			ob_end_flush();
		}
		exit;
	}
	
	public function loadimg($type='jpg') {
		error_reporting(E_ALL);
		ini_set('display_errors','on');
		$imgPath = IMG_PATH;
		if ($this->isAdminPath) $imgPath = IMG_ADMIN;
		if ($type=='jpg') {
			header("Content-Type: image/jpeg");
			$include = $imgPath.'/'.preg_replace("/^\/".IMG_URL."\/(.*).jpg\$/", "$1", $this->urlPass).'.jpg';
			$version = floatval('1.'.filemtime($include));
			if (isset($this->_server["HTTP_IF_NONE_MATCH"]) && $this->_server["HTTP_IF_NONE_MATCH"] == $version) {
				header("HTTP/1.0 304 Not Modified");
				exit;
			}
			$img = @imagecreatefromjpeg($include);
			if(!$img) {
				$img  = imagecreatetruecolor(150, 30);
				$bgc  = imagecolorallocate($img, 255, 255, 255);
				$tc   = imagecolorallocate($img, 0, 0, 0);
				imagefilledrectangle($img, 0, 0, 150, 30, $bgc);
				imagestring($img, 1, 5, 5, 'Error loading ' . $imgname, $tc);
			}
			header("ETag: ".$version);
			if (file_exists($include)) {
				imagejpeg($img);
				imagedestroy($img);
			} else {
				require_once($imgPath.'/error.jpg');
			}
			require_once($include);
			exit;
		} else if ($type=='png') {
			header("Content-Type: image/png");
			$include = $imgPath.'/'.preg_replace("/^\/".IMG_URL."\/(.*).png\$/", "$1", $this->urlPass).'.png';
			$version = floatval('1.'.filemtime($include));
			if (isset($this->_server["HTTP_IF_NONE_MATCH"]) && $this->_server["HTTP_IF_NONE_MATCH"] == $version) {
				header("HTTP/1.0 304 Not Modified");
				exit;
			}
			$img = imagecreatefrompng($include);
			imagealphablending($img, true);
			imagesavealpha($img, true);
			if(!$img){
				$img  = imagecreatetruecolor(150, 30);
				$bgc  = imagecolorallocate($img, 255, 255, 255);
				$tc   = imagecolorallocate($img, 0, 0, 0);
				imagefilledrectangle($img, 0, 0, 150, 30, $bgc);
				imagestring($img, 1, 5, 5, 'Error loading ' . $imgname, $tc);
			}
			header("ETag: ".$version);
			header("Exists: ". file_exists($include));
			if (file_exists($include)) {
				imagepng($img);
				imagedestroy($img);
			} else {
				require_once($imgPath.'/error.png');
			}
			exit;
		} else if ($type=='gif') {
			header("Content-Type: image/gif");
			$include = $imgPath.'/'.preg_replace("/^\/".IMG_URL."\/(.*).gif\$/", "$1", $this->urlPass).'.gif';
			$version = floatval('1.'.filemtime($include));
			if (isset($this->_server["HTTP_IF_NONE_MATCH"]) && $this->_server["HTTP_IF_NONE_MATCH"] == $version) {
				header("HTTP/1.0 304 Not Modified");
				exit;
			}
			$img = @imagecreatefromgif($include);
			if(!$img){
				$img  = imagecreatetruecolor(150, 30);
				$bgc  = imagecolorallocate($img, 255, 255, 255);
				$tc   = imagecolorallocate($img, 0, 0, 0);
				imagefilledrectangle($img, 0, 0, 150, 30, $bgc);
				imagestring($img, 1, 5, 5, 'Error loading ' . $imgname, $tc);
			}
			header("ETag: ".$version);
			if (file_exists($include)) {
				imagegif($img);
				imagedestroy($img);
			} else {
				require_once($imgPath.'/error.gif');
			}
			exit;
		}
	}
	
	public function loadvideo($type='mp4') {
		if ($type=='mp4') {
			$path = DATA_PATH.'/'.VID_URL.'/'.preg_replace("/^\/".VID_URL."\/(.*)\$/", "$1", $this->urlPass);
			$version = floatval('1.'.filemtime($path));
			$size=filesize($path);
			$fm=@fopen($path,'rb');
			if(!$fm) {
				// You can also redirect here
				header ("HTTP/1.0 404 Not Found");
				exit;
			}
			$begin=0;
			$end=$size;
			if(isset($this->_server['HTTP_RANGE'])) {
				if(preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $this->_server['HTTP_RANGE'], $matches)) {
					$begin=intval($matches[0]);
					if(!empty($matches[1])) {
						$end=intval($matches[1]);
					}
				}
			}

			if($begin>0||$end<$size)
				('HTTP/1.0 206 Partial Content');
			else
				header('HTTP/1.0 200 OK');

			header("Content-Type: video/mp4");
			header('Accept-Ranges: bytes');
			header('Content-Length:'.($end-$begin));
			header("Content-Disposition: inline;");
			header("Content-Range: bytes $begin-$end/$size");
			header("Content-Transfer-Encoding: binary\n");
			header('Connection: close');
			header("ETag: ".$version);

			$cur=$begin;
			fseek($fm,$begin,0);
			
			while(!feof($fm)&&$cur<$end&&(connection_status()==0)) {
				print fread($fm,min(1024*16,$end-$cur));
				$cur+=1024*16;
				usleep(1000);
			}
			exit;
		}
	}
	
	public function load() {
		$matchUrl = false;
		$ctrlPath = CTRL_PATH;
		if ($this->isAdminPath) {
			$ctrlPath = CTRL_ADMIN;
		}

		if (preg_match("/^\/".CSS_URL."\/(.*).css$/", $this->urlPass) || preg_match("/^\/".URL_ADMIN."\/".CSS_URL."\/(.*).css$/", $this->urlPass)) {
			$this->loadcss();
		} else if (preg_match("/^\/".JS_URL."\/(.*).js$/", $this->urlPass) || preg_match("/^\/".URL_ADMIN."\/".JS_URL."\/(.*).js$/", $this->urlPass)) {
			$this->loadjs();
		} else if (preg_match("/^\/".CSS_URL."\/(.*).htc$/", $this->urlPass) || preg_match("/^\/".URL_ADMIN."\/".CSS_URL."\/(.*).htc$/", $this->urlPass)) {
			$this->loadhtc();
		} else if (preg_match("/^\/".IMG_URL."\/(.*).jpg$/", $this->urlPass) || preg_match("/^\/".URL_ADMIN."\/".IMG_URL."\/(.*).jpg$/", $this->urlPass)) {
			$this->loadimg('jpg');
		} else if (preg_match("/^\/".IMG_URL."\/(.*).png$/", $this->urlPass) || preg_match("/^\/".URL_ADMIN."\/".IMG_URL."\/(.*).png/", $this->urlPass)) {
			$this->loadimg('png');
		} else if (preg_match("/^\/".IMG_URL."\/(.*).gif$/", $this->urlPass) || preg_match("/^\/".URL_ADMIN."\/".IMG_URL."\/(.*).gif$/", $this->urlPass)) {
			$this->loadimg('gif');
		} else if (preg_match("/^\/".VID_URL."\/(.*).mp4$/", $this->urlPass) || preg_match("/^\/".VID_URL."\/(.*).webm$/", $this->urlPass) || preg_match("/^\/".URL_ADMIN."\/".VID_URL."\/(.*).mp4$/", $this->urlPass) || preg_match("/^\/".URL_ADMIN."\/".VID_URL."\/(.*).webm$/", $this->urlPass)) {
			$this->loadvideo('mp4');
		} else if (preg_match("/^\/robots.txt$/", $this->urlPass)) {
			require_once(SYS_PATH.'/robots.txt');
			exit;
		}
		
		if (count($this->routes)>0) {
			foreach ($this->routes as $path => $arr) {
				if (preg_match("/^".$path."$/", $this->urlPass)) {
					$matchUrl = $path;
					$matchPath = 'view';
					$controller = $arr['controller'];
					$values = $arr['values'];
				}
			}
		}

		if (count($this->admin_routes)>0 && $this->isAdminPath) {
			foreach ($this->admin_routes as $path => $arr) {
				if (preg_match("/^\/".URL_ADMIN.$path."$/", $this->urlPass)) {
					$matchUrl = $path;
					$matchPath = 'admin';
					$controller = $arr['controller'];
					$values = $arr['values'];
				}
			}
		}
		
		if ($matchUrl !== false) {
			$chkURL = explode('/', $controller);
			$file = str_replace('-', '_', $chkURL[count($chkURL)-2]);
			$method = str_replace('-', '_', $chkURL[count($chkURL)-1]);
			if (strlen((string)trim(strtolower($file)))==0 && strlen((string)trim(strtolower($method)))>0) {
				$file = str_replace('-', '_', $chkURL[count($chkURL)-1]);
			}
			$path = str_replace($file, '', str_replace('/'.$method, '', str_replace('/'.$file, '',  str_replace('-', '_', '/'.str_replace('/'.URL_ADMIN, '', $controller)))));
		} else {
			$chkURL = explode('/', $this->urlPass);
			$file = str_replace('-', '_', $chkURL[count($chkURL)-2]);
			$method = str_replace('-', '_', $chkURL[count($chkURL)-1]);
			if (strlen((string)trim(strtolower($file)))==0 && strlen((string)trim(strtolower($method)))>0) {
				$file = str_replace('-', '_', $chkURL[count($chkURL)-1]);
			}
			$path = str_replace($file, '', str_replace('/'.$file, '', str_replace('/'.$method, '', str_replace('-', '_', str_replace('/'.URL_ADMIN, '', $this->urlPass)))));
		}

		if ($chkURL[1] != '') {
			$controllerSetup = false;
			if (strpos($file,'_') !== false) {
				$controller = str_replace('_', ' ', $file);
				$controller = ucwords($controller);
				$controller = str_replace(' ', '', $controller);
			} else
				$controller = ucwords($file);

			if (strpos($method,'_') !== false) {
				$controllerMethod = str_replace('_', ' ', $method);
				$controllerMethod = ucwords($controllerMethod);
				$controllerMethod = str_replace(' ', '', $controllerMethod);
			} else
				$controllerMethod = ucwords($method);

			if (
				file_exists($ctrlPath.$path.'/'.$method.'.controller.php') ||
				file_exists($ctrlPath.$path.'/'.$file.'/'.$method.'.controller.php')
			) {
				if (file_exists($ctrlPath.$path.'/'.$method.'.controller.php')) {
					require_once($ctrlPath.$path.'/'.$method.'.controller.php');
					$setView = $method;
				} else {
					require_once($ctrlPath.$path.'/'.$file.'/'.$method.'.controller.php');
					$setView = $file.'/'.$method;
					$preController = ($file != $controllerMethod) ? str_replace(' ', '', ucwords(str_replace('_', ' ', $file))) : '';
					$preControllerPath = ($path != $controllerMethod) ? str_replace(' ', '', ucwords(str_replace('_', ' ', str_replace('/', ' ', $path)))) : '';
					$controllerMethod = ($preController!=$controllerMethod?$preController:$preControllerPath).$controllerMethod;
				}

				$controllerMethod = $controllerMethod.'Controller';
				$loadController = new $controllerMethod();

				if (!method_exists($loadController, 'view')) {
					unset($controllerModule);
					unset($loadController);
				} else {
					$controller = $controllerMethod;
					$method = 'view';
					$controllerSetup = true;
				}
			}

			if ($controllerSetup==false && file_exists($ctrlPath.$path.'/'.$file.'.controller.php')) {
				$method = (trim($file) == trim($method) || $method == '') ? 'view' : $method;
				$preController = str_replace(' ', '', ucwords(str_replace('_', ' ', $file)));
				$preControllerPath = ($path != $controllerMethod) ? str_replace(' ', '', ucwords(str_replace('_', ' ', str_replace('/', ' ', $path)))) : '';
				$controllerFile = ($preController!=$controller?$preController:$preControllerPath).$controller.'Controller';
				require_once($ctrlPath.$path.'/'.$file.'.controller.php');
				$setView = $file.'/'.$method;
				$loadController = new $controllerFile();
				if (!method_exists($loadController, $method)) {
					unset($controller);
					unset($loadController);
					$this->setResponseCode(404);
					require_once($ctrlPath.'/error.controller.php');
					$load = new ErrorController;
					$load->view($this->getResponseCode());
					$load->renderView();
				} else {
					$controller = $controllerFile;
					$controllerSetup = true;
				}
			}

			if ($controllerSetup) {
				$loadController = new $controller();
				if ($matchUrl != false) {
					$i=count($values);
					// Register variables from custom routes as __get method
					foreach ($values as $part) {
						$chkURL = explode('/', $this->urlPass);
						$loadController->__set($part['key'] , $chkURL[(int)$part['pos']]);
						$i--;
					}
				}
				$loadController->setView($setView);
				$loadController->{$method}();
				$loadController->renderView();
			} else {
				$this->setResponseCode(404);
				require_once($ctrlPath.'/error.controller.php');
				$load = new ErrorController;
				$load->view($this->getResponseCode());
				$load->renderView();
			}
		} else if ($chkURL[1] == '') {
			$this->setResponseCode(200);
			require_once($ctrlPath.'/home.controller.php');
			$load = new HomeController;
			$load->view();
			$load->renderView();
		}
	}
}