<?php

use block_user_manager\service;

require_once('../../../config.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once('upload_form.php');
require_once($CFG->libdir . '/csvlib.class.php');

$contextid = optional_param('contextid', 0, PARAM_INT);
$returnurl = required_param('returnurl', PARAM_LOCALURL);
$blockurl  = required_param('blockurl', PARAM_LOCALURL);

require_login();

if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

if ($context->contextlevel != CONTEXT_COURSECAT && $context->contextlevel != CONTEXT_SYSTEM) {
    print_error('invalidcontext');
}

require_capability('moodle/cohort:manage', $context);

$strheading = get_string('uploadcohorts', 'cohort');

/*if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} else {*/
//}

$blockurl = new moodle_url($blockurl);

// Навигация: Начало
$backnode = $PAGE->navigation->add(get_string('back'), $blockurl);

$usermanagernode = $backnode->add(get_string('user_manager', 'block_user_manager'));

$userstableurl_params = array('returnurl' => $blockurl);
$userstableurl = new moodle_url('/blocks/user_manager/user.php', $userstableurl_params);
$userstablenode = $usermanagernode->add(get_string('users', 'block_user_manager'), $userstableurl);

$returnurl = new moodle_url('/blocks/user_manager/cohort/index.php', array(
    'contextid' => $context->id,
    'returnurl' => $blockurl,
    'blockurl'  => $blockurl
));

$chtstablenode = $usermanagernode->add(get_string('cohorts', 'block_user_manager'), $returnurl);

$pageurl = '/blocks/user_manager/cohort/upload.php';
$urlparams = array(
    'contextid' => $context->id,
    'returnurl' => $returnurl,
    'blockurl' => $blockurl
);
$baseurl = new moodle_url($pageurl, $urlparams);

$basenode = $chtstablenode->add($strheading, $baseurl);

$uploaduserurl_params = array('returnurl' => $blockurl);
$uploaduserurl = new moodle_url('/blocks/user_manager/uploaduser/index.php', $uploaduserurl_params);
$uploadusernode = $usermanagernode->add(get_string('uploaduser', 'block_user_manager'), $uploaduserurl);

$instructionurl_params = array('returnurl' => $blockurl);
$instructionurl = new moodle_url('/blocks/user_manager/instruction.php', $instructionurl_params);
$instructionnode = $usermanagernode->add(get_string('instruction', 'block_user_manager'), $instructionurl);

$basenode->make_active();
// Навигация: Конец

$PAGE->set_context($context);
$PAGE->set_url($baseurl);
$PAGE->set_heading($COURSE->fullname);
$PAGE->set_pagelayout('admin');

/*if ($context->contextlevel == CONTEXT_COURSECAT) {
    $PAGE->set_category_by_id($context->instanceid);
    navigation_node::override_active_url(new moodle_url('/blocks/user_manager/cohort/index.php', array('contextid' => $context->id)));
} else {
    navigation_node::override_active_url(new moodle_url('/blocks/user_manager/cohort/index.php', array()));
}*/

$uploadform = new um_cohort_upload_form(null, array(
    'contextid' => $context->id,
    'returnurl' => $returnurl,
    'blockurl'  => $blockurl
));

if ($uploadform->is_cancelled()) {
    redirect($returnurl);
}

//$strheading = get_string('uploadcohorts', 'cohort');
//$PAGE->navbar->add($strheading);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('user_manager', 'block_user_manager'));

if ($editcontrols = service::user_manager_edit_controls($baseurl, $blockurl, 'cohorts')) {
    echo $OUTPUT->render($editcontrols);
}

echo $OUTPUT->heading_with_help($strheading, 'uploadcohorts', 'cohort');

if ($editcontrols = service::cohort_edit_controls($context, $baseurl)) {
    echo $OUTPUT->render($editcontrols);
}

if ($data = $uploadform->get_data()) {
    $cohortsdata = $uploadform->get_cohorts_data();
    foreach ($cohortsdata as $cohort) {
        cohort_add_cohort($cohort);
    }
    echo $OUTPUT->notification(get_string('uploadedcohorts', 'cohort', count($cohortsdata)), 'notifysuccess');
    echo $OUTPUT->continue_button($returnurl);
} else {
    $uploadform->display();
}

echo $OUTPUT->footer();
