<?php

namespace Test;

include_once(dirname(dirname(dirname(dirname(__DIR__)))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');
spl_autoload_register('Autoload::autoload_library');

class Functions_VariableArguments extends Analyzer {
    /* 5 methods */

    public function testFunctions_VariableArguments01()  { $this->generic_test('Functions_VariableArguments.01'); }
    public function testFunctions_VariableArguments02()  { $this->generic_test('Functions/VariableArguments.02'); }
    public function testFunctions_VariableArguments03()  { $this->generic_test('Functions/VariableArguments.03'); }
    public function testFunctions_VariableArguments04()  { $this->generic_test('Functions/VariableArguments.04'); }
    public function testFunctions_VariableArguments05()  { $this->generic_test('Functions/VariableArguments.05'); }
}
?>