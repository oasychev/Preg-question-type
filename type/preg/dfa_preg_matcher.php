<?php //$Id: dfa_preg_matcher.php, v 0.1 beta 2010/08/08 23:47:35 dvkolesov Exp $

/**
 * Defines class dfa_preg_matcher
 *
 * @copyright &copy; 2010  Kolesov Dmitriy 
 * @author Kolesov Dmitriy, Volgograd State Technical University
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questions
 */

//fa - finite automate
//marked state, it's mean that the state is ready, all it's passages point to other states(marked and not marked), not marked state isn't ready, it's passages point to nothing.

require_once($CFG->dirroot . '/question/type/preg/preg_matcher.php');
require_once($CFG->dirroot . '/question/type/preg/dfa_preg_nodes.php');

class finite_automate_state {//finite automate state
    var $asserts;
    var $passages;//contain numbers of state which can go from this
    var $marked;//if marked then true else false.
    
    function name() {
        return 'finite_automate_state';
    }
}

class fptab {//member of follow's map table, use on merge time only
    public $number;
    public $inaccessible;
    //arrays, use member with identically indexes
    public $aindex;//index of symbol in assert's connection
    public $mindex;//index of symbol in main's connection
    //reference
    public $leaf;//contain leaf, which be on crossing of assert's and main's leaf
    public function __construct() {
        $this->inaccessible = true;
        $this->aindex = array();
        $this->mindex = array();
    }
}

class dfa_preg_matcher extends preg_matcher {

    


    var $connection;//array, $connection[0] for main regex, $connection[<assert number>] for asserts
    var $roots;//array,[0] main root, [<assert number>] assert's root
    var $finiteautomates;
    var $maxnum;
    var $built;
    var $result;
    var $picnum;//number of last picture
    protected $map;//map of symbol's following
    protected $maxstatecount;
    protected $maxpasscount;
    
    var $graphvizpath;//path to dot.exe of graphviz, used only for debugging
    
    public function name() {
        return 'dfa_preg_matcher';
    }


    /**
    *returns true for supported capabilities
    @param capability the capability in question
    @return bool is capanility supported
    */
    public function is_supporting($capability) {
        switch($capability) {
        case preg_matcher::PARTIAL_MATCHING :
        case preg_matcher::NEXT_CHARACTER :
        case preg_matcher::CHARACTERS_LEFT :
            return true;
            break;
        }
        return false;
    }
    
    protected function is_preg_node_acceptable($pregnode) {
        switch ($pregnode->name()) {
        case 'leaf_charset':
        case 'leaf_meta':
        case 'leaf_assert':
            return true;
            break;
        }
        return get_string($pregnode->name(), 'qtype_preg');
    }

    /**
    *function form node with concatenation, first operand old root of tree, second operant leaf with sign of end regex (it match with end of string)
    *@param index - number of tree for adding end's leaf.
    */
    function append_end($index) {
        /*
        if ($index==0) {
            $root =& $this->roots[0];
        } else {
            $root =& $this->roots[$index]->pregnode->operands[0];
        }
        */
        $root =& $this->roots[$index];
        $oldroot = $root;
        $root = new preg_node_concat;
        $root->operands[1] = new preg_leaf_meta;
        $root->operands[1]->subtype = preg_leaf_meta::SUBTYPE_ENDREG;
        $root = $this->from_preg_node($root);
        $root->pregnode->operands[0] = $oldroot;
    }
    
