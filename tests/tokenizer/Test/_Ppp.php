<?php

namespace Test;

include_once(dirname(dirname(dirname(__DIR__))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');

class _Ppp extends Tokenizer {
    /* 4 methods */

    public function test_Ppp01()  { $this->generic_test('_Ppp.01'); }
    public function test_Ppp02()  { $this->generic_test('_Ppp.02'); }
    public function test_Ppp03()  { $this->generic_test('_Ppp.03'); }
    public function test_Ppp04()  { $this->generic_test('_Ppp.04'); }
}
?>