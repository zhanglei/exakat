<?php

namespace Test;

include_once(dirname(dirname(dirname(dirname(__DIR__)))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');
spl_autoload_register('Autoload::autoload_library');

class Structures_AddZero extends Analyzer {
    /* 5 methods */

    public function testStructures_AddZero01()  { $this->generic_test('Structures_AddZero.01'); }
    public function testStructures_AddZero02()  { $this->generic_test('Structures_AddZero.02'); }
    public function testStructures_AddZero03()  { $this->generic_test('Structures/AddZero.03'); }
    public function testStructures_AddZero04()  { $this->generic_test('Structures/AddZero.04'); }
    public function testStructures_AddZero05()  { $this->generic_test('Structures/AddZero.05'); }
}
?>