    /**
    *function build determined finite automate, fa saving in $this->finiteautomates[$index], in $this->finiteautomates[$index][0] start state.
    *@param index number of assert (0 for main regex) for which building fa
    */
    function buildfa($index=0) {
        if ($index==0) {
            $root = $this->roots[0];
        } else {
            $root = $this->roots[$index]->pregnode->operands[0];
        }
        $statecount = 0;
        $passcount = 0;
        $this->maxnum = 0;//no one leaf numerated, yet.
        $this->finiteautomates[$index][0] = new finite_automate_state;
        //create start state.
        foreach ($root->firstpos as $value) {
            $this->finiteautomates[$index][0]->passages[$value] = -2;
        }
        $this->finiteautomates[$index][0]->marked = false;//start state not marked, because not readey, yet
        //form the determined finite automate
        while ($this->not_marked_state($index) !== false) {
            //while has one or more not ready state.
            $currentstateindex = $this->not_marked_state($index);
            $this->finiteautomates[$index][$currentstateindex]->marked = true;//mark current state, because it will be ready on this step of loop
            //form not marked state for each passage of current state
            foreach ($this->finiteautomates[$index][$currentstateindex]->passages as $num => $passage) {
                $newstate = new finite_automate_state;
                $statecount++;
                $fpU = $this->followposU($num, $this->map[0], $this->finiteautomates[$index][$currentstateindex]->passages, $index);
                foreach ($fpU as $follow) {
                    if ($follow<dfa_preg_node_assert::ASSERT_MIN_NUM) {
                        //if number less then dfa_preg_node_assert::ASSERT_MIN_NUM constant than this is character class, to passages it.
                        $newstate->passages[$follow] = -2;
                        $passcount++;
                    }
                }
                if ($this->connection[$index][$num]->pregnode->type === preg_node::TYPE_LEAF_META && 
                    $this->connection[$index][$num]->pregnode->subtype === preg_leaf_meta::SUBTYPE_ENDREG) {
                    //if this passage point to end state
                    //end state is imagined and not match with real object, index -1 in array, which have zero and positive index only
                    $this->finiteautomates[$index][$currentstateindex]->passages[$num] = -1;
                } else {
                    //if this passage not point to end state
                    if ($this->state($newstate->passages, $index) === false && count($newstate->passages) != 0) {
                        //if fa hasn't other state matching with this and this state not empty
                        array_push($this->finiteautomates[$index], $newstate);//add it to fa's array
                        end($this->finiteautomates[$index]);
                        $this->finiteautomates[$index][$currentstateindex]->passages[$num] = key($this->finiteautomates[$index]);
                    } else {
                        //else do passage point to state, which has in fa already
                        $this->finiteautomates[$index][$currentstateindex]->passages[$num] = $this->state($newstate->passages, $index);
                    }
                }
                if (($passcount > $this->maxpasscount || $statecount > $this->maxstatecount) && $this->maxstatecount != 0 && $this->maxpasscount != 0) {
                    $this->errors[] = get_string('toolargefa', 'qtype_preg');
                    return;
                }
            }
        }
        /*
        foreach ($this->finiteautomates[$index] as $key=>$state) {
            $this->del_double($this->finiteautomates[$index][$key]->passages, $index);
        }
        foreach ($this->finiteautomates[$index] as $key=>$state) {
            $this->unite_parallel($this->finiteautomates[$index][$key]->passages, $index);
        }*/
    }
    /**
    *function compare regex and string, with using of finite automate builded of buildfa function
    *and determine match or not match string with regex, lenght of matching substring and character which can be on next position in string
    *@param string - string for compare with regex
    *@param assertnumber - number of assert with which string will compare, 0 for main regex
    *@param offset - index of character in string which must be beginning for match
    *@param endanchor - if endanchor == false than string can continue after end of matching, else string must end on end of matching
    *@return object with three property:
    *   1)index - index of last matching character (integer)
    *   2)full  - fullnes of matching (boolean)
    *   3)next  - next character (mixed, int(0) for end of string, else string with character which can be next)
 *   If matching is impossible, return bool(false)
    */
    function compare($string, $assertnumber, $offset = 0, $endanchor = true) {//if main regex then assertnumber is 0
        $index = 0;//char index in string, comparing begin of first char in string
        $length = 0;//count of character matched with current leaf
        $end = false;//current state is end state, not yet
        $full = true;//if string match with asserts
        $next = 0;// character can put on next position, 0 for full matching with regex string
        $maxindex = strlen($string);//string cannot match with regex after end, if mismatch with assert - index of last matching with assert character
        $currentstate = 0;//finite automate begin work at start state, zero index in array
        $substringmatch = new stdClass;
        $substringmatch->full = false;
        $substringmatch->index = -1;
        $acceptedcharcount = -1;
        $ismatch = false;
        $laststates = array();//array of states without changing index
        if (strpos($this->modifiers, 'i') === false) {
            $casesens=true;
        } else {
            $casesens=false;
        }
        do {
        /*check current character while: 1)checked substring match with regex
                                         2)current character isn't end of string
                                         3)finite automate not be in end state
        */
            $maybeend = false;
            $found = false;//current character no accepted to fa yet
            $afound = false;
            $akey = false;
            reset($this->finiteautomates[$assertnumber][$currentstate]->passages);
            ///////////////////////////////////////////////////////////////////////////////////////////////////////////
            //finding leaf with this character
            reset($this->finiteautomates[$assertnumber][$currentstate]->passages);
            $key = false;
            while (!$found && current($this->finiteautomates[$assertnumber][$currentstate]->passages) !== false) { //while not found and all passages not checked yet
                //current character is contain in character class
                $key = key($this->finiteautomates[$assertnumber][$currentstate]->passages);
                if ($key != dfa_preg_leaf_meta::ENDREG && $offset + $index <= strlen($string)) {
                    $found = $this->connection[$assertnumber][$key]->pregnode->match($string, $offset + $index, &$length, $casesens);
                }
                if ($found && $this->connection[$assertnumber][$key]->pregnode->type == preg_node::TYPE_LEAF_ASSERT) {
                    $afound = true;
                    $akey = $key;
                    $found  = false;
                }
                if (!$found) {
                    next($this->finiteautomates[$assertnumber][$currentstate]->passages);
                }
            }
            if (!$found && $afound) {
                $found = true;
                $key = $akey;
            }
            if ($found) {
                $foundkey = $key;
                $ismatch = true;
            }
            if (array_key_exists(dfa_preg_leaf_meta::ENDREG, $this->finiteautomates[$assertnumber][$currentstate]->passages)) {
            //if current character is end of string and fa can go to end state.
                if ($offset + $index == strlen($string)) { //must be end   
                    $found = true;
                    $foundkey = dfa_preg_leaf_meta::ENDREG;
                    $length = 0;
                } elseif(count($this->finiteautomates[$assertnumber][$currentstate]->passages) == 1) {//must be end
                    //$foundkey = dfa_preg_leaf_meta::ENDREG;
                    $length = 0;
                }
                $maybeend = true;//may be end.
                $substringmatch->full = true;
                $substringmatch->index = $index;
            }
            $index += $length;
            if ($found && $foundkey != dfa_preg_leaf_meta::ENDREG) {
                $acceptedcharcount += $length;
            }
            //form results of check this character
            if ($found) { //if finite automate did accept this character
                $correct = true;
                if ($foundkey != dfa_preg_leaf_meta::ENDREG) {// if finite automate go to not end state
                    if ($length == 0) {
                        foreach ($laststates as $state) {
                            if ($state == $currentstate) {
                                return false;
                            }
                        }
                        $laststates[] = $currentstate;
                    } else {
                        $laststates = array();
                    }
                    $currentstate = $this->finiteautomates[$assertnumber][$currentstate]->passages[$key];
                    $end = false;
                } else { 
                    $end = true;
                }
            } else {
                $correct = false;
            }
        } while($correct && !$end && $offset + $index <= strlen($string));
        //form result comparing string with regex
        $result = new stdClass;
        $result->offset = $offset;
        if ($full) {//if asserts not give border to lenght of matching substring
            $result->index = $acceptedcharcount;
            $assertrequirenext = false;
        } else {
            $result->index = $maxindex;
            $assertrequirenext = true;
        }
        if (strlen($string) == $result->index + 1 && $end && $full && $correct || $maybeend && !$endanchor && $full) {//if all string match with regex.
            $result->full = true;
        } elseif ($substringmatch->full && !$endanchor && $full) {
            $result->full = true;
            $result->index = $substringmatch->index -1;
        } else {
            $result->full = false;
        }
        if (($result->full || $maybeend || $end) && !$assertrequirenext) {//if string must be end on end of matching substring.
            $result->next = 0;
            $result->left = 0;
        //determine next character, which will be correct and increment lenght of matching substring.
        } elseif (!$assertrequirenext) {//if assert not border next character //$full && $offset + $index-1 < $maxindex && 
            $wres = $this->wave($currentstate, $assertnumber);
            $key = $wres->nextkey;
            $result->left = $wres->left;
            $result->next = $this->connection[$assertnumber][$key]->pregnode->next_character($string, $result->index);
        } else {
            $result->next = $next;
            $wres = $this->wave($currentstate, $assertnumber);
            $result->left = $wres->left;
        }
        $result->ismatch = $ismatch;
        return $result;
    }
    
    /**
    *Function search for shortest way from current state to end state
    @param current - number of current state dfa
    @param assertnum - number of dfa for which do search
    @return number of state, which is first step on shortest way to end state and count of left character, as class
    */
    function wave($current, $assertnum) {
        //form start state of waves: start chars, current states of dfa and states of next step
        $i = 0;
        $left = 1;
        foreach ($this->finiteautomates[$assertnum][$current]->passages as $key => $passage) {
            $endafterassert = false;
            if ($this->connection[$assertnum][$key]->pregnode->type == preg_node::TYPE_LEAF_ASSERT) {
                foreach ($this->finiteautomates[$assertnum][$passage]->passages as $secondpass) {
                    if ($secondpass == -1) {
                        $endafterassert = true;
                    }
                }
            }
            if ($passage == -1 || $endafterassert) {
                $res = new stdClass;
                $res->nextkey = $key;
                $res->left = 0;
                return $res;
            }
            $front[$i] = new stdClass;
            $front[$i]->charnum = $key;
            $front[$i]->currentstep[] = $passage;
            $front[$i]->assertinpath = 0;
            $i++;
        }
        $found = false;
        $counter = 0;
        while (!$found) {//while not found way to end state
            foreach ($front as $i => $curr) {//for each start char and it's subfront
                if ($counter > 10000) {
                //TODO set wave error flag!
                    $res = new stdClass;
                    $res->nextkey = 1;
                    $res->left = 0;
                    return $res;
                    return new stdClass;
                } else {
                    $counter++;
                }
                foreach ($curr->currentstep as $step) {//for all state if current subfront
                    foreach ($this->finiteautomates[$assertnum][$step]->passages as $passkey => $passage) {//for all passage in this state
                        if ($passage != $step) {//if passage not to self
                            $endafterassert = false;
                            if ($this->connection[$assertnum][$passkey]->pregnode->type == preg_node::TYPE_LEAF_ASSERT) {
                                foreach ($this->finiteautomates[$assertnum][$passage]->passages as $secondpass) {
                                    if ($secondpass == -1) {
                                        $endafterassert = true;
                                    }
                                }
                            }
                            if ($passage == -1 || $endafterassert) {//if passage to end state
                                $found = true;
                                $result = new stdClass;
                                $result->left = $left - $front[$i]->assertinpath;
                                $result->nextkey = $front[$i]->charnum;
                                return $result;
                            } else if ($this->connection[$assertnum][$passkey]->pregnode->type == preg_node::TYPE_LEAF_ASSERT) {
                                foreach ($this->finiteautomates[$assertnum][$passage]->passages as $secondpass) {
                                    $front[$i]->nextstep[] = $secondpass;
                                }
                            } else {
                                $front[$i]->nextstep[] = $passage;
                            }
                        }
                    }
                }
                $front[$i]->currentstep = $front[$i]->nextstep;
                $front[$i]->nextstep = array();
            }
            $left++;
        }
    }
    
