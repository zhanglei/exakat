<?php

function foo(array &$a, Stdclass &$b, Callable &$c) {}

function foo2(array &$a2 = array(), Stdclass &$b2 = null, Callable &$c2 = null) {}

?>
