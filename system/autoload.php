<?php
/*
# AUTOLOAD
# by Danny Broadbent
*/

// Load system controllers
require_once(CTRL_PATH.'/core/registry.controller.php');
require_once(CTRL_PATH.'/core/system.controller.php');
require_once(CTRL_PATH.'/core/base.controller.php');
require_once(CTRL_PATH.'/core/router.controller.php');

// Load default helper class
require_once(HELPER_PATH.'/core/helper.php');

// Load database models
require_once(MODEL_PATH.'/core/root.model.php');
require_once(MODEL_PATH.'/core/database.model.php');

// Kick everything off here
$route = new RouterController;
$route->load();