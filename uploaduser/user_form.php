<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->dirroot.'/admin/tool/uploaduser/user_form.php');

use block_user_manager\table;
use block_user_manager\uploaduser;

class um_admin_uploaduser_form extends moodleform {
    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;
        list($stdfields, $systemfields, $helpfields, $baseurl) = $this->_customdata;

        $mform->addElement('header', 'instructionheader', get_string('instruction', 'block_user_manager'));

        $instruction = uploaduser::get_uploaduser_instruction();

        $mform->addElement('html', $instruction);
        $mform->setExpanded('instructionheader', false);

        $mform->addElement('header', 'validfieldsheader', get_string('validfields', 'block_user_manager'));

        $validfields = table::generate_valid_fields_table($stdfields, $systemfields, $baseurl, $helpfields);

        $mform->addElement('html', $validfields);
        $mform->setExpanded('validfieldsheader', false);

        $mform->addElement('header', 'settingsheader', get_string('upload'));

        $mform->addElement('filepicker', 'userfile', get_string('file'));
        $mform->addRule('userfile', null, 'required');

        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $choices = array('10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000);
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'tool_uploaduser'), $choices);
        $mform->setType('previewrows', PARAM_INT);

        $this->add_action_buttons(false, get_string('upload'));
    }
}

class um_select_selectaction_form extends moodleform {
    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('selectaction', 'block_user_manager'));

        $choices = array(
            1 => get_string('exportcsv', 'block_user_manager'),
            2 => get_string('exportxls', 'block_user_manager'),
            3 => get_string('uploaduser', 'block_user_manager')
        );
        $mform->addElement('select', 'action', get_string('action'), $choices);
        $mform->setType('action', PARAM_INT);

        $choices = array('10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000);
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'tool_uploaduser'), $choices);
        $mform->setType('previewrows', PARAM_INT);

        $this->add_action_buttons(true, get_string('complete', 'block_user_manager'));
    }
}
