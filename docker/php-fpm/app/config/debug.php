<?php
function exception_handler($e, $sub = 0) {
	if (PHP_SAPI == 'cli' || (defined('DEBUG_OUTPUT') && DEBUG_OUTPUT == 'text')) {
		ob_start();
		echo $e;
		$content = ob_get_contents();
		ob_end_clean();
		foreach (explode("\n", $content) as $line) {
			echo str_repeat(' ', $sub * 4) . $line . "\n";
		}
	} else {
		echo '<div style="margin: 0 0 10px ' . ($sub * 30) . 'px">';
		echo '<code style="color: red"><b>' . get_class($e) . '</b>: ' . $e->getMessage() . '</code><br />';
		echo "<p style=\"font-family: monospace; background: #eee; padding: 5px;\">";
		echo "<span style=\"color: #555;\">";
		echo render_backtrace($e->getTrace());
		echo '</span></div>';
	}

	// Show reason
	if ($e instanceof Lib_Exception && $e->reason) {
		exception_handler($e->reason, $sub + 1);
	}
}

function dump() {
	// Search the function caller
	$backtrace = debug_backtrace();
	$trace = current($backtrace);
	// If parent function is debug, shift
	if (empty($trace['file']) && @$backtrace[1]['function'] == 'call_user_func_array') {
		next($backtrace); // Skip call_user_func_array
		$trace = next($backtrace);
	}
	// Cut root dir from file path
	$root = str_replace('\\', '/', defined('ROOT_DIR') ? ROOT_DIR : $_SERVER['DOCUMENT_ROOT']);
	$file = str_replace('\\', '/', $trace['file']);
	$file = preg_replace('/^' . preg_quote($root, '/') . '/', '', $file);
	// Environment detection
	$http = (PHP_SAPI != 'cli' && !(defined('DEBUG_OUTPUT') && DEBUG_OUTPUT == 'text'));
	$dump = $http ? 'var_dump' : 'print_r';
	$args = ($a = func_get_args()) ? $a : array("checkpoint!");
	// Output file path
	echo $http ?
		"<!-- don't be evil --><code style='color: green'><b>" . $file . "</b> on line <b>" . @$trace['line'] . "</b></code><br /><br />" :
		"$file on line $trace[line]\n";
	// Output variables
	foreach ($args as $cur) {
		ob_start();
		$dump($cur);
		$cur = ob_get_contents();
		ob_end_clean();
		if (!extension_loaded('xdebug') && $http) {
			$cur = highlight_string("<?php\n\n$cur\n?>", true);
		}
		echo $cur;
	}
	echo $http ? "<hr><br />" : "\n" . str_repeat('-', 70) . "\n\n";
}

function rdump() {
	$args = func_get_args();
	ob_start();
	call_user_func_array("dump", $args);
	$contents = ob_get_contents();
	ob_end_clean();
	return $contents;
}

function debug() {
	// Debug
	$http = (PHP_SAPI != 'cli' && !(defined('DEBUG_OUTPUT') && DEBUG_OUTPUT == 'text'));
	$args = func_get_args();
	if (empty($args)) $args = array('checkpoint');
	echo $http ? "<h2>Debug from chin</h2>" : "Debug from chin\n";
	call_user_func_array("dump", $args);
	exit;
}

function debug2() {
	// Debug
	$http = (PHP_SAPI != 'cli' && !(defined('DEBUG_OUTPUT') && DEBUG_OUTPUT == 'text'));
	$args = func_get_args();
	if (empty($args)) $args = array('checkpoint');
	echo $http ? "<h2>Debug from chin</h2>" : "Debug from chin\n";
	call_user_func_array("dump", $args);

}

function backtrace($backtrace = null) {

	$root = str_replace('\\', '/', defined('ROOT_DIR') ? ROOT_DIR : $_SERVER['DOCUMENT_ROOT']);
	if (is_null($backtrace)) {
		$backtrace = debug_backtrace();
	}
	$trace = current($backtrace);
	if (empty($trace['file'])) {
		$trace['file'] = 'Unknown';
	}
	if (empty($trace['line'])) {
		$trace['line'] = '0';
	}
	$file = str_replace('\\', '/', $trace['file']);
	$file = preg_replace('/^' . preg_quote($root, '/') . '/', '', $file);
	// Output file path
	array_shift($backtrace);
	$htmldoc = "<h2>Backtrace from chin</h2>";
	$htmldoc .= "<code style='color: green'><b>" . $file . "</b> on line <b>" . $trace['line'] . "</b></code><br />\n";
	$htmldoc .= "<p style=\"font-family: monospace; background: #eee; padding: 5px;\">";
	$htmldoc .= "<span style=\"color: #555;\">";
	$htmldoc .= render_backtrace($backtrace);
	$htmldoc .= "</span></p>";

	echo (PHP_SAPI == 'cli' || (defined('DEBUG_OUTPUT') && DEBUG_OUTPUT == 'text'))
		? strip_tags($htmldoc) : $htmldoc;

	$args = func_get_args();
	if ($args) {
		call_user_func_array('dump', $args);
	}
}

