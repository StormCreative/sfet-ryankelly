<?php
// XSS Protection
header("X-Content-Type-Options: nosniff");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("X-Frame-Options: DENY");
header('X-XSS-Protection: 1; mode=block');

// Include Setup Config
require_once('config.inc.php');
require_once(SYS_PATH.'/autoload.php');
exit;