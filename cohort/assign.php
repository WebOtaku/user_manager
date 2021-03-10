<?php

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('assign_form.php');

$userid = required_param('userid', PARAM_INT);
$returnurl = required_param('returnurl', PARAM_LOCALURL);

require_login();

$context = context_system::instance();
$site = get_site();

if (!has_capability('moodle/cohort:assign', $context)) {
    print_error('nopermissions', 'error', '', 'assign cohorts');
}

$PAGE->set_context($context);

$pageurl = '/blocks/user_manager/cohort/assign.php';

$urlparams = array(
    'userid'=> $userid,
    'returnurl' => $returnurl
);

$baseurl = new moodle_url($pageurl, $urlparams);

$PAGE->set_url($baseurl);
$PAGE->set_title(get_string('addtocht', 'block_user_manager'));
$PAGE->set_heading(get_string('addtocht', 'block_user_manager'));
$PAGE->set_pagelayout('standard');

$returnurl = new  moodle_url($returnurl);

$backnode = $PAGE->navigation->add(get_string('back'), $returnurl->get_param('returnurl'));
$userslistnode = $backnode->add(get_string('userlist', 'admin'), $returnurl);
$basenode = $userslistnode->add(get_string('addtochtshort', 'block_user_manager'), $baseurl);

$basenode->make_active();

if (!$user = $DB->get_record('user', array('id' => $userid))) {
    print_error('invaliduser');
}

$assign_form = new assign_form(null, array($user));
$assign_form->set_data($urlparams);

if($assign_form->is_cancelled()) {
    redirect($returnurl);
} else if ($form_data = $assign_form->get_data()) {
    require_capability('moodle/cohort:assign', $context);

    if (isset($form_data->chtids) && isset($form_data->userid)) {
        foreach ($form_data->chtids as $chtid) {
            cohort_add_member($chtid, $form_data->userid);
        }
        redirect($returnurl);
    }
    else print_error('invaliddata', 'block_user_manager', $returnurl);

} else {
    echo $OUTPUT->header();
    $assign_form->display();
    echo $OUTPUT->footer();
}
