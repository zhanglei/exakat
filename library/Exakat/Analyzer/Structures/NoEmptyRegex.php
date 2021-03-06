<?php
/*
 * Copyright 2012-2017 Damien Seguy – Exakat Ltd <contact(at)exakat.io>
 * This file is part of Exakat.
 *
 * Exakat is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Exakat is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Exakat.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The latest code can be found at <http://exakat.io/>.
 *
*/

namespace Exakat\Analyzer\Structures;

use Exakat\Analyzer\Analyzer;

class NoEmptyRegex extends Analyzer {
    public function analyze() {
        $pregFunctions = array('\\preg_match_all', '\\preg_match', '\\preg_replace', '\\preg_replace_callback', '\\preg_relace_callback_array');

        // preg_match(''.$b, $d, $d); Empty delimiter
        $this->atomFunctionIs($pregFunctions)
             ->outWithRank('ARGUMENT', 0)
             ->outIsIE('CONCAT')
             ->is('rank', 0) // useful if previous is used
             ->atomIs('String')
             ->tokenIs(array('T_CONSTANT_ENCAPSED_STRING', 'T_ENCAPSED_AND_WHITESPACE'))
             ->noDelimiterIs('');
        $this->prepareQuery();

        // preg_match('a'.$b, $d, $d); Non-alpha numerical delimiter
        $this->atomFunctionIs($pregFunctions)
             ->outWithRank('ARGUMENT', 0)
             ->outIsIE('CONCAT')
             ->is('rank', 0) // useful if previous is used
             ->atomIs('String')
             ->tokenIs(array('T_CONSTANT_ENCAPSED_STRING', 'T_ENCAPSED_AND_WHITESPACE'))
             ->noDelimiterIsNot('')
             ->regexIs('noDelimiter', '^[A-Za-z0-9]');
        $this->prepareQuery();
    }
}

?>
