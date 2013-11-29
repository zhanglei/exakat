<?php

namespace Test;

include_once(dirname(dirname(dirname(__DIR__))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');

class Nsname extends Tokenizer {
    /* 11 methods */

    public function testNsname01()  { $this->generic_test('Nsname.01'); }
    public function testNsname02()  { $this->generic_test('Nsname.02'); }
    public function testNsname03()  { $this->generic_test('Nsname.03'); }
    public function testNsname04()  { $this->generic_test('Nsname.04'); }
    public function testNsname05()  { $this->generic_test('Nsname.05'); }
    public function testNsname06()  { $this->generic_test('Nsname.06'); }
    public function testNsname07()  { $this->generic_test('Nsname.07'); }
    public function testNsname08()  { $this->generic_test('Nsname.08'); }
    public function testNsname09()  { $this->generic_test('Nsname.09'); }
    public function testNsname10()  { $this->generic_test('Nsname.10'); }
    public function testNsname11()  { $this->generic_test('Nsname.11'); }
}
?>