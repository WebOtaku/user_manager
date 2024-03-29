<?php

use block_user_manager\service;

require('../../../config.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->libdir.'/adminlib.php');

$contextid = optional_param('contextid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$searchquery  = optional_param('search', '', PARAM_RAW);
$showall = optional_param('showall', false, PARAM_BOOL);
$returnurl = required_param('returnurl', PARAM_LOCALURL);
$blockurl = required_param('returnurl', PARAM_LOCALURL);

$pageurl = '/blocks/user_manager/cohort/index.php';

require_login();

if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

if ($context->contextlevel != CONTEXT_COURSECAT and $context->contextlevel != CONTEXT_SYSTEM) {
    print_error('invalidcontext');
}

$category = null;
if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', array('id' => $context->instanceid), '*', MUST_EXIST);
}

$manager = has_capability('moodle/cohort:manage', $context);
$canassign = has_capability('moodle/cohort:assign', $context);

if (!$manager) {
    require_capability('moodle/cohort:view', $context);
}

$strcohorts = get_string('cohorts', 'cohort');

if ($category) {
    $PAGE->set_pagelayout('admin');
    $PAGE->set_context($context);
//    $PAGE->set_url($pageurl, array('contextid'=>$context->id));
    $PAGE->set_title($strcohorts);
    $PAGE->set_heading($COURSE->fullname);
    $showall = false;
} else {
    service::admin_externalpage_setup('cohorts', '', null, '', array('pagelayout'=>'report'));
}

$blockurl = $returnurl;

$params = array(
    'page' => $page,
    'returnurl' => $returnurl,
    'blockurl' => $blockurl
);

if ($contextid) {
    $params['contextid'] = $contextid;
}
if ($searchquery) {
    $params['search'] = $searchquery;
}
if ($showall) {
    $params['showall'] = true;
}

$baseurl = new moodle_url($pageurl, $params);

$PAGE->set_url($baseurl, array('contextid' => $context->id));

$returnurl = new moodle_url($returnurl);

// Навигация: Начало
$backnode = $PAGE->navigation->add(get_string('back'), $returnurl);
$usermanagernode = $backnode->add(get_string('user_manager', 'block_user_manager'));

$userstableurl_params = array('returnurl' => $returnurl);
$userstableurl = new moodle_url('/blocks/user_manager/user.php', $userstableurl_params);
$userstablenode = $usermanagernode->add(get_string('users', 'block_user_manager'), $userstableurl);

$basenode = $usermanagernode->add(get_string('cohorts', 'block_user_manager'), $baseurl);

$uploaduserurl_params = array('returnurl' => $returnurl);
$uploaduserurl = new moodle_url('/blocks/user_manager/uploaduser/index.php', $uploaduserurl_params);
$uploadusernode = $usermanagernode->add(get_string('uploaduser', 'block_user_manager'), $uploaduserurl);

$instructionurl_params = array('returnurl' => $returnurl);
$instructionurl = new moodle_url('/blocks/user_manager/instruction.php', $instructionurl_params);
$instructionnode = $usermanagernode->add(get_string('instruction', 'block_user_manager'), $instructionurl);

$basenode->make_active();
// Навигация: Конец

echo $OUTPUT->header();

if ($showall) {
    $cohorts = cohort_get_all_cohorts($page, 25, $searchquery);
} else {
    $cohorts = cohort_get_cohorts($context->id, $page, 25, $searchquery);
}

$count = '';
if ($cohorts['allcohorts'] > 0) {
    if ($searchquery === '') {
        $count = ' ('.$cohorts['allcohorts'].')';
    } else {
        $count = ' ('.$cohorts['totalcohorts'].'/'.$cohorts['allcohorts'].')';
    }
}

echo $OUTPUT->heading(get_string('user_manager', 'block_user_manager'));

if ($editcontrols = service::user_manager_edit_controls($baseurl, $returnurl, 'cohorts')) {
    echo $OUTPUT->render($editcontrols);
}

echo $OUTPUT->heading(get_string('cohortsin', 'cohort', $context->get_context_name()).$count);

if ($editcontrols = service::cohort_edit_controls($context, $baseurl)) {
    echo $OUTPUT->render($editcontrols);
}

// Add search form.
$search  = html_writer::start_tag('form', array('id'=>'searchcohortquery', 'method'=>'get', 'class' => 'form-inline search-cohort'));
$search .= html_writer::start_div('m-b-1');
$search .= html_writer::label(get_string('searchcohort', 'cohort'), 'cohort_search_q', true,
        array('class' => 'm-r-1')); // No : in form labels!
$search .= html_writer::empty_tag('input', array('id' => 'cohort_search_q', 'type' => 'text', 'name' => 'search',
        'value' => $searchquery, 'class' => 'form-control m-r-1'));
$search .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('search', 'cohort'),
        'class' => 'btn btn-secondary'));
$search .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'contextid', 'value'=>$contextid));
$search .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'showall', 'value'=>$showall));
$search .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'returnurl', 'value'=>$returnurl));
$search .= html_writer::end_div();
$search .= html_writer::end_tag('form');
echo $search;

// Output pagination bar.
echo $OUTPUT->paging_bar($cohorts['totalcohorts'], $page, 25, $baseurl);

