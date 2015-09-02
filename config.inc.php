<?php
/*
# CONFIG
# by Danny Broadbent
*/
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1
header('Pragma: no-cache'); // HTTP 1.0
header('Expires: 0'); // Proxies
ini_set('session.name', 'secure-session');
set_time_limit(30);
session_start();

date_default_timezone_set('Europe/London');

$isMaintenance=false;
$page_load_start_time = microtime(true);

// AUTO = select for me based on my environment, LIVE = production only, LOCAL = development only
define("MYSQL_USE","AUTO");

// Full = Everything cached, Partial = Template only (excludes header/footer), None = No caching
define("CACHE_LVL","Partial");

// Debug Errors (uncomment in production mode)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Connection Settings (Live)
define("MYSQL_HOST", "localhost", true);
define("MYSQL_DATABASE", "", true);
define("MYSQL_USER", "", true);
define("MYSQL_PASSWORD", "", true);
define("MYSQL_PORT", 3306, true);

// Database Connection Settings (Local)
define("MYSQL_HOST_LOCAL", "localhost", true);
define("MYSQL_DATABASE_LOCAL", "mvc_main", true);
define("MYSQL_USER_LOCAL", "root", true);
define("MYSQL_PASSWORD_LOCAL", "root", true);
define("MYSQL_PORT_LOCAL", 3306, true);

// Server Password - Once live DO NOT change
define("PASSWORD_SALT","(o3~1n5ur4nc3)");

// Maximum file size for uploads (10MB)
define("MAX_UPLOAD_SIZE", 10000000);
ini_set('upload_max_filesize', (MAX_UPLOAD_SIZE/1000000).'M');

// System Paths - No trailing slash
define("DIR_PATH", $_SERVER["DOCUMENT_ROOT"]);
define("SYS_CACHE", DIR_PATH."/system/models/cache");
define("TPL_PATH", DIR_PATH."/view");
define("COMMON_PATH", TPL_PATH."/global");
define("LANG_PATH", DIR_PATH."/lang");
define("ADMIN_PATH", DIR_PATH."/admin");
define("PUB_DIR",DIR_PATH.'/public');
define("SYS_PATH", DIR_PATH."/system");
define("CTRL_PATH", SYS_PATH."/controllers");
define("MODEL_PATH", SYS_PATH."/models");
define("HELPER_PATH", SYS_PATH."/helpers");
define("DATA_PATH", DIR_PATH."/assets");
define("FONTS_PATH", DATA_PATH."/fonts");
define("IMG_PATH", DATA_PATH."/images");
define("CSS_PATH", DATA_PATH."/styles");
define("JS_PATH", DATA_PATH."/scripts");
define("EMAIL_DIR", SYS_PATH."/emails");
define("PDF_DIR", SYS_PATH."/pdfs");

// Admin Paths - No trailing slash
define("DIR_ADMIN", DIR_PATH."/admin");
define("TPL_ADMIN", DIR_ADMIN."/view");
define("COMMON_ADMIN", TPL_ADMIN."/global");
define("MODEL_ADMIN", DIR_ADMIN."/models");
define("CTRL_ADMIN", DIR_ADMIN."/controllers");
define("HELPER_ADMIN", DIR_ADMIN."/helpers");
define("DATA_ADMIN", DIR_ADMIN."/assets");
define("IMG_ADMIN", DATA_ADMIN."/images");
define("CSS_ADMIN", DATA_ADMIN."/styles");
define("JS_ADMIN", DATA_ADMIN."/scripts");

// URL's
define("URL_ADMIN", "admin");
define("CSS_URL", "styles");
define("JS_URL", "scripts");
define("IMG_URL", "images");
define("VID_URL", "videos");

if ($isMaintenance && $_SERVER['REMOTE_ADDR'] != '79.78.177.100' && $_SERVER['REMOTE_ADDR'] != '31.193.3.30' && $_SERVER['REMOTE_ADDR'] != NULL && $_SERVER['REMOTE_ADDR'] != '31.193.3.28' && $_SERVER['REMOTE_ADDR'] != '107.170.186.163' && $_SERVER['REMOTE_ADDR'] != '188.29.164.122') {
	require_once('maintenance.php');
	exit;
}