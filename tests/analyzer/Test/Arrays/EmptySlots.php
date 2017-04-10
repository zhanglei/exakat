<?php

namespace Test;

include_once(dirname(dirname(dirname(dirname(__DIR__)))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');
spl_autoload_register('Autoload::autoload_library');

class Arrays_EmptySlots extends Analyzer {
    /* 3 methods */

    public function testArrays_EmptySlots01()  { $this->generic_test('Arrays/EmptySlots.01'); }
    public function testArrays_EmptySlots02()  { $this->generic_test('Arrays/EmptySlots.02'); }
    public function testArrays_EmptySlots03()  { $this->generic_test('Arrays/EmptySlots.03'); }
}
?>