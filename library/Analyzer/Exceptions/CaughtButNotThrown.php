<?php

namespace Analyzer\Exceptions;

use Analyzer;

class CaughtButNotThrown extends Analyzer\Analyzer {
    public function analyze() {
        // There is a catch() but its class is not defined
        
        $phpExceptions = $this->loadIni('php_exception.ini', 'classes');

        $this->atomIs('Catch')
             ->outIs('CLASS')
             ->fullnspathIsNot($this->makeFullNsPath($phpExceptions))
             ->savePropertyAs('fullnspath', 'fnp')
             ->filter('g.idx("atoms")[["atom":"Throw"]].out("THROW").out("NEW").hasNot("fullnspath", null)
                         .hasNot("fullnspath", null)
                         .filter{ g.idx("classes").get("path", it.fullnspath).any(); }
                         .transform{ g.idx("classes")[["path":it.fullnspath]].next(); }
                         .filter{fnp in it.classTree}.any() == false');
        $this->prepareQuery();
    }
}

?>