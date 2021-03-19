<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/cohort/upload_form.php');

/**
 * Cohort upload form class
 *
 * @package    core_cohort
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class um_cohort_upload_form extends cohort_upload_form {
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
