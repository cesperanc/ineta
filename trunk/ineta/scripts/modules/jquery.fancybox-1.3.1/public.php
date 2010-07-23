<?php
if(!function_exists("getKernelInstance")){
	error_log("Tentativa de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}

/**
 * Área pública
 */
getKernelInstance()->actionAddFunction("_init", function(){
	$kernel = getKernelInstance();
	if(!$kernel->actionExecuted("requestJQuery") && !$kernel->actionExecute("requestJQuery")){
		$kernel->logMsg("Não foi possível carregar a biblioteca jquery no módulo jquery.fancybox.");
	}
});

getKernelInstance()->actionAddFunction("_head", function(){
	$baseDir = getBaseUrl().SCRIPTSDIR.getKernelInstance()->getPathFromRoot(__FILE__);
	?>
		<script type="text/javascript" src="<?php echo($baseDir); ?>fancybox/jquery.mousewheel-3.0.2.pack.js"></script>
		<script type="text/javascript" src="<?php echo($baseDir); ?>fancybox/jquery.fancybox-1.3.1.js"></script>
		<link rel="stylesheet" type="text/css" href="<?php echo($baseDir); ?>fancybox/jquery.fancybox-1.3.1.css" media="screen" />
		<script type="text/javascript">
			/* <![CDATA[ */
				$(document).ready(function (event){
					$('#content div.entry a img:only-child').each(function(index, img) {
						$(img).parent('a[href=""],a[href="#"],a[href="'+$(img).attr('src')+'"]').attr('href', $(img).attr('src')).fancybox({
							'transitionIn'	: 'elastic',
							'transitionOut'	: 'elastic',
							'hideOnContentClick' : true
						});
					});
				});
			/* ]]> */
		</script>
	<?php 
});