    /**
    *function append array2 to array1, non unique values not add
    *@param arr1 - first array
    *@param arr2 - second array, which will appended to arr1
    *@param $index index of dfa for which do verify sybol unique
    */
    static protected function push_unique(&$arr1, $arr2) {
        if (!is_array($arr1)) {
            $arr1 = array();
        }
        foreach ($arr2 as $value) {
            if (!in_array($value, $arr1)) {
                $arr1[] = $value;
            }
        }
    }
    /**
    *function delete repeat passages frm state of dfa
    *@param $array array of passages of state of dfa
    *@param $index index of dfa for which do verify sybol unique
    */
    protected function del_double(&$array, $index) {
        foreach ($array as $leaf=>$Passage) {
            foreach ($array as $member=>$passage) {//variable [Pp]assage not use, need only leaf and member
                $typeequ = $this->connection[$index][$member]->pregnode->type==$this->connection[$index][$leaf]->pregnode->type;
                $subtypeequ = $this->connection[$index][$member]->pregnode->subtype==$this->connection[$index][$leaf]->pregnode->subtype;
                $directionequ = $this->connection[$index][$member]->pregnode->negative==$this->connection[$index][$leaf]->pregnode->negative;
                if ($this->connection[$index][$member]->pregnode->type==preg_node::TYPE_LEAF_CHARSET && $this->connection[$index][$leaf]->pregnode->type==preg_node::TYPE_LEAF_CHARSET) {
                    $charsetequ = $this->connection[$index][$member]->pregnode->charset==$this->connection[$index][$leaf]->pregnode->charset;
                } else {
                    $charsetequ = true;
                }
                if ($leaf!=$member && $typeequ && $subtypeequ && $directionequ && $charsetequ) {
                    unset($array[$leaf]);
                }
            }
        }
    }
   /**
    *function unite parallel passages in dfa state
    *@param $array array of passages of state of dfa
    *@param $index index of dfa for which do verify sybol unique
    */
    protected function unite_parallel(&$array, $index) {
        foreach ($array as $key1=>$passage1) {
            foreach ($array as $key2=>$passage2) {
               if($passage1==$passage2 && $key1!=$key2) {
                    $newleaf = preg_leaf_combo::get_unite($this->connection[$index][$key1]->pregnode, $this->connection[$index][$key2]->pregnode);
                    $newleaf = $this->from_preg_node($newleaf);
                    $this->connection[$index][++$this->maxnum] = $newleaf;
                    $array[$this->maxnum] = $passage1;
                    unset($array[$key1]);
                    unset($array[$key2]);
                    break;
               }
            }
        }
    }
    /**
    *function search not marked state if finite automate, while one not marked state will be found, searching will be stopped.
    *@param index - number of automate
    *@return link to not marked state
    */
    function not_marked_state($index) {
        $notmarkedstate = false;
        $size = count($this->finiteautomates[$index]);
        for ($i = 0; $i < $size && $notmarkedstate === false; $i++) {
            if (!$this->finiteautomates[$index][$i]->marked) {
                $notmarkedstate = $i;
            }
        }
        return $notmarkedstate;
    }
    /**
    *function check: string1 include string2, or not include, without stock of sequence character
    *@param string1 - string which may contain string2 
    *@param string2 - string which may be included in string1
    *@return true if string1 include string2
    */
    static function is_include_characters($string1, $string2) {
        $result = true;
        $size = strlen($string2);
        for ($i = 0; $i < $size && $result; $i++) {
            if (strpos($string1, $string2[$i]) === false) {
                $result = false;
            }
        }
        return $result;
    }
    /**
    *function concatenate list of follow character for this number of character and other number match with character which mean this number
    *@param number - for this number will concatenate list of follow chars
    *@param fpmap - map of following characters
    *@param passages - passges of current state fa
    *@param index - number of assert (number of connection map if $this->connection array)
    *@return concatenated list of follow chars
    */
    function followposU($number, $fpmap, $passages, $index) {
        if ($this->connection[$index][$number]->pregnode->type == preg_node::TYPE_LEAF_META && 
            $this->connection[$index][$number]->pregnode->subtype == preg_leaf_meta::SUBTYPE_ENDREG) {
            $res = array();
            return $res;
        }
        $equnum = array();
        if ($this->connection[$index][$number]->pregnode->type == preg_node::TYPE_LEAF_CHARSET) {//if this leaf is character class
            $str1 = $this->connection[$index][$number]->pregnode->charset;//for this charclass will found equivalent numbers
            foreach ($this->connection[$index] as $num => $cc) {//forming vector of equivalent numbers
                if ($cc->pregnode->type == preg_node::TYPE_LEAF_CHARSET) {
                    $str2 = $cc->pregnode->charset;
                    $equdirection = $cc->pregnode->negative === $this->connection[$index][$number]->pregnode->negative; 
                    if (dfa_preg_matcher::is_include_characters($str1, $str2) && array_key_exists($num, $passages) && $equdirection) {//if charclass 1 and 2 equivalenta and number exist in passages
                        array_push($equnum, $num);
                    }
                }
            }
        } elseif ($this->connection[$index][$number]->pregnode->type == preg_node::TYPE_LEAF_META) {//if this leaf is metacharacter
            foreach ($this->connection[$index] as $num => $cc) {
                if ($cc->pregnode->type == preg_node::TYPE_LEAF_META && $cc->pregnode->subtype == $this->connection[$index][$number]->pregnode->subtype) {
                    array_push($equnum, $num);
                }
            }
        } elseif ($this->connection[$index][$number]->pregnode->type == preg_node::TYPE_LEAF_ASSERT) {//if this leaf is metacharacter
            foreach ($this->connection[$index] as $num => $cc) {
                if ($cc->pregnode->type == preg_node::TYPE_LEAF_ASSERT && $cc->pregnode->subtype == $this->connection[$index][$number]->pregnode->subtype) {
                    array_push($equnum, $num);
                }
            }
        } elseif ($this->connection[$index][$number]->pregnode->type == preg_node::TYPE_LEAF_COMBO) {
            foreach ($this->connection[$index] as $num => $cc) {
                $cmpop = true;
                if ($cc->pregnode->type == preg_node::TYPE_LEAF_COMBO) {
                    if ($cc->pregnode->childs[0]->type != $this->connection[$index][$number]->pregnode->childs[1]->type ||
                        $cc->pregnode->childs[0]->subtype != $this->connection[$index][$number]->pregnode->childs[1]->subtype ||
                        $cc->pregnode->childs[0]->negative != $this->connection[$index][$number]->pregnode->childs[1]->negative ||
                        $cc->pregnode->childs[0]->type == preg_node::TYPE_LEAF_CHARSET && $cc->pregnode->childs[0]->charset != $this->connection[$index][$number]->pregnode->childs[1]->charset) {
                        $cmpop = false;
                    }
                }
                if ($cc->pregnode->type == preg_node::TYPE_LEAF_COMBO && $cc->pregnode->subtype == $this->connection[$index][$number]->pregnode->subtype) {
                    array_push($equnum, $num);
                }
            }
        }
        $followU = array();
        foreach ($equnum as $num) {//forming map of following numbers
            dfa_preg_matcher::push_unique($followU, $fpmap[$num]);
        }
        return $followU;
    }
    /**
    *function search state in fa
    *@param state - state which be finding
    *@param index - assert number (index in $this->finiteautomates array on which will be search)
    *@return false if state not found, else number of found state
    */
    function state($state, $index) {
        $passcount = count($state);
        $result = false;
        $fas = count($this->finiteautomates[$index]);
        for ($i=0; $i < $fas && $result === false; $i++) {
            $flag = true;
            if ($passcount != count($this->finiteautomates[$index][$i]->passages)) {
                $flag = false;
            }
            reset($state);
            reset($this->finiteautomates[$index][$i]->passages);
            for ($j=0; $flag && $j < $passcount; $j++) {
                if (key($state) != key($this->finiteautomates[$index][$i]->passages)) {
                    $flag = false;
                }
                next($state);
                next($this->finiteautomates[$index][$i]->passages);
            }
            if ($flag) {
                $result =$i;
            }
        }
        return $result;
    }
    /**
    *get regex and build finite automates
    @param regex - regular expirience for which will be build finite automate
    @param modifiers - modifiers of regular expression
    */
    function __construct($regex = null, $modifiers = null) {
        global $CFG;
        $this->picnum=0;
        if (isset($CFG->qtype_preg_graphvizpath)) {
            $this->graphvizpath = $CFG->qtype_preg_graphvizpath;//in few unit tests dfa_preg_matcher objects create without regex,
                                                  //but dfa will be build later and need for drawing dfa may be
        } else {
            $this->graphvizpath = 1;
        }
        if (isset($CFG->qtype_preg_dfastatecount)) {
            $this->maxstatecount = $CFG->qtype_preg_dfastatecount;
        } else {
            $this->maxstatecount = 0;
        }
        if (isset($CFG->qtype_preg_dfapasscount)) {
            $this->maxpasscount = $CFG->qtype_preg_dfapasscount;
        } else {
            $this->maxpasscount = 0;
        }
        if (!isset($regex)) {//not build tree and dfa, if regex not given
            return;
        }
        parent::__construct($regex, $modifiers);
        $this->roots[0] = $this->dst_root;//place dst root in engine specific place
        //building finite automates
        if ($this->is_error_exists()) {
            return;
        }
        $this->append_end(0);
        //form the map of following
        $this->roots[0]->number($this->connection[0], $this->maxnum);
        $this->roots[0]->nullable();
        $this->roots[0]->firstpos();
        $this->roots[0]->lastpos();
        $this->roots[0]->followpos($this->map[0]);
        $this->split_leafs(0);
        $this->roots[0]->find_asserts($this->roots);
        foreach ($this->roots as $key => $value) {
            if ($key!=0) {
                //TODO: use subtype of assert, when few subtype will be supported.
                $this->roots[$key] = $this->roots[$key]->pregnode->operands[0];
                $this->append_end($key);
                $this->roots[$key]->number($this->connection[$key], $this->maxnum);
                $this->roots[$key]->nullable();
                $this->roots[$key]->firstpos();
                $this->roots[$key]->lastpos();
                $this->roots[$key]->followpos($this->map[$key]);
    $this->split_leafs($key);
                $this->merge_fp_maps($key);
            }
        }
        $this->buildfa(); // TODO: check for dfa size!
        $this->built = true;
        return;
    }
    /**
    * Function merge simple assert in map of character following
    * @param $num number of map for merging simple asserts
    */
    protected function split_leafs($num) {
        foreach ($this->map[$num] as $prev => $arrnext) {
            foreach ($arrnext as $i => $first) {
                foreach ($arrnext as $j => $second) {
                    if (self::is_leafs_part_match($this->connection[$num][$first], $this->connection[$num][$second]) && isset($this->map[$num][$first]) && isset($this->map[$num][$second])) {
                        $arrres = $this->get_unequ_leafs($this->connection[$num][$first], $this->connection[$num][$second]);//get unique or equivalent leaf, but not partial equivalent with diff
                        $firstnexts = $this->map[$num][$first];
                        $secondnexts = $this->map[$num][$second];
                        unset ($this->map[$num][$i]);
                        unset ($this->map[$num][$j]);
                        foreach ($arrres as $key => $newleaf) {
                            $arrresnumbers[$key] = $this->save_new_leaf($num, $newleaf);
                        }
                        $this->map[$num][$arrresnumbers[0]] = $firstnexts;
                        $this->map[$num][$arrresnumbers[1]] = $firstnexts;
                        $this->map[$num][$arrresnumbers[2]] = $secondnexts;
                        $this->map[$num][$arrresnumbers[3]] = $secondnexts;
                        $this->replace_num_in_map($num, $first, $arrresnumbers[0], $arrresnumbers[1]);
                        $this->replace_num_in_map($num, $second, $arrresnumbers[2], $arrresnumbers[3]);
                    }
                }
            }
        }
        foreach ($this->map[$num] as $prev => $arrnext) {
            if (!is_array($arrnext))
                $this->map[$num][$prev] = array();
            }
    }
    /**
    * Function verify is two leaf partial match
    * partial match mean, that not equivalent, but can match with one character
    * @param $first first leaf
    * @param $second second leaf
    * @return is partial match
    */
    static protected function is_leafs_part_match($first, $second) {
        if ($first->pregnode->type == preg_node::TYPE_LEAF_ASSERT || $second->pregnode->type == preg_node::TYPE_LEAF_ASSERT ||
            $first->pregnode->type == preg_node::TYPE_NODE_ASSERT || $second->pregnode->type == preg_node::TYPE_NODE_ASSERT) {
            return false;
        } elseif ($first->pregnode->type == preg_node::TYPE_LEAF_META && $second->pregnode->type == preg_node::TYPE_LEAF_META) {
            return $first->pregnode->subtype != $second->pregnode->subtype;
        } elseif ($first->pregnode->type == preg_node::TYPE_LEAF_CHARSET && $second->pregnode->type == preg_node::TYPE_LEAF_CHARSET) {
            if ($first->pregnode->negative && $second->pregnode->negative) {
                return $first->pregnode->charset != $second->pregnode->charset;
            } elseif (!$first->pregnode->negative && !$second->pregnode->negative) {
                $flag = false;
                for ($i=0; $i<strlen($first->pregnode->charset); $i++) {
                    for ($j=0; $j<strlen($second->pregnode->charset); $j++) {
                        if ($first->pregnode->charset[$i] == $second->pregnode->charset[$j]) {
                            $flag = true;
                        }
                    }
                }
                return $flag && $first->pregnode->charset != $second->pregnode->charset;
            } else {
                return false;
            }
        } else {//meta and charset
            if ($second->pregnode->type == preg_node::TYPE_LEAF_META) {
                $tmp = $first;
                $first = $second;
                $second = $tmp;
            }
            //first is meta, second is charset
            if ($first->pregnode->subtype == preg_leaf_meta::SUBTYPE_ENDREG) {
                return false;//ENDREG not partial match with any other operand
            }
            for ($j=0; $j<strlen($second->pregnode->charset); $j++) {
                if ($first->pregnode->match($second->pregnode->charset, $j, $trash, false)) {
                    return true;// \w \W or . can't be equivalent to enumerable charset
                }
            }
            return $second->pregnode->negative;
        }
    }
    /**
    * Function split two leaf to four UNique and EQUivalen leafs
    * @param $first first leaf
    * @param $second second leaf
    * @return unique and equivalent leafs array
    */
    protected function get_unequ_leafs($first, $second) {
        if ($first->pregnode->type == preg_node::TYPE_LEAF_META && $second->pregnode->type == preg_node::TYPE_LEAF_META) {
        //two meta leafs
            $result[0] = $this->cross_meta_leafs(clone $first, clone $second, false, true);
            $result[1] = $this->cross_meta_leafs(clone $first, clone $second, false, false);
            $result[2] = $this->cross_meta_leafs(clone $first, clone $second, false, false);
            $result[3] = $this->cross_meta_leafs(clone $first, clone $second, true, false);
        } elseif ($first->pregnode->type == preg_node::TYPE_LEAF_CHARSET && $second->pregnode->type == preg_node::TYPE_LEAF_CHARSET) {
        //two charset leafs
            $result[0] = $this->cross_charsets(clone $first, clone $second, false, true);
            $result[1] = $this->cross_charsets(clone $first, clone $second, false, false);
            $result[2] = $this->cross_charsets(clone $first, clone $second, false, false);
            $result[3] = $this->cross_charsets(clone $first, clone $second, true, false);
        } else {
        //meta and charset
            $fixindex1 = 0;
            $fixindex2 = 2;
            if ($second->pregnode->type == preg_node::TYPE_LEAF_META) {
                $tmp = $first;
                $first = $second;
                $second = $tmp;
                $fixindex1 = 2;
                $fixindex2 = 0;
            }
            //first is meta, second is charset
            $result[$fixindex1] = $this->cross_meta_charset(clone $first, clone $second, false, true);
            $result[$fixindex1+1] = $this->cross_meta_charset(clone $first, clone $second, false, false);
            $result[$fixindex2] = $this->cross_meta_charset(clone $first, clone $second, false, false);
            $result[$fixindex2+1] = $this->cross_meta_charset(clone $first, clone $second, true, false);
        }
        if ($result[0] === false) {
            $result[0] = $result[1];
        }
        if ($result[1] === false) {
            $result[1] = $result[0];
        }
        if ($result[2] === false) {
            $result[2] = $result[3];
        }
        if ($result[3] === false) {
            $result[3] = $result[2];
        }
        return $result;
    }
    protected function cross_charsets($leaf1, $leaf2, $invert1, $invert2) {
        $result = new preg_leaf_charset;
        if ($invert1) {
            $leaf1->pregnode->negative = !$leaf1->pregnode->negative;
        }
        if ($invert2) {
            $leaf2->pregnode->negative = !$leaf2->pregnode->negative;
        }
        $str = '';
        if (!$leaf1->pregnode->negative && !$leaf2->pregnode->negative) {//++
            for ($i=0; $i<strlen($leaf1->pregnode->charset); $i++) {
                if (strchr($leaf2->pregnode->charset, $leaf1->pregnode->charset[$i]) !== false) {
                    $str .= $leaf1->pregnode->charset[$i];
                }
            }
        } else if (!$leaf1->pregnode->negative && $leaf2->pregnode->negative) {//+-
            for ($i=0; $i<strlen($leaf1->pregnode->charset); $i++) {
                if (strchr($leaf2->pregnode->charset, $leaf1->pregnode->charset[$i]) === false) {
                    $str .= $leaf1->pregnode->charset[$i];
                }
            }
        } else if ($leaf1->pregnode->negative && !$leaf2->pregnode->negative) {//-+
            for ($i=0; $i<strlen($leaf2->pregnode->charset); $i++) {
                if (strchr($leaf1->pregnode->charset, $leaf2->pregnode->charset[$i]) === false) {
                    $str .= $leaf2->pregnode->charset[$i];
                }
            }
        } else {//--
            $str = $leaf2->pregnode->charset . $leaf1->pregnode->charset;
            $result->negative = true;
        }
        if ($invert1) {
            $leaf1->pregnode->negative = !$leaf1->pregnode->negative;
        }
        if ($invert2) {
            $leaf2->pregnode->negative = !$leaf2->pregnode->negative;
        }
        if ($str=='') {
            return false;
        }
        $result->charset = $str;
        $result = $this->from_preg_node($result);
        return $result;
    }
    protected function cross_meta_leafs($leaf1, $leaf2, $invert1, $invert2) {
        if ($invert1) {
            $leaf1->pregnode->negative = !$leaf1->pregnode->negative;
        }
        if ($invert2) {
            $leaf2->pregnode->negative = !$leaf2->pregnode->negative;
        }
        //one of leaf is \w, other is metadot
        $flag = false;
        if ($leaf1->pregnode->subtype == preg_leaf_meta::SUBTYPE_WORD_CHAR) {
            $tmp = $leaf1;
            $leaf1 = $leaf2;
            $second = $tmp;
            $flag = true;
        }
        //now first is meta dot, second is wordchar
        if ($leaf1->pregnode->negative) {
            $result =  false;//impossible to match
        } else {
            $result = new preg_leaf_meta;
            $result->negative = $leaf2->pregnode->negative;
            $result->subtype = preg_leaf_meta::SUBTYPE_WORD_CHAR;
            $result = $this->from_preg_node($result);
        }
        if ($flag) {
            $tmp = $leaf1;
            $leaf1 = $leaf2;
            $second = $tmp;
        }
        if ($invert1) {
            $leaf1->pregnode->negative = !$leaf1->pregnode->negative;
        }
        if ($invert2) {
            $leaf2->pregnode->negative = !$leaf2->pregnode->negative;
        }
        return $result;
    }
    protected function cross_meta_charset($leaf1, $leaf2, $invert1, $invert2) {
        if ($invert1) {
            $leaf1->pregnode->negative = !$leaf1->pregnode->negative;
        }
        if ($invert2) {
            $leaf2->pregnode->negative = !$leaf2->pregnode->negative;
        }
        $flag = false;
        if ($leaf1->pregnode->type == preg_node::TYPE_LEAF_CHARSET) {
            $tmp = $leaf1;
            $leaf1 = $leaf2;
            $second = $tmp;
            $flag = true;
        }
        //now leaf1 is meta and leaf2 is charset
        if ($leaf1->pregnode->subtype == preg_leaf_meta::SUBTYPE_DOT) {
            if ($leaf1->pregnode->negative) {
                $result =  false;//impossible to match
            } else {
                $result = new preg_leaf_charset;
                $result->negative = $leaf2->pregnode->negative;
                $result->charset = $leaf2->pregnode->charset;
                $result = $this->from_preg_node($result);
            }
        } else {//leaf1 is word char
            if ($leaf2->pregnode->negative) {
                $result = preg_leaf_combo::get_cross($leaf1->pregnode, $leaf2->pregnode);
                $result = $this->from_preg_node($result);
            } else {
                $result = new preg_leaf_charset;
                $result->negative = $leaf2->pregnode->negative;
                $result->charset = '';
                for ($i=0; $i < strlen($leaf2->pregnode->charset); $i++) {
                    if ($leaf1->pregnode->match($leaf2->pregnode->charset, $i, $l, false)) {
                        $result->charset .= $leaf2->pregnode->charset[$i];
                    }
                }
                $result = $this->from_preg_node($result);
                if ($result->pregnode->charset == '') {
                    return false;
                }
            }
        }
        if ($flag) {
            $tmp = $leaf1;
            $leaf1 = $leaf2;
            $second = $tmp;
        }
        if ($invert1) {
            $leaf1->pregnode->negative = !$leaf1->pregnode->negative;
        }
        if ($invert2) {
            $leaf2->pregnode->negative = !$leaf2->pregnode->negative;
        }
        return $result;
    }
    protected function save_new_leaf($num, $leaf) {
        $index = 0;
        foreach ($this->connection[$num] as $key => $val) {
            if ($key > $index && $val->pregnode->type != preg_node::TYPE_NODE_ASSERT && $key < 186759556) {
                $index = $key;
            }
        }
        $this->connection[$num][++$index] = $leaf;
        return $index;
    }
    protected function replace_num_in_map($num, $old, $new1, $new2) {
        foreach ($this->map[$num] as $cur => $arrnext) {
            if (is_array($arrnext)) {
                foreach ($arrnext as $i => $leafnum) {
                    if ($leafnum == $old) {
                        $this->map[$num][$cur][$i] = $new1;
                        $this->map[$num][$cur][] = $new2;
                        break;
                    }
                }
            }
        }
        foreach ($this->roots[$num]->firstpos as $key => $val) {
            if ($val == $old) {
                $this->roots[$num]->firstpos[$key] = $new1;
                $this->roots[$num]->firstpos[] = $new2;
            }
        }
    }
    /**
    * Function merge map of symbol's following, first operand $this->map[0], second operand $this->map[$num]
    * and put result in $this->map[0]
    * @param $num number of other map to merging
    */
    protected function merge_fp_maps($num) {
        //create table of crossing
        $table = array();
        foreach ($this->map[$num] as $akey=>$aleaf) {
            foreach ($this->map[0] as $mkey=>$mleaf) {
                if ($akey!=dfa_preg_leaf_meta::ENDREG && $mkey!=dfa_preg_leaf_meta::ENDREG && $mkey!=$num) {
                    $newleaf = preg_leaf_combo::get_cross($this->connection[0][$mkey]->pregnode, $this->connection[$num][$akey]->pregnode);
                    $table[$akey][$mkey] = new fptab;
                    $table[$akey][$mkey]->leaf = $this->from_preg_node($newleaf);
                }
            }
        }
        foreach ($this->map[$num] as $akey=>$aleaf) {
            $table[$akey][dfa_preg_leaf_meta::ENDREG] = false;
        }
        foreach ($this->map[0] as $mkey=>$mleaf) {
            if ($mkey!=$num) {
                $table[dfa_preg_leaf_meta::ENDREG][$mkey] = new fptab;
                $table[dfa_preg_leaf_meta::ENDREG][$mkey]->leaf = $this->connection[0][$mkey];
            }
        }
        $newleaf = new preg_leaf_meta;
        $newleaf->subtype = preg_leaf_meta::SUBTYPE_ENDREG;
        $newleaf = $this->from_preg_node($newleaf);
        $table[dfa_preg_leaf_meta::ENDREG][dfa_preg_leaf_meta::ENDREG] = new fptab;
        $table[dfa_preg_leaf_meta::ENDREG][dfa_preg_leaf_meta::ENDREG]->leaf = $newleaf;
        //forming ?passages?
        foreach ($table as $akey=>$str) {
            if ($akey!=dfa_preg_leaf_meta::ENDREG) {
                foreach ($str as $mkey=>$member) {
                    if ($mkey!=dfa_preg_leaf_meta::ENDREG) {
                        foreach ($this->map[$num][$akey] as $afollow) {
                            foreach ($this->map[0][$mkey] as $mfollow) {
                                if (($afollow==dfa_preg_leaf_meta::ENDREG || $mfollow!=dfa_preg_leaf_meta::ENDREG) &&
                                    ($akey!=dfa_preg_leaf_meta::ENDREG || $mkey!=dfa_preg_leaf_meta::ENDREG)) {
                                    if ($mfollow!=$num) {
                                        $table[$akey][$mkey]->aindex[] = $afollow;
                                        $table[$akey][$mkey]->mindex[] = $mfollow;
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                foreach ($str as $mkey=>$member) {
                    if ($mkey!=dfa_preg_leaf_meta::ENDREG) {
                        foreach ($this->map[0][$mkey] as $follow) {
                            if ($follow!=$num) {
                                $table[$akey][$mkey]->aindex[] = $akey;//meta endreg
                                $table[$akey][$mkey]->mindex[] = $follow;//copying passage
                            } else {
                                foreach ($this->roots[$num]->firstpos as $afirpos) {
                                    foreach ($this->map[0][$num] as $mainnext) {
                                        if ($mainnext!=dfa_preg_leaf_meta::ENDREG || $afirpos==dfa_preg_leaf_meta::ENDREG) {
                                            $table[$akey][$mkey]->aindex[] = $afirpos;
                                            $table[$akey][$mkey]->mindex[] = $mainnext;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        //wave for deleting inaccessible postion
            //form start front
        $firsta = $afront = array();
        $firstm = $mfront = array();
        foreach ($this->roots[0]->firstpos as $firstpos) {
            if ($firstpos!=$num) {
                $firsta[] = $afront[] = dfa_preg_leaf_meta::ENDREG;
                $firstm[] = $mfront[] = $firstpos;
                $table[dfa_preg_leaf_meta::ENDREG][$firstpos]->inaccessible = false;
            } else {
                foreach ($this->roots[$num]->firstpos as $afirpos) {
                    foreach ($this->map[0][$num] as $mainnext) {
                        if ($mainnext!=dfa_preg_leaf_meta::ENDREG || $afirpos==dfa_preg_leaf_meta::ENDREG) {
                            $firsta[] = $afront[] = $afirpos;
                            $firstm[] = $mfront[] = $mainnext;
                            $table[$afirpos][$mainnext]->inaccessible = false;
                        }
                    }
                }
            }
        }
            //wave
        do {
            $newafront = array();
            $newmfront = array();
            $newfirstposfortree = array();
            foreach ($afront as $key=>$val) {
                foreach ($table[$val][$mfront[$key]]->aindex as $newkey=>$apass) {
                    if ($table[$apass][$table[$val][$mfront[$key]]->mindex[$newkey]]->inaccessible) {
                        $newafront[] = $apass;
                        $newmfront[] = $table[$val][$mfront[$key]]->mindex[$newkey];
                        $table[$apass][$table[$val][$mfront[$key]]->mindex[$newkey]]->inaccessible = false;
                    }
                }
            }
            $afront = $newafront;
            $mfront = $newmfront;
        } while (count($afront)!=0);       
            //deleting
        foreach ($table as $akey=>$str) {
            foreach ($str as $mkey=>$member) {
                if ($member!==false && $member->inaccessible) {
                    $table[$akey][$mkey] = false;
                }
            }
        }
        //formin fpmap from table
            //numerating
        $maxnum = 0;
        $this->connection[0] = array();
        foreach ($table as $akey=>$str) {
            foreach ($str as $mkey=>$member) {
                if ($member!==false) {
                    if ($akey==dfa_preg_leaf_meta::ENDREG && $mkey==dfa_preg_leaf_meta::ENDREG) {
                        $table[$akey][$mkey]->number = dfa_preg_leaf_meta::ENDREG;
                        $this->connection[0][dfa_preg_leaf_meta::ENDREG] = $table[$akey][$mkey]->leaf;
                    } else {
                        $table[$akey][$mkey]->number = ++$maxnum;
                        $this->connection[0][$maxnum] = $table[$akey][$mkey]->leaf;
                    }
                    //forming firstpos
                    if (in_array($akey, $firsta) && in_array($mkey, $firstm) && !($table[$akey][$mkey]->leaf->pregnode->type==preg_node::TYPE_LEAF_CHARSET && $table[$akey][$mkey]->leaf->pregnode->charset == '')) {
                        $newfirstposfortree[] = $table[$akey][$mkey]->number;
                    }
                }
            }
        }
        $this->roots[0]->firstpos = $newfirstposfortree;
            //forming fpmap
        $this->map[0] = array();
        foreach ($table as $akey=>$str) {
            foreach ($str as $mkey=>$member) {
                if ($member!==false) {
                    foreach ($member->aindex as $key=>$aind) {
                        $this->map[0][$member->number][] = $table[$aind][$member->mindex[$key]]->number;
                    }
                }
            }
        }
        //delete empty symbols
        foreach ($this->map[0] as $key=>$val) {
            if ($this->connection[0][$key]->pregnode->type==preg_node::TYPE_LEAF_CHARSET && $this->connection[0][$key]->pregnode->charset == '') {
                unset($this->map[0][$key]);
                unset($this->connection[0][$key]);
            }
        }
        foreach ($this->map[0] as $key=>$val) {
            foreach ($val as $key2=>$val2) {
                if (!array_key_exists($val2, $this->connection[0])) {
                    unset ($this->map[0][$key][$key2]);
                }
            }
        }
        
    }
    /**
    * DFA node factory
    * @param pregnode preg_node child class instance
    * @return corresponding dfa_preg_node child class instance
    */
    public function &from_preg_node($pregnode) {
        $name = $pregnode->name();
        switch ($name) {
            case 'node_finite_quant':
                $pregnode =& $this->convert_finite_quant($pregnode);
                break;
            case 'node_infinite_quant':
                $pregnode =& $this->convert_infinite_quant($pregnode);
                break;
            //TODO write dfa_preg_node_subpatt to process situations like subpattern inside subpattern
            case 'node_subpatt':
                $pregnode =& $pregnode->operands[0];
                return $this->from_preg_node($pregnode);
                break;
            case 'node_alt':
                if ($pregnode->operands[1]->type == preg_node::TYPE_LEAF_META && $pregnode->operands[1]->subtype == preg_leaf_meta::SUBTYPE_EMPTY) {
                    $tmp =& $pregnode->operands[0];
                    $pregnode = new preg_node_finite_quant;
                    $pregnode->leftborder = 0;
                    $pregnode->rightborder = 1;
                    $pregnode->operands[0] =& $tmp;
                } else if ($pregnode->operands[0]->type == preg_node::TYPE_LEAF_META && $pregnode->operands[0]->subtype == preg_leaf_meta::SUBTYPE_EMPTY) {
                    $tmp =& $pregnode->operands[1];
                    $pregnode = new preg_node_finite_quant;
                    $pregnode->leftborder = 0;
                    $pregnode->rightborder = 1;
                    $pregnode->operands[0] =& $tmp;
                }
        }
        return parent::from_preg_node($pregnode);
    }

    /**
    * Returns prefix for engine specific classes
    */
    protected function node_prefix() {
        return 'dfa';
    }

    /**
    * Function converts operand{} quantificator to operand and operand? combination
    * @param node node with {}
    * @return node subtree with ? 
    */
    protected function &convert_finite_quant($node) {
        if (!($node->leftborder==0 && $node->rightborder==1 || $node->leftborder==1 && $node->rightborder==1)) {
            $tmp = $node->operands[0];
            $subroot = new preg_node_concat;
            $subroot->operands[0] = $this->copy_preg_node($tmp);
            $subroot->operands[1] = $this->copy_preg_node($tmp);
            $count = $node->leftborder;
            for ($i=2; $i<$count; $i++) {
                $newsubroot = new preg_node_concat;
                $newsubroot->operands[0] = $subroot;
                $newsubroot->operands[1] = $this->copy_preg_node($tmp);
                $subroot = $newsubroot;
            }
            $tmp = new preg_node_finite_quant;
            $tmp->leftborder = 0;
            $tmp->rightborder = 1;
            $tmp->greed = $node->greed;
            $tmp->operands[0] = $node->operands[0];
            if ($node->leftborder == 0) {
                $subroot->operands[0] =& $this->copy_preg_node($tmp);
                $subroot->operands[1] =& $this->copy_preg_node($tmp);
                $count = $node->rightborder - 2;
            } else if ($node->leftborder == 1) {
                $subroot->operands[1] =& $this->copy_preg_node($tmp);
                $count = $node->rightborder - 2;
            } else {
                $count = $node->rightborder - $node->leftborder;
            }
            for ($i=0; $i<$count; $i++) {
                $newsubroot = new preg_node_concat;
                $newsubroot->operands[0] = $subroot;
                $newsubroot->operands[1] =& $this->copy_preg_node($tmp);
                $subroot = $newsubroot;
            }
            return $subroot;
        }
        return $node;
    }

    /**
    * Function convert operand{} quantificater to operand, operand? and operand* combination
    * @param node node with {}
    * @return node subtree with ? *
    */
    protected function &convert_infinite_quant($node) {
        if ($node->leftborder == 0) {
            return $node;
        } else if ($node->leftborder == 1) {
            $tmp = $node->operands[0];
            $subroot = new preg_node_concat;
            $subroot->operands[0] =& $this->copy_preg_node($tmp);
            $subroot->operands[1] =& $this->copy_preg_node($node);
            $subroot->operands[1]->leftborder = 0;
        } else {
            $tmp = $node->operands[0];
            $subroot = new preg_node_concat;
            $subroot->operands[0] =& $this->copy_preg_node($tmp);
            $subroot->operands[1] =& $this->copy_preg_node($tmp);
            $count = $node->leftborder;
            for ($i=2; $i<$count; $i++) {
                $newsubroot = new preg_node_concat;
                $newsubroot->operands[0] = $subroot;
                $newsubroot->operands[1] =& $this->copy_preg_node($tmp);
                $subroot = $newsubroot;
            }
            $newsubroot = new preg_node_concat;
            $newsubroot->operands[0] =& $this->copy_preg_node($subroot);
            $newsubroot->operands[1] =& $this->copy_preg_node($node);
            $newsubroot->operands[1]->leftborder = 0;
            $subroot = $newsubroot;
        }
        return $subroot;
    }

    /**
    *function get string and compare it with regex
    *@param response - string which will be compared with regex
    *@return result of compring, see compare function for format of result
    */
    function match_inner($response) {
        if ($response == '' && $this->roots[0]->pregnode->operands[0]->nullable) {
            $this->is_match = true;
            $this->full = true;
            $this->index_first[0] = 0;
            $this->index_last[0] = -1;
            $this->next = '';
            $this->left = 0;
        } else {
            $result = new stdClass;
            $result->full = false;
            $result->index = -1;
            $result->left = 999999;
            for ($i=0; $i<=strlen($response) && !$result->full; $i++) {
                $tmpres = $this->compare($response, 0, $i, $this->anchor->end);
                if ($tmpres !== false) { 
                    if ($tmpres->full || $tmpres->left < $result->left || !isset($result->next)&&false) {
                        $result = $tmpres;
                    }
                } else {
                //TODO: error message about zero length loop
                }
            }
        

            $this->is_match =  $result->ismatch;
            $this->full = $result->full;
            $this->index_first[0] = $result->offset;
            $this->index_last[0] = $result->index+$result->offset;
            if ($result->index==-1) {
                $this->index_last[0]=-1;
            }
            if ($result->next === 0) {
                $this->next = '';
            } else {
                $this->next = $result->next;
            }
            $this->left = $result->left;
        }
        return;
    }
    /**
    *@return list of supported operation as array of string
    */
    static function list_of_supported_operations_and_operands() {
        $result = array(
                        'character                                  - a',
                        'character class                            - [abc][a-c] and other formats of CC',
                        'negative character class                   - [^abc] ...',
                        'character class in \w\W\d\D\s\S\t format',
                        'empty                                      - something|',
                        'metasymbol dot                             - .',
                        'concatenation',
                        'alternative                                - ab|cd',
                        'greed iteration                            - a*',
                        'greed quantificator plus                   - a+',
                        'greed quantificator in curly               - a{15,137}',
                        'greed question quantificator               - a?',
                        'true forward assert                        - (?=...)',
                        'grouping                                   - (?:...)'
                       );
        return $result;               
    }
    public function print_connection($index) {
        foreach ($this->connection[$index] as $num=>$leaf) {
            echo 'number: ', $num, '</br>';
            $leaf->print_self(0);
            echo '</br>';
        }
    }
    /**
    * Debug function draw finite automate with number number in human readable form
    * don't work without right to execute file
    * @param number number of drawing finite automate
    * @param $subject type of drawing, may be: 'dfa', 'tree', 'fp'
    */
    public function draw ($number, $subject) {
        global $CFG;
        if ($this->graphvizpath===1) {
            echo '<br>ERROR: Missed path to GraphViz!<br>Can\'t draw '.$subject.'.';
            return;
        }
        $tempfolder = $CFG->dirroot . '\\question\\type\\preg\\temp\\';
        $dotcode = call_user_func(array('dfa_preg_matcher', 'generate_'.$subject.'_dot_code'), $number);
        $dotfile = fopen($tempfolder.'dotcode.dot', 'w');
        foreach ($dotcode as $dotstring) {
            fprintf($dotfile, "%s\n", $dotstring);
        }
        fclose($dotfile);
        chdir($this->graphvizpath);
        exec('dot.exe -Tjpg -o"'.$tempfolder.$subject.$this->picnum.'.jpg" -Kdot "'.$tempfolder.'dotcode.dot"');
        echo '<IMG src="/question/type/preg/temp/'.$subject.$this->picnum.'.jpg" alt="Can\'t display '.$subject.' #'.$this->picnum.' graph.">';
        'IMG src="/question/type/preg/temp/'.$subject.$this->picnum.'.jpg" alt="Can\'t display '.$subject.' #'.$this->picnum.' graph."';
        $this->picnum++;
    }
    /**
    * Debug function generate dot code for drawing finite automate
    * @param number number of drawing finite automate
    */
    protected function generate_dfa_dot_code($number) {
        $dotcode = array();
        $dotcode[] = 'digraph {';
        $dotcode[] = 'rankdir = LR;';
        foreach ($this->finiteautomates[$number] as $index=>$state) {
            foreach ($state->passages as  $leafcode=>$target) {
                if (is_object($this->connection[$number][$leafcode]->pregnode)) {
                    $symbol = $this->connection[$number][$leafcode]->pregnode->tohr();
                } else {
                    $symbol = 'ERROR: '.var_export($this->connection[$number][$leafcode]->pregnode, true);
                }
                if ($target==-2) {
                    $target = '"Not build yet."';
                } elseif ($target==-1) {
                    $target = '"End state."';
                }
                $dotcode[] = "$index->$target"."[label=\"$symbol\"];";
            }
        }
        $dotcode[] = '};';
        return $dotcode;
    }
    /**
    * Debug function generate dot code for drawing syntax tree
    * @param number number of drawing syntax tree
    */
    protected function generate_tree_dot_code($number) {
        $dotcode = array();
        $dotcode[] = 'digraph {';
        $dotcode[] = 'rankdir = TB;';
        $this->roots[$number]->generate_dot_code($dotcode, $maxnum=0);
        $dotcode[] = '};';
        return $dotcode;
    }
    /**
    * Debug function generate dot code for drawing follow position map
    * @param number number of drawing finite automate
    */
    protected function generate_fp_dot_code($number) {
        $dotcode = array('digraph {', 'rankdir=LR');
        foreach ($this->map[$number] as $start=>$ends) {
            foreach ($ends as $end) {
                $dotcode[] = '"'.$start.': '.$this->connection[$number][$start]->pregnode->tohr().
                        '"->"'.$end.': '.$this->connection[$number][$end]->pregnode->tohr().'";';
            }
        }
        $dotcode[] = '};';
        return $dotcode;
    }
}
?>