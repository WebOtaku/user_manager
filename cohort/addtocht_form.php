<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class addtocht_form extends moodleform {

    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        list($user) = $this->_customdata;

        $full_name = "$user->lastname $user->firstname $user->middlename";

        $full_name_html = '
            <div id="fitem_id_fullname" class="form-group row  fitem">
                <div class="col-md-3">
                    <span class="float-sm-right text-nowrap"></span>
                    <span class="col-form-label d-inline">'.get_string('user', 'admin').' </span>
                </div>
                <div class="col-md-9 form-inline felement" data-fieldtype="static">
                    <div class="form-control-static">'.$full_name.'</div>
                    <div class="form-control-feedback invalid-feedback" id="id_error_fullname"></div>
                </div>
            </div>
        ';

        $mform->addElement('html', $full_name_html);

        $options = array('multiple' => true);

        $mform->addElement('cohort', 'chtids', get_string('cohorts', 'cohort'), $options);
        $mform->addRule('chtids', get_string('required'), 'required', null, 'client');

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_LOCALURL);

        $this->add_action_buttons(true, get_string('add', 'block_user_manager'));
    }

}
