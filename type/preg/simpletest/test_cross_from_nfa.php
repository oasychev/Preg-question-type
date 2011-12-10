<?php
/**
 * Unit tests for matchers
 *
 * @copyright &copy; 2011  Valeriy Streltsov
 * @author Valeriy Streltsov, Volgograd State Technical University
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questions
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/question/type/preg/simpletest/crosstester.php');

class test_cross_from_nfa extends preg_cross_tester {

    //-----------------------------------------------------------------------tests for general cases----------------------------------------------------------//
    function data_for_test_concat() {
        $test1 = array( 'str'=>'the matcher works',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>16),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'_the matcher works',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>-1),
                        'left'=>array(17),
                        'next'=>'t');

        $test3 = array( 'str'=>'the matcher',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>10),
                        'left'=>array(6),
                        'next'=>' ');

        $test4 = array( 'str'=>'',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>-1),
                        'left'=>array(17),
                        'next'=>'t');

        return array('regex'=>'^the matcher works',
                     'tests'=>array($test1, $test2, $test3, $test4));
    }

    function data_for_test_alt_1() {
        $test1 = array( 'str'=>'abcf',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>2),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'def',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>2),
                        'left'=>array(0),
                        'next'=>'');

        $test3 = array( 'str'=>'deff',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>2),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'^abc|def$',
                     'tests'=>array($test1, $test2, $test3));
    }

    function data_for_test_alt_2() {
        $test1 = array( 'str'=>'abi',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>2),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'cdi',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>2),
                        'left'=>array(0),
                        'next'=>'');

        $test3 = array( 'str'=>'efi',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>2),
                        'left'=>array(0),
                        'next'=>'');

        $test4 = array( 'str'=>'ghi',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>2),
                        'left'=>array(0),
                        'next'=>'');

        $test5 = array( 'str'=>'yzi',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>-1),
                        'left'=>array(3),
                        'next'=>'aceg');

        return array('regex'=>'^(?:ab|cd|ef|gh)i$',
                     'tests'=>array($test1, $test2, $test3, $test4, $test5));
    }

    function data_for_test_assertions_simple_1() {
        $test1 = array( 'str'=>' abc',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>2),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>' 9bc',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>2),
                        'left'=>array(0),
                        'next'=>'');

        $test3 = array( 'str'=>'  b',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>0),
                        'left'=>array(2),
                        'next'=>'abcdefghijklmnopqrstuvwxyz');

        return array('regex'=>'^[a-z 0-9]\b[a-z 0-9]\B[a-z 0-9]',
                     'tests'=>array($test1, $test2, $test3));
    }

    function data_for_test_zero_length_loop() {
        $test1 = array( 'str'=>' a',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>1),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'[prefix] a',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>8),
                        'index_last'=>array(0=>9),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'^*[a-z 0-9](?:\b)+a${1,}',
                     'tests'=>array($test1, $test2));
    }

    function data_for_test_negative_charset() {
        $test1 = array( 'str'=>'abcd',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>0),
                        'left'=>array(3),
                        'next'=>' acdefghijklmnopqrstuvwxyz0123456789!?.,');

        $test2 = array( 'str'=>'axcd',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>3),
                        'left'=>array(0),
                        'next'=>'');

        $test3 = array( 'str'=>'aacde',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>3),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'^a[^b]cd$',
                     'tests'=>array($test1, $test2, $test3));
    }

    function data_for_test_case_sensitive() {
        $test1 = array( 'str'=>'abcd',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>0),
                        'left'=>array(3),
                        'next'=>'B');

        $test2 = array( 'str'=>'aBC',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>1),
                        'left'=>array(2),
                        'next'=>'c');

        return array('regex'=>'aBcD',
                     'tests'=>array($test1, $test2));
    }

    function data_for_test_case_insensitive() {
        $test1 = array( 'str'=>'abcd',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>3),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'aBcD',
                     'modifiers'=>'i',
                     'tests'=>array($test1));
    }

    //-----------------------------------------------tests for cases with ambiguity - subpatterns, quantifiers and backreferences-----------------------------//
    function data_for_test_empty_match() {
        $test1 = array( 'str'=>'abcd',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>0),
                        'index_last'=>array(0=>3,1=>3),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>0),
                        'index_last'=>array(0=>-1,1=>-1),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'(abcd|)',
                     'tests'=>array($test1, $test2));
    }

    function data_for_test_subpatt_nested() {
        $test1 = array( 'str'=>'abcbcd',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>1,2=>3,3=>4),
                        'index_last'=>array(0=>5,1=>4,2=>4,3=>4),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'ad',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>1,2=>-1,3=>-1),    // the quantifier is outside subpatterns 2 and 3 so they are not matched!
                        'index_last'=>array(0=>1,1=>0,2=>-2,3=>-2),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'^a((b(c))*)d$',
                     'tests'=>array($test1, $test2));
    }

    function data_for_test_subpatt_with_quant_nested() {
        $test1 = array( 'str'=>'12',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>0,2=>-1),
                        'index_last'=>array(0=>1,1=>1,2=>-2),
                        'left'=>array(2),
                        'next'=>'.');

        $test2 = array( 'str'=>'1',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>0,2=>-1),
                        'index_last'=>array(0=>0,1=>0,2=>-2),
                        'left'=>array(2),
                        'next'=>'.');

        return array('regex'=>'[+\-]?([0-9]+)?\.([0-9]+)',
                     'tests'=>array($test1, $test2));
    }

    function data_for_test_subpatt_concatenated() {
        $test1 = array( 'str'=>'_abcdef',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>1,1=>1,2=>3,3=>5),
                        'index_last'=>array(0=>6,1=>2,2=>4,3=>6),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'[prefix] abef',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>9,1=>9,2=>-1,3=>11),
                        'index_last'=>array(0=>12,1=>10,2=>-2,3=>12),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'(ab)(cd)?(ef)',
                     'tests'=>array($test1, $test2));
    }

    function data_for_test_subpatt_alternated() {
        $test1 = array( 'str'=>'abcdefgh',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>0,2=>0,3=>-1,4=>-1),
                        'index_last'=>array(0=>1,1=>1,2=>1,3=>-2,4=>-2),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'((ab)|(cd)|(efgh))',
                     'tests'=>array($test1));
    }

    function data_for_test_subpatt_quantifier_inside() {
        $test1 = array( 'str'=>'aaaaa',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>0),
                        'index_last'=>array(0=>4,1=>4),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'(a*)',
                     'tests'=>array($test1));
    }

    function data_for_test_subpatt_quantifier_outside() {
        $test1 = array( 'str'=>'aaaaa',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>4),
                        'index_last'=>array(0=>4,1=>4),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'(a)*',
                     'tests'=>array($test1));
    }

    function data_for_test_subpatt_tricky() {
        $test1 = array( 'str'=>'abc',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>0,2=>0,3=>1,4=>2),
                        'index_last'=>array(0=>2,1=>2,2=>0,3=>1,4=>2),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'abcabc',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>3,2=>3,3=>4,4=>5),
                        'index_last'=>array(0=>5,1=>5,2=>3,3=>4,4=>5),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'(([a*]|\b)([b*]|\b)([c*]|\b))+',
                     'tests'=>array($test1, $test2));
    }

    function data_for_test_quant_qu() {
        $test1 = array( 'str'=>'ac',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>1),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'abc',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>2),
                        'left'=>array(0),
                        'next'=>'');

        $test3 = array( 'str'=>'abbc',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>1),
                        'left'=>array(1),
                        'next'=>'c');

        return array('regex'=>'^ab?c$',
                     'tests'=>array($test1, $test2, $test3));
    }

    function data_for_test_quant_aster_1() {
        $test1 = array( 'str'=>'ab',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>1),
                        'left'=>array(1),    // 'left' takes priority
                        'next'=>'ab');

        $test2 = array( 'str'=>'abb',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>2),
                        'left'=>array(0),
                        'next'=>'');

        $test3 = array( 'str'=>'...ababababababababababbabababaabbbbbbbbbbbbaaaaaaaaaaaaabbbbbbbbbababababababb',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>3),
                        'index_last'=>array(0=>78),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'(?:a|b)*abb$',
                     'tests'=>array($test1, $test2, $test3));
    }

    function data_for_test_quant_aster_2() {
        $test1 = array( 'str'=>'abcabcabcabcabcabcabcabcabcabc',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>29),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'abcabcabcabcabcabcabcabcabcab',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>26),
                        'left'=>array(0),
                        'next'=>'');

        $test3 = array( 'str'=>'ab',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>-1),
                        'left'=>array(0),
                        'next'=>'');

        $test4 = array( 'str'=>'',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>-1),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'(?:abc)*',
                     'tests'=>array($test1, $test2, $test3, $test4));
    }

    function data_for_test_quant_plus() {
        $test1 = array( 'str'=>'ac',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>0),
                        'left'=>array(2),
                        'next'=>'b');

        $test2 = array( 'str'=>'abc',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>2),
                        'left'=>array(0),
                        'next'=>'');

        $test3 = array( 'str'=>'abbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbc',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>100),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'^ab+c$',
                     'tests'=>array($test1, $test2, $test3));
    }

    function data_for_test_quant_brace_finite() {
        $test1 = array( 'str'=>'abbbbbc',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>5),
                        'left'=>array(11),
                        'next'=>'b');

        $test2 = array( 'str'=>'abbbbbbbbbbbbbbbbbbbbbbbbbc',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>26),
                        'left'=>array(0),
                        'next'=>'');

        $test3 = array( 'str'=>'abbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbc',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>35),
                        'left'=>array(1),
                        'next'=>'c');

        return array('regex'=>'^ab{15,35}c$',
                     'tests'=>array($test1, $test2, $test3));
    }

    function data_for_test_quant_brace_infinite() {
        $test1 = array( 'str'=>'abbbbbc',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>5),
                        'left'=>array(11),
                        'next'=>'b');

        $test2 = array( 'str'=>'abbbbbbbbbbbbbbbbbbbbbbbbbc',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>26),
                        'left'=>array(0),
                        'next'=>'');

        $test3 = array( 'str'=>'abbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbc',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>103),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'^ab{15,}c$',
                     'tests'=>array($test1, $test2, $test3));
    }

    function data_for_test_quant_greedy() {
        $test1 = array('str'=>'abacd',
                       'results'=>array(array('is_match'=>true,    // result for backtracking engine
                                              'full'=>false,
                                              'index_first'=>array(0=>0),
                                              'index_last'=>array(0=>2),
                                              'left'=>array(4),
                                              'next'=>'b'),
                                        array('is_match'=>true,    // result for fa engine
                                              'full'=>false,
                                              'index_first'=>array(0=>0),
                                              'index_last'=>array(0=>4),
                                              'left'=>array(4),
                                              'next'=>'b')
                                        ));
        $test2 = array('str'=>'ababac',
                       'results'=>array(array('is_match'=>true,    // result for backtracking engine
                                              'full'=>false,
                                              'index_first'=>array(0=>0),
                                              'index_last'=>array(0=>2),
                                              'left'=>array(4),
                                              'next'=>'b'),
                                        array('is_match'=>true,    // result for fa engine
                                              'full'=>false,
                                              'index_first'=>array(0=>0),
                                              'index_last'=>array(0=>5),
                                              'left'=>array(1),
                                              'next'=>'d')
                                        ));

        return array('regex'=>'ab+[a-z]*bacd',
                     'tests'=>array($test1, $test2));
    }

    function data_for_test_characters_left_simple() {
        $test1 = array( 'str'=>'ab cd',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>4),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'abc',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>1),
                        'left'=>array(3),
                        'next'=>' ');

        $test3 = array( 'str'=>'a',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>0),
                        'left'=>array(4),
                        'next'=>'b');

        $test4 = array( 'str'=>'',
                        'is_match'=>false,
                        'full'=>false,
                        'index_first'=>array(0=>-1),
                        'index_last'=>array(0=>-2),
                        'left'=>array(5),
                        'next'=>'a');

        return array('regex'=>'ab\b cd',
                     'tests'=>array($test1, $test2, $test3, $test4));
    }

    function data_for_test_characters_left() {
        $test1 = array( 'str'=>'abefg',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>2),
                        'index_last'=>array(0=>4,1=>4),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'ab',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>-1),
                        'index_last'=>array(0=>1,1=>-2),
                        'left'=>array(1),
                        'next'=>'h');

        $test3 = array( 'str'=>'abe',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>-1),
                        'index_last'=>array(0=>2,1=>-2),
                        'left'=>array(2),
                        'next'=>'f');

        return array('regex'=>'ab(cd|efg|h)',
                     'tests'=>array($test1, $test2, $test3));
    }

    function data_for_test_backrefs_simple() {
        $test1 = array( 'str'=>'abcabcabcabc',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>0,2=>0),
                        'index_last'=>array(0=>11,1=>5,2=>2),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'abcabc',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>0,2=>0),
                        'index_last'=>array(0=>5,1=>5,2=>2),
                        'left'=>array(6),
                        'next'=>'a');    // backref #1 not captured at all

        $test3 = array( 'str'=>'abcabcab',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>0,2=>0),
                        'index_last'=>array(0=>7,1=>5,2=>2),
                        'left'=>array(4),
                        'next'=>'c');    // backref #1 captured partially

        return array('regex'=>'((abc)\2)\1',
                     'tests'=>array($test1, $test2, $test3));
    }

    function data_for_test_backrefs_alternated() {
        $test1 = array( 'str'=>'abab',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>0,2=>-1,3=>2),
                        'index_last'=>array(0=>3,1=>1,2=>-2,3=>3),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'cdcd',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>-1,2=>0,3=>2),
                        'index_last'=>array(0=>3,1=>-2,2=>1,3=>3),
                        'left'=>array(0),
                        'next'=>'');

        $test3 = array( 'str'=>'aba',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>0,2=>-1,3=>-1),
                        'index_last'=>array(0=>2,1=>1,2=>-2,3=>-2),
                        'left'=>array(1),
                        'next'=>'b');

        $test4 = array( 'str'=>'abc',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>0,2=>-1,3=>-1),
                        'index_last'=>array(0=>1,1=>1,2=>-2,3=>-2),
                        'left'=>array(2),
                        'next'=>'a');

        return array('regex'=>'(?:(ab)|(cd))(\1|\2)',
                     'tests'=>array($test1, $test2, $test3, $test4));
    }

    function data_for_test_backrefs_quantified() {
        $test1 = array( 'str'=>'ababcdababcdababcdababcd',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>12,2=>12),
                        'index_last'=>array(0=>23,1=>17,2=>13),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'cdcd',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>-1,2=>-1),
                        'index_last'=>array(0=>-1,1=>-2,2=>-2),
                        'left'=>array(10000000),                    // TODO: standardize this value
                        'next'=>'');

        return array('regex'=>'((ab)\2cd)*\1',
                     'tests'=>array($test1, $test2));
    }

    function data_for_test_backrefs_full_and_partial() {
        $test1 = array( 'str'=>'abcdabcd',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>0),
                        'index_last'=>array(0=>7,1=>3),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'abcdab',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>0),
                        'index_last'=>array(0=>5,1=>3),
                        'left'=>array(2),
                        'next'=>'c');

        $test3 = array( 'str'=>'abcd',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>0),
                        'index_last'=>array(0=>3,1=>3),
                        'left'=>array(4),
                        'next'=>'a');

        $test4 = array( 'str'=>'abc',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>-1),
                        'index_last'=>array(0=>2,1=>-2),
                        'left'=>array(5),
                        'next'=>'d');

        return array('regex'=>'(abcd)\1',
                     'tests'=>array($test1, $test2, $test3, $test4));
    }

    function data_for_test_backrefs_noway() {
        $test1 = array( 'str'=>'abxyabab',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>0),
                        'index_last'=>array(0=>7,1=>1),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'abxycd',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>0),
                        'index_last'=>array(0=>5,1=>1),
                        'left'=>array(0),
                        'next'=>'');

        $test3 = array( 'str'=>'cdxyabab',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>-1),
                        'index_last'=>array(0=>5,1=>-2),
                        'left'=>array(10000000),
                        'next'=>'');

        $test4 = array( 'str'=>'cdxycd',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>-1),
                        'index_last'=>array(0=>5,1=>-2),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'(?:(ab)|cd)xy(?:ab\1|cd)',
                     'tests'=>array($test1, $test2, $test3, $test4));
    }

    function data_for_test_backrefs_alt_in_subpatt_1() {
        $test1 = array( 'str'=>'Do hats eat cats?',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>-1),
                        'index_last'=>array(0=>2,1=>-2),
                        'left'=>array(12),
                        'next'=>'cbr');

        $test2 = array( 'str'=>'Do cats',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>6),
                        'index_last'=>array(0=>6,1=>6),
                        'left'=>array(10),
                        'next'=>' ');

        return array('regex'=>'Do (?:[cbr]at(s|)) eat (?:[cbr]at\1)\?',
                     'tests'=>array($test1, $test2));
    }

    function data_for_test_backrefs_alt_in_subpatt2() {
        $test1 = array( 'str'=>'0x',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>-1),
                        'index_last'=>array(0=>0,1=>-2),
                        'left'=>array(9),
                        'next'=>'a');

        $test2 = array( 'str'=>'0as',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>-1),
                        'index_last'=>array(0=>1,1=>-2),
                        'left'=>array(8),
                        'next'=>'b');

        $test3 = array( 'str'=>'0defab',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>-1),
                        'index_last'=>array(0=>3,1=>-2),
                        'left'=>array(12),
                        'next'=>'g');

        return array('regex'=>'0(abc|defghx)[0-9]{3}\1',
                     'tests'=>array($test1, $test2, $test3));
    }

    function data_for_test_backrefs_quant_in_subpatt() {
        $test1 = array( 'str'=>'0x',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>-1),
                        'index_last'=>array(0=>0,1=>-2),
                        'left'=>array(13),
                        'next'=>'a');

        $test2 = array( 'str'=>'0aaaaaaz',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>1),
                        'index_last'=>array(0=>6,1=>6),
                        'left'=>array(9),
                        'next'=>'0123456789');

        return array('regex'=>'0(a{5,10})[0-9]{3}\1',
                     'tests'=>array($test1, $test2));
    }

    function data_for_test_backrefs_subpatt_modifying() {
        $test1 = array( 'str'=>'ababba',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>3),
                        'index_last'=>array(0=>5,1=>5),
                        'left'=>array(4),
                        'next'=>'x');

        $test2 = array( 'str'=>'ababbaxbba',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>3),
                        'index_last'=>array(0=>9,1=>5),
                        'left'=>array(0),
                        'next'=>'');

        $test3 = array( 'str'=>'abab',
                        'is_match'=>true,
                        'full'=>false,
                        'index_first'=>array(0=>0,1=>1),
                        'index_last'=>array(0=>3,1=>2),
                        'left'=>array(6),
                        'next'=>'b');

        return array('regex'=>'(a|b\1)+x\1',
                     'tests'=>array($test1, $test2, $test3));
    }

    function data_for_test_backrefs_tricky_1() {
        $test1 = array( 'str'=>'abxab',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>2,2=>0),
                        'index_last'=>array(0=>4,1=>4,2=>1),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'xabxab',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>1,1=>3,2=>1),
                        'index_last'=>array(0=>5,1=>5,2=>2),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'(x\2|(ab))+',
                     'tests'=>array($test1, $test2));
    }

    function data_for_test_backrefs_tricky_2() {
        $test1 = array( 'str'=>'aaa',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>2),
                        'index_last'=>array(0=>2,1=>2),
                        'left'=>array(0),
                        'next'=>'');

        $test2 = array( 'str'=>'ababba',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>3),
                        'index_last'=>array(0=>5,1=>5),
                        'left'=>array(0),
                        'next'=>'');

        $test3 = array( 'str'=>'ababbabbba',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>6),
                        'index_last'=>array(0=>9,1=>9),
                        'left'=>array(0),
                        'next'=>'');


        $test4 = array( 'str'=>'ababbabbbabbbba',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>10),
                        'index_last'=>array(0=>14,1=>14),
                        'left'=>array(0),
                        'next'=>'');

        $test5 = array( 'str'=>'ababbabbbabbbb',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0,1=>6),
                        'index_last'=>array(0=>9,1=>9),
                        'left'=>array(0),
                        'next'=>'');

        $test6 = array( 'str'=>'',
                        'is_match'=>false,
                        'full'=>false,
                        'index_first'=>array(0=>-1,1=>-2),
                        'index_last'=>array(0=>-1,1=>-2),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'(a|b\1)+',
                     'tests'=>array($test1, $test2, $test3, $test4, $test5, $test6));
    }

    //-----------------------------------------------------------------tests for acceptance-------------------------------------------------------------------//
    function data_for_test_node_assert() {
        $test1 = array( 'str'=>'abcd',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>1),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'ab(?=cd)',
                     'tests'=>array($test1));
    }

    function data_for_test_node_cond_subpatt() {
        $test1 = array( 'str'=>'11-aaa-11',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>8),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'(?(?=[^a-z]*[a-z])\d{2}-[a-z]{3}-\d{2}|\d{2}-\d{2}-\d{2})',
                     'tests'=>array($test1));
    }

    function data_for_test_shortanswer_notation() {
        $test1 = array( 'str'=>'/+fghjhj4587abc*',
                        'is_match'=>true,
                        'full'=>true,
                        'index_first'=>array(0=>0),
                        'index_last'=>array(0=>15),
                        'left'=>array(0),
                        'next'=>'');

        return array('regex'=>'^(?:/\+.*abc\*)$',
                     'tests'=>array($test1));
    }
}
?>