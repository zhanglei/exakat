<?php

namespace Tokenizer;

class Token {
    protected static $client = null;
    protected static $reserved = array();
    
    function __construct($client) {
        // @todo typehint ? 
        Token::$client = $client; 
    }
    
    final function check() {
        
        print get_class($this)." check \n";
        if (!method_exists($this, '_check')) {
            print get_class($this). " has no check yet\n";
        } else {
            return $this->_check();
        }

        return true;
    }
    
    function reserve() {
        return true;
    }

    function resetReserve() {
        Token::$reserved = array();
    }

    static function countTotalToken() {
        $result = Token::query("g.V.count()");
    	
    	return $result[0][0];
    }

    static function countLeftToken() {
        $result = Token::query("g.V.has('atom',null).except([g.v(0)]).hasNot('hidden', true).count()");
    	
    	return $result[0][0];
    }

    static public function query($query) {
    	$queryTemplate = $query;
    	$params = array('type' => 'IN');
    	try {
    	    $query = new \Everyman\Neo4j\Gremlin\Query(Token::$client, $queryTemplate, $params);
        	return $query->getResultSet();
    	} catch (\Exception $e) {
    	    $message = $e->getMessage();
    	    $message = preg_replace('#^.*\[message\](.*?)\[exception\].*#is', '\1', $message);
    	    print "Exception : ".$message."\n";
    	    
    	    print $queryTemplate."\n";
    	    die();
    	}
    	return $query->getResultSet();
    }
}

?>