--TEST--
Bug #55399 (parse_url() incorrectly treats ':' as a valid path)
--FILE--
<?php

var_dump(php_parse_url(":"));

?>
--EXPECT--
bool(false)
