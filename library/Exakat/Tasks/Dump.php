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
use Exakat\Analyzer\Analyzer;
use Exakat\Exceptions\NoSuchAnalyzer;
use Exakat\Exceptions\NoSuchProject;
use Exakat\Exceptions\NoSuchThema;
use Exakat\Exceptions\NotProjectInGraph;
use Exakat\Tokenizer\Token;

class Dump extends Tasks {
    const CONCURENCE = self::DUMP;

    private $sqlite            = null;

    private $rounds            = 0;
    private $sqliteFile        = null;
    private $sqliteFileFinal   = null;
    
    private $files = array();

    const WAITING_LOOP = 1000;

    public function run() {
        if (!file_exists($this->config->projects_root.'/projects/'.$this->config->project)) {
            throw new NoSuchProject($this->config->project);
        }

        $projectInGraph = $this->gremlin->query('g.V().hasLabel("Project").values("fullcode")')->toArray()[0];
        
        if ($projectInGraph !== $this->config->project) {
            throw new NotProjectInGraph($this->config->project, $projectInGraph);
        }
        
        // move this to .dump.sqlite then rename at the end, or any imtermediate time
        // Mention that some are not yet arrived in the snitch
        $this->sqliteFile = $this->config->projects_root.'/projects/'.$this->config->project.'/.dump.sqlite';
        $this->sqliteFileFinal = $this->config->projects_root.'/projects/'.$this->config->project.'/dump.sqlite';
        if (file_exists($this->sqliteFile)) {
            unlink($this->sqliteFile);
            display('Removing old .dump.sqlite');
        }
        $this->addSnitch();

        if ($this->config->update === true) {
            copy($this->sqliteFileFinal, $this->sqliteFile);
            $this->sqlite = new \Sqlite3($this->sqliteFile);
        } else {
            $this->sqlite = new \Sqlite3($this->sqliteFile);

            $query = <<<SQL
CREATE TABLE themas (  id    INTEGER PRIMARY KEY AUTOINCREMENT,
                       thema STRING
                    )
SQL;
            $this->sqlite->query($query);

            $query = <<<SQL
CREATE TABLE results (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                        fullcode STRING,
                        file STRING,
                        line INTEGER,
                        namespace STRING,
                        class STRING,
                        function STRING,
                        analyzer STRING,
                        severity STRING
                     )
SQL;
            $this->sqlite->query($query);

            $query = <<<SQL
CREATE TABLE resultsCounts ( id INTEGER PRIMARY KEY AUTOINCREMENT,
                             analyzer STRING,
                             count INTEGER DEFAULT -6,
                            CONSTRAINT "analyzers" UNIQUE (analyzer) ON CONFLICT REPLACE
                           )
SQL;
            $this->sqlite->query($query);

            $this->collectDatastore();

            display('Inited tables');
        }
        
        if ($this->config->collect === true) {
            display('Collecting data');
            $this->collectFiles();

            $this->collectFilesDependencies();
            $this->getAtomCounts();

            $this->collectStructures();
            $this->collectFunctions();
            $this->collectVariables();
            $this->collectLiterals();
            $this->collectReadability();

            display('Collecting data finished');
        }

        $themes = array();
        if ($this->config->thema !== null) {
            $thema = $this->config->thema;
            $themes = Analyzer::getThemeAnalyzers($thema);
            if (empty($themes)) {
                $r = Analyzer::getSuggestionThema($thema);
                if (!empty($r)) {
                    echo 'did you mean : ', implode(', ', str_replace('_', '/', $r)), "\n";
                }
                throw new NoSuchThema($thema);
            }
            display('Processing thema : '.$thema);
        } elseif ($this->config->program !== null) {
            $analyzer = $this->config->program;
            if(!is_array($analyzer)) {
                $themes = array($analyzer);
            } else {
                $themes = $analyzer;
            }

            foreach($themes as $a) {
                if (!Analyzer::getClass($a)) {
                    throw new NoSuchAnalyzer($a);
                }
            }
            display('Processing '.count($themes).' analyzer'.(count($themes) > 1 ? 's' : '').' : '.implode(', ', $themes));
        }

        $sqlitePath = $this->config->projects_root.'/projects/'.$this->config->project.'/datastore.sqlite';

        $counts = array();
        $datastore = new \Sqlite3($sqlitePath, \SQLITE3_OPEN_READONLY);
        $datastore->busyTimeout(5000);
        $res = $datastore->query('SELECT * FROM analyzed');
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $counts[$row['analyzer']] = $row['counts'];
        }
        $this->log->log( 'count analyzed : '.count($counts)."\n");
        $this->log->log( 'counts '.implode(', ', $counts)."\n");
        $datastore->close();
        unset($datastore);
        
        foreach($themes as $id => $thema) {
            if (isset($counts[$thema])) {
                display( $thema.' : '.($counts[$thema] >= 0 ? 'Yes' : 'N/A')."\n");
                $this->processResults($thema, $counts[$thema]);
                unset($themes[$id]);
            } else {
                display( $thema.' : No'.PHP_EOL);
            }
        }

        $this->log->log( 'Still '.count($themes)." to be processed\n");
        display('Still '.count($themes)." to be processed\n");
        if (count($themes) === 0) {
            if ($this->config->thema !== null) {
                $this->sqlite->query('INSERT INTO themas ("id", "thema") VALUES ( NULL, "'.$this->config->thema.'")');
            }
        }

