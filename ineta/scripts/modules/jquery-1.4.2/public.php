<?php
if(!function_exists("getKernelInstance")){
	error_log("Tentativa de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}
getKernelInstance()->actionAddFunction("requestJQuery", function(){
	getKernelInstance()->actionAddFunction("_head", function(){
		 ?>
		 	<script type="text/javascript" src="<?php echo(getBaseUrl().SCRIPTSDIR.getKernelInstance()->getPathFromRoot(__FILE__)); ?>jquery-1.4.2.min.js"></script>
		 	<script type="text/javascript" src="<?php echo(getBaseUrl().SCRIPTSDIR.getKernelInstance()->getPathFromRoot(__FILE__)); ?>jquery.validate.min.js"></script>
		 	<script type="text/javascript" src="<?php echo(getBaseUrl().SCRIPTSDIR.getKernelInstance()->getPathFromRoot(__FILE__)); ?>localization/messages_ptpt.js"></script>
		 	<script type="text/javascript" src="<?php echo(getBaseUrl().SCRIPTSDIR.getKernelInstance()->getPathFromRoot(__FILE__)); ?>jquery-ui-1.8.1.min.js"></script>
		 	<link rel="stylesheet" type="text/css" href="<?php echo(getBaseUrl().SCRIPTSDIR.getKernelInstance()->getPathFromRoot(__FILE__)); ?>css/redmond/jquery-ui-1.8.1.css" media="all" />
		 <?php 
		 return true;
	}, 9);
	return true;
});