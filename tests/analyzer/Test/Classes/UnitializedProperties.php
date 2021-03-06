<?php

namespace Test;

include_once(dirname(dirname(dirname(dirname(__DIR__)))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');
spl_autoload_register('Autoload::autoload_library');

class Classes_UnitializedProperties extends Analyzer {
    /* 6 methods */

    public function testClasses_UnitializedProperties01()  { $this->generic_test('Classes/UnitializedProperties.01'); }
    public function testClasses_UnitializedProperties02()  { $this->generic_test('Classes/UnitializedProperties.02'); }
    public function testClasses_UnitializedProperties03()  { $this->generic_test('Classes/UnitializedProperties.03'); }
    public function testClasses_UnitializedProperties04()  { $this->generic_test('Classes/UnitializedProperties.04'); }
    public function testClasses_UnitializedProperties05()  { $this->generic_test('Classes/UnitializedProperties.05'); }
    public function testClasses_UnitializedProperties06()  { $this->generic_test('Classes/UnitializedProperties.06'); }
}
?>