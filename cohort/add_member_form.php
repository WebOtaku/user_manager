<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class add_member_form extends moodleform {

    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        list($user) = $this->_customdata;

        $mform->addElement('header', 'header', get_string('addtocht', 'block_user_manager'));

        $attributes = array(
            'value' => "$user->lastname $user->firstname $user->middlename",
            'disabled' => true,
            'size' => '32'
        );

        $mform->addElement('text', 'fullname', get_string('user', 'admin'), $attributes);
        $mform->setType('fullname', PARAM_TEXT);

        $options = array('multiple' => true);

        $mform->addElement('cohort', 'chtids', get_string('cohorts', 'cohort'), $options);
        $mform->addRule('chtids', get_string('required'), 'required', null, 'client');

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('hidden', 'sort');
        $mform->setType('sort', PARAM_INT);

        $mform->addElement('hidden', 'dir');
        $mform->setType('dir', PARAM_INT);

        $mform->addElement('hidden', 'perpage');
        $mform->setType('perpage', PARAM_INT);

        $mform->addElement('hidden', 'page');
        $mform->setType('page', PARAM_INT);

        $mform->addElement('hidden', 'chtid');
        $mform->setType('chtid', PARAM_INT);

        $mform->addElement('hidden', 'userfilter');
        $mform->setType('userfilter', PARAM_INT);

        $mform->addElement('hidden', 'chtsreturnurl');
        $mform->setType('chtsreturnurl', PARAM_INT);

        $this->add_action_buttons(true, get_string('add', 'block_user_manager'));
    }

}
