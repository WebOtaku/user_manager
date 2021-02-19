<?php

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('add_member_form.php');

$sort = optional_param('sort', 'name', PARAM_ALPHANUM);
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$perpage = optional_param('perpage', 20, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);

$func = optional_param('func', '', PARAM_TEXT);
$chtid = optional_param('chtid', 0, PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$userfilter = optional_param('userfilter', '', PARAM_TEXT);

$chtsreturnurl = optional_param('chtsreturnurl', null, PARAM_LOCALURL);

$context = context_system::instance();
$site = get_site();

require_login();

if (!has_capability('moodle/cohort:manage', $context) and !has_capability('moodle/cohort:assign', $context)) {
    print_error('nopermissions', 'error', '', 'manage/assign cohorts');
}

$PAGE->set_context($context);

$pageurl = '/blocks/user_manager/cohort/add_member_view.php';
$pageparams = array('userid'=> $userid);
$baseurl = new moodle_url($pageurl, $pageparams);

$url = '/blocks/user_manager/user_tabs.php';
$urlparams = array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'page' => $page);

if ($userfilter == 'cohort') {
    $urlparams['chtid'] = $chtid;
    $urlparams['userfilter'] = $userfilter;
}

if ($chtsreturnurl)
    $urlparams['chtsreturnurl'] = $chtsreturnurl;

$returnurl = new moodle_url($url, $urlparams);

$PAGE->set_url($baseurl);
$PAGE->set_title(get_string('addtocht', 'block_user_manager'));

$PAGE->set_heading(get_string('addtocht', 'block_user_manager'));
$PAGE->set_pagelayout('standard');

$adminurl = new moodle_url('/admin/search.php');
$adminnode = $PAGE->navigation->add(get_string('administrationsite', 'moodle'), $adminurl);

$usersurl = new moodle_url('/admin/category.php?category=users');
$usersnode = $adminnode->add(get_string('users', 'moodle'), $usersurl);

$accountsurl = new moodle_url('/admin/category.php?category=accounts');
$accountsnode = $usersnode->add(get_string('accounts', 'admin'), $accountsurl);

//$userslisturl = new moodle_url('/blocks/user_manager/user.php');
$userslistnode = $accountsnode->add(get_string('userlist', 'admin'), $returnurl);

$addmemberurl = new moodle_url('/blocks/user_manager/cohort/add_member_view.php');
$addmembernode = $userslistnode->add(get_string('addtochtshort', 'block_user_manager'), $addmemberurl);
$addmembernode->make_active();

$user = $DB->get_record('user', ['id' => $userid]);

$add_member_form = new add_member_form(null, array($user));

$toform['userid'] = $userid;

$toform += $urlparams;
$add_member_form->set_data($toform);

if($add_member_form->is_cancelled()) {
    redirect($returnurl);
} else if ($form_data = $add_member_form->get_data()) {
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
    $add_member_form->display();
    echo $OUTPUT->footer();
}
