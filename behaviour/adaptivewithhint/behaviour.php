<?php


defined('MOODLE_INTERNAL') || die();


/**
 * Question behaviour for question with hints in adaptive mode.
 *
 * Behaviour variables:
 * _try - number of submissions (inherited from adaptive)
 * _rawfraction - fraction for the step without penalties (inherited from adaptive)
 * _hashint - there was hint requested in the step
 * _render_<hintname> - true if hint with hintname should be rendered when rendering question next time
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

require_once($CFG->dirroot . '/question/behaviour/adaptive/behaviour.php');
require_once($CFG->dirroot . '/question/type/preg/question.php');//Contains question_with_specific_hints interface for now

class qbehaviour_adaptivewithhint extends qbehaviour_adaptive {
    const IS_ARCHETYPAL = false;

    public static function get_required_behaviours() {
        return array('qbehaviour_adaptive');
    }

    public function is_compatible_question(question_definition $question) {//TODO - it should also require question_with_specific_hints interface, but for now it is able to return only one type
        return ($question instanceof question_automatically_gradable) && ($question instanceof question_with_specific_hints);
    }

    public function get_expected_data() {
        $expected = parent::get_expected_data();

        if ($this->qa->get_state()->is_active()) {//returning an array of hint buttons
            foreach ($this->question->available_specific_hint_types() as $hintkey => $hintdescription) {
                $expected[$hintkey.'btn'] = PARAM_BOOL;
            }
        }
        return $expected;
    }

    public function adjust_display_options(question_display_options $options) {
        parent::adjust_display_options($options);//there seems to nothing to be done until question_display_options will be passed to specific_feedback function of question renderer
        //maybe add correctness if there were a response there
    }

    ////Summarise functions
    public function summarise_action(question_attempt_step $step) {
        //Summarise hint action
        foreach ($this->question->available_specific_hint_types() as $hintkey => $hintdescription) {
            if ($step->has_behaviour_var($hintkey.'btn')) {
                return $this->summarise_hint($step, $hintkey, $hintdescription);
            }
        }

        return parent::summarise_action($step);
    }

    public function summarise_hint(question_attempt_step $step, $hintkey, $hintdescription) {
        $response = $step->get_qt_data();
        $a = new stdClass();
        $a->hint = $hintdescription;
        $a->response = $this->question->summarise_response($response);
        $a->penalty = $this->question->penalty_for_specific_hint($hintkey, $response);
        return get_string('hintused', 'qbehaviour_adaptivewithhint', $a);
    }

    ////Process functions
    public function process_action(question_attempt_pending_step $pendingstep) {
        foreach ($this->question->available_specific_hint_types() as $hintkey => $hintdescription) {
            if ($pendingstep->has_behaviour_var($hintkey.'btn')) {
                return $this->process_hint($pendingstep, $hintkey);
            }
        }

        return parent::process_action($pendingstep);
    }

    public function process_hint(question_attempt_pending_step $pendingstep, $hintkey) {
        $status = $this->process_save($pendingstep);

        $response = $pendingstep->get_qt_data();
        if (!$this->question->hint_available($hintkey, $response)) {//Couldn't compute hint for such response
            return question_attempt::DISCARD;
        }

        //process data from last graded state (e.g. submit)
        $prevstep = $this->get_graded_step();
        if (!is_null($prevstep)) {//TODO - deal with situation where hint requested for response that is correct already
            if ($prevstep->get_state() == question_state::$complete) {
                $pendingstep->set_state(question_state::$complete);
            } else {
                $pendingstep->set_state(question_state::$todo);
            }
            $pendingstep->set_behaviour_var('_rawfraction', $prevstep->get_behaviour_var('_rawfraction'));
        } else {//hint requested before submitting anything
            $pendingstep->set_fraction(0);
            $pendingstep->set_behaviour_var('_rawfraction', 0);
            $pendingstep->set_state(question_state::$todo);
        }

        //Set hint variables
        $pendingstep->set_behaviour_var('_hashint',true);
        $prevtotal = $this->qa->get_last_behaviour_var('_totalpenalties', 0);
        $penalty = $this->question->penalty_for_specific_hint($hintkey, $response);
        $pendingstep->set_behaviour_var('_penalty', $penalty);
        $newtotal = $prevtotal + $penalty;
        $pendingstep->set_behaviour_var('_totalpenalties', $newtotal);
        $pendingstep->set_behaviour_var('_render_'.$hintkey, true);


        $prevbest = $pendingstep->get_fraction();
        if (is_null($prevbest)) {
            $prevbest = 0;
        }
        //fraction = rawfraction - totalpenalties (already collected)
        $pendingstep->set_fraction(max($prevbest, $this->adjusted_fraction($pendingstep->get_behaviour_var('_rawfraction'),  $newtotal)));

        $pendingstep->set_new_response_summary($this->question->summarise_response($response));

        return question_attempt::KEEP;
    }

    //Overload process_submit to recalculate fraction and add _totalpenalties
    public function process_submit(question_attempt_pending_step $pendingstep) {

        //Must find out prevbest before parent function get in it's fraction
        $prevbest = $pendingstep->get_fraction();
        if (is_null($prevbest)) {
            $prevbest = 0;
        }

        $status = parent::process_submit($pendingstep);

        $response = $pendingstep->get_qt_data();
        if ($this->question->is_gradable_response($response) && $status == question_attempt::KEEP) {//state was graded
            $prevtotal = $this->qa->get_last_behaviour_var('_totalpenalties', 0);
            //fraction = rawfraction - totalpenalties (already collected)
            $pendingstep->set_fraction(max($prevbest, $this->adjusted_fraction($pendingstep->get_behaviour_var('_rawfraction'), $prevtotal)));
            $pendingstep->set_behaviour_var('_totalpenalties', $prevtotal + $this->question->penalty);//for submit penalty is added after fraction is calculated
            $pendingstep->set_behaviour_var('_penalty', $this->question->penalty);
        }
        return $status;
    }

    //Overload process_finish to recalculate fraction and add _totalpenalties
    public function process_finish(question_attempt_pending_step $pendingstep) {

        //Must find out prevbest before parent function get in it's fraction
        $prevbest = $this->qa->get_fraction();
        if (is_null($prevbest)) {
            $prevbest = 0;
        }

        $status = parent::process_finish($pendingstep);

        if ($pendingstep->get_state() != question_state::$gaveup) {//state was graded
            $laststep = $this->qa->get_last_step();
            $total = $this->qa->get_last_behaviour_var('_totalpenalties', 0);
            if (!$laststep->has_behaviour_var('_try')) {//Submitting ( not previous grading) resulted in finishing, so need to apply penalty
                $total += $this->question->penalty;
                $pendingstep->set_behaviour_var('_penalty', $this->question->penalty);
            }
            $pendingstep->set_behaviour_var('_totalpenalties', $total);
            //Must substract by one submission penalty less , to account for one lawful submission
            $pendingstep->set_fraction(max($prevbest, $this->adjusted_fraction($pendingstep->get_behaviour_var('_rawfraction'), $total - $this->question->penalty)));
        }
        return question_attempt::KEEP;
    }

    //Overloading this to have easy 'no penalties' adaptive version
    protected function adjusted_fraction($fraction, $penalty) {
        return $fraction - $penalty;
    }

    //Overload get_graded_step since hinting changes grade too, we need to use last one with grade
    public function get_graded_step() {
        $step = $this->qa->get_last_step_with_behaviour_var('_totalpenalties');//_totalpenalties is set only when grading, i.e. on hinting, finishing and submitting
        if ($step->has_behaviour_var('_totalpenalties')) {
            return $step;
        } else {
            return null;
        }
    }
}