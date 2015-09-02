#####################################
#                                   #
#      CMS - Framework - README     #
#           Storm Creative          #
#                                   #
#####################################


Check Environment
==================
You can check your environment settings by simply using the following get request
http://www.domain.com?show=env

show = env flag is used to request for environment settings (PHP/MySQL Version, Apache/DB Environment)


Clear Page Cache
==================

** Method 1: **
Visit url using ?nocache appended to the end of the URL, e.g.:
http://localhost/page?nocache

** Method 2: **
Go to cache directory, delete all files to clear entire cache:
/lang/xx/cache/

** Change Cache Level in Controller **
use cacheType() method, allowed values:

"Full" = Entire page including header/footer (not recommended for account / blog websites)
"Partial" = Cache just the template page (header/footer remain un-cached)
"None" = Do not cache the page


MySQL Database
==================
Look at configuration file in:
/config.inc.php - MySQL database connection details can be found here for live and local

** MYSQL_USE **
Constant can be used to change MySQL environment, e.g.:

"AUTO" = Automatically select based on environment
"LIVE" = Use production database
"LOCAL" = Use development database

** Clearing DB Cache **
All models cache the fields used in the database for when building up select queries, folder:
/system/models/cache


Custom Routes
==================
You can add custom routes to the CMS framework simple open to file below:
/system/routes.json

** Assigning Variables **
You can assign a registry GET variable to the controller within the routes like below:
"/custom_page/custom_page/{user_id}" : "users/profile",

The above will create a variable in get called user_id, you can reference this in the controller by:
$this->__get('user_id');


Controllers
==================

Useful functions to know within the controllers (using $this->):

addData('key', 'value'); - using add data will add a variable to the template page with the given value
addMetaTag('name','content','http-equiv','property'); - Add a meta tag to header
addHeaderScript('path/name/or/url'); - Adds a script tag within the header
addScript('path/name/or/url'); - Adds a script tag to the very bottom of the page before end body tag
setView('folder/file'); - Set the template to load in the view folder

setTitle('title here'); - Set page title
setDescription('description here'); - Set page meta description
setKeywords('keywords, here'); - Set page meta keywords

site['site_attr']; - Get site attribute value from database 'site' table

_get[]; usual $_GET added to controller and cleaned up
_post[]; usual $_POST added to controller and cleaned up
_request[]; usual $_REQUEST added to controller and cleaned up
_cookie[]; usual $_COOKIE added to controller and cleaned up
_files[]; usual $_FILES added to controller and cleaned up
_server[]; usual $_SERVER added to controller and cleaned up
_session[]; usual $_SESSION added to controller and cleaned up


Templates
==================
$site['site_attr']; - Get site attribute value from database 'site' table
$main_url - Gets the website url including if your in HTTPS mode (http://domain.com or https://domain.com)