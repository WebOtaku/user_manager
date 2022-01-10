<?php

use block_user_manager\service;

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('addtocht_form.php');
require_once('../locallib.php');

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

$pagetitle = get_string('addtocht', 'block_user_manager');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
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
    $userstablenode = $usermanagernode->add(get_string('users', 'block_user_manager'), $userstableurl);

    $chtstablenode = $usermanagernode->add(get_string('cohorts', 'block_user_manager'), $returnurl->get_param('returnurl'));

    $cht = $DB->get_record('cohort', array('id' => $returnurl->get_param('chtid')));
    $userschttablenode = $chtstablenode->add($cht->name, $returnurl);

    $basenode = $userschttablenode->add($pagetitle, $baseurl);

    $uploaduserurl_params = array('returnurl' => $backurl);
    $uploaduserurl = new moodle_url('/blocks/user_manager/uploaduser/index.php', $uploaduserurl_params);
    $uploadusernode = $usermanagernode->add(get_string('uploaduser', 'block_user_manager'), $uploaduserurl);

    $instructionurl_params = array('returnurl' => $backurl);
    $instructionurl = new moodle_url('/blocks/user_manager/instruction.php', $instructionurl_params);
    $instructionnode = $usermanagernode->add(get_string('instruction', 'block_user_manager'), $instructionurl);
} else {
    $backurl = $returnurl->get_param('returnurl');

    $backnode = $PAGE->navigation->add(get_string('back'), $backurl);
    $usermanagernode = $backnode->add(get_string('user_manager', 'block_user_manager'));
    $userstablenode = $usermanagernode->add(get_string('users', 'block_user_manager'), $returnurl);

    $chtstableurl_params = array('returnurl' => $backurl);
    $chtstableurl = new moodle_url('/blocks/user_manager/cohort/index.php', $chtstableurl_params);
    $chtstablenode = $usermanagernode->add(get_string('cohorts', 'block_user_manager'), $chtstableurl);

    $basenode = $userstablenode->add($pagetitle, $baseurl);

    $uploaduserurl_params = array('returnurl' => $backurl);
    $uploaduserurl = new moodle_url('/blocks/user_manager/uploaduser/index.php', $uploaduserurl_params);
    $uploadusernode = $usermanagernode->add(get_string('uploaduser', 'block_user_manager'), $uploaduserurl);

    $instructionurl_params = array('returnurl' => $backurl);
    $instructionurl = new moodle_url('/blocks/user_manager/instruction.php', $instructionurl_params);
    $instructionnode = $usermanagernode->add(get_string('instruction', 'block_user_manager'), $instructionurl);
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

    if (isset($form_data->chtid) && isset($form_data->userid)) {
        if (is_array($form_data->chtid)) {
            foreach ($form_data->chtid as $chtid) {
                cohort_add_member($chtid, $form_data->userid);
            }
        } else {
            cohort_add_member($form_data->chtid, $form_data->userid);
        }
        redirect($returnurl);
    }
    else print_error('invaliddata', 'block_user_manager', $returnurl);

} else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('user_manager', 'block_user_manager'));

    if ($editcontrols = service::user_manager_edit_controls($baseurl, $returnurl, 'users')) {
        echo $OUTPUT->render($editcontrols);
    }

    echo $OUTPUT->heading($pagetitle);
    $assign_form->display();

    $css_url = (string)(new moodle_url('/blocks/user_manager/css/uplodauser.css'));
    echo '<link rel="stylesheet" href="'.$css_url.'">';

    // ------ Подключение JS модуля ------
    $selectFieldId = 'id_chtid';

    $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/blocks/user_manager/js/autocomplete_info.js?newversion'));
    $request_url = (string)(new moodle_url('/blocks/user_manager/uploaduser/get_cohort_info.php'));
    $PAGE->requires->js_init_call('M.block_user_manager_autocomplete_info.init',  array(
        $request_url, $selectFieldId, 'cohort', CONTEXT_COHORT_SYNC
    ));
    $PAGE->requires->strings_for_js(
        array('cohortinfo', 'nocohortinfo', 'selectcohort'), 'block_user_manager'
    );
    // -----------------------------------

    echo $OUTPUT->footer();
}
