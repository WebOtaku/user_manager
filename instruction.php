<?php

use block_user_manager\service;
use block_user_manager\uploaduser;

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('locallib.php');

$returnurl = required_param('returnurl', PARAM_LOCALURL);

service::admin_externalpage_setup('tooluploaduser');
require_capability('moodle/site:uploadusers', context_system::instance());

$pageurl = '/blocks/user_manager/instruction.php';
$urlparams = array('returnurl' => $returnurl);

$baseurl = new moodle_url($pageurl, $urlparams);

$returnurl = new moodle_url($returnurl);

$pagetitle = get_string('instruction', 'block_user_manager');
$PAGE->set_url($baseurl);

// Навигация: Начало
$backnode = $PAGE->navigation->add(get_string('back'), $returnurl);
$usermanagernode = $backnode->add(get_string('user_manager', 'block_user_manager'));

$userstableurl_params = array('returnurl' => $returnurl);
$userstableurl = new moodle_url('/blocks/user_manager/user.php', $userstableurl_params);
$userstablenode = $usermanagernode->add(get_string('users', 'block_user_manager'), $userstableurl);

$chtstableurl_params = array('returnurl' => $returnurl);
$chtstableurl = new moodle_url('/blocks/user_manager/cohort/index.php', $chtstableurl_params);
$chtstablenode = $usermanagernode->add(get_string('cohorts', 'block_user_manager'), $chtstableurl);

$uploaduserurl_params = array('returnurl' => $returnurl);
$uploaduserurl = new moodle_url('/blocks/user_manager/uploaduser/index.php', $uploaduserurl_params);
$uploadusernode = $usermanagernode->add(get_string('uploaduser', 'block_user_manager'), $uploaduserurl);

$new_baseurl = new moodle_url($pageurl, array('returnurl' => $returnurl));
$basenode = $usermanagernode->add($pagetitle, $new_baseurl);

$basenode->make_active();
// Навигация: Конец

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('user_manager', 'block_user_manager'));

if ($editcontrols = service::user_manager_edit_controls($baseurl, $returnurl, 'instruction')) {
    echo $OUTPUT->render($editcontrols);
}

echo $OUTPUT->heading($pagetitle);
echo get_config('user_manager', 'Instruction');
echo $OUTPUT->footer();