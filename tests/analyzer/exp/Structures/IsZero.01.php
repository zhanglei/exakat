<?php

$expected     = array('$c - $c',
                      '1 - 1',
                      '$f[1 - 3] - $f[1 - 3]',
                      '$g + $h - $g',
                     );

$expected_not = array('$d + $d',
                      '$d + $d',
                      '$f[1 - 3] + $f[1 - 3]',
                     );

?>