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


namespace Exakat\Tokenizer;

abstract class Token {
    static public $ATOMS = array('Abstract', 
                                 'Addition', 
                                 'Array', 
                                 'Arrayappend', 
                                 'Arrayliteral', 
                                 'As', 
                                 'Assignation', 
                                 'Bitshift', 
                                 'Block', 
                                 'Boolean', 
                                 'Break', 
                                 'Case', 
                                 'Cast', 
                                 'Catch', 
                                 'Class', 
                                 'Classanonymous', 
                                 'Clone', 
                                 'Closure', 
                                 'Coalesce', 
                                 'Comparison', 
                                 'Concatenation', 
                                 'Const', 
                                 'Constant', 
                                 'Continue', 
                                 'Declare', 
                                 'Default', 
                                 'Defineconstant', 
                                 'Dowhile', 
                                 'Echo', 
                                 'Empty', 
                                 'Eval', 
                                 'Exit', 
                                 'File', 
                                 'Final', 
                                 'Finally', 
                                 'For', 
                                 'Foreach', 
                                 'Function', 
                                 'Functioncall', 
                                 'Global', 
                                 'Globaldefinition', 
                                 'Goto', 
                                 'Gotolabel', 
                                 'Halt', 
                                 'Heredoc', 
                                 'Identifier', 
                                 'Ifthen', 
                                 'Include', 
                                 'Inlinehtml', 
                                 'Instanceof', 
                                 'Insteadof', 
                                 'Integer', 
                                 'Interface', 
                                 'Isset', 
                                 'Keyvalue', 
                                 'List', 
                                 'Logical', 
                                 'Magicconstant', 
                                 'Member', 
                                 'Magicmethod', 
                                 'Method', 
                                 'Methodcall', 
                                 'Methodcallname', 
                                 'Multiplication', 
                                 'Name', 
                                 'Namespace', 
                                 'New', 
                                 'Newcall', 
                                 'Noscream', 
                                 'Not', 
                                 'Nsname', 
                                 'Null', 
                                 'Parent', 
                                 'Parenthesis', 
                                 'Php', 
                                 'Phpvariable', 
                                 'Postplusplus', 
                                 'Power', 
                                 'Ppp', 
                                 'Preplusplus', 
                                 'Print', 
                                 'Private', 
                                 'Project', 
                                 'Propertydefinition', 
                                 'Protected', 
                                 'Public', 
                                 'Real', 
                                 'Return', 
                                 'Self', 
                                 'Sequence', 
                                 'Shell', 
                                 'Sign', 
                                 'Static', 
                                 'Staticclass', 
                                 'Staticconstant', 
                                 'Staticdefinition', 
                                 'Staticmethodcall', 
                                 'Staticproperty', 
                                 'String', 
                                 'Switch', 
                                 'Ternary', 
                                 'This', 
                                 'Throw', 
                                 'Trait', 
                                 'Try', 
                                 'Unset', 
                                 'Usenamespace', 
                                 'Usetrait', 
                                 'Var', 
                                 'Variable', 
                                 'Variablearray', 
                                 'Variableobject', 
                                 'Void', 
                                 'While', 
                                 'Yield', 
                                 'Yieldfrom',
                                );
    static public $ATOMS_EXAKAT = array('Analysis', 
                                        'Noresult',
                                       );
    
    static public $LINKS = array('ABSTRACT', 
                                 'APPEND', 
                                 'ARGUMENT', 
                                 'AS', 
                                 'AT', 
                                 'BLOCK', 
                                 'BREAK', 
                                 'CASE', 
                                 'CASES', 
                                 'CAST', 
                                 'CATCH', 
                                 'CLASS', 
                                 'CLONE', 
                                 'CODE', 
                                 'CONCAT', 
                                 'CONDITION', 
                                 'CONST', 
                                 'CONSTANT', 
                                 'CONTINUE', 
                                 'DEFAULT', 
                                 'EXPRESSION', 
                                 'ELSE', 
                                 'EXTENDS', 
                                 'FILE', 
                                 'FINAL', 
                                 'FINALLY', 
                                 'FUNCTION', 
                                 'GLOBAL', 
                                 'GOTO', 
                                 'GROUPUSE', 
                                 'IMPLEMENTS', 
                                 'INCREMENT', 
                                 'INDEX', 
                                 'INIT', 
                                 'INSTEADOF', 
                                 'GOTOLABEL', 
                                 'LEFT', 
                                 'METHOD', 
                                 'NAME', 
                                 'NEW', 
                                 'NOT', 
                                 'NULLABLE', 
                                 'OBJECT', 
                                 'PPP', 
                                 'POSTPLUSPLUS', 
                                 'PREPLUSPLUS', 
                                 'PRIVATE', 
                                 'PROJECT', 
                                 'MAGICMETHOD', 
                                 'MEMBER', 
                                 'PROTECTED', 
                                 'PUBLIC', 
                                 'RETURN', 
                                 'RETURNTYPE', 
                                 'RIGHT', 
                                 'SIGN', 
                                 'SOURCE', 
                                 'STATIC', 
                                 'THEN', 
                                 'THROW', 
                                 'TRAILING', 
                                 'TYPEHINT', 
                                 'USE', 
                                 'VALUE', 
                                 'VAR', 
                                 'VARIABLE', 
                                 'YIELD', 
                                 'ALIAS',
                                );
    static public $LINKS_EXAKAT = array('DEFINITION', 
                                        'ANALYZED',
                                       );
    
    static public function linksAsList() {
        return '"'.implode('", "', self::$LINKS).'"';
    }
}

?>
