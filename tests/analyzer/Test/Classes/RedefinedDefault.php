<?php

namespace Test;

include_once(dirname(dirname(dirname(dirname(__DIR__)))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');
spl_autoload_register('Autoload::autoload_library');

class Classes_RedefinedDefault extends Analyzer {
    /* 3 methods */

    public function testClasses_RedefinedDefault01()  { $this->generic_test('Classes/RedefinedDefault.01'); }
    public function testClasses_RedefinedDefault02()  { $this->generic_test('Classes/RedefinedDefault.02'); }
    public function testClasses_RedefinedDefault03()  { $this->generic_test('Classes/RedefinedDefault.03'); }
}
?>