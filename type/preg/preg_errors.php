<?php

class preg_error {

    //Human-understandable error message
    public $errormsg;
    //
    public $index_first;
    //
    public $index_last;
    
    protected function highlight_regex($regex, $indfirst, $indlast) {
        return substr($regex, 0, $indfirst) . '<b>' . substr($regex, $indfirst, $indlast-$indfirst+1) . '</b>' . substr($regex, $indlast + 1);
    }

     public function __construct($errormsg, $regex='', $index_first=-2, $index_last=-2) {
        $this->index_first = $index_first;
        $this->index_last = $index_last;
        if ($index_first != -2) {
            $this->errormsg = $this->highlight_regex($regex, $index_first, $index_last). '<br/>' . $errormsg;
        } else {
            $this->errormsg = $errormsg;
        }
     }
}

// A syntax error occured while parsing a regex
class preg_parsing_error extends preg_error {

    public function __construct($regex, $parsernode) {
        $this->index_first = $parsernode->firstindxs[0];
        $this->index_last = $parsernode->lastindxs[0];
        $this->errormsg = $this->highlight_regex($regex, $this->index_first, $this->index_last) . '<br/>' . $parsernode->error_string();
    }

}

// There's an unacceptable node in a regex
class preg_accepting_error extends preg_error {

    /*
     * Returns a string with first character converted to upper case.
     */
    public function uppercase_first_letter($str) {
        $textlib = textlib_get_instance();
        $firstchar = $textlib->strtoupper($textlib->substr($str, 0, 1));
        $rest = $textlib->substr($str, 1, $textlib->strlen($str));
        return $firstchar.$rest;
    }

    public function __construct($regex, $matchername, $nodename, $indexes) {
        $a = new stdClass;
        $a->nodename = $this->uppercase_first_letter($nodename);
        $a->indfirst = $indexes['start'];
        $a->indlast = $indexes['end'];
        $a->engine = get_string($matchername, 'qtype_preg');
        $this->index_first = $a->indfirst;
        $this->index_last = $a->indlast;
        $this->errormsg = $this->highlight_regex($regex, $this->index_first, $this->index_last) . '<br/>' . get_string('unsupported','qtype_preg',$a);
    }

}

// There's an unsupported modifier in a regex
class preg_modifier_error extends preg_error {

    public function __construct($matchername, $modifier) {
        $a = new stdClass;
        $a->modifier = $modifier;
        $a->classname = $matchername;
        $this->errormsg = get_string('unsupportedmodifier','qtype_preg',$a);
    }

}

// FA is too large
class preg_too_complex_error extends preg_error {

    public function __construct($regex, $matcher, $indexes = array('start' => -1, 'end' => -2)) {
        $a = new stdClass;
        if ($indexes['start'] == -1 && $indexes['end'] == -2) {
            $textlib = textlib_get_instance();
            $a->indfirst = 0;
            $a->indlast = $textlib->strlen($regex) - 1;
        } else {
            $a->indfirst = $indexes['start'];
            $a->indlast = $indexes['end'];
        }
        $a->engine = get_string($matcher->name(), 'qtype_preg');
        $this->index_first = $a->indfirst;
        $this->index_last = $a->indlast;
        $this->errormsg = $this->highlight_regex($regex, $this->index_first, $this->index_last) . '<br/>' . get_string('toolargefa','qtype_preg',$a);
    }

}