<?php define('BASE_DIR', realpath(dirname(__FILE__))); require_once("./system/lib/index.php"); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Associa&ccedil;&atilde;o F&aacute;tima Cultural</title>
		<link rel="shortcut icon" href="<?php echo(getBaseUrl()); ?>favicon.ico" type="image/x-icon" />
		<?php getKernelInstance()->actionExecute("_head"); ?>
	</head>
	<body>
		<?php getKernelInstance()->actionExecute("_body"); ?>
		<?php getKernelInstance()->actionExecute("_bodyFooter"); ?>
	</body>
</html>