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


namespace Exakat\Tasks;

use Exakat\Config;
use Exception;

class CleanDb extends Tasks {
    const CONCURENCE = self::ANYTIME;

    protected $logname = self::LOG_NONE;

    public function run() {
        $this->cleanTmpDir();
         if (Tasks::$semaphore !== null) {
            fclose(Tasks::$semaphore);
            $this->gremlin->clean();
            Tasks::$semaphore = @stream_socket_server("udp://0.0.0.0:".Tasks::$semaphorePort, $errno, $errstr, STREAM_SERVER_BIND);
        } else {
            $this->gremlin->clean();
        }
    }

    private function cleanTmpDir() {
        rmdirRecursive($this->config->projects_root.'/projects/.exakat/');
        mkdir($this->config->projects_root.'/projects/.exakat/', 0755);
    }
}

?>
