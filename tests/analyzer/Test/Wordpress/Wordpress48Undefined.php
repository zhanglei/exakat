<?php

namespace Test;

include_once(dirname(dirname(dirname(dirname(__DIR__)))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');
spl_autoload_register('Autoload::autoload_library');

class Wordpress_Wordpress48Undefined extends Analyzer {
    /* 1 methods */

    public function testWordpress_Wordpress48Undefined01()  { $this->generic_test('Wordpress/Wordpress48Undefined.01'); }
}
?>