function render_backtrace($backtrace) {
	$root = str_replace('\\', '/', defined('ROOT_DIR') ? ROOT_DIR : $_SERVER['DOCUMENT_ROOT']);

	$htmldoc = '';
	foreach ($backtrace as $key => $data) {
		if (!$key) $htmldoc .= "<b style=\"font-size: 1.1em; color: #000;\">";
		$htmldoc .= '#' . ($key + 1) . ' ';
		if (array_key_exists("file", $data)) {
			$file = str_replace('\\', '/', $data["file"]);
			$htmldoc .= preg_replace('/^' . preg_quote($root, '/') . '/', '', $file);
		}
		if (array_key_exists("line", $data)) {
			$htmldoc .= " " . $data["line"] . ": ";
		}
		if (array_key_exists("class", $data) && array_key_exists("type", $data)) {
			$htmldoc .= $data["class"] . $data["type"];
		}
		if (array_key_exists("function", $data)) {
			$htmldoc .= $data["function"] . "(";
			if (array_key_exists("args", $data)) {
				if (count($data["args"]) > 0) {
					$args = $data["args"];
					$type = gettype($args[0]);
					$value = $args[0];
					unset($args);
					if ($type == "boolean") {
						if ($value) {
							$htmldoc .= "true";
						} else {
							$htmldoc .= "false";
						}
					} elseif ($type == "integer" || $type == "double") {
						if (settype($value, "string")) {
							if (strlen($value) <= 20) {
								$htmldoc .= $value;
							} else {
								$htmldoc .= "<span title=\"" . htmlspecialchars($value) . "\"" . substr($value, 0, 17) . "...</span>";
							}
						} else {
							if ($type == "integer") {
								$htmldoc .= "? integer ?";
							} else {
								$htmldoc .= "? double or float ?";
							}
						}
					} elseif ($type == "string") {
						if (strlen($value) <= 18) {
							$htmldoc .= "'$value'";
						} else {
							$htmldoc .= "<span title=\"" . htmlspecialchars($value) . "\">'" . substr($value, 0, 15) . "...'</span>";
						}
					} elseif ($type == "array") {
						$htmldoc .= "Array";
					} elseif ($type == "object") {
						$htmldoc .= "Object";
					} elseif ($type == "resource") {
						$htmldoc .= "Resource";
					} elseif ($type == "NULL") {
						$htmldoc .= "null";
					} elseif ($type == "unknown type") {
						$htmldoc .= "? unknown type ?";
					}
					unset($type);
					unset($value);
				}
				if (count($data["args"]) > 1) {
					$htmldoc .= ", ...";
				}
			}
			$htmldoc .= ")<br/>\n";
		}
		if (!$key) $htmldoc .= "</b>";
	}

	return $htmldoc;
}

function dump_code_coverage($coverage) {
	foreach ($coverage as $file => $lines) {
		$content = file($file);
		$fc = '';
		$cnt = 0;
		foreach ($content as $num => $line) {
			$num++;
			$line = htmlspecialchars($line);
			$line = str_replace(' ', '&nbsp;', $line);
			$fc .= "$num.&nbsp;&nbsp;&nbsp;";
			if (isset($lines[$num])) {
				$fc .= '<span style="background: #00E000">' . $line . '</span>';
				$cnt++;
			} else {
				$fc .= $line;
			}
			$fc .= "<br />";
		}
		$percent = round(($cnt * 100) / count($content), 2);
		echo "<h2>$file ($percent%)</h2><code>$fc";
		echo "</code>";
	}
}

function dump_array($arr) {
	ob_start();
	print_r($arr);
	$asdf = ob_get_contents();
	ob_end_clean();
	return $asdf;
}
