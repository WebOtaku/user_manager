<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->dirroot.'/admin/tool/uploaduser/user_form.php');
require_once('../locallib.php');

use block_user_manager\cohort1c_lib1c;
use block_user_manager\service;
use block_user_manager\table, block_user_manager\uploaduser;
use block_user_manager\string_operation;

class um_select_upload_method_form extends moodleform {
    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;
        list($groups) = $this->_customdata;

        $choices = array(
            UPLOAD_METHOD_1C => get_string('upfrom1c', 'block_user_manager'),
            UPLOAD_METHOD_FILE => get_string('upfromfile', 'block_user_manager'),
        );

        $attributes = array(
            ' size' => count($choices), // Фикс игнорирования аттрибута size это добавление пробела до или после слова size
            'class' => 'um-custom-select'
        );

        $select = $mform->addElement('select', 'upload_method', get_string('uploadmethod', 'block_user_manager'),
            $choices, $attributes);
        $select->setSelected(UPLOAD_METHOD_1C);
        $mform->setType('upload_method', PARAM_INT);

        $choices = array_combine($groups, $groups);
        $mform->addElement('autocomplete', 'group', get_string('group', 'block_user_manager'),
            $choices, array('class' => 'um-autocomplete-group'));
        $mform->hideIf('group', 'upload_method', 'eq', UPLOAD_METHOD_FILE);
        $mform->setType('group', PARAM_TEXT);

        $choices = array('10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000);
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'tool_uploaduser'), $choices);
        $mform->hideIf('previewrows', 'upload_method', 'eq', UPLOAD_METHOD_FILE);
        $mform->setType('previewrows', PARAM_INT);

        $this->add_action_buttons(false, get_string('further', 'block_user_manager'));
    }
}

class um_admin_uploaduser_form extends moodleform {
    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;
        list($stdfields, $systemfields, $helpfields, $required_fields, $prffields, $prflabels) = $this->_customdata;

        $mform->addElement('header', 'validfieldsheader', get_string('validfields', 'block_user_manager'));

        $validfields = table::generate_valid_fields_table($stdfields, $systemfields, $helpfields, $prffields, $prflabels);

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

        $this->add_action_buttons(true, get_string('further', 'block_user_manager'));
    }
}

class um_uploaduser_action_form extends admin_uploaduser_form2 {
    /**
     * Form definition
     */
    public function definition()
    {
        $mform = $this->_form;
        $data = (object)$this->_customdata;

        parent::definition($this, $data);

        // В зависимости от версии moodle в стандартной форме загрузки для скрытия полей используется
        // метод disabledIf(..) или метод hideIf(..)

        // Убираем действие метода disabledIf(..), т.е. делаем так чтобы поле email не блокировалось
        // при определённых значениях поля uutype: UU_USER_ADD_UPDATE и UU_USER_UPDATE
        if (isset($mform->_dependencies)) {
            if ($index = array_search('email', $mform->_dependencies['uutype']['eq'][UU_USER_ADD_UPDATE])) {
                unset($mform->_dependencies['uutype']['eq'][UU_USER_ADD_UPDATE][$index]);
            }
            if ($index = array_search('email', $mform->_dependencies['uutype']['eq'][UU_USER_UPDATE])) {
                unset($mform->_dependencies['uutype']['eq'][UU_USER_UPDATE][$index]);
            }
        }

        // ИЛИ

        // Убираем действие метода hideIf(..), т.е. делаем так чтобы поле email не блокировалось
        // при определённых значениях поля uutype: UU_USER_ADD_UPDATE и UU_USER_UPDATE
        if (isset($mform->_hideifs)) {
            if ($index = array_search('email', $mform->_hideifs['uutype']['eq'][UU_USER_ADD_UPDATE])) {
                unset($mform->_hideifs['uutype']['eq'][UU_USER_ADD_UPDATE][$index]);
            }
            if ($index = array_search('email', $mform->_hideifs['uutype']['eq'][UU_USER_UPDATE])) {
                unset($mform->_hideifs['uutype']['eq'][UU_USER_UPDATE][$index]);
            }
        }

        $mform->setDefault('email', $data->data['default_email']);

        $mform->addElement('hidden', 'group');
        $mform->setType('group', PARAM_INT);

        $this->set_data($data);
    }
}

class um_select_action_form extends moodleform {
    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;

        list($faculties, $groups, $from, $group, $group_info, $edu_forms) = $this->_customdata;

