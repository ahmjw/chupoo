<!DOCTYPE html>
<html>
<head>
	<title>Error <?php echo $exception->getCode(); ?></title>
<style type="text/css">
.sys{
	color: #f93;
}
</style>
</head>
<body>
<h1>Error <?php echo $exception->getCode(); ?></h1>
<p><?php echo $exception->getMessage(); ?></p>
<pre>
<?php
echo '<ol>';
foreach ($exception->getTrace() as $trace) {
	$file = isset($trace['file']) ? Djokka\Starter::abbrPath($trace['file']) : '';
	$class = isset($trace['class']) ? $trace['class'] : '';
	$type = isset($trace['type']) ? $trace['type'] : '';
	$function = $class. $type . $trace['function'] . '()';
	$line = isset($trace['line']) ? $trace['line'] : '';
	$file = !empty($file) ? '<b>' . $file . '</b> line <b>' . $line . '</b><br/>' : '';
	echo '<li>'. $file . $function . '</li>';
}
echo '</ol>';
?>
</pre>
</body>
</html>