<?php

use block_user_manager\service;

require('../../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once('edit_form.php');

$id        = optional_param('id', 0, PARAM_INT);
$contextid = optional_param('contextid', 0, PARAM_INT);
$delete    = optional_param('delete', 0, PARAM_BOOL);
$show      = optional_param('show', 0, PARAM_BOOL);
$hide      = optional_param('hide', 0, PARAM_BOOL);
$confirm   = optional_param('confirm', 0, PARAM_BOOL);
$returnurl = required_param('returnurl', PARAM_LOCALURL);
$blockurl  = required_param('blockurl', PARAM_LOCALURL);

require_login();

$category = null;

if ($id) {
    $cohort = $DB->get_record('cohort', array('id' => $id), '*', MUST_EXIST);
    $context = context::instance_by_id($cohort->contextid, MUST_EXIST);
} else {
    $context = context::instance_by_id($contextid, MUST_EXIST);
    if ($context->contextlevel != CONTEXT_COURSECAT and $context->contextlevel != CONTEXT_SYSTEM) {
        print_error('invalidcontext');
    }
    $cohort = new stdClass();
    $cohort->id          = 0;
    $cohort->contextid   = $context->id;
    $cohort->name        = '';
    $cohort->description = '';
}

require_capability('moodle/cohort:manage', $context);

/*if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} else {*/
//}

$editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $SITE->maxbytes, 'context' => $context);
if ($cohort->id) {
    // Edit existing.
    $cohort = file_prepare_standard_editor($cohort, 'description', $editoroptions,
        $context, 'cohort', 'description', $cohort->id);
    $strheading = get_string('editcohort', 'cohort');

}
else {
    // Add new.
    $cohort = file_prepare_standard_editor($cohort, 'description', $editoroptions,
        $context, 'cohort', 'description', null);
    $strheading = get_string('addcohort', 'cohort');
}

if ($delete and $cohort->id) {
    $strheading = get_string('delcohort', 'cohort');
}

// Навигация: Начало
$backnode = $PAGE->navigation->add(get_string('back'), $blockurl);

$usermanagernode = $backnode->add(get_string('user_manager', 'block_user_manager'));

$userstableurl_params = array('returnurl' => $blockurl);
$userstableurl = new moodle_url('/blocks/user_manager/user.php', $userstableurl_params);
$userstablenode = $usermanagernode->add(get_string('users_table', 'block_user_manager'), $userstableurl);

$returnurl = new moodle_url('/blocks/user_manager/cohort/index.php', array(
    'contextid' => $context->id,
    'returnurl' => $blockurl,
    'blockurl'  => $blockurl
));

$chtstablenode = $usermanagernode->add(get_string('chts_table', 'block_user_manager'), $returnurl);

$pageurl = '/blocks/user_manager/cohort/edit.php';
$urlparams = array(
    'contextid' => $context->id,
    'id' => $cohort->id,
    'returnurl' => $returnurl,
    'blockurl' => $blockurl
);

if ($delete and $cohort->id) {
    $urlparams['delete'] = $delete;
}

$baseurl = new moodle_url($pageurl, $urlparams);

$basenode = $chtstablenode->add($strheading, $baseurl);

$uploaduserurl_params = array('returnurl' => $blockurl);
$uploaduserurl = new moodle_url('/blocks/user_manager/uploaduser/index.php', $uploaduserurl_params);
$uploadusernode = $usermanagernode->add(get_string('uploadusers', 'tool_uploaduser'), $uploaduserurl);

$basenode->make_active();
// Навигация: Конец

if (!empty($cohort->component)) {
    // We can not manually edit cohorts that were created by external systems, sorry.
    redirect($returnurl);
}

$PAGE->set_context($context);
$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');

// Удаление глобальной группы
if ($delete and $cohort->id) {
    $PAGE->url->param('delete', 1);
    if ($confirm and confirm_sesskey()) {
        cohort_delete_cohort($cohort);
        redirect($returnurl);
    }
    //$strheading = get_string('delcohort', 'cohort');
    //$PAGE->navbar->add($strheading);
    $PAGE->set_title($strheading);
    $PAGE->set_heading($COURSE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strheading);
    $yesurl = new moodle_url('/blocks/user_manager/cohort/edit.php', array(
        'id' => $cohort->id,
        'delete' => 1,
        'confirm' => 1,
        'sesskey' => sesskey(),
        'returnurl' => $returnurl->out_as_local_url(),
        'blockurl' => $blockurl
    ));
    $message = get_string('delconfirm', 'cohort', format_string($cohort->name));
    echo $OUTPUT->confirm($message, $yesurl, $returnurl);
    echo $OUTPUT->footer();
    die;
}

/*if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', array('id' => $context->instanceid), '*', MUST_EXIST);
    navigation_node::override_active_url(new moodle_url('/blocks/user_manager/cohort/index.php', array('contextid' => $cohort->contextid)));
} else {
    navigation_node::override_active_url(new moodle_url('/blocks/user_manager/cohort/index.php', array()));
}*/

if ($show && $cohort->id && confirm_sesskey()) {
    if (!$cohort->visible) {
        $record = (object)array('id' => $cohort->id, 'visible' => 1, 'contextid' => $cohort->contextid);
        cohort_update_cohort($record);
    }
    redirect($returnurl);
}

if ($hide && $cohort->id && confirm_sesskey()) {
    if ($cohort->visible) {
        $record = (object)array('id' => $cohort->id, 'visible' => 0, 'contextid' => $cohort->contextid);
        cohort_update_cohort($record);
    }
    redirect($returnurl);
}

$PAGE->set_title($strheading);
$PAGE->set_heading($COURSE->fullname);
//$PAGE->navbar->add($strheading);

$editform = new um_cohort_edit_form(null, array(
    'editoroptions' => $editoroptions,
    'data' => $cohort,
    'returnurl' => $returnurl,
    'blockurl'  => $blockurl
));

if ($editform->is_cancelled()) {
    redirect($returnurl);
}
else if ($data = $editform->get_data()) {
    $oldcontextid = $context->id;
    $editoroptions['context'] = $context = context::instance_by_id($data->contextid);

    if ($data->id) {
        if ($data->contextid != $oldcontextid) {
            // Cohort was moved to another context.
            get_file_storage()->move_area_files_to_new_context($oldcontextid, $context->id,
                'cohort', 'description', $data->id);
        }
        $data = file_postupdate_standard_editor($data, 'description', $editoroptions,
            $context, 'cohort', 'description', $data->id);
        cohort_update_cohort($data);
    } else {
        $data->descriptionformat = $data->description_editor['format'];
        $data->description = $description = $data->description_editor['text'];
        $data->id = cohort_add_cohort($data);
        $editoroptions['context'] = $context = context::instance_by_id($data->contextid);
        $data = file_postupdate_standard_editor($data, 'description', $editoroptions,
            $context, 'cohort', 'description', $data->id);
        if ($description != $data->description) {
            $updatedata = (object)array('id' => $data->id,
                'description' => $data->description, 'contextid' => $context->id);
            cohort_update_cohort($updatedata);
        }
    }

    if ($returnurl->get_param('showall') || $returnurl->get_param('contextid') == $data->contextid) {
        // Redirect to where we were before.
        redirect($returnurl);
    } else {
        // Use new context id, it has been changed.
        redirect(new moodle_url('/blocks/user_manager/cohort/index.php', array('contextid' => $data->contextid)));
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($strheading);

if (!$id && ($editcontrols = service::cohort_edit_controls($context, $baseurl))) {
    echo $OUTPUT->render($editcontrols);
}

echo $editform->display();
echo $OUTPUT->footer();
