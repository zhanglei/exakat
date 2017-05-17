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


namespace Exakat\Analyzer\Php;

use Exakat\Analyzer\Analyzer;

class UseWeb extends Analyzer {
    public function analyze() {
        // GPC + R
        $this->atomIs(self::$VARIABLES_ALL)
             ->codeIs(array('$_GET', '$_POST', '$_REQUEST', '$_COOKIE'), true);
        $this->prepareQuery();

        // $_SERVER + special index
        $this->atomIs('Variablearray')
             ->codeIs('$_SERVER', true)
             ->inIs('VARIABLE')
             ->outIs('INDEX')
             ->noDelimiterIs(array('SERVER_PROTOCOL',
                                   'SERVER_NAME',
                                   'SERVER_PORT',
         
                                   'HTTP_HOST',
                                   'HTTP_ORIGIN',
                                   'HTTP_USER_AGENT',
                                   'HTTP_COOKIE',
                                   'HTTP_ACCEPT',
                                   'HTTP_ACCEPT_LANGUAGE',
                                   'HTTP_ACCEPT_ENCODING',
                                   'HTTP_CONNECTION',
                                   'HTTP_REFERER',
                                   'HTTP_IF_MODIFIED_SINCE',
                                   'HTTP_IF_NONE_MATCH',
         
                                   'CONTENT_TYPE',
         
                                   'REQUEST_URI',
                                   'REQUEST_METHOD',
                                   'QUERY_STRING',
                                   'REMOTE_ADDR',
                                   'REMOTE_PORT',
                                 ))
            ->back('first');
        $this->prepareQuery();
    }
}

?>
