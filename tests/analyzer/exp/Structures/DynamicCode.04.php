<?php

$expected     = array('extract(parse_url($_SERVER["HTTP_DESTINATION"]))',
                      'parse_str(a($b->d), $e)',
                     );

$expected_not = array('include ($d->g[strtoupper($this->_tld)])',
                      'require_once \'include/a/b.php\'',
                      'require (\'include/e/r.php\')',
                      'include (\'include/b/n/m/h.php\')',
                     );

?>