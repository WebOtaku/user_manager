<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->dirroot.'/admin/tool/uploaduser/user_form.php');

/**
 * Cohort related management functions, this file needs to be included manually.
 *
 * @package    core_cohort
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class um_admin_uploaduser_form extends admin_uploaduser_form1 {
    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;
        list($stdfields) = $this->_customdata;

        $mform->addElement('header', 'instructionheader', get_string('instruction', 'block_user_manager'));

        $instruction = '
            <div class="instruction">Инструкция</div>
        ';

        $mform->addElement('html', $instruction);
        $mform->setExpanded('instructionheader', false);

        $mform->addElement('header', 'validfieldsheader', get_string('validfields', 'block_user_manager'));

        $validfields = '
            <table class="table">
                <thead>
                    <tr>
                        <th>'.get_string('systemfields', 'block_user_manager').'</th>
                        <th>'.get_string('associatedfields', 'block_user_manager').'</th>
                    </tr>
                </thead>
                <tbody>';
                foreach ($stdfields as $systemfield => $associatedfields) {
                    $validfields .= "<tr>";
                    $validfields .= "<td>$systemfield</td>";

                    $validfields .= "<td>";
                    if (is_array($associatedfields)) {
                        $validfields .= implode(', ', $associatedfields);
                    } else $validfields .= $associatedfields;
                    $validfields .= "</td>";

                    $validfields .= "</tr>";
                }
        $validfields .= '
                </tbody>
            </table>
        ';

        $mform->addElement('html', $validfields);
        $mform->setExpanded('validfieldsheader', false);

        $mform->addElement('header', 'settingsheader', get_string('upload'));

        $choices = array(
            1 => 'Экспорт в формате .csv',
            2 => 'Экспорт в формате .xls (Excel)',
            3 => 'Загрузка пользователей в систему'
        );
        $mform->addElement('select', 'action', get_string('action'), $choices);
        $mform->setType('action', PARAM_INT);

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

        //$this->set_data($data);
    }

    /*public function definition() {
        $mform = $this->_form;
        $data  = (object)$this->_customdata;

        parent::definition($this, $data);;

        $this->set_data($data);
    }*/

}
