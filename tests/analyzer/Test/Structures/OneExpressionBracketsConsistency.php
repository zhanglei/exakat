<?php

namespace Test;

include_once(dirname(dirname(dirname(dirname(__DIR__)))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');
spl_autoload_register('Autoload::autoload_library');

class Structures_OneExpressionBracketsConsistency extends Analyzer {
    /* 5 methods */

    public function testStructures_OneExpressionBracketsConsistency01()  { $this->generic_test('Structures/OneExpressionBracketsConsistency.01'); }
    public function testStructures_OneExpressionBracketsConsistency02()  { $this->generic_test('Structures/OneExpressionBracketsConsistency.02'); }
    public function testStructures_OneExpressionBracketsConsistency03()  { $this->generic_test('Structures/OneExpressionBracketsConsistency.03'); }
    public function testStructures_OneExpressionBracketsConsistency04()  { $this->generic_test('Structures/OneExpressionBracketsConsistency.04'); }
    public function testStructures_OneExpressionBracketsConsistency05()  { $this->generic_test('Structures/OneExpressionBracketsConsistency.05'); }
}
?>