        $this->finish();
    }

    private function processResults($class, $count) {
        $this->sqlite->query("DELETE FROM results WHERE analyzer = '$class'");

        $this->sqlite->query('REPLACE INTO resultsCounts ("id", "analyzer", "count") VALUES (NULL, \''.$class.'\', '.(int) $count.')');

        $this->log->log( "$class : $count\n");
        // No need to go further
        if ($count <= 0) {
            return;
        }

        $analyzer = Analyzer::getInstance($class, $this->gremlin, $this->config);
        $res = $analyzer->getDump();

        $saved = 0;
        $severity = $analyzer->getSeverity( );

        $query = array();
        foreach($res as $id => $result) {
            if (empty($result)) {
                continue;
            }
            
            $query[] = "(null, 
                         '".$this->sqlite->escapeString($result['fullcode'])."', 
                         '".$this->sqlite->escapeString($result['file'])."', 
                         ". $this->sqlite->escapeString($result['line']).", 
                         '".$this->sqlite->escapeString($result['namespace'])."', 
                         '".$this->sqlite->escapeString($result['class'])."', 
                         '".$this->sqlite->escapeString($result['function'])."',
                         '".$this->sqlite->escapeString($class)."',
                         '".$this->sqlite->escapeString($severity)."')";
            ++$saved;
        }
        
        if (!empty($query)) {
            $values = implode(', ', $query);
            $query = <<<SQL
REPLACE INTO results ("id", "fullcode", "file", "line", "namespace", "class", "function", "analyzer", "severity") 
             VALUES $values
SQL;
            $this->sqlite->query($query);
        }

        $this->log->log("$class : dumped $saved");

        if ($count == $saved) {
            display("All $saved results saved for $class\n");
        } else {
            assert($count == $saved, 'results were not correctly dumped in '.$class. ' '.$saved.'/'.$count);
            display("$saved results saved, $count expected for $class\n");
        }
    }

    private function getAtomCounts() {
        $this->sqlite->query('DROP TABLE IF EXISTS atomsCounts');
        $this->sqlite->query('CREATE TABLE atomsCounts (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                          atom STRING,
                                                          count INTEGER
                                              )');

        $query = 'g.V().groupCount("b").by(label).cap("b").next();';
        $atomsCount = $this->gremlin->query($query);

        $counts = array();
        foreach($atomsCount as $c) {
            foreach($c as $atom => $count) {
                $counts[] = "(null, '$atom', $count)";
            }
        }
        
        $query = 'INSERT INTO atomsCounts ("id", "atom", "count") VALUES '.implode(', ', $counts);
        $this->sqlite->query($query);
        
        display(count($atomsCount)." atoms\n");

    }

    private function finish() {
        $this->sqlite->query('REPLACE INTO resultsCounts ("id", "analyzer", "count") VALUES (NULL, \'Project/Dump\', '.$this->rounds.')');

        // Redo each time so we update the final counts
        $totalNodes = $this->gremlin->query('g.V().count()')->toString();
        $this->sqlite->query('REPLACE INTO hash VALUES(null, "total nodes", '.$totalNodes.')');

        $totalEdges = $this->gremlin->query('g.E().count()')->toString();
        $this->sqlite->query('REPLACE INTO hash VALUES(null, "total edges", '.$totalEdges.')');

        $totalProperties = $this->gremlin->query('g.V().properties().count()')->toString();
        $this->sqlite->query('REPLACE INTO hash VALUES(null, "total properties", '.$totalProperties.')');

        rename($this->sqliteFile, $this->sqliteFileFinal);

        $this->removeSnitch();
    }

    private function collectDatastore() {
        $datastorePath = $this->config->projects_root.'/projects/'.$this->config->project.'/datastore.sqlite';
        $this->sqlite->query('ATTACH "'.$datastorePath.'" AS datastore');

        $tables = array('analyzed',
                        'compilation52',
                        'compilation53',
                        'compilation54',
                        'compilation55',
                        'compilation56',
                        'compilation70',
                        'compilation71',
                        'compilation72',
                        'composer',
                        'configFiles',
                        'externallibraries',
                        'files',
                        'hash',
                        'hashAnalyzer',
                        'ignoredFiles',
                        'shortopentag',
                        'tokenCounts',
                        );
        $query = "SELECT name, sql FROM datastore.sqlite_master WHERE type='table' AND name in ('".implode("', '", $tables)."');";
        $existingTables = $this->sqlite->query($query);

        while($table = $existingTables->fetchArray(\SQLITE3_ASSOC)) {
            $createTable = $table['sql'];
            $createTable = str_replace('CREATE TABLE ', 'CREATE TABLE IF NOT EXISTS ', $createTable);

            $this->sqlite->query($createTable);
            $this->sqlite->query('REPLACE INTO '.$table['name'].' SELECT * FROM datastore.'.$table['name']);
        }
    }

    private function collectVariables() {
        // Name spaces
        $this->sqlite->query('DROP TABLE IF EXISTS variables');
        $this->sqlite->query('CREATE TABLE variables (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                        variable STRING,
                                                        type STRING
                                                 )');

        $query = <<<GREMLIN
g.V().hasLabel("Variable", "Variablearray", "Variableobject").map{ ['name' : it.get().value("fullcode"), 
                                                                    'type' : it.get().label()        ] }.unique();
GREMLIN;
        $variables = $this->gremlin->query($query);

        $total = 0;
        $query = array();

        $types = array('Variable'       => 'var',
                       'Variablearray'  => 'array',
                       'Variableobject' => 'object',
                      );
        foreach($variables as $row) {
            $type = $types[$row['type']];
            $query[] = "(null, '".strtolower($this->sqlite->escapeString($row['name']))."', '".$type."')";
            ++$total;
        }
        
        if (!empty($query)) {
            $query = 'INSERT INTO variables ("id", "variable", "type") VALUES '.implode(', ', $query);
            $this->sqlite->query($query);
        }
        
        display( "Variables : $total\n");
    }
    
    private function collectStructures() {

        // Name spaces
        $this->sqlite->query('DROP TABLE IF EXISTS namespaces');
        $this->sqlite->query('CREATE TABLE namespaces (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                         namespace STRING
                                                 )');
        $this->sqlite->query('INSERT INTO namespaces VALUES ( 1, "")');

        $query = <<<GREMLIN
g.V().hasLabel("Namespace").out("NAME").map{ ['name' : it.get().value("fullcode")] }.unique();
GREMLIN;
        $res = $this->gremlin->query($query);

        $total = 0;
        $query = array();
        foreach($res as $row) {
            $query[] = "(null, '\\".strtolower($this->sqlite->escapeString($row['name']))."')";
            ++$total;
        }
        
        if (!empty($query)) {
            $query = 'INSERT INTO namespaces ("id", "namespace") VALUES '.implode(', ', $query);
            $this->sqlite->query($query);
        }

        $query = 'SELECT id, lower(namespace) AS namespace FROM namespaces';
        $res = $this->sqlite->query($query);

        $namespacesId = array('' => 1);
        while($namespace = $res->fetchArray(\SQLITE3_ASSOC)) {
            $namespacesId[$namespace['namespace']] = $namespace['id'];
        }
        display("$total namespaces\n");

        // Classes
        $this->sqlite->query('DROP TABLE IF EXISTS cit');
        $this->sqlite->query('CREATE TABLE cit (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                   name STRING,
                                                   abstract INTEGER,
                                                   final INTEGER,
                                                   type TEXT,
                                                   extends TEXT DEFAULT "",
                                                   begin INTEGER,
                                                   end INTEGER,
                                                   file INTEGER,
                                                   namespaceId INTEGER DEFAULT 1
                                                 )');

        $this->sqlite->query('DROP TABLE IF EXISTS cit_implements');
        $this->sqlite->query('CREATE TABLE cit_implements (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                             implementing INTEGER,
                                                             implements INTEGER,
                                                             type    TEXT
                                                 )');

        $query = <<<GREMLIN
g.V().hasLabel("Class")
.sideEffect{ extendList = ''; }.where(__.out("EXTENDS").sideEffect{ extendList = it.get().value("fullnspath"); }.fold() )
.sideEffect{ implementList = []; }.where(__.out("IMPLEMENTS").sideEffect{ implementList.push( it.get().value("fullnspath"));}.fold() )
.sideEffect{ useList = []; }.where(__.out("USE").hasLabel("Use").out("USE").sideEffect{ useList.push( it.get().value("fullnspath"));}.fold() )
.sideEffect{ lines = [];}.where( __.out("METHOD", "USE", "PPP", "CONST").emit().repeat( __.out()).times(15).sideEffect{ lines.add(it.get().value("line")); }.fold())
.sideEffect{ file = '';}.where( __.in().emit().repeat( __.in()).times(10).hasLabel("File").sideEffect{ file = it.get().value("fullcode"); }.fold() )
.map{ 
        ['fullnspath':it.get().value("fullnspath"),
         'name': it.get().vertices(OUT, "NAME").next().value("fullcode"),
         'abstract':it.get().vertices(OUT, "ABSTRACT").any(),
         'final':it.get().vertices(OUT, "FINAL").any(),
         'extends':extendList,
         'implements':implementList,
         'uses':useList,
         'type':'class',
         'begin':lines.min(),
         'end':lines.max(),
         'file':file
         ];
}

GREMLIN;
        $classes = $this->gremlin->query($query);

        $total = 0;
        $extendsId = array();
        $implementsId = array();
        $usesId = array();
        
        $cit = array();
        $citId = array();
        $citCount = 0;

        foreach($classes as $row) {
            $namespace = preg_replace('#\\\\[^\\\\]*?$#is', '', $row['fullnspath']);

            if (isset($namespacesId[$namespace])) {
                $namespaceId = $namespacesId[$namespace];
            } else {
                $namespaceId = 1;
            }
            
            $cit[] = $row;
            $citId[$row['fullnspath']] = ++$citCount;
            
            ++$total;
        }

        display("$total classes\n");

        // Interfaces
        $query = <<<GREMLIN
g.V().hasLabel("Interface")
.sideEffect{ extendList = ''; }.where(__.out("EXTENDS").sideEffect{ extendList = it.get().value("fullnspath"); }.fold() )
.sideEffect{ lines = [];}.where( __.out("METHOD", "CONST").emit().repeat( __.out()).times(15).sideEffect{ lines.add(it.get().value("line")); }.fold())
.sideEffect{ file = [];}.where( __.in().emit().repeat( __.in()).times(10).hasLabel("File").sideEffect{ file = it.get().value("fullcode"); }.fold() )
.map{ 
        ['fullnspath':it.get().value("fullnspath"),
         'name': it.get().vertices(OUT, "NAME").next().value("fullcode"),
         'extends':extendList,
         'type':'interface',
         'abstract':0,
         'final':0,
         'begin':lines.min(),
         'end':lines.max(),
         'file':file
         ];
}
GREMLIN;
        $res = $this->gremlin->query($query);

        $total = 0;
        foreach($res as $row) {
            $namespace = preg_replace('#\\\\[^\\\\]*?$#is', '', $row['fullnspath']);

            if (isset($namespacesId[$namespace])) {
                $namespaceId = $namespacesId[$namespace];
            } else {
                $namespaceId = 1;
            }
            
            $cit[] = $row;
            $citId[$row['fullnspath']] = ++$citCount;

            ++$total;
        }

        display("$total interfaces\n");

        // Traits
        $query = <<<GREMLIN
g.V().hasLabel("Trait")
.sideEffect{ useList = []; }.where(__.out("USE").hasLabel("Use").out("USE").sideEffect{ useList.push( it.get().value("fullnspath"));}.fold() )
.sideEffect{ lines = [];}.where( __.out("METHOD", "USE", "PPP").emit().repeat( __.out()).times(15).sideEffect{ lines.add(it.get().value("line")); }.fold())
.sideEffect{ file = '';}.where( __.in().emit().repeat( __.in()).times(10).hasLabel("File").sideEffect{ file = it.get().value("fullcode"); }.fold() )
.map{ 
        ['fullnspath':it.get().value("fullnspath"),
         'name': it.get().vertices(OUT, "NAME").next().value("fullcode"),
         'uses':useList,
         'type':'trait',
         'abstract':0,
         'final':0,
         'begin':lines.min(),
         'end':lines.max(),
         'file':file
         ];
}

GREMLIN;
        $res = $this->gremlin->query($query);

        $total = 0;
        foreach($res as $row) {
            $namespace = preg_replace('#\\\\[^\\\\]*?$#is', '', $row['fullnspath']);

            if (isset($namespacesId[$namespace])) {
                $namespaceId = $namespacesId[$namespace];
            } else {
                $namespaceId = 1;
            }
            
            $row['implements'] = array(); // always empty
            $cit[] = $row;
            $citId[$row['fullnspath']] = ++$citCount;

            ++$total;
        }
        
        display("$total traits\n");
        
        if (!empty($cit)) {
            $query = array();
            
            foreach($cit as $row) {
                if (empty($row['extends'])) {
                    $extends = "''";
                } elseif (isset($citId[$row['extends']])) {
                    $extends = $citId[$row['extends']];
                } else {
                    $extends = '"'.$this->sqlite->escapeString($row['extends']).'"';
                }

                $namespace = preg_replace('/\\\\[^\\\\]*?$/', '', $row['fullnspath']);
                if (isset($namespacesId[$namespace])) {
                    $namespaceId = $namespacesId[$namespace];
                } else {
                    $namespaceId = 1;
                }

                $query[] = "(".$citId[$row['fullnspath']].
                           ", '".$this->sqlite->escapeString($row['name'])."'".
                           ", ".$namespaceId.
                           ", ".(int) $row['abstract'].
                           ",".(int) $row['final'].
                           ", '".$row['type']."'".
                           ", ".$extends.
                           ", ".(int) $row['begin'].
                           ", ".(int) $row['end'].
                           ", '".$this->files[$row['file']]."'".
                           " )";
            }

            if (!empty($query)) {
                $query = 'INSERT OR IGNORE INTO cit ("id", "name", "namespaceId", "abstract", "final", "type", "extends", "begin", "end", "file") VALUES '.implode(", \n", $query);
                $this->sqlite->query($query);
            }

            $query = array();
            foreach($cit as $row) {
                if (empty($row['implements'])) {
                    continue;
                }

                foreach($row['implements'] as $implements) {
                    if (isset($citId[$implements])) {
                        $query[] = "(null, ".$citId[$row['fullnspath']].", $citId[$implements], 'implements')";
                    } else {
                        $query[] = "(null, ".$citId[$row['fullnspath']].", '".$this->sqlite->escapeString($implements)."', 'implements')";
                    }
                }
            }

            if (!empty($query)) {
                $query = 'INSERT INTO cit_implements ("id", "implementing", "implements", "type") VALUES '.implode(', ', $query);
                $this->sqlite->query($query);
            }

            $query = array();
            foreach($cit as $row) {
                if (empty($row['uses'])) {
                    continue;
                }
                
                foreach($row['uses'] as $uses) {
                    if (isset($citId[$uses])) {
                        $query[] = "(null, ".$citId[$row['fullnspath']].", $citId[$uses], 'use')";
                    } else {
                        $query[] = "(null, ".$citId[$row['fullnspath']].", '".$this->sqlite->escapeString($row['name'])."', 'use')";
                    }
                }
            }

            if (!empty($query)) {
                $query = 'INSERT INTO cit_implements ("id", "implementing", "implements", "type") VALUES '.implode(', ', $query);
                $this->sqlite->query($query);
            }
        }

        // Manage use (traits)
        // Same SQL than for implements

        $total = 0;
        $query = array();
        foreach($usesId as $id => $usesFNP) {
            foreach($usesFNP as $fnp) {
                if (substr($fnp, 0, 2) == '\\\\') {
                    $fnp = substr($fnp, 2);
                }
                if (isset($citId[$fnp])) {
                    $query[] = "(null, $id, $citId[$fnp], 'use')";

                    ++$total;
                } // Else ignore. Not in the project
            }
        }
        if (!empty($query)) {
            $query = 'INSERT INTO cit_implements ("id", "implementing", "implements", "type") VALUES '.implode(', ', $query);
            $this->sqlite->query($query);
        }
        display("$total uses \n");

        // Methods
        $this->sqlite->query('DROP TABLE IF EXISTS methods');
        $this->sqlite->query('CREATE TABLE methods (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                method INTEGER,
                                                citId INTEGER,
                                                static INTEGER,
                                                final INTEGER,
                                                abstract INTEGER,
                                                visibility STRING,
                                                begin INTEGER,
                                                end INTEGER
                                                 )');

        
        $query = <<<GREMLIN
g.V().hasLabel("Method").as('method')
     .in("METHOD").hasLabel("Class", "Interface", "Trait").sideEffect{classe = it.get().value('fullnspath'); }
     .select('method')
.where( __.sideEffect{ lines = [];}
               .out("BLOCK").out("EXPRESSION")
               .emit().repeat( __.out()).times(15)
               .sideEffect{ lines.add(it.get().value("line")); }
               .fold()
      )
.map{ 
    x = ['name': it.get().value("fullcode"),
         'abstract':it.get().vertices(OUT, "ABSTRACT").any(),
         'final':it.get().vertices(OUT, "FINAL").any(),
         'static':it.get().vertices(OUT, "STATIC").any(),

         'public':it.get().vertices(OUT, "PUBLIC").any(),
         'protected':it.get().vertices(OUT, "PROTECTED").any(),
         'private':it.get().vertices(OUT, "PRIVATE").any(),         
         'class': classe,
         'begin': lines.min(),
         'end': lines.max()
         ];
}

GREMLIN;
        $res = $this->gremlin->query($query);

        $total = 0;
        $query = array();
        foreach($res as $row) {
            if ($row['public']) {
                $visibility = 'public';
            } elseif ($row['protected']) {
                $visibility = 'protected';
            } elseif ($row['private']) {
                $visibility = 'private';
            } else {
                $visibility = '';
            }

            if (!isset($citId[$row['class']])) {
                continue;
            }
            $query[] = "(null, '".$this->sqlite->escapeString($row['name'])."', ".$citId[$row['class']].
                        ", ".(int) $row['static'].", ".(int) $row['final'].", ".(int) $row['abstract'].", '".$visibility."'".
                        ", ".(int) $row['begin'].", ".(int) $row['end'].")";

            ++$total;
        }

        if (!empty($query)) {
            $query = 'INSERT INTO methods ("id", "method", "citId", "static", "final", "abstract", "visibility", "begin", "end") VALUES '.implode(', ', $query);
            $this->sqlite->query($query);
        }

        display("$total methods\n");

        // Properties
        $this->sqlite->query('DROP TABLE IF EXISTS properties');
        $this->sqlite->query('CREATE TABLE properties (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                property INTEGER,
                                                citId INTEGER,
                                                visibility STRING,
                                                static INTEGER,
                                                value TEXT
                                                 )');

        $query = <<<GREMLIN
g.V().hasLabel("Class", "Interface", "Trait")
     .sideEffect{classe = it.get().value('fullnspath'); }
     .out('PPP') // Out of the CIT
.sideEffect{ 
    x_static = it.get().vertices(OUT, "STATIC").any();
    x_public = it.get().vertices(OUT, "PUBLIC").any();
    x_protected = it.get().vertices(OUT, "PROTECTED").any();
    x_private = it.get().vertices(OUT, "PRIVATE").any();
    x_var = it.get().vertices(OUT, "VAR").any();
}
.out('PPP') // out to the details
.map{ 
    if (it.get().label() == 'Propertydefinition') { 
        name = it.get().value("fullcode");
        v = ''; 
    } else { 
        name = it.get().vertices(OUT, "LEFT").next().value("fullcode");
        v = it.get().vertices(OUT, "RIGHT").next().value("fullcode");
    }

    x = ["class":classe,
         "static":x_static,
         "public":x_public,
         "protected":x_protected,
         "private":x_private,
         "var":x_var,
         "name": name,
         "value": v];
}

GREMLIN;
        $res = $this->gremlin->query($query);

        $total = 0;
        $query = array();
        foreach($res as $row) {
            if ($row['public']) {
                $visibility = 'public';
            } elseif ($row['protected']) {
                $visibility = 'protected';
            } elseif ($row['private']) {
                $visibility = 'private';
            } elseif ($row['var']) {
                $visibility = '';
            } else {
                continue;
            }

            // If we haven't found any definition for this class, just ignore it.
            if (!isset($citId[$row['class']])) {
                continue;
            }
            $query[] = "(null, '".$this->sqlite->escapeString($row['name'])."', ".$citId[$row['class']].
                        ", '".$visibility."', '".$this->sqlite->escapeString($row['value'])."', ".(int) $row['static'].")";

            ++$total;
        }
        if (!empty($query)) {
            $query = 'INSERT INTO properties ("id", "property", "citId", "visibility", "value", "static") VALUES '.implode(', ', $query);
            $this->sqlite->query($query);
        }
        
        display("$total properties\n");

        // Constants
        $this->sqlite->query('DROP TABLE IF EXISTS constants');
        $this->sqlite->query('CREATE TABLE constants (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                constant INTEGER,
                                                citId INTEGER,
                                                visibility STRING,
                                                value TEXT
                                                 )');

        $query = <<<GREMLIN
g.V().hasLabel("Class")
     .out('CONST')
.sideEffect{ 
    x_public = it.get().vertices(OUT, "PUBLIC").any();
    x_protected = it.get().vertices(OUT, "PROTECTED").any();
    x_private = it.get().vertices(OUT, "PRIVATE").any();
}
     .out('CONST')
     .map{ 
    x = ['name': it.get().vertices(OUT, 'NAME').next().value("fullcode"),
         'value': it.get().vertices(OUT, 'VALUE').next().value("fullcode"),
         "public":x_public,
         "protected":x_protected,
         "private":x_private,
         'class': it.get().vertices(IN, 'CONST').next().vertices(IN, 'CONST').next().value("fullnspath")
         ];
}

GREMLIN;
        $res = $this->gremlin->query($query);

        $total = 0;
        $query = array();
        foreach($res as $row) {
            if ($row['public']) {
                $visibility = 'public';
            } elseif ($row['protected']) {
                $visibility = 'protected';
            } elseif ($row['private']) {
                $visibility = 'private';
            } else {
                $visibility = '';
            }

            if (isset($namespacesId[$namespace])) {
                $namespaceId = $namespacesId[$namespace];
            } else {
                $namespaceId = 1;
            }

            $query[] = "(null, '".$this->sqlite->escapeString($row['name'])."'".
                       ", ".$citId[$row['class']].
                       ", '".$visibility."'".
                       ", '".$this->sqlite->escapeString($row['value'])."')";

            ++$total;
        }

        if (!empty($query)) {
            $query = 'INSERT INTO constants ("id", "constant", "citId", "visibility", "value") VALUES '.implode(', ', $query);
            $this->sqlite->query($query);
        }
        display("$total constants\n");
    }

    private function collectFiles() {
        $res = $this->sqlite->query('SELECT * FROM files');
        $this->files = array();
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $this->files[$row['file']] = $row['id'];
        }
    }
    
    private function collectFunctions() {
        // Functions
        $this->sqlite->query('DROP TABLE IF EXISTS functions');
        $this->sqlite->query(<<<SQL
CREATE TABLE functions (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                          function TEXT,
                          file TEXT,
                          begin INTEGER,
                          end INTEGER
)
SQL
);

        $query = <<<GREMLIN
g.V().hasLabel("Function")
.sideEffect{ lines = [];}.where( __.out("BLOCK").out("EXPRESSION").emit().repeat( __.out()).times(15).sideEffect{ lines.add(it.get().value("line")); }.fold() )
.sideEffect{ file = '';}.where( __.in().emit().repeat( __.in()).times(10).hasLabel("File").sideEffect{ file = it.get().value("fullcode"); }.fold() )
.map{ 
    x = ['name': it.get().vertices(OUT, "NAME").next().value("fullcode"),
         'file': file,
         'begin': lines.min(),
         'end': lines.max()
         ];
}

GREMLIN;
        $res = $this->gremlin->query($query);

        $total = 0;
        $query = array();
        foreach($res as $row) {
            $query[] = "(null, '".$this->sqlite->escapeString($row['name'])."', '".$this->files[$row['file']]."', ".(int) $row['begin'].", ".(int) $row['end'].")";

            ++$total;
        }

        if (!empty($query)) {
            $query = 'INSERT INTO functions ("id", "function", "file", "begin", "end") VALUES '.implode(', ', $query);
            $this->sqlite->query($query);
        }

        display("$total functions\n");
    }

    private function collectLiterals() {
        $types = array('Integer', 'Real', 'String', 'Heredoc', 'Arrayliteral');

        foreach($types as $type) {
            $this->sqlite->query('DROP TABLE IF EXISTS literal'.$type);
            $this->sqlite->query('CREATE TABLE literal'.$type.' (  
                                                   id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                   name STRING,
                                                   file STRING,
                                                   line INTEGER
                                                 )');

            $filter = 'hasLabel("'.$type.'")';

            $b = microtime(true);
            $query = <<<GREMLIN

g.V().$filter.has('constant', true)
.sideEffect{ name = it.get().value("fullcode");
             line = it.get().value('line');
             file='None'; 
             }
.until( hasLabel('Project') ).repeat( 
    __.in()
      .sideEffect{ if (it.get().label() == 'File') { file = it.get().value('fullcode')} }
       )
.map{ 
    x = ['name': name,
         'file': file,
         'line': line
         ];
}

GREMLIN;
            $res = $this->gremlin->query($query);
    
            $total = 0;
            $query = array();
            foreach($res as $value => $row) {
                $query[] = "('".$this->sqlite->escapeString($row['name'])."','".$this->sqlite->escapeString($row['file'])."',".$row['line'].')';
                ++$total;
                if ($total % 10000 === 0) {
                    $query = 'INSERT INTO literal'.$type.' (name, file, line) VALUES '.implode(', ', $query);
                    $this->sqlite->query($query);
                    $query = array();
                }
            }
            
            if (!empty($query)) {
                $query = 'INSERT INTO literal'.$type.' (name, file, line) VALUES '.implode(', ', $query);
                $this->sqlite->query($query);
            }
            display( "literal$type : $total\n");
        }

       $this->sqlite->query('DROP TABLE IF EXISTS stringEncodings');
       $this->sqlite->query('CREATE TABLE stringEncodings (  
                                              id INTEGER PRIMARY KEY AUTOINCREMENT,
                                              encoding STRING,
                                              block STRING,
                                              CONSTRAINT "encoding" UNIQUE (encoding, block)
                                            )');

        $query = <<<GREMLIN
g.V().hasLabel('String').map{ x = ['encoding':it.get().values('encoding')[0]];
    if (it.get().values('block').size() != 0) {
        x['block'] = it.get().values('block')[0];
    }
    x;
}

GREMLIN;
        $res = $this->gremlin->query($query);
        
        $total = 0;
        $query = array();
        foreach($res as $value => $row) {
            if (isset($row['block'])){
                $query[] = '(\''.$row['encoding'].'\', \''.$row['block'].'\')';
            } else {
                $query[] = '(\''.$row['encoding'].'\', \'\')';
            }
        }
       
       if (!empty($query)) {
           $query = 'REPLACE INTO stringEncodings ("encoding", "block") VALUES '.implode(', ', $query);
           $this->sqlite->query($query);
       }
    }

    private function collectFilesDependencies() {
        $this->sqlite->query('DROP TABLE IF EXISTS filesDependencies');
        $this->sqlite->query('CREATE TABLE filesDependencies ( id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                               including STRING,
                                                               included STRING,
                                                               type STRING
                                                 )');

        // Direct inclusion
        $query = 'g.V().hasLabel("File").as("file")
                   .repeat( out() ).emit().times(15).hasLabel("Include").as("include")
                   .select("file", "include").by("fullcode").by("fullcode")';
        $res = $this->gremlin->query($query);
        
        $query = array();
        if (isset($res->results)) {
            $includes = $res->results;

            foreach($includes as $link) {
                $query[] = "(null, '".$this->sqlite->escapeString($link['file'])."', '".$this->sqlite->escapeString($link['include'])."', 'INCLUDE')";
            }

            if (!empty($query)) {
                $query = 'INSERT INTO filesDependencies ("id", "including", "included", "type") VALUES '.implode(', ', $query);
                $this->sqlite->query($query);
            }
            display(count($includes)." inclusions ");
        }

        // Finding extends and implements
        $query = <<<GREMLIN
g.V().hasLabel("Class", "Interface").as("classe")
.where( __.repeat( __.in() ).emit().times(15).hasLabel("File").sideEffect{ calling = it.get().value('fullcode'); })
.select("classe").outE().hasLabel("EXTENDS", "IMPLEMENTS").sideEffect{ type = it.get().label(); }.inV()
.where( __.repeat( __.in() ).emit().times(15).hasLabel("File").sideEffect{ called = it.get().value('fullcode'); })
.map{ [ 'file':calling, 'type':type, 'include':called];}
GREMLIN;

        $extends = $this->gremlin->query($query);
        $query = array();

        foreach($extends as $link) {
            $query[] = "(null, '".$this->sqlite->escapeString($link['file'])."', '".$this->sqlite->escapeString($link['include'])."', '".$link['type']."')";
        }

        if (!empty($query)) {
            $sqlQuery = 'INSERT INTO filesDependencies ("id", "including", "included", "type") VALUES '.implode(', ', $query);
            $this->sqlite->query($sqlQuery);
        }
        display(count($extends)." extends for classes ");

        // Finding extends for interfaces
        $query = <<<GREMLIN
g.V().hasLabel("Interface").as("classe")
     .repeat( __.in() ).emit().times(15).hasLabel("File").as("file")
     .select("classe").out("EXTENDS")
     .repeat( __.in() ).emit().times(15).hasLabel("File").as("include")
     .select("file", "include").by("fullcode").by("fullcode")
GREMLIN;
        $res = $this->gremlin->query($query);
        $query = array();

        foreach($res as $link) {
            $query[] = "(null, '".$this->sqlite->escapeString($link['file'])."', '".$this->sqlite->escapeString($link['include'])."', 'EXTENDS')";
        }

        if (!empty($query)) {
            $query = 'INSERT INTO filesDependencies ("id", "including", "included", "type") VALUES '.implode(', ', $query);
            $this->sqlite->query($query);
        }
        display(count($res)." extends for interfaces ");

        // traits
        $query = <<<GREMLIN
g.V().hasLabel("Class", "Trait").as("classe")
     .repeat( __.in() ).emit().times(15).hasLabel("File").as("file")
     .select("classe").out("USE").hasLabel("Use").out("USE").in("DEFINITION")
     .repeat( __.in() ).emit().times(15).hasLabel("File").as("include")
     .select("file", "include").by("fullcode").by("fullcode")
GREMLIN;
        $res = $this->gremlin->query($query);
        $query = array();

        foreach($res as $link) {
            $query[] = "(null, '".$this->sqlite->escapeString($link['file'])."', '".$this->sqlite->escapeString($link['include'])."', 'USE')";
        }

        if (!empty($query)) {
            $query = 'INSERT INTO filesDependencies ("id", "including", "included", "type") VALUES '.implode(', ', $query);
            $this->sqlite->query($query);
        }
        display(count($res)." use ");

        // Functioncall()
        $query = <<<GREMLIN
g.V().hasLabel("Functioncall")
.where(__.repeat( __.in() ).emit(hasLabel("File")).times(15).hasLabel("File").sideEffect{ calling = it.get().value("fullcode"); })
.in("DEFINITION")
.where(__.repeat( __.in() ).emit(hasLabel("File")).times(15).hasLabel("File").sideEffect{ called = it.get().value("fullcode"); })
.map{['file':calling, 'include':called]}
GREMLIN;
        $functioncall = $this->gremlin->query($query);
        $query = array();

        foreach($functioncall as $link) {
            $query[] = "(null, '".$this->sqlite->escapeString($link['file'])."', '".$this->sqlite->escapeString($link['include'])."', 'FUNCTIONCALL')";
        }

        if (!empty($query)) {
            $query = 'INSERT INTO filesDependencies ("id", "including", "included", "type") VALUES '.implode(', ', $query);
            $this->sqlite->query($query);
        }
        display(count($functioncall)." functioncall ");

        // constants
        $query = <<<GREMLIN
g.V().hasLabel("Identifier").not(where( __.in("NAME", "CLASS", "MEMBER", "AS", "CONSTANT", "TYPEHINT", "EXTENDS", "USE", "IMPLEMENTS", "INDEX" ) ) )
     .where( __.repeat( __.in() ).emit().times(15).hasLabel("File").sideEffect{ calling = it.get().value('fullcode'); })
     .in("DEFINITION")
     .where( __.repeat( __.in() ).emit().times(15).hasLabel("File").sideEffect{ called = it.get().value('fullcode'); })
     .map{ [ 'file':calling, 'include':called];}
GREMLIN;
        $constants = $this->gremlin->query($query);
        $query = array();

        foreach($constants as $link) {
            $query[] = "(null, '".$this->sqlite->escapeString($link['file'])."', '".$this->sqlite->escapeString($link['include'])."', 'CONSTANT')";
        }

        if (!empty($query)) {
            $query = 'INSERT INTO filesDependencies ("id", "including", "included", "type") VALUES '.implode(', ', $query);
            $this->sqlite->query($query);
        }
        display(count($constants)." constants ");

        // New
        $query = <<<GREMLIN
g.V().hasLabel("New").out("NEW")
     .where( __.repeat( __.in() ).emit().times(15).hasLabel("File").sideEffect{ calling = it.get().value("fullcode"); })
     .in("DEFINITION")
     .where( __.repeat( __.in() ).emit().times(15).hasLabel("File").sideEffect{ called = it.get().value("fullcode"); })
     .map{ [ "file":calling, "include":called];}
GREMLIN;
        $res = $this->gremlin->query($query);
        $query = array();

        foreach($res as $link) {
            $query[] = "(null, '".$this->sqlite->escapeString($link['file'])."', '".$this->sqlite->escapeString($link['include'])."', 'NEW')";
        }

        if (!empty($query)) {
            $query = 'INSERT INTO filesDependencies ("id", "including", "included", "type") VALUES '.implode(', ', $query);
            $this->sqlite->query($query);
        }
        display(count($res)." new ");

        // static calls (property, constant, method)
        $query = <<<GREMLIN
g.V().hasLabel("Staticconstant", "Staticmethodcall", "Staticproperty")
     .sideEffect{ type = it.get().label().toLowerCase(); }
     .where( __.repeat( __.in() ).emit().times(15).hasLabel("File").sideEffect{ calling = it.get().value('fullcode'); })
     .out("CLASS").in("DEFINITION")
     .where( __.repeat( __.in() ).emit().times(15).hasLabel("File").sideEffect{ called = it.get().value('fullcode'); })
     .map{ [ 'file':calling, 'type':type, 'include':called];}
GREMLIN;
        $statics = $this->gremlin->query($query);
        $query = array();

        foreach($statics as $link) {
            $query[] = "(null, '".$this->sqlite->escapeString($link['file'])."', '".$this->sqlite->escapeString($link['include'])."', '".strtoupper($link['type'])."')";
        }

        if (!empty($query)) {
            $query = 'INSERT INTO filesDependencies ("id", "including", "included", "type") VALUES '.implode(', ', $query);
            $this->sqlite->query($query);
        }
        display(count($statics)." static calls CPM");
    }

    private function collectReadability() {
        $loops = 20;
        $query = <<<GREMLIN
g.V().sideEffect{ functions = 0; name=''; expression=0;}
    .hasLabel("Function", "Closure", "Method", "File")
    .not(where( __.out("BLOCK").hasLabel('Void')))
    .sideEffect{ ++functions; }
    .where(__.coalesce( __.out('NAME').sideEffect{ name=it.get().value("fullcode"); }.in("NAME"),
                        __.filter{true; }.sideEffect{ name='global'; file = it.get().value("fullcode");} )
    .sideEffect{ total = 0; expression = 0; type=it.get().label();}
    .coalesce( __.out("BLOCK"), __.out("FILE").out("EXPRESSION").out("EXPRESSION") )
    .repeat( __.out().not(hasLabel("Class", "Function", "Closure", "Interface", "Trait", "Void")) ).emit().times($loops)
    .sideEffect{ ++total; }
    .not(hasLabel('Void'))
    .where( __.in("EXPRESSION", "CONDITION").sideEffect{ expression++; })
    .where( __.repeat( __.in() ).emit().times($loops).hasLabel("File").sideEffect{ file = it.get().value("fullcode"); })
    .fold()
    )
    .map{ if (expression > 0) {
        ['name':name, 'type':type, 'total':total, 'expression':expression, 'index': 102 - expression - total / expression, 'file':file];
    } else {
        ['name':name, 'type':type, 'total':total, 'expression':0, 'index': 100, 'file':file];
    }
}    
GREMLIN;
        $index = $this->gremlin->query($query);

        $this->sqlite->query('DROP TABLE IF EXISTS readability');
        $query = <<<SQL
CREATE TABLE readability (  
    id      INTEGER PRIMARY KEY AUTOINCREMENT,
    name    STRING,
    type    STRING,
    tokens  INTEGER,
    expressions INTEGER,
    file        STRING
                    )
SQL;
        $this->sqlite->query($query);

        $values = array();
        foreach($index as $row) {
            $values[] = '("'.$row['name'].'","'.$row['type'].'",'.$row['total'].','.$row['expression'].',"'.$row['file'].'") ';
        }

        $query = 'INSERT INTO readability ("name", "type", "tokens", "expressions", "file") VALUES '.implode(', ', $values);
        $this->sqlite->query($query);

        display( count($values).' readability index');
    }
}

?>
