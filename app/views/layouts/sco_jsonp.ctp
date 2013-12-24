<?php
/**
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.view.templates.layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php echo $this->Html->charset(); ?>
	<title><?php echo $title_for_layout; ?></title>
	<link href="http://hec.unil.ch/info1ere/favicon.ico" type="image/x-icon" rel="icon" />
	<link href="http://hec.unil.ch/info1ere/favicon.ico" type="image/x-icon" rel="shortcut icon" />
	<link rel="stylesheet" type="text/css" href="http://hec.unil.ch/info1ere/css/sqlexplorer.css" />
	<link rel="stylesheet" type="text/css" href="http://hec.unil.ch/info1ere/css/cupertino/jquery-ui-1.8.4.custom.css" />
	<script type="text/javascript" src="http://hec.unil.ch/info1ere/js/jquery-1.4.2.min.js"></script>
	<script type="text/javascript" src="http://hec.unil.ch/info1ere/js/jquery-ui-1.8.4.custom.min.js"></script>
	<script type="text/javascript" src="http://hec.unil.ch/info1ere/js/APIWrapper.js"></script>
	<script type="text/javascript" src="http://hec.unil.ch/info1ere/js/SCOFunctions.js"></script>
	<script type="text/javascript" src="http://hec.unil.ch/info1ere/js/jquery.dataTables.min.js"></script>
	<script type="text/javascript" src="http://hec.unil.ch/info1ere/js/jquery.flydom-3.1.1.js"></script>
	<script type="text/javascript" src="http://hec.unil.ch/info1ere/js/codemirror/codemirror.js"></script>
	<script type="text/javascript">	
//<![CDATA[
var baseurl = 'http://hec.unil.ch/info1ere/';

//]]>
</script>
	<script type="text/javascript" src="http://hec.unil.ch/info1ere/js/sqlexplorer.js"></script>
</head>
<body onload="loadPage()" onunload="unloadPage()">
	<?php echo $this->Session->flash(); ?>
	<?php echo $this->Session->flash('auth'); ?>
	<?php echo $content_for_layout; ?>
</body>
</html>