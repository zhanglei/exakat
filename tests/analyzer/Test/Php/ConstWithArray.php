<?php

namespace Test;

include_once(dirname(dirname(dirname(dirname(__DIR__)))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');
spl_autoload_register('Autoload::autoload_library');

class Php_ConstWithArray extends Analyzer {
    /* 2 methods */

    public function testPhp_ConstWithArray01()  { $this->generic_test('Php/ConstWithArray.01'); }
    public function testPhp_ConstWithArray02()  { $this->generic_test('Php/ConstWithArray.02'); }
}
?>