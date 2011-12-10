<?php  // $Id: testquestiontype.php,v 0.1 beta 2010/08/10 21:40:20 dvkolesov Exp $
/**
 * Unit tests for (some of) question/type/preg/dfa_preg_matcher.php.
 *
 * @copyright &copy; 2010 Dmitriy Kolesov
 * @author Dmitriy Kolesov
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package question
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/question/type/preg/dfa_preg_matcher.php');
//see carefully commented example of test on lines 617-644
class dfa_preg_matcher_test extends UnitTestCase {
    var $qtype;
    
    function setUp() {
        $this->qtype = new dfa_preg_matcher();
    }
    
    function tearDown() {
        $this->qtype = null;   
    }

    function test_name() {
        $this->assertEqual($this->qtype->name(), 'dfa_preg_matcher');
    }
    //Unit test for nullable function
    function test_nullable_leaf() {
        $this->qtype = new dfa_preg_matcher('a');
        $this->assertFalse($this->qtype->roots[0]->pregnode->operands[0]->nullable());
    }
    function test_nullable_leaf_iteration_node() {
        $this->qtype = new dfa_preg_matcher('a*');
        $this->assertTrue($this->qtype->roots[0]->pregnode->operands[0]->nullable());
    }
    function test_nullable_leaf_concatenation_node() {
        $this->qtype = new dfa_preg_matcher('ab');
        $this->assertFalse($this->qtype->roots[0]->pregnode->operands[0]->nullable());
    }
    function test_nullable_leaf_alternative_node() {
        $this->qtype = new dfa_preg_matcher('a|b');
        $this->assertFalse($this->qtype->roots[0]->pregnode->operands[0]->nullable());
    }
    function test_nullable_node_concatenation_node() {
        $this->qtype = new dfa_preg_matcher('a*bc');
        $this->assertFalse($this->qtype->roots[0]->pregnode->operands[0]->nullable());
    }
    function test_nullable_node_alternative_node() {
        $this->qtype = new dfa_preg_matcher('a*|bc');
        $this->assertTrue($this->qtype->roots[0]->pregnode->operands[0]->nullable());
    }
    function test_nullable_third_level_node() {
        $this->qtype = new dfa_preg_matcher('(?:(?:a|b)|c*)|d*');
        $this->assertTrue($this->qtype->roots[0]->pregnode->operands[0]->nullable());
    }
    function test_nullable_question_quantificator() {
        $this->qtype = new dfa_preg_matcher('a?');
        $this->assertTrue($this->qtype->roots[0]->pregnode->operands[0]->nullable());
    }
    function test_nullable_negative_character_class() {
        $this->qtype = new dfa_preg_matcher('[^a]');
        $this->assertFalse($this->qtype->roots[0]->pregnode->operands[0]->nullable());
    }
    function test_nullable_assert() {
        $this->qtype = new dfa_preg_matcher('a(?=.*b)[xcvbnm]*');
        $this->assertFalse($this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[0]->pregnode->operands[1]->nullable());
    }
    //Unit test for firstpos function
    function test_firstpos_leaf() {
        $this->qtype = new dfa_preg_matcher('a');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $result = $this->qtype->roots[0]->pregnode->operands[0]->firstpos();
        $this->assertTrue(count($result) == 1 && $result[0] == 1);
    }
    function test_firstpos_leaf_concatenation_node() {
        $this->qtype = new dfa_preg_matcher('ab');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable;
        $result = $this->qtype->roots[0]->pregnode->operands[0]->firstpos();
        $this->assertTrue(count($result) == 1 && $result[0] == 1);
    }
    function test_firstpos_leaf_alternative_node() {
        $this->qtype = new dfa_preg_matcher('a|b');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $result=$this->qtype->roots[0]->pregnode->operands[0]->firstpos();
        $this->assertTrue(count($result) == 2 && $result[0] == 1 && $result[1] == 2);
    }
    function test_firstpos_three_leaf_alternative() {//TODO:fix it!!!
        $this->qtype = new dfa_preg_matcher('a|b|c');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $result = $this->qtype->roots[0]->pregnode->operands[0]->firstpos();
        $this->assertTrue(count($result) == 3 && $result[0] == 1 && $result[1] == 2 && $result[2] == 3);
    }
    function test_firstpos_leaf_iteration_node() {
        $this->qtype = new dfa_preg_matcher('a*');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $result = $this->qtype->roots[0]->pregnode->operands[0]->firstpos();
        $this->assertTrue(count($result) == 1 && $result[0] == 1);
    }
    function test_firstpos_node_concatenation_node() {
        $this->qtype = new dfa_preg_matcher('c*(?:a|b)');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $result = $this->qtype->roots[0]->pregnode->operands[0]->firstpos();
        $this->assertTrue(count($result) == 3 && $result[0] == 1 && $result[1] == 2 && $result[2] == 3);
    }
    function test_firstpos_node_alternative_node() {
        $this->qtype = new dfa_preg_matcher('a|b|c*');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $result = $this->qtype->roots[0]->pregnode->operands[0]->firstpos();
        $this->assertTrue(count($result) == 3 && $result[0] == 1 && $result[1] == 2 && $result[2] == 3);
    }
    function test_firstpos_node_iteration_node() {
        $this->qtype = new dfa_preg_matcher('(?:a*)*');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $result = $this->qtype->roots[0]->pregnode->operands[0]->firstpos();
        $this->assertTrue(count($result) == 1 && $result[0] == 1);
    }
    function test_firstpos_question_quantificator() {
        $this->qtype = new dfa_preg_matcher('a?');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $result = $this->qtype->roots[0]->pregnode->operands[0]->firstpos();
        $this->assertTrue(count($result) == 1 && $result[0] == 1);
    }
    function test_firstpos_negative_character_class() {
        $this->qtype = new dfa_preg_matcher('[^a]b');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $this->qtype->roots[0]->pregnode->operands[0]->firstpos();
        $this->assertTrue(count($this->qtype->roots[0]->pregnode->operands[0]->firstpos) == 1 && $this->qtype->roots[0]->pregnode->operands[0]->firstpos[0] == 1);
        $this->assertTrue(count($this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[0]->firstpos) == 1 && $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[0]->firstpos[0] == 1);
    }
    function test_firstpos_assert() {
        $this->qtype = new dfa_preg_matcher('a(?=.*b)[xcvbnm]*');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $this->qtype->roots[0]->pregnode->operands[0]->firstpos();
        $this->assertTrue(count($this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[0]->pregnode->operands[1]->firstpos) == 1 && 
                            $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[0]->pregnode->operands[1]->firstpos[0]>dfa_preg_node_assert::ASSERT_MIN_NUM);
    }
    //Unit test for lastpos function
    function test_lastpos_leaf() {
        $this->qtype = new dfa_preg_matcher('a');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $result = $this->qtype->roots[0]->pregnode->operands[0]->lastpos();
        $this->assertTrue(count($result) == 1 && $result[0] == 1);
    }
    function test_lastpos_leaf_concatenation_node() {
        $this->qtype = new dfa_preg_matcher('ab');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $result = $this->qtype->roots[0]->pregnode->operands[0]->lastpos();
        $this->assertTrue(count($result) == 1 && $result[0] == 2);
    }
    function test_lastpos_leaf_alternative_node() {
        $this->qtype = new dfa_preg_matcher('a|b');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $result = $this->qtype->roots[0]->pregnode->operands[0]->lastpos();
        $this->assertTrue(count($result) == 2 && $result[0] == 1 && $result[1] == 2);
    }
    function test_lastpos_leaf_iteration_node() {
        $this->qtype = new dfa_preg_matcher('a*');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $result = $this->qtype->roots[0]->pregnode->operands[0]->lastpos();
        $this->assertTrue(count($result) == 1 && $result[0] == 1);
    }
    function test_lastpos_node_concatenation_node() {
        $this->qtype = new dfa_preg_matcher('(?:a|b)c*');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $result = $this->qtype->roots[0]->pregnode->operands[0]->lastpos();
        $this->assertTrue(count($result) == 3 && $result[0] == 1 && $result[1] == 2 && $result[2] == 3);
    }
    function test_lastpos_node_alternative_node() {
        $this->qtype = new dfa_preg_matcher('a|b|c*');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $result = $this->qtype->roots[0]->pregnode->operands[0]->lastpos();
        $this->assertTrue(count($result) == 3 && $result[0] == 1 && $result[1] == 2 && $result[2] == 3);
    }
    function test_lastpos_node_iteration_node() {
        $this->qtype = new dfa_preg_matcher('(?:a*)*');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $result = $this->qtype->roots[0]->pregnode->operands[0]->lastpos();
        $this->assertTrue(count($result) == 1 && $result[0] == 1);
    }
    function test_lastpos_question_quantificator() {
        $this->qtype = new dfa_preg_matcher('a?');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $result = $this->qtype->roots[0]->pregnode->operands[0]->lastpos();
        $this->assertTrue(count($result) == 1 && $result[0] == 1);
    }
    function test_lastpos_negative_character_class() {
        $this->qtype = new dfa_preg_matcher('[^a]|b');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $result = $this->qtype->roots[0]->pregnode->operands[0]->lastpos();
        $this->assertTrue(count($result) == 2 && $result[0] == 1 && $result[1] == 2);
    }
    function test_lastpos_assert() {
        $this->qtype = new dfa_preg_matcher('a(?=.*b)[xcvbnm]*');
        $connection = array();
        $maxnum = 0;
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $this->qtype->roots[0]->pregnode->operands[0]->lastpos();
        $this->assertTrue(count($this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[0]->pregnode->operands[1]->lastpos) && 
                            $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[0]->pregnode->operands[1]->lastpos[0]>dfa_preg_node_assert::ASSERT_MIN_NUM);
    }
    //Unit tests for followpos function
    function test_followpos_node_concatenation_node() {
        $this->qtype = new dfa_preg_matcher('(?:a|b)*ab');
        $this->qtype->roots[0]->pregnode->operands[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->pregnode->operands[0]->nullable();
        $this->qtype->roots[0]->pregnode->operands[0]->firstpos();
        $this->qtype->roots[0]->pregnode->operands[0]->lastpos();
        $result=null;
        $this->qtype->roots[0]->followpos($result);
        $res1 = (count($result[1]) == 3 && $result[1][0] == 1 && $result[1][1] == 2 && $result[1][2] == 3);
        $res2 = (count($result[2]) == 3 && $result[2][0] == 1 && $result[2][1] == 2 && $result[2][2] == 3);
        $res3 = (count($result[3]) == 1 && $result[3][0] == 4);
        $this->assertTrue($res1 && $res2 && $res3);
    }
    function test_followpos_three_node_alternative() {
        $this->qtype = new dfa_preg_matcher('ab|cd|ef');
        $this->qtype->roots[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->nullable();
        $this->qtype->roots[0]->firstpos();
        $this->qtype->roots[0]->lastpos();
        $result=null;
        $this->qtype->roots[0]->followpos($result);
        $this->assertTrue(count($result[1]) == 1 && $result[1][0] == 2);
        $this->assertTrue(count($result[3]) == 1 && $result[3][0] == 4);
        $this->assertTrue(count($result[5]) == 1 && $result[5][0] == 6);
    }
    function test_followpos_question_quantificator() {
        $this->qtype = new dfa_preg_matcher('a?b');
        $this->qtype->roots[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->nullable();
        $this->qtype->roots[0]->firstpos();
        $this->qtype->roots[0]->lastpos();
        $result=null;
        $this->qtype->roots[0]->followpos($result);
        $this->assertTrue(count($result[1]) == 1 && $result[1][0] == 2);
    }
    function test_followpos_negative_character_class() {
        $this->qtype = new dfa_preg_matcher('[^a]b');
        $this->qtype->roots[0]->number($connection, $maxnum);
        $this->qtype->roots[0]->nullable();
        $this->qtype->roots[0]->firstpos();
        $this->qtype->roots[0]->lastpos();
        $result=null;
        $this->qtype->roots[0]->followpos($result);
        $this->assertTrue(count($result[1]) == 1 && $result[1][0] == 2);
    }
    //Unit test for buildfa function
    function test_buildfa_easy() {//ab
        $this->qtype = new dfa_preg_matcher('ab');
        $this->assertTrue(count($this->qtype->finiteautomates[0][0]->passages) == 1 && $this->qtype->finiteautomates[0][0]->passages[1] == 1);
        $this->assertTrue(count($this->qtype->finiteautomates[0][1]->passages) == 1 && $this->qtype->finiteautomates[0][1]->passages[2] == 2);
        $this->assertTrue(count($this->qtype->finiteautomates[0][2]->passages) == 1 && $this->qtype->finiteautomates[0][2]->passages[dfa_preg_leaf_meta::ENDREG] == -1);
    }
    function test_buildfa_iteration() {//ab*
        $this->qtype = new dfa_preg_matcher('ab*');
        $this->assertTrue(count($this->qtype->finiteautomates[0][0]->passages) == 1);
        $n1 = $this->qtype->finiteautomates[0][0]->passages[1];
        $this->assertTrue(count($this->qtype->finiteautomates[0][$n1]->passages) == 2);
        $this->assertTrue($this->qtype->finiteautomates[0][$n1]->passages[dfa_preg_leaf_meta::ENDREG] == -1 && $this->qtype->finiteautomates[0][$n1]->passages[2] == $n1);
    }
    function test_buildfa_alternative() {//a|b
        $this->qtype = new dfa_preg_matcher('a|b');
        $this->assertTrue(count($this->qtype->finiteautomates[0][0]->passages) == 2 && $this->qtype->finiteautomates[0][0]->passages[1] == 1 && $this->qtype->finiteautomates[0][0]->passages[2] == 1);
        $this->assertTrue(count($this->qtype->finiteautomates[0][1]->passages) == 1 && $this->qtype->finiteautomates[0][1]->passages[dfa_preg_leaf_meta::ENDREG] == -1);
    }
    function test_buildfa_alternative_and_iteration() {//(a|b)c*
        $this->qtype = new dfa_preg_matcher('(?:a|b)c*');
        $this->assertTrue(count($this->qtype->finiteautomates[0][0]->passages) == 2);
        $this->assertTrue(count($this->qtype->finiteautomates[0][1]->passages) == 2 && $this->qtype->finiteautomates[0][1]->passages[3] == 1 && 
                            $this->qtype->finiteautomates[0][1]->passages[dfa_preg_leaf_meta::ENDREG] == -1);
    }
    function test_buildfa_nesting_alternative_and_iteration() {//(ab|cd)*
        $this->qtype = new dfa_preg_matcher('(?:ab|cd)*');
        $this->assertTrue(count($this->qtype->finiteautomates[0][0]->passages) == 3 && $this->qtype->finiteautomates[0][0]->passages[dfa_preg_leaf_meta::ENDREG] == -1);
        $n1 = $this->qtype->finiteautomates[0][0]->passages[1];
        $n2 = $this->qtype->finiteautomates[0][0]->passages[3];
        $this->assertTrue(count($this->qtype->finiteautomates[0][$n1]->passages) == 1 && $this->qtype->finiteautomates[0][$n1]->passages[2] == 0);
        $this->assertTrue(count($this->qtype->finiteautomates[0][$n2]->passages) == 1 && $this->qtype->finiteautomates[0][$n2]->passages[4] == 0);
    }
    function test_buildfa_question_quantificator() {//a?b
        $this->qtype = new dfa_preg_matcher('a?b');
        $this->assertTrue(count($this->qtype->finiteautomates[0][0]->passages) == 2);
        $n1 = $this->qtype->finiteautomates[0][0]->passages[1];
        $n2 = $this->qtype->finiteautomates[0][0]->passages[2];
        $this->assertTrue(count($this->qtype->finiteautomates[0][$n1]->passages) == 1 && $this->qtype->finiteautomates[0][$n1]->passages[2] == $n2);
        $this->assertTrue(count($this->qtype->finiteautomates[0][$n2]->passages) == 1 && $this->qtype->finiteautomates[0][$n2]->passages[dfa_preg_leaf_meta::ENDREG] == -1);
    }
    function test_buildfa_negative_character_class() {//(a[^b]|c[^d])*
        global $QTYPE_PREG_DEBUG;
        $QTYPE_PREG_DEBUG = true;
        $this->qtype = new dfa_preg_matcher('(?:a[^b]|c[^d])*');
        $this->assertTrue(count($this->qtype->finiteautomates[0][0]->passages) == 3);
        $n1 = $this->qtype->finiteautomates[0][0]->passages[1];
        $n2 = $this->qtype->finiteautomates[0][0]->passages[3];
        $this->assertTrue(count($this->qtype->finiteautomates[0][$n1]->passages) == 1 && $this->qtype->finiteautomates[0][$n1]->passages[2] == 0);
        $this->assertTrue(count($this->qtype->finiteautomates[0][$n2]->passages) == 1 && $this->qtype->finiteautomates[0][$n2]->passages[4] == 0);
        $QTYPE_PREG_DEBUG = false;
    }
    /*function test_buildfa_assert() {//a(?=.*b)[xcvbnm]* test for old style assert matching
        $this->qtype = new dfa_preg_matcher('a(?=[xcvnm]*b)[xcvbnm]*');
        $this->assertTrue(count($this->qtype->finiteautomates[0][0]->passages)==1 && $this->qtype->finiteautomates[0][0]->passages[3]==1);
        $this->assertTrue(count($this->qtype->finiteautomates[0][1]->passages)==2 && $this->qtype->finiteautomates[0][1]->passages[1]==1 && $this->qtype->finiteautomates[0][1]->passages[2]==2);
        $this->assertTrue(count($this->qtype->finiteautomates[0][2]->passages)==2 && $this->qtype->finiteautomates[0][2]->passages[4]==2 && $this->qtype->finiteautomates[0][2]->passages[186759556]==-1);
        $this->assertTrue(count($this->qtype->finiteautomates[0])==3);
    }*/
    
    //Unit tests for compare function
    function test_compare_full_incorrect() {//ab
        $this->qtype = new dfa_preg_matcher('ab');
        $this->qtype->roots[0]->pregnode->operands[0]->number($this->qtype->connection[0], $maxnum=0);
        $this->qtype->finiteautomates[0][0] = new finite_automate_state;
        $this->qtype->finiteautomates[0][1] = new finite_automate_state;
        $this->qtype->finiteautomates[0][2] = new finite_automate_state;
        $this->qtype->finiteautomates[0][0]->passages[1] = 1;
        $this->qtype->finiteautomates[0][1]->passages[2] = 2;
        $this->qtype->finiteautomates[0][2]->passages[dfa_preg_leaf_meta::ENDREG] = -1;
        $this->connection[0][1] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[0];
        $this->connection[0][2] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[1];
        $result=$this->qtype->compare('b',0);
        $this->assertFalse($result->full);
        $this->assertTrue($result->index == -1 && $result->next == 'a');
    }
    function test_compare_first_character_incorrect() {//ab
        $this->qtype = new dfa_preg_matcher('ab');
        $this->qtype->finiteautomates[0][0] = new finite_automate_state;
        $this->qtype->finiteautomates[0][1] = new finite_automate_state;
        $this->qtype->finiteautomates[0][2] = new finite_automate_state;
        $this->qtype->finiteautomates[0][0]->passages[1] = 1;
        $this->qtype->finiteautomates[0][1]->passages[2] = 2;
        $this->qtype->finiteautomates[0][2]->passages[dfa_preg_leaf_meta::ENDREG] = -1;
        $this->qtype->connection[0][1] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[0];
        $this->qtype->connection[0][2] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[1];
        $result = $this->qtype->compare('cb',0);
        $this->assertFalse($result->full);
        $this->assertTrue($result->index == -1 && $result->next == 'a');
    }
    function test_compare_particular_correct() {//ab
        $this->qtype = new dfa_preg_matcher('ab');
        $this->qtype->finiteautomates[0][0] = new finite_automate_state;
        $this->qtype->finiteautomates[0][1] = new finite_automate_state;
        $this->qtype->finiteautomates[0][2] = new finite_automate_state;
        $this->qtype->finiteautomates[0][0]->passages[1] = 1;
        $this->qtype->finiteautomates[0][1]->passages[2] = 2;
        $this->qtype->finiteautomates[0][2]->passages[dfa_preg_leaf_meta::ENDREG] = -1;
        $this->qtype->connection[0][1] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[0];
        $this->qtype->connection[0][2] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[1];
        $result = $this->qtype->compare('ac',0);
        $this->assertFalse($result->full);
        $this->assertTrue($result->index == 0 && $result->next == 'b');
    }
    function test_compare_full_correct() {//ab
        $this->qtype = new dfa_preg_matcher('ab');
        $this->qtype->finiteautomates[0][0] = new finite_automate_state;
        $this->qtype->finiteautomates[0][1] = new finite_automate_state;
        $this->qtype->finiteautomates[0][2] = new finite_automate_state;
        $this->qtype->finiteautomates[0][0]->passages[1] = 1;
        $this->qtype->finiteautomates[0][1]->passages[2] = 2;
        $this->qtype->finiteautomates[0][2]->passages[dfa_preg_leaf_meta::ENDREG] = -1;
        $this->qtype->connection[0][1] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[0];
        $this->qtype->connection[0][2] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[1];
        $result = $this->qtype->compare('ab',0);
        $this->assertTrue($result->full);
        $this->assertTrue($result->index == 1 && $result->next == 0);
    }
    function test_compare_question_quantificator() {//a?b
        $this->qtype = new dfa_preg_matcher('a?b');
        $this->qtype->finiteautomates[0][0] = new finite_automate_state;
        $this->qtype->finiteautomates[0][1] = new finite_automate_state;
        $this->qtype->finiteautomates[0][2] = new finite_automate_state;
        $this->qtype->finiteautomates[0][0]->passages[1] = 1;
        $this->qtype->finiteautomates[0][0]->passages[2] = 2;
        $this->qtype->finiteautomates[0][1]->passages[2] = 2;
        $this->qtype->finiteautomates[0][2]->passages[dfa_preg_leaf_meta::ENDREG] = -1;
        $this->qtype->connection[0][1] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[0]->pregnode->operands[0];
        $this->qtype->connection[0][2] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[1];
        $result1 = $this->qtype->compare('ab', 0);
        $result2 = $this->qtype->compare('b', 0);
        $result3 = $this->qtype->compare('Incorrect string', 0);
        $this->assertTrue($result1->full);
        $this->assertTrue($result1->index == 1 && $result1->next == 0);
        $this->assertTrue($result2->full);
        $this->assertTrue($result2->index == 0 && $result2->next == 0);
        $this->assertFalse($result3->full);
        $this->assertTrue($result3->index == -1 && $result3->next == 'b' || $result3->next == 'a');
    }
    function test_compare_negative_character_class() {//[^a][b]
        $this->qtype = new dfa_preg_matcher('[^a][b]');
        $this->qtype->finiteautomates[0][0] = new finite_automate_state;
        $this->qtype->finiteautomates[0][1] = new finite_automate_state;
        $this->qtype->finiteautomates[0][2] = new finite_automate_state;
        $this->qtype->finiteautomates[0][0]->passages[1] = 1;
        $this->qtype->finiteautomates[0][1]->passages[2] = 2;
        $this->qtype->finiteautomates[0][2]->passages[dfa_preg_leaf_meta::ENDREG] = -1;
        $this->qtype->connection[0][1] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[0];
        $this->qtype->connection[0][2] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[1];
        $result1 = $this->qtype->compare('ab',0);
        $result2 = $this->qtype->compare('bb',0);
        $this->assertFalse($result1->full);
        $this->assertTrue($result1->index == -1 && isset($result1->next) && $result1->next != 'a');
        $this->assertTrue($result2->full);
        $this->assertTrue($result2->index == 1 && $result2->next == 0);
    }
    function test_compare_dot() {//.b
        $this->qtype = new dfa_preg_matcher('.b');
        $this->qtype->finiteautomates[0][0] = new finite_automate_state;
        $this->qtype->finiteautomates[0][1] = new finite_automate_state;
        $this->qtype->finiteautomates[0][2] = new finite_automate_state;
        $this->qtype->finiteautomates[0][0]->passages[1] = 1;
        $this->qtype->finiteautomates[0][1]->passages[2] = 2;
        $this->qtype->finiteautomates[0][2]->passages[dfa_preg_leaf_meta::ENDREG] = -1;
        $this->qtype->connection[0][1] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[0];
        $this->qtype->connection[0][2] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[1];
        $result1 = $this->qtype->compare('ab', 0);
        $result2 = $this->qtype->compare('fbf',0);
        $result3 = $this->qtype->compare('fff',0);
        $this->assertTrue($result1->full);
        $this->assertTrue($result1->index == 1 && $result1->next == 0);
        $this->assertFalse($result2->full);
        $this->assertTrue($result2->index == 1 && $result2->next == 0);
        $this->assertFalse($result3->full);
        $this->assertTrue($result3->index == 0 && $result3->next == 'b');
    }
    function _test_compare_assert() {//a(?=.*b)[xcvbnm]*
        $this->qtype = new dfa_preg_matcher('a(?=[xcvnm]*b)[xcvbnm]*');
        $result1 = $this->qtype->compare('an',0);
        $result2 = $this->qtype->compare('annvnvb',0);
        $result3 = $this->qtype->compare('annvnvv',0);
        $result4 = $this->qtype->compare('abnm',0);
        $this->assertFalse($result1->full);
        $this->assertTrue($result1->index == 1 && ($result1->next !== 0));
        $this->assertTrue($result2->full);
        $this->assertTrue($result2->index == 6 && $result2->next === 0);
        $this->assertFalse($result3->full);
        $this->assertTrue($result3->index == 6 && ($result3->next !== 0));
        $this->assertTrue($result4->full);
        $this->assertTrue($result4->index == 3 && $result4->next === 0);
    }
    function test_compare_unanchor() {//ab
        $this->qtype = new dfa_preg_matcher('ab');
        $this->qtype->finiteautomates[0][0] = new finite_automate_state;
        $this->qtype->finiteautomates[0][1] = new finite_automate_state;
        $this->qtype->finiteautomates[0][2] = new finite_automate_state;
        $this->qtype->finiteautomates[0][0]->passages[1] = 1;
        $this->qtype->finiteautomates[0][1]->passages[2] = 2;
        $this->qtype->finiteautomates[0][2]->passages[dfa_preg_leaf_meta::ENDREG] = -1;
        $this->qtype->connection[0][1] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[0];
        $this->qtype->connection[0][2] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[1];
        $result = $this->qtype->compare('OabO', 0, 0, false);
        $this->assertFalse($result->full);
        $this->assertTrue($result->index == -1 && $result->next === 'a' && $result->offset == 0);
        $result = $this->qtype->compare('OabO', 0, 1, false);
        $this->assertTrue($result->full);
        $this->assertTrue($result->index == 1 && $result->next === 0 && $result->offset == 1);
        $result = $this->qtype->compare('OabO', 0, 1, true);
        $this->assertFalse($result->full);
        $this->assertTrue($result->index == 1 && $result->next === 0 && $result->offset == 1);
        $result = $this->qtype->compare('OabO', 0, 2, false);
        $this->assertFalse($result->full);
        $this->assertTrue($result->index == -1 && $result->next === 'a' && $result->offset == 2);
    }
    function test_compare_unanchor_iteration() {//(?:abc)*
        $this->qtype = new dfa_preg_matcher('(?:abc)*');
        $this->qtype->finiteautomates[0][0] = new finite_automate_state;
        $this->qtype->finiteautomates[0][1] = new finite_automate_state;
        $this->qtype->finiteautomates[0][2] = new finite_automate_state;
        $this->qtype->finiteautomates[0][0]->passages[1] = 1;
        $this->qtype->finiteautomates[0][0]->passages[dfa_preg_leaf_meta::ENDREG] = -1;
        $this->qtype->finiteautomates[0][1]->passages[2] = 2;
        $this->qtype->finiteautomates[0][2]->passages[3] = 0;
        $this->qtype->connection[0][1] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[0]->pregnode->operands[0]->pregnode->operands[0];
        $this->qtype->connection[0][2] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[0]->pregnode->operands[0]->pregnode->operands[1];
        $this->qtype->connection[0][3] = $this->qtype->roots[0]->pregnode->operands[0]->pregnode->operands[0]->pregnode->operands[1];
        $result = $this->qtype->compare('abcabcab', 0, 0, false);
        $this->assertTrue($result->full);
        $this->assertTrue($result->index == 5 && $result->next === 0 && $result->offset == 0);
    }
    //General tests, testing parser + buildfa + compare (also nullable, firstpos, lastpos, followpos and other in buildfa)
    //dfa_preg_matcher without input and output data.
    function test_general_repeat_characters() {
        $matcher = new dfa_preg_matcher('^(?:a|b)*abb$');
        $matcher->match('cd');
        $this->assertFalse($matcher->is_matching_complete());
        $this->assertTrue($matcher->last_correct_character_index() == strlen('cd')-1 && $matcher->next_char() === 'a');
        $matcher->match('ca');
        $this->assertFalse($matcher->is_matching_complete());
        $this->assertTrue($matcher->last_correct_character_index() == strlen('ca')-1 && $matcher->next_char() === 'a');
        $matcher->match('ac');
        $this->assertFalse($matcher->is_matching_complete());
        $this->assertTrue($matcher->last_correct_character_index() == 0 && ($matcher->next_char() === 'b') || $matcher->next_char() === 'a');
        $matcher->match('bb');
        $this->assertFalse($matcher->is_matching_complete());
        $this->assertTrue($matcher->last_correct_character_index() == 1 && $matcher->next_char() === 'a');
        $matcher->match('abb');
        $this->assertTrue($matcher->is_matching_complete());
        $this->assertTrue($matcher->last_correct_character_index() == 2 && $matcher->next_char() === '');
        $matcher->match('ababababababaabbabababababababaabb');//34 characters
        $this->assertTrue($matcher->is_matching_complete());
        $this->assertTrue($matcher->last_correct_character_index() == 33 && $matcher->next_char() ==='');
    }
    function test_general_assert() {
        $matcher = new dfa_preg_matcher('a(?=[xcvnm]*b)[xcvbnm]*');
        $result1 = $matcher->match('an');
        $this->assertFalse($matcher->is_matching_complete());
        $char = $matcher->next_char();
        $this->assertTrue($matcher->last_correct_character_index() == 1 && strchr('xcvbnm', $char)!==false);
        $matcher->match('anvnvb');
        $this->assertTrue($matcher->is_matching_complete());
        $this->assertTrue($matcher->last_correct_character_index() == 5 && $matcher->next_char() === '');
        $matcher->match('avnvnv');
        $char = $matcher->next_char();
        $this->assertFalse($matcher->is_matching_complete());
        $this->assertTrue($matcher->last_correct_character_index() == 5 && strchr('xcvbnm', $char)!==false);
        $matcher->match('abnm');
        $this->assertTrue($matcher->is_matching_complete());
        $this->assertTrue($matcher->last_correct_character_index() == 3 && $matcher->next_char() === '');
    }
    /*
    *   this is overall test for dfa_preg_matcher class
    *   you may use it as example of test
    */
    function _test_general_two_asserts() {
        $matcher = new dfa_preg_matcher('^a(?=b)(?=[xvbnm]*c)[xcvbnm]*$');//put regular expirience in constructor for building dfa.
        /*  
        *   call match method for matching string with regex, string is argument, regex was got in constructor,
        *   results of matching get with method
        *   1)index - last_correct_character_index()
        *   2)full  - is_matching_complete()
        *   3)next  - next_char()
        */
        $matcher->match('avnm');
        $this->assertFalse($matcher->is_matching_complete());
        $this->assertTrue($matcher->last_correct_character_index() == 0 && $matcher->next_char() === 'b');
        $matcher->match('acnm');
        $this->assertFalse($matcher->is_matching_complete());
        $this->assertTrue($matcher->last_correct_character_index() == 0 && $matcher->next_char() === 'b');
        $matcher->match('abnm');
        $char = $matcher->next_char();
        $this->assertFalse($matcher->is_matching_complete());
        $this->assertTrue($matcher->last_correct_character_index() == 3 && strchr('xcvbnm', $char)!==false);
        $matcher->match('abnc');
        $this->assertTrue($matcher->is_matching_complete());
        $this->assertTrue($matcher->last_correct_character_index() == 3 && $matcher->next_char() === '');
    }
    //Unit tests for convert tree
    function _test_convert_tree_quantificator_l2r4() {
        $this->qtype = new dfa_preg_matcher('a{2,4}b');
        $result1 = $this->qtype->compare('ab', 0);
        $result2 = $this->qtype->compare('aab', 0);
        $result3 = $this->qtype->compare('aaab', 0);
        $result4 = $this->qtype->compare('aaaab', 0);
        $result5 = $this->qtype->compare('aaaaab', 0);
        $this->assertFalse($result1->full);
        $this->assertTrue($result2->full);
        $this->assertTrue($result3->full);
        $this->assertTrue($result4->full);
        $this->assertFalse($result5->full);
    }
    function _test_convert_tree_quantificator_l0r4() {
        $this->qtype = new dfa_preg_matcher('a{,4}b');
        $result0 = $this->qtype->compare('b', 0);
        $result1 = $this->qtype->compare('ab', 0);
        $result2 = $this->qtype->compare('aab', 0);
        $result3 = $this->qtype->compare('aaab', 0);
        $result4 = $this->qtype->compare('aaaab', 0);
        $result5 = $this->qtype->compare('aaaaab', 0);
        $this->assertTrue($result0->full);
        $this->assertTrue($result1->full);
        $this->assertTrue($result2->full);
        $this->assertTrue($result3->full);
        $this->assertTrue($result4->full);
        $this->assertFalse($result5->full);
    }
    function _test_convert_tree_quantificator_l2rinf() {
        $this->qtype = new dfa_preg_matcher('a{2,}b');
        $result1 = $this->qtype->compare('ab', 0);
        $result2 = $this->qtype->compare('aab', 0);
        $result3 = $this->qtype->compare('aaab', 0);
        $result4 = $this->qtype->compare('aaaab', 0);
        $result5 = $this->qtype->compare('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaab', 0);
        $this->assertFalse($result1->full);
        $this->assertTrue($result2->full);
        $this->assertTrue($result3->full);
        $this->assertTrue($result4->full);
        $this->assertTrue($result5->full);
    }
    function test_convert_tree_subpattern() {
        $this->qtype = new dfa_preg_matcher('(a|b)');
        $result1 = $this->qtype->compare('b', 0);
        $result2 = $this->qtype->compare('a', 0);
        $result3 = $this->qtype->compare('Incorrect', 0);
        $this->assertTrue($result1->full);
        $this->assertTrue($result2->full);
        $this->assertFalse($result3->full);
    }
    //Unit test for wave
    function test_wave_easy() {
        $matcher = new dfa_preg_matcher('abcd');
        $matcher->match('abce');
        $this->assertTrue($matcher->next_char() === 'd');
    }
    function test_wave_iteration() {
        $matcher = new dfa_preg_matcher('abc*d');
        $matcher->match('abB');
        $this->assertTrue($matcher->next_char() === 'd');
    }
    function test_wave_alternative() {;
        $matcher = new dfa_preg_matcher('a(?:cdgfhghghgdhgfhdgfydgfdhgfdhgfdhgfhdgfhdgfhdgfydgfy|b)');
        $matcher->match('a_incorrect');
        $this->assertTrue($matcher->next_char() === 'b');
    }
    function test_wave_repeat_chars() {
        $matcher = new dfa_preg_matcher('^(?:a|b)*abb$');
        $matcher->match('ababababbbbaaaabbbabbbab');
        $this->assertTrue($matcher->next_char() === 'b');
    }
    function test_wave_complex() {
        $matcher = new dfa_preg_matcher('(?:fgh|ab?c)+');
        $matcher->match('something');
        $this->assertTrue($matcher->next_char() === 'a');
    }
    //Unit tests for left character count determined by wave function
    function test_wave_left_full_true() {
        $matcher = new dfa_preg_matcher('abcd');
        $matcher->match('abcd');
        $this->assertTrue($matcher->characters_left() == 0);
    }
    function test_wave_left_easy_regex() {
        $matcher = new dfa_preg_matcher('abcdefghi');
        $matcher->match('abcd');
        $this->assertTrue($matcher->characters_left() == 5);
    }
    function test_wave_left_complex_regex() {
        $matcher = new dfa_preg_matcher('ab+c{5,9}(?:ab?c|dfg)|averylongword');
        $matcher->match('a');
        $this->assertTrue($matcher->characters_left() == 8);
    }
}
?>