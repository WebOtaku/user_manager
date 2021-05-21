<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->dirroot.'/admin/tool/uploaduser/user_form.php');

use block_user_manager\table, block_user_manager\uploaduser;

class um_select_upload_method_form extends moodleform {
    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;
        list($systemfields, $helpfields, $required_fields) = $this->_customdata;

        $mform->addElement('header', 'instructionheader', get_string('instruction', 'block_user_manager'));

        $instruction = uploaduser::get_uploaduser_instruction($systemfields, $helpfields, $required_fields);

        $mform->addElement('html', $instruction);
        $mform->setExpanded('instructionheader', false);

        $mform->addElement('header', 'settingsheader', get_string('selectaction', 'block_user_manager'));

        $choices = array(
            1 => "Upload from file",
            2 => "Upload from 1C"
        );
        $mform->addElement('select', 'upload_method', "Upload method", $choices);
        $mform->setType('upload_method', PARAM_INT);

        $choices = array('10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000);
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'tool_uploaduser'), $choices);
        $mform->setType('previewrows', PARAM_INT);

        $this->add_action_buttons(true, get_string('complete', 'block_user_manager'));
    }
}

class um_admin_uploaduser_form extends moodleform {
    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;
        list($stdfields, $systemfields, $helpfields, $required_fields, $prffields) = $this->_customdata;

        $mform->addElement('header', 'instructionheader', get_string('instruction', 'block_user_manager'));

        $instruction = uploaduser::get_uploaduser_instruction($systemfields, $helpfields, $required_fields);

        $mform->addElement('html', $instruction);
        $mform->setExpanded('instructionheader', false);

        $mform->addElement('header', 'validfieldsheader', get_string('validfields', 'block_user_manager'));

        $validfields = table::generate_valid_fields_table($stdfields, $systemfields, $helpfields, $prffields);

        $mform->addElement('html', $validfields);
        $mform->setExpanded('validfieldsheader', false);

        $mform->addElement('header', 'settingsheader', get_string('upload'));

        $required_fields = uploaduser::get_fields_with_helper($systemfields, $helpfields, $required_fields);

        $a = new stdClass();
        $a->emailhelper = uploaduser::get_field_helper($systemfields, $helpfields, 'email');
        $a->requiredfields = implode(', ', $required_fields);

        $mform->addElement('html', '<div class="alert alert-primary um-alert-inform" role="alert">
            '. get_string('requiredfields', 'block_user_manager', $a) .'</div>');

        $mform->addElement('checkbox', 'email_required', get_string('emailrequired', 'block_user_manager'));

        $mform->addElement('filepicker', 'userfile', get_string('file') . ' (.csv)');
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

class um_select_action_form extends moodleform {
    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;
        list($systemfields, $helpfields, $required_fields, $faculties, $groups) = $this->_customdata;

        $mform->addElement('header', 'instructionheader', get_string('instruction', 'block_user_manager'));

        $instruction = uploaduser::get_uploaduser_instruction($systemfields, $helpfields, $required_fields);

        $mform->addElement('html', $instruction);
        $mform->setExpanded('instructionheader', false);

        $mform->addElement('header', 'settingsheader', get_string('selectaction', 'block_user_manager'));

        $choices = array(
            1 => get_string('exportcsv', 'block_user_manager'),
            2 => get_string('exportcsvad', 'block_user_manager'),
            3 => get_string('exportxls', 'block_user_manager'),
            4 => get_string('uploaduser', 'block_user_manager')
        );
        $mform->addElement('select', 'action', get_string('action'), $choices);
        $mform->setType('action', PARAM_INT);

        $choices = array_combine($faculties, $faculties);
        $mform->addElement('select', 'faculty', get_string('faculty', 'block_user_manager'), $choices);
        $mform->setType('faculty', PARAM_TEXT);

        $choices = array_combine($groups, $groups);
        $mform->addElement('autocomplete', 'group', get_string('group', 'block_user_manager'), $choices);
        $mform->setType('group', PARAM_TEXT);

        $auths = core_component::get_plugin_list('auth');
        $enabled = get_string('pluginenabled', 'core_plugin');
        $disabled = get_string('plugindisabled', 'core_plugin');
        $authoptions = array($enabled => array(), $disabled => array());
        $cannotchangepass = array();
        $cannotchangeusername = array();
        $userid = -1;
        foreach ($auths as $auth => $unused) {
            $authinst = get_auth_plugin($auth);

            if (!$authinst->is_internal()) {
                $cannotchangeusername[] = $auth;
            }

            $passwordurl = $authinst->change_password_url();
            if (!($authinst->can_change_password() && empty($passwordurl))) {
                if ($userid < 1 and $authinst->is_internal()) {
                    // This is unlikely but we can not create account without password
                    // when plugin uses passwords, we need to set it initially at least.
                } else {
                    $cannotchangepass[] = $auth;
                }
            }
            if (is_enabled_auth($auth)) {
                $authoptions[$enabled][$auth] = get_string('pluginname', "auth_{$auth}");
            } else {
                $authoptions[$disabled][$auth] = get_string('pluginname', "auth_{$auth}");
            }
        }

        $mform->addElement('selectgroups', 'auth', get_string('chooseauthmethod', 'auth'), $authoptions);
        $mform->addHelpButton('auth', 'chooseauthmethod', 'auth');

        $choices = array('10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000);
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'tool_uploaduser'), $choices);
        $mform->setType('previewrows', PARAM_INT);

        $this->add_action_buttons(true, get_string('complete', 'block_user_manager'));
    }
}
