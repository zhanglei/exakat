<?php

namespace Test;

include_once(dirname(dirname(dirname(dirname(__DIR__)))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');
spl_autoload_register('Autoload::autoload_library');

class Files_InclusionWrongCase extends Analyzer {
    /* 7 methods */

    public function testFiles_InclusionWrongCase01()  { $this->generic_test('Files/InclusionWrongCase.01'); }
    public function testFiles_InclusionWrongCase02()  { $this->generic_test('Files/InclusionWrongCase.02'); }
    public function testFiles_InclusionWrongCase03()  { $this->generic_test('Files/InclusionWrongCase.03'); }
    public function testFiles_InclusionWrongCase04()  { $this->generic_test('Files/InclusionWrongCase.04'); }
    public function testFiles_InclusionWrongCase05()  { $this->generic_test('Files/InclusionWrongCase.05'); }
    public function testFiles_InclusionWrongCase06()  { $this->generic_test('Files/InclusionWrongCase.06'); }
    public function testFiles_InclusionWrongCase07()  { $this->generic_test('Files/InclusionWrongCase.07'); }
}
?>