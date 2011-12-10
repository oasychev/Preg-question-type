<?php  //$Id: upgrade.php,v 1.2.2.2 2009/08/31 16:37:52 arborrow Exp $

// This file keeps track of upgrades to 
// the preg qtype plugin
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_qtype_preg_upgrade($oldversion=0) {

    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2010072201) {

        /// Define field exactmatch to be added to question_preg
        $table = new xmldb_table('question_preg');
        $field = new xmldb_field('exactmatch', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'rightanswer');
        /// Launch add field exactmatch
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2010072201, 'qtype', 'preg');
    }

    if ($oldversion < 2010080800) {
        $table = new xmldb_table('question_preg');
        /// Define field usehint to be added to question_preg
        $field = new xmldb_field('usehint', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'exactmatch');
        // Conditionally launch add field usehint
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        /// Define field hintpenalty to be added to question_preg
        $field = new xmldb_field('hintpenalty', XMLDB_TYPE_FLOAT, '4, 2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'usehint');
        /// Launch add field hintpenalty
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2010080800, 'qtype', 'preg');
    }

    if ($oldversion < 2010081600) {
        $table = new xmldb_table('question_preg');
        // Define field hintgradeborder to be added to question_preg
        $field = new xmldb_field('hintgradeborder', XMLDB_TYPE_FLOAT, '4, 2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', 'hintpenalty');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Define field engine to be added to question_preg
         $field = new xmldb_field('engine', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, 'preg_php_matcher', 'hintgradeborder');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Rename field rightanswer on table question_preg to correctanswer
        $table = new xmldb_table('question_preg');
        $field = new xmldb_field('rightanswer', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'usecase');
        // Launch rename field rightanswer
        $dbman->rename_field($table, $field, 'correctanswer');

        // preg savepoint reached
        upgrade_plugin_savepoint(true, 2010081600, 'qtype', 'preg');
    }

    if ($oldversion < 2011111900) {

        // Define field notation to be added to question_preg
        $table = new xmldb_table('question_preg');
        $field = new xmldb_field('notation', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, 'native', 'engine');

        // Conditionally launch add field notation
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // preg savepoint reached
        upgrade_plugin_savepoint(true, 2011111900, 'qtype', 'preg');
    }

    return true;

}

?>
