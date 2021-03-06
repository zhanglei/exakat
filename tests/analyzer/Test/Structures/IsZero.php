<?php

namespace Test;

include_once(dirname(dirname(dirname(dirname(__DIR__)))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');
spl_autoload_register('Autoload::autoload_library');

class Structures_IsZero extends Analyzer {
    /* 5 methods */

    public function testStructures_IsZero01()  { $this->generic_test('Structures/IsZero.01'); }
    public function testStructures_IsZero02()  { $this->generic_test('Structures/IsZero.02'); }
    public function testStructures_IsZero03()  { $this->generic_test('Structures/IsZero.03'); }
    public function testStructures_IsZero04()  { $this->generic_test('Structures/IsZero.04'); }
    public function testStructures_IsZero05()  { $this->generic_test('Structures/IsZero.05'); }
}
?>