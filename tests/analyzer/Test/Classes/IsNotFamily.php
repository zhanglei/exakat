<?php

namespace Test;

include_once(dirname(dirname(dirname(dirname(__DIR__)))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');
spl_autoload_register('Autoload::autoload_library');

class Classes_IsNotFamily extends Analyzer {
    /* 6 methods */

    public function testClasses_IsNotFamily01()  { $this->generic_test('Classes_IsNotFamily.01'); }
    public function testClasses_IsNotFamily02()  { $this->generic_test('Classes/IsNotFamily.02'); }
    public function testClasses_IsNotFamily03()  { $this->generic_test('Classes/IsNotFamily.03'); }
    public function testClasses_IsNotFamily04()  { $this->generic_test('Classes/IsNotFamily.04'); }
    public function testClasses_IsNotFamily05()  { $this->generic_test('Classes/IsNotFamily.05'); }
    public function testClasses_IsNotFamily06()  { $this->generic_test('Classes/IsNotFamily.06'); }
}
?>