        $choices = array(
            ACTION_EXPORTCSV => get_string(ACTION_EXPORTCSV, 'block_user_manager'),
            ACTION_EXPORTCSVAD => get_string(ACTION_EXPORTCSVAD, 'block_user_manager'),
            ACTION_EXPORTXLS => get_string(ACTION_EXPORTXLS, 'block_user_manager'),
            ACTION_UPLOADUSER => get_string(ACTION_UPLOADUSER, 'block_user_manager')
        );
        $attributes = array(
            ' size' => count($choices), // Фикс игнорирования аттрибута size это добавление пробела до или после слова size
            'class' => 'um-custom-select'
        );
        $mform->addElement('select', 'action', get_string('action'), $choices, $attributes);
        $mform->getElement('action')->setSelected(ACTION_EXPORTCSV);
        $mform->setType('action', PARAM_TEXT);

        $choices = array_combine($faculties, $faculties);
        $mform->addElement('select', 'faculty', get_string('faculty', 'block_user_manager'), $choices);
        $mform->hideIf('faculty', 'action', 'eq', ACTION_EXPORTCSV);
        $mform->hideIf('faculty', 'action', 'eq', ACTION_EXPORTXLS);
        $mform->hideIf('faculty', 'action', 'eq', ACTION_UPLOADUSER);
        $mform->setType('faculty', PARAM_TEXT);

        // $groups = array_keys($groups); // TODO: Заглушка

        $choices = array_combine($groups, $groups);
        $mform->addElement('autocomplete', 'group', get_string('group', 'block_user_manager'),
            $choices, array('class' => 'um-autocomplete-group'));
        if ($from === UPLOAD_METHOD_FILE) {
            $mform->hideIf('group', 'action', 'eq', ACTION_EXPORTCSV);
            $mform->hideIf('group', 'action', 'eq', ACTION_EXPORTCSVAD);
        }
//        $mform->hideIf('group', 'action', 'eq', ACTION_UPLOADUSER);
        $mform->setType('group', PARAM_TEXT);

        if ($from === UPLOAD_METHOD_1C || $from === UPLOAD_METHOD_FILE) {
            if ($group) {
                if (in_array($group, $groups))
                    $mform->setDefault('group', $group);
            } else {
                if (count($groups))
                    $mform->setDefault('group', $groups[0]);
            }

            /*if (isset($group_info['Факультет']) && in_array($group_info['Факультет'], $faculties)) {
                $mform->setDefault('faculty', $group_info['Факультет']);
            }*/

            if (isset($group_info['Факультет']) && count($faculties))
            {
                $faculty = $group_info['Факультет'];

                if (in_array($faculty, $faculties))
                    $mform->setDefault('faculty', $faculty);
                else if (($key = string_operation::first_substr_of_str_in_strarr($faculties, $faculty)) >= 0)
                    $mform->setDefault('faculty', $faculties[$key]);
                else if (($key = string_operation::first_substr_in_strarr($faculty, $faculties)) >= 0)
                    $mform->setDefault('faculty', $faculties[$key]);
                else $mform->setDefault('faculty', $faculties[0]);
            }
        }

        $choices = array('10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000);
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'tool_uploaduser'), $choices);
        $mform->hideIf('previewrows', 'action', 'eq', ACTION_EXPORTCSV);
        $mform->hideIf('previewrows', 'action', 'eq', ACTION_EXPORTCSVAD);
        $mform->hideIf('previewrows', 'action', 'eq', ACTION_EXPORTXLS);
        $mform->setType('previewrows', PARAM_INT);

        $choices = array();

        if (isset($group_info['ФормаОбучения']) && count($group_info['ФормаОбучения'])) {
            $choices = array_combine($group_info['ФормаОбучения'], $group_info['ФормаОбучения']);

            foreach ($group_info['ФормаОбучения'] as $edu_form) {
                //$edu_form = mb_strtolower($edu_form);
                if (isset($edu_forms[$edu_form])) {
                    $choices[$edu_form] = get_string($edu_forms[$edu_form], 'block_user_manager');
                }
            }
        }
        else {
            foreach ($edu_forms as $key => $edu_form) {
                $choices[$key] = get_string($edu_form, 'block_user_manager');
            }
        }

        $mform->addElement('select', 'eduform', get_string('eduform', 'block_user_manager'), $choices);
        $mform->setType('eduform', PARAM_TEXT);

        if ($from === UPLOAD_METHOD_FILE) {
            $mform->hideIf('eduform', 'action', 'eq', ACTION_EXPORTCSV);
            $mform->hideIf('eduform', 'action', 'eq', ACTION_EXPORTCSVAD);
        }

        $this->add_action_buttons(true, get_string('complete', 'block_user_manager'));
    }
}
