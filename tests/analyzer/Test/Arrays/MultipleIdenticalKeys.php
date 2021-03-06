<?php

namespace Test;

include_once(dirname(dirname(dirname(dirname(__DIR__)))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');
spl_autoload_register('Autoload::autoload_library');

class Arrays_MultipleIdenticalKeys extends Analyzer {
    /* 8 methods */

    public function testArrays_MultipleIdenticalKeys01()  { $this->generic_test('Arrays/MultipleIdenticalKeys.01'); }
    public function testArrays_MultipleIdenticalKeys02()  { $this->generic_test('Arrays/MultipleIdenticalKeys.02'); }
    public function testArrays_MultipleIdenticalKeys03()  { $this->generic_test('Arrays/MultipleIdenticalKeys.03'); }
    public function testArrays_MultipleIdenticalKeys04()  { $this->generic_test('Arrays/MultipleIdenticalKeys.04'); }
    public function testArrays_MultipleIdenticalKeys05()  { $this->generic_test('Arrays/MultipleIdenticalKeys.05'); }
    public function testArrays_MultipleIdenticalKeys06()  { $this->generic_test('Arrays/MultipleIdenticalKeys.06'); }
    public function testArrays_MultipleIdenticalKeys07()  { $this->generic_test('Arrays/MultipleIdenticalKeys.07'); }
    public function testArrays_MultipleIdenticalKeys08()  { $this->generic_test('Arrays/MultipleIdenticalKeys.08'); }
}
?>