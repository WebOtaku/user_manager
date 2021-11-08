<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

use block_user_manager\html;

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

        $mform->addElement('html', html::generate_label_with_html(
            get_string('user', 'admin'), $full_name
        ));

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