$data = array();
$editcolumnisempty = true;
foreach($cohorts['cohorts'] as $cohort) {
    $line = array();
    $cohortcontext = context::instance_by_id($cohort->contextid);

    $cohort->description = file_rewrite_pluginfile_urls($cohort->description, 'pluginfile.php', $cohortcontext->id,
            'cohort', 'description', $cohort->id);

    if ($showall) {
        if ($cohortcontext->contextlevel == CONTEXT_COURSECAT) {
            $line[] = html_writer::link(new moodle_url('/blocks/user_manager/cohort/index.php' ,
                    array('contextid' => $cohort->contextid)), $cohortcontext->get_context_name(false));
        } else {
            $line[] = $cohortcontext->get_context_name(false);
        }
    }

    $tmpl = new \core_cohort\output\cohortname($cohort);
    $line[] = $OUTPUT->render_from_template('core/inplace_editable', $tmpl->export_for_template($OUTPUT));
    $tmpl = new \core_cohort\output\cohortidnumber($cohort);
    $line[] = $OUTPUT->render_from_template('core/inplace_editable', $tmpl->export_for_template($OUTPUT));
    $line[] = format_text($cohort->description, $cohort->descriptionformat);

    $line[] = $DB->count_records('cohort_members', array('cohortid'=>$cohort->id));

    if (empty($cohort->component)) {
        $line[] = get_string('nocomponent', 'cohort');
    } else {
        $line[] = get_string('pluginname', $cohort->component);
    }

    $buttons = array();
    if (empty($cohort->component)) {
        $cohortmanager = has_capability('moodle/cohort:manage', $cohortcontext);
        $cohortcanassign = has_capability('moodle/cohort:assign', $cohortcontext);

        $urlparams = array(
            'id' => $cohort->id,
            'returnurl' => $baseurl->out_as_local_url(),
            'blockurl' => $blockurl
        );
        $showhideurl = new moodle_url('/blocks/user_manager/cohort/edit.php', $urlparams + array('sesskey' => sesskey()));
        if ($cohortmanager) {
            if ($cohort->visible) {
                $showhideurl->param('hide', 1);
                $visibleimg = $OUTPUT->pix_icon('t/hide', get_string('hide'));
                $buttons[] = html_writer::link($showhideurl, $visibleimg, array('title' => get_string('hide')));
            } else {
                $showhideurl->param('show', 1);
                $visibleimg = $OUTPUT->pix_icon('t/show', get_string('show'));
                $buttons[] = html_writer::link($showhideurl, $visibleimg, array('title' => get_string('show')));
            }

            $buttons[] = html_writer::link(new moodle_url('/blocks/user_manager/cohort/edit.php', $urlparams + array('delete' => 1)),
                $OUTPUT->pix_icon('t/delete', get_string('delete')),
                array('title' => get_string('delete')));

            $buttons[] = html_writer::link(new moodle_url('/blocks/user_manager/cohort/edit.php', $urlparams),
                $OUTPUT->pix_icon('t/edit', get_string('edit')),
                array('title' => get_string('edit')));

            // Ссылка ведушая на страницу с пользователями состоящими в группе
            $user_urlparams = array(
                'chtid' => $cohort->id,
                'userfilter' => 'cohort',
                'returnurl' => $baseurl
            );
            $buttons[] = html_writer::link(new moodle_url('/blocks/user_manager/user.php', $user_urlparams),
                $OUTPUT->pix_icon('i/grades', get_string('cht_users_table', 'block_user_manager')),
                array('title' => get_string('cht_users_table', 'block_user_manager')));

            $editcolumnisempty = false;
        }
        if ($cohortcanassign) {
            $buttons[] = html_writer::link(new moodle_url('/cohort/assign.php', $urlparams),
                $OUTPUT->pix_icon('i/users', get_string('assign', 'core_cohort')),
                array('title' => get_string('assign', 'core_cohort')));
            $editcolumnisempty = false;
        }
    }
    $line[] = implode(' ', $buttons);

    $data[] = $row = new html_table_row($line);
    if (!$cohort->visible) {
        $row->attributes['class'] = 'dimmed_text';
    }
}

$table = new html_table();
$table->head = array(get_string('name', 'cohort'), get_string('idnumber', 'cohort'), get_string('description', 'cohort'),
                      get_string('memberscount', 'cohort'), get_string('component', 'cohort'));
$table->colclasses = array('leftalign name', 'leftalign id', 'leftalign description', 'leftalign size','centeralign source');

if ($showall) {
    array_unshift($table->head, get_string('category'));
    array_unshift($table->colclasses, 'leftalign category');
}
if (!$editcolumnisempty) {
    $table->head[] = get_string('edit');
    $table->colclasses[] = 'centeralign action';
} else {
    // Remove last column from $data.
    foreach ($data as $row) {
        array_pop($row->cells);
    }
}
$table->id = 'cohorts';
$table->attributes['class'] = 'admintable generaltable';
$table->data = $data;
echo html_writer::table($table);
echo $OUTPUT->paging_bar($cohorts['totalcohorts'], $page, 25, $baseurl);
echo $OUTPUT->footer();
