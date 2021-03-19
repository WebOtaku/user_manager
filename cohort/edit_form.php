<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/cohort/edit_form.php');

/**
 * Cohort related management functions, this file needs to be included manually.
 *
 * @package    core_cohort
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class um_cohort_edit_form extends cohort_edit_form {
    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;
        $data  = (object)$this->_customdata;

        parent::definition($this, $data);

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_LOCALURL);

        $mform->addElement('hidden', 'blockurl');
        $mform->setType('blockurl', PARAM_LOCALURL);

        $this->set_data($data);
    }

}
