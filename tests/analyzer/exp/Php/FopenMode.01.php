<?php

$expected     = array('fopen(\'g\', \'h\')',
                     );

$expected_not = array('fopen(\'a\', \'r\')',
                      'fopen(\'b\', \'w+\')',
                      '\\fopen(\'c\', \'xb+\')',
                      '\\fopen(\'d\', \'t\')',
                      '\\fopen(\'i\', \'c+\')',
                      '\\b\\fopen(\'j\', \'ce+\')',
                      '$object->fopen(\'e\', \'f\')',
                      'classe::fopen(\'e\', \'f\')',
                      'fopen(\'k\', \'a\'.\'+\')',
                      'fopen(\'l\', "$a+");',
                     );

?>