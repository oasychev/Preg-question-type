<?php


defined('MOODLE_INTERNAL') || die();


/**
 * Question behaviour for question with hints in adaptive mode (no penalties).
 *
 * Behaviour variables:
 * _try - number of submissions (inherited from adaptive)
 * _rawfraction - fraction for the step without penalties (inherited from adaptive)
 * _hashint - there was hint requested in the step
 * _<hintname>count - count of hint named <hintname>
 * _penalty - penalty added in this state (used for rendering and summarising mainly)
 * _totalpenalties - sum of all penalties already done
 *
 * Behaviour controls:
 * submit - submit answer to grading (inherited from adaptive)
 * <hintname>btn - buttons to get hint <hintname>
 *
 * @copyright  2011 Oleg Sychev Volgograd State Technical University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/question/behaviour/adaptivewithhint/behaviour.php');

class qbehaviour_adaptivehintnopenalties extends qbehaviour_adaptivewithhint {
    const IS_ARCHETYPAL = false;

    public function summarise_hint(question_attempt_step $step, $hintkey, $hintdescription) {
        $response = $step->get_qt_data();
        $a = new stdClass();
        $a->hint = $hintdescription;
        $a->response = $this->question->summarise_response($response);
        return get_string('hintused', 'qbehaviour_adaptivehintnopenalties', $a);
    }

    //Overloading this to have easy 'no penalties' adaptive version
    protected function adjusted_fraction($fraction, $penalty) {
        return $fraction;
    }

}