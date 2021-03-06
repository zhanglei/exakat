<?php

namespace Test;

include_once(dirname(dirname(dirname(dirname(__DIR__)))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');
spl_autoload_register('Autoload::autoload_library');

class Structures_UnconditionLoopBreak extends Analyzer {
    /* 4 methods */

    public function testStructures_UnconditionLoopBreak01()  { $this->generic_test('Structures/UnconditionLoopBreak.01'); }
    public function testStructures_UnconditionLoopBreak02()  { $this->generic_test('Structures/UnconditionLoopBreak.02'); }
    public function testStructures_UnconditionLoopBreak03()  { $this->generic_test('Structures/UnconditionLoopBreak.03'); }
    public function testStructures_UnconditionLoopBreak04()  { $this->generic_test('Structures/UnconditionLoopBreak.04'); }
}
?>