<?php

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('addtocht_form.php');

$userid = required_param('userid', PARAM_INT);
$returnurl = required_param('returnurl', PARAM_LOCALURL);

require_login();

$context = context_system::instance();
$site = get_site();

if (!has_capability('moodle/cohort:assign', $context)) {
    print_error('nopermissions', 'error', '', 'assign cohorts');
}

$PAGE->set_context($context);

$pageurl = '/blocks/user_manager/cohort/addtocht.php';

$urlparams = array(
    'userid'=> $userid,
    'returnurl' => $returnurl
);

$baseurl = new moodle_url($pageurl, $urlparams);

$PAGE->set_url($baseurl);
$PAGE->set_title(get_string('addtocht', 'block_user_manager'));
$PAGE->set_heading(get_string('addtocht', 'block_user_manager'));
$PAGE->set_pagelayout('standard');

$returnurl = new moodle_url($returnurl);

$userfilter = $returnurl->get_param('userfilter');

// Навигация: Начало
if ($userfilter === 'cohort') {
    $backurl = (new moodle_url($returnurl->get_param('returnurl')))->get_param('returnurl');

    $backnode = $PAGE->navigation->add(get_string('back'), $backurl);
    $usermanagernode = $backnode->add(get_string('user_manager', 'block_user_manager'));

    $userstableurl_params = array('returnurl' => $backurl);
    $userstableurl = new moodle_url('/blocks/user_manager/user.php', $userstableurl_params);
    $userstablenode = $usermanagernode->add(get_string('users_table', 'block_user_manager'), $userstableurl);

    $chtstablenode = $usermanagernode->add(get_string('chts_table', 'block_user_manager'), $returnurl->get_param('returnurl'));

    $cht = $DB->get_record('cohort', array('id' => $returnurl->get_param('chtid')));
    $userschttablenode = $chtstablenode->add($cht->name, $returnurl);

    $basenode = $userschttablenode->add(get_string('addtochtshort', 'block_user_manager'), $baseurl);

    $uploaduserurl_params = array('returnurl' => $backurl);
    $uploaduserurl = new moodle_url('/blocks/user_manager/uploaduser/index.php', $uploaduserurl_params);
    $uploadusernode = $usermanagernode->add(get_string('uploadusers', 'tool_uploaduser'), $uploaduserurl);
} else {
    $backurl = $returnurl->get_param('returnurl');

    $backnode = $PAGE->navigation->add(get_string('back'), $backurl);
    $usermanagernode = $backnode->add(get_string('user_manager', 'block_user_manager'));
    $userstablenode = $usermanagernode->add(get_string('users_table', 'block_user_manager'), $returnurl);

    $chtstableurl_params = array('returnurl' => $backurl);
    $chtstableurl = new moodle_url('/blocks/user_manager/cohort/index.php', $chtstableurl_params);
    $chtstablenode = $usermanagernode->add(get_string('chts_table', 'block_user_manager'), $chtstableurl);

    $basenode = $userstablenode->add(get_string('addtochtshort', 'block_user_manager'), $baseurl);

    $uploaduserurl_params = array('returnurl' => $backurl);
    $uploaduserurl = new moodle_url('/blocks/user_manager/uploaduser/index.php', $uploaduserurl_params);
    $uploadusernode = $usermanagernode->add(get_string('uploadusers', 'tool_uploaduser'), $uploaduserurl);
}

$basenode->make_active();
// Навигация: Конец


if (!$user = $DB->get_record('user', array('id' => $userid))) {
    print_error('invaliduser');
}

$assign_form = new addtocht_form(null, array($user));
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
