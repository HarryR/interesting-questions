<?php
function memchr($str, $s, $c, $n) {
	$str = substr($str, $s, $n);
	$x = strpos($str, $c);
	if( $x === FALSE ) return NULL;
	return $x + $s;
}

function zend_memrchr($str, $s, $c, $n) {
	$str = substr($str, $s, $n);
	$x = strrpos($str, $c);
	if( $x === FALSE ) return NULL;
	return $x + $s;
}

function estrndup($str, $off, $len) {
	return substr($str, $off, $len);
}

function php_replace_controlchars_ex($str) {
	for( $i = 0; $i < strlen($str); $i++ ) {
		if( ctype_cntrl($str[$i]) ) {
			$str[$i] = '_';
		}
	}
	return $str;
}

/**
 * Functionally equivalent version of PHP's parse_url function
 */
function php_parse_url($str=NULL, $component=-1) {
	$arg_count = func_num_args();
	if( $arg_count < 1 ) {
		trigger_error(sprintf('parse_url() expects at least 1 parameter, %d given', $arg_count), E_USER_WARNING);
		return NULL;
	}
	if( $arg_count > 2 ) {
		trigger_error(sprintf('parse_url() expects at most 2 parameters, %d given', $arg_count), E_USER_WARNING);
		return NULL;
	}
	if( is_array($str) ) {
		trigger_error('parse_url() expects parameter 1 to be string, array given', E_USER_WARNING);
		return NULL;
	}
	if( is_object($str) ) {
		trigger_error('parse_url() expects parameter 1 to be string, object given', E_USER_WARNING);
		return NULL;	
	}
	if( is_array($component) ) {
		trigger_error('parse_url() expects parameter 2 to be long, array given', E_USER_WARNING);
		return NULL;
	}
	if( is_string($component) ) {
		trigger_error('parse_url() expects parameter 2 to be long, string given', E_USER_WARNING);
		return NULL;	
	}
	if( is_object($component) ) {
		trigger_error('parse_url() expects parameter 2 to be long, object given', E_USER_WARNING);
		return NULL;		
	}

	$str = (string)$str;
	$component = (int)$component;
	
	$ret = array(
		PHP_URL_SCHEME => NULL,
		PHP_URL_HOST => NULL,
		PHP_URL_PORT => NULL,
		PHP_URL_USER => NULL,
		PHP_URL_PASS => NULL,
		PHP_URL_PATH => NULL,
		PHP_URL_QUERY => NULL,
		PHP_URL_FRAGMENT => NULL,
	);
	if( $component > PHP_URL_FRAGMENT ) {
		trigger_error("parse_url(): Invalid URL component identifier $component", E_USER_WARNING);
		return FALSE;
	}

	$length = strlen($str);
	$str .= "\0";
	$s = 0;
	$ue = $s + $length;

	/* parse scheme */
	// if ((e = memchr(s, ':', length)) && (e - s)) {
	if (($e = memchr($str, $s, ':', $length)) !== NULL && ($e - $s)) {		
		/* validate scheme */
		$p = $s;
		while ($p < $e) {
			/* scheme = 1*[ lowalpha | digit | "+" | "-" | "." ] */
			$char = $str[$p]; // *p
			if (!ctype_alpha($char) && !ctype_digit($char) && $char != '+' && $char != '.' && $char != '-') {
				if ( ($e + 1) < $ue) {
					goto parse_port;
				} else {
					goto just_path;
				}
			}
			$p++;
		}

		// if (*(e + 1) == '\0') {
		if ($str[$e + 1] == "\0") { /* only scheme is available */
			$ret[PHP_URL_SCHEME] = estrndup($str, $s, ($e - $s));
			$ret[PHP_URL_SCHEME] = php_replace_controlchars_ex($ret[PHP_URL_SCHEME]);
			goto end;
		}

		/* 
		 * certain schemas like mailto: and zlib: may not have any / after them
		 * this check ensures we support those.
		 */
		// if (*(e+1) != '/') {
		if ($str[$e+1] != '/') {
			/* check if the data we get is a port this allows us to 
			 * correctly parse things like a.com:80
			 */
			$p = $e + 1;
			while (ctype_digit($str[$p])) {
				$p++;
			}

			// if ((*p == '\0' || *p == '/') && (p - e) < 7) {
			if (($str[$p] == "\0" || $str[$p] == '/') && ($p - $e) < 7) {
				goto parse_port;
			}

			$ret[PHP_URL_SCHEME] = estrndup($str, $s, ($e-$s));
			$ret[PHP_URL_SCHEME] = php_replace_controlchars_ex($ret[PHP_URL_SCHEME]);

			$length -= ++$e - $s;
			$s = $e;
			goto just_path;
		} else {
			$ret[PHP_URL_SCHEME] = estrndup($str, $s, ($e-$s));
			$ret[PHP_URL_SCHEME] = php_replace_controlchars_ex($ret[PHP_URL_SCHEME]);

			// if (*(e+2) == '/') {
			if ($str[$e+2] == '/') {
				$s = $e + 3;
				if (!strncasecmp("file", $ret[PHP_URL_SCHEME], sizeof("file"))) {
					if ($str[$e + 3] == '/') {
						/* support windows drive letters as in:
						   file:///c:/somedir/file.txt
						*/
						if ($str[$e + 5] == ':') {
							$s = $e + 4;
						}
						goto nohost;
					}
				}
			} else {
				if (!strncasecmp("file", $ret[PHP_URL_SCHEME], sizeof("file"))) {
					$s = $e + 1;
					goto nohost;
				} else {
					$length -= ++$e - $s;
					$s = $e;
					goto just_path;
				}	
			}
		}	
	// } else if (e) {
	} else if ($e !== NULL) { /* no scheme; starts with colon: look for port */
		parse_port:
		$p = $e + 1;
		$pp = $p;

		while ($pp-$p < 6 && ctype_digit($str[$pp])) {
			$pp++;
		}

		if ($pp - $p > 0 && $pp - $p < 6 && ($str[$pp] == '/' || $str[$pp] == "\0")) {
			$port = intval(substr($str, $p, ($pp - $p)));
			//memcpy(port_buf, p, (pp - p));
			//port_buf[pp - p] = '\0';
			//port = strtol(port_buf, NULL, 10);
			if ($port > 0 && $port <= 65535) {
				$ret[PHP_URL_PORT] = $port;
			} else {
				return FALSE;
			}
		} else if ($p == $pp && $str[$pp] == "\0") {
			return FALSE;
		} else if ($str[$s] == '/' && $str[$s+1] == '/') { /* relative-scheme URL */
			$s += 2;
		} else {
			goto just_path;
		}
	} else if ($str[$s] == '/' && $str[$s+1] == '/') { /* relative-scheme URL */
		$s += 2;
	} else {
		just_path:
		$ue = $s + $length;
		goto nohost;
	}

	$e = $ue;

	// if (!(p = memchr(s, '/', (ue - s)))) {
	if (NULL===($p = memchr($str, $s, '/', ($ue - $s)))) {		
		$query = memchr($str, $s, '?', ($ue - $s));
		$fragment = memchr($str, $s, '#', ($ue - $s));

		if ($query !== NULL && $fragment !== NULL) {
			if ($query > $fragment) {
				$e = $fragment;
			} else {
				$e = $query;
			}
		} else if ($query !== NULL) {
			$e = $query;
		} else if ($fragment !== NULL) {
			$e = $fragment;
		}
	} else {
		$e = $p;
	}	

	/* check for login and password */
	if (NULL !== ($p = zend_memrchr($str, $s, '@', ($e-$s)))) {
		if (NULL !== ($pp = memchr($str, $s, ':', ($p-$s)))) {
			if (($pp-$s) > 0) {
				$ret[PHP_URL_USER] = estrndup($str, $s, ($pp-$s));
				$ret[PHP_URL_USER] = php_replace_controlchars_ex($ret[PHP_URL_USER]);
			}	

			$pp++;
			if ($p-$pp > 0) {
				$ret[PHP_URL_PASS] = estrndup($str, $pp, ($p-$pp));
				$ret[PHP_URL_PASS] = php_replace_controlchars_ex($ret[PHP_URL_PASS]);
			}	
		} else {
			$ret[PHP_URL_USER] = estrndup($str, $s, ($p-$s));
			$ret[PHP_URL_USER] = php_replace_controlchars_ex($ret[PHP_URL_USER]);
		}

		$s = $p + 1;
	}

	/* check for port */
	if ($str[$s] == '[' && $str[$e-1] == ']') {
		/* Short circuit portscan, 
		   we're dealing with an 
		   IPv6 embedded address */
		$p = $s;
	} else {
		/* memrchr is a GNU specific extension
		   Emulate for wide compatibility */
		for($p = $e; $str[$p] != ':' && $p >= $s; $p--);
	}

	if ($p >= $s && $str[$p] == ':') {
		if (!$ret[PHP_URL_PORT]) {
			$p++;
			if ($e-$p > 5) { /* port cannot be longer then 5 characters */
				return FALSE;
			} else if ($e - $p > 0) {				
				$port = intval(substr($str, $p, ($e - $p)));
				//memcpy(port_buf, p, (e - p));
				//port_buf[e - p] = "\0";
				//port = strtol(port_buf, NULL, 10);
				if ($port > 0 && $port <= 65535) {
					$ret[PHP_URL_PORT] = $port;
				} else {
					return FALSE;
				}
			}
			$p--;
		}	
	} else {
		$p = $e;
	}

	/* check if we have a valid host, if we don't reject the string as url */
	if (($p-$s) < 1) {
		return FALSE;
	}

	$ret[PHP_URL_HOST] = estrndup($str, $s, ($p-$s));
	$ret[PHP_URL_HOST] = php_replace_controlchars_ex($ret[PHP_URL_HOST]);

	if ($e == $ue) {
		goto end;
		//return $ret;
	}

	$s = $e;

	nohost:

	if (NULL !== ($p = memchr($str, $s, '?', ($ue - $s)))) {		
		$pp = memchr($str, $s, '#', strlen($str) - $s);

		if ($pp !== NULL && $pp < $p) {
			if ($pp - $s) {
				$ret[PHP_URL_PATH] = estrndup($str, $s, ($pp-$s));
				$ret[PHP_URL_PATH] = php_replace_controlchars_ex($ret[PHP_URL_PATH]);
			}
			$p = $pp;
			goto label_parse;
		}

		if ($p - $s) {
			$ret[PHP_URL_PATH] = estrndup($str, $s, ($p-$s));
			$ret[PHP_URL_PATH] = php_replace_controlchars_ex($ret[PHP_URL_PATH]);
		}	

		if ($pp) {
			if ($pp - ++$p) { 
				$ret[PHP_URL_QUERY] = estrndup($str, $p, ($pp-$p));
				$ret[PHP_URL_QUERY] = php_replace_controlchars_ex($ret[PHP_URL_QUERY]);
			}
			$p = $pp;
			goto label_parse;
		} else if (++$p - $ue) {
			$ret[PHP_URL_QUERY] = estrndup($str, $p, ($ue-$p));
			$ret[PHP_URL_QUERY] = php_replace_controlchars_ex($ret[PHP_URL_QUERY]);
		}
	} else if (NULL !== ($p = memchr($str, $s, '#', ($ue - $s)))) {
		if ($p - $s) {
			$ret[PHP_URL_PATH] = estrndup($str, $s, ($p-$s));
			$ret[PHP_URL_PATH] = php_replace_controlchars_ex($ret[PHP_URL_PATH]);
		}	

		label_parse:
		$p++;

		if ($ue - $p) {
			$ret[PHP_URL_FRAGMENT] = estrndup($str, $p, ($ue-$p));
			$ret[PHP_URL_FRAGMENT] = php_replace_controlchars_ex($ret[PHP_URL_FRAGMENT]);
		}	
	} else {
		$ret[PHP_URL_PATH] = estrndup($str, $s, ($ue-$s));
		$ret[PHP_URL_PATH] = php_replace_controlchars_ex($ret[PHP_URL_PATH]);
	}

end:
	if( $component >= PHP_URL_SCHEME ) {
		return $ret[$component];
	}
	
	// Turn the output into a dictionary
	$names = array(
		PHP_URL_SCHEME => 'scheme',
		PHP_URL_HOST => 'host',
		PHP_URL_PORT => 'port',
		PHP_URL_USER => 'user',
		PHP_URL_PASS => 'pass',
		PHP_URL_PATH => 'path',
		PHP_URL_QUERY => 'query',
		PHP_URL_FRAGMENT => 'fragment',
	);
	$components = array_combine(array_values($names), array_values($ret));	
	return array_filter($components, function ($v) {
		return $v !== NULL;
	});
}
