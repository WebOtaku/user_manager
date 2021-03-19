<?php

use block_user_manager\db_request;
use block_user_manager\cohort;
use block_user_manager\course;
use block_user_manager\table;
use block_user_manager\service;
use block_user_manager\remove_entry_params;

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');
require_once($CFG->dirroot.'/user/lib.php');

require_once($CFG->dirroot.'/cohort/lib.php');

$delete       = optional_param('delete', 0, PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash
$confirmuser  = optional_param('confirmuser', 0, PARAM_INT);
$sort         = optional_param('sort', 'name', PARAM_ALPHANUM);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 20, PARAM_INT);        // how many per page
$ru           = optional_param('ru', '2', PARAM_INT);            // show remote users
$lu           = optional_param('lu', '2', PARAM_INT);            // show local users
$acl          = optional_param('acl', '0', PARAM_INT);           // id of user to tweak mnet ACL (requires $access)
$suspend      = optional_param('suspend', 0, PARAM_INT);
$unsuspend    = optional_param('unsuspend', 0, PARAM_INT);
$unlock       = optional_param('unlock', 0, PARAM_INT);
$resendemail  = optional_param('resendemail', 0, PARAM_INT);

$func         = optional_param('func', '', PARAM_TEXT);
$chtid        = optional_param('chtid', 0, PARAM_INT);
$delchtid     = optional_param('chtid', 0, PARAM_INT);
$userid       = optional_param('userid', 0, PARAM_INT);

$userfilter = optional_param('userfilter', '', PARAM_TEXT);

$returnurl = required_param('returnurl', PARAM_LOCALURL);

service::admin_externalpage_setup('editusers');

$context = context_system::instance();
$site = get_site();

//require_login();

if (!has_capability('moodle/user:update', $context) and !has_capability('moodle/user:delete', $context)) {
    print_error('nopermissions', 'error', '', 'edit/delete users');
}

/*if (!has_capability('block/user_manager:edit', $context)) {
    print_error('nopermissions', 'error', '', 'edit users');
}*/

//$PAGE->set_context($context);

$stredit   = get_string('edit');
$strdelete = get_string('delete');
$strdeletecheck = get_string('deletecheck');
$strshowallusers = get_string('showallusers');
$strsuspend = get_string('suspenduser', 'admin');
$strunsuspend = get_string('unsuspenduser', 'admin');
$strunlock = get_string('unlockaccount', 'admin');
$strconfirm = get_string('confirm');
$strresendemail = get_string('resendemail');

$pageurl = '/blocks/user_manager/user.php';
$urlparams = array('sort' => $sort, 'dir' => $dir);

// Добавления параметров в запрос для случая фильтрации по данным
if ($userfilter == 'cohort') {
    $urlparams['chtid'] = $chtid;
    $urlparams['userfilter'] = $userfilter;
}

$urlparams['returnurl'] = $returnurl;
$urlparams['perpage'] = $perpage;
$urlparams['page'] = $page;

$baseurl = new moodle_url($pageurl, $urlparams);

$pagetitle = get_string('users_table', 'block_user_manager');

$PAGE->set_url($baseurl);
/*$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
$PAGE->set_pagelayout('admin');*/

$returnurl = new moodle_url($returnurl);

if ($userfilter == 'cohort')  {
    $cht = $DB->get_record('cohort', array('id' => $chtid));
    $backnode = $PAGE->navigation->add(get_string('back'), $returnurl->get_param('returnurl'));
    $usermanagernode = $backnode->add(get_string('user_manager', 'block_user_manager'));

    $userstableurl = new moodle_url($baseurl);
    $userstableurl->remove_params('userfilter', 'chtid');
    $userstableurl->param('returnurl', (new moodle_url($userstableurl->get_param('returnurl')))->get_param('returnurl'));
    $userstablenode = $usermanagernode->add(get_string('users_table', 'block_user_manager'), $userstableurl);

    $chtstablenode = $usermanagernode->add(get_string('chts_table', 'block_user_manager'), $returnurl);

    $basenode = $chtstablenode->add($cht->name, $baseurl);
    $basenode->make_active();
}
else {
    $backnode = $PAGE->navigation->add(get_string('back'), $returnurl);
    $usermanagernode = $backnode->add(get_string('user_manager', 'block_user_manager'));

    $basenode = $usermanagernode->add(get_string('users_table', 'block_user_manager'), $baseurl);

    $chtstableurl_params = array('returnurl' => $returnurl);
    $chtstableurl = new moodle_url('/blocks/user_manager/cohort/index.php', $chtstableurl_params);
    $chtstablenode = $usermanagernode->add(get_string('chts_table', 'block_user_manager'), $chtstableurl);
}

$basenode->make_active();

// The $user variable is also used outside of these if statements.
$user = null;
if ($confirmuser and confirm_sesskey()) {
    require_capability('moodle/user:update', $context);
    if (!$user = $DB->get_record('user', array('id'=>$confirmuser, 'mnethostid'=>$CFG->mnet_localhost_id))) {
        print_error('nousers');
    }

    $auth = get_auth_plugin($user->auth);

    $result = $auth->user_confirm($user->username, $user->secret);

    if ($result == AUTH_CONFIRM_OK or $result == AUTH_CONFIRM_ALREADY) {
        redirect($baseurl);
    } else {
        echo $OUTPUT->header();
        redirect($baseurl, get_string('usernotconfirmed', '', fullname($user, true)));
    }

} else if ($resendemail && confirm_sesskey()) {
    if (!$user = $DB->get_record('user', ['id' => $resendemail, 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0])) {
        print_error('nousers');
    }

    // Prevent spamming users who are already confirmed.
    if ($user->confirmed) {
        print_error('alreadyconfirmed');
    }

    $returnmsg = get_string('emailconfirmsentsuccess');
    $messagetype = \core\output\notification::NOTIFY_SUCCESS;
    if (!send_confirmation_email($user)) {
        $returnmsg = get_string('emailconfirmsentfailure');
        $messagetype = \core\output\notification::NOTIFY_ERROR;
    }

    redirect($baseurl, $returnmsg, null, $messagetype);
} else if ($delete and confirm_sesskey()) {              // Delete a selected user, after confirmation
    require_capability('moodle/user:delete', $context);

    $user = $DB->get_record('user', array('id'=>$delete, 'mnethostid'=>$CFG->mnet_localhost_id), '*', MUST_EXIST);

    if ($user->deleted) {
        print_error('usernotdeleteddeleted', 'error');
    }
    if (is_siteadmin($user->id)) {
        print_error('useradminodelete', 'error');
    }

    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();
        $fullname = fullname($user, true);
        echo $OUTPUT->heading(get_string('deleteuser', 'admin'));

        $optionsyes = array('delete'=>$delete, 'confirm'=>md5($delete), 'sesskey'=>sesskey());
        $deleteurl = new moodle_url($baseurl, $optionsyes);
        $deletebutton = new single_button($deleteurl, get_string('delete'), 'post');

        echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$fullname'"), $deletebutton, $baseurl);
        echo $OUTPUT->footer();
        die;
    } else if (data_submitted()) {
        if (delete_user($user)) {
            \core\session\manager::gc(); // Remove stale sessions.
            redirect($baseurl);
        } else {
            \core\session\manager::gc(); // Remove stale sessions.
            echo $OUTPUT->header();
            echo $OUTPUT->notification($baseurl, get_string('deletednot', '', fullname($user, true)));
        }
    }
} else if ($acl and confirm_sesskey()) {
    if (!has_capability('moodle/user:update', $context)) {
        print_error('nopermissions', 'error', '', 'modify the NMET access control list');
    }
    if (!$user = $DB->get_record('user', array('id'=>$acl))) {
        print_error('nousers', 'error');
    }
    if (!is_mnet_remote_user($user)) {
        print_error('usermustbemnet', 'error');
    }
    $accessctrl = strtolower(required_param('accessctrl', PARAM_ALPHA));
    if ($accessctrl != 'allow' and $accessctrl != 'deny') {
        print_error('invalidaccessparameter', 'error');
    }
    $aclrecord = $DB->get_record('mnet_sso_access_control', array('username'=>$user->username, 'mnet_host_id'=>$user->mnethostid));
    if (empty($aclrecord)) {
        $aclrecord = new stdClass();
        $aclrecord->mnet_host_id = $user->mnethostid;
        $aclrecord->username = $user->username;
        $aclrecord->accessctrl = $accessctrl;
        $DB->insert_record('mnet_sso_access_control', $aclrecord);
    } else {
        $aclrecord->accessctrl = $accessctrl;
        $DB->update_record('mnet_sso_access_control', $aclrecord);
    }
    $mnethosts = $DB->get_records('mnet_host', null, 'id', 'id,wwwroot,name');
    redirect($baseurl);

} else if ($suspend and confirm_sesskey()) {
    require_capability('moodle/user:update', $context);

    if ($user = $DB->get_record('user', array('id'=>$suspend, 'mnethostid'=>$CFG->mnet_localhost_id, 'deleted'=>0))) {
        if (!is_siteadmin($user) and $USER->id != $user->id and $user->suspended != 1) {
            $user->suspended = 1;
            // Force logout.
            \core\session\manager::kill_user_sessions($user->id);
            user_update_user($user, false);
        }
    }
    redirect($baseurl);

} else if ($unsuspend and confirm_sesskey()) {
    require_capability('moodle/user:update', $context);

    if ($user = $DB->get_record('user', array('id'=>$unsuspend, 'mnethostid'=>$CFG->mnet_localhost_id, 'deleted'=>0))) {
        if ($user->suspended != 0) {
            $user->suspended = 0;
            user_update_user($user, false);
        }
    }
    redirect($baseurl);

} else if ($unlock and confirm_sesskey()) {
    require_capability('moodle/user:update', $context);

    if ($user = $DB->get_record('user', array('id'=>$unlock, 'mnethostid'=>$CFG->mnet_localhost_id, 'deleted'=>0))) {
        login_unlock_account($user);
    }
    redirect($baseurl);
}
else if (isset($_GET['func']) and confirm_sesskey()) {
    require_capability('moodle/cohort:manage', $context);

    if ($_GET['func'] === 'cohort_remove_member') {
        if (isset($_GET['delchtid']) && isset($_GET['userid'])) {
            if ($confirm != md5($_GET['userid'])) {
                global $DB;

                $cht = $DB->get_record('cohort', ['id' => $_GET['delchtid']]);
                $user = $DB->get_record('user', ['id' => $_GET['userid']]);

                echo $OUTPUT->header();
                echo $OUTPUT->heading(get_string('removefromcht', 'block_user_manager'));

                $optionsyes = array(
                    'userid' => $_GET['userid'], 'confirm' => md5($_GET['userid']),
                    'func'=> $_GET['func'], 'delchtid' => $_GET['delchtid'], 'sesskey'=>sesskey()
                );

                $deleteurl = new moodle_url($baseurl, $optionsyes);
                $deletebutton = new single_button($deleteurl, get_string('delete'), 'get');

                $messagedata = new stdClass();
                $messagedata->lastname = $user->lastname;
                $messagedata->firstname = $user->firstname;
                $messagedata->middlename = $user->middlename;
                $messagedata->chtname = $cht->name;

                echo $OUTPUT->confirm(
                    get_string('removeuserchtwarning', 'block_user_manager', $messagedata),
                    $deletebutton, $baseurl
                );
                echo $OUTPUT->footer();
                die;
            } else  {
                cohort_remove_member($_GET['delchtid'], $_GET['userid']);
                redirect($baseurl);
            }
        }
    }
}

$fieldnames = array('realname' => 0, 'lastname' => 1, 'firstname' => 1, 'username' => 1, 'email' => 1, 'city' => 1,
    'country' => 1, 'confirmed' => 1, 'suspended' => 1, 'profile' => 1, 'courserole' => 1,
    'anycourses' => 1, 'systemrole' => 1, 'cohort' => 1, 'firstaccess' => 1, 'lastaccess' => 1,
    'neveraccessed' => 1, 'timemodified' => 1, 'nevermodified' => 1, 'auth' => 1, 'mnethostid' => 1,
    'idnumber' => 1);

// create the user filter form
$filterurl = new moodle_url($baseurl);
$filterurl->param('page', 0);
$ufiltering = new user_filtering($fieldnames, $filterurl);
echo $OUTPUT->header();

/*// Carry on with the user listing
$context = context_system::instance();*/

// These columns are always shown in the users list.
$requiredcolumns = array(
    'course', 'roles', 'lastaccess', 'cht_code_mdl', 'cht_code', 'form', 'enrol_method'
);
// Extra columns containing the extra user fields, excluding the required columns (city and country, to be specific).
$extracolumns = get_extra_user_fields($context, $requiredcolumns);
// Get all user name fields as an array.
$allusernamefields = get_all_user_name_fields(false, null, null, null, true);
$columns = array_merge($allusernamefields, $extracolumns, $requiredcolumns);

// Список колонок недоступных для сортировки по ним
$notsortable = array(
    'course', 'roles', 'cht_code_mdl', 'cht_code', 'form', 'enrol_method'
);

foreach ($columns as $column) {
    //$string[$column] = get_user_field_name($column);
    $string[$column] = get_string($column, 'block_user_manager');

    if ($sort != $column) {
        $columnicon = "";
        if ($column == "lastaccess") {
            $columndir = "DESC";
        } else {
            $columndir = "ASC";
        }
    } else {
        $columndir = $dir == "ASC" ? "DESC":"ASC";
        if ($column == "lastaccess") {
            $columnicon = ($dir == "ASC") ? "sort_desc" : "sort_asc";
        } else {
            $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
        }
        $columnicon = $OUTPUT->pix_icon('t/' . $columnicon, get_string(strtolower($columndir)), 'core',
            ['class' => 'iconsort']);
    }

    if (!in_array($column, $notsortable)) {
        $sorturlparams = $urlparams;
        $sorturlparams['sort'] = $column;
        $sorturlparams['dir'] = $columndir;
        $sorturl = new moodle_url($pageurl, $sorturlparams);
        $$column = html_writer::link($sorturl, $string[$column])."$columnicon";
    }
    else $$column = $string[$column];
}

// We need to check that alternativefullnameformat is not set to '' or language.
// We don't need to check the fullnamedisplay setting here as the fullname function call further down has
// the override parameter set to true.
$fullnamesetting = $CFG->alternativefullnameformat;
// If we are using language or it is empty, then retrieve the default user names of just 'firstname' and 'lastname'.
if ($fullnamesetting == 'language' || empty($fullnamesetting)) {
    // Set $a variables to return 'firstname' and 'lastname'.
    $a = new stdClass();
    $a->firstname = 'firstname';
    $a->lastname = 'lastname';
    // Getting the fullname display will ensure that the order in the language file is maintained.
    $fullnamesetting = get_string('fullnamedisplay', null, $a);
}

// Order in string will ensure that the name columns are in the correct order.
$usernames = order_in_string($allusernamefields, $fullnamesetting);
$fullnamedisplay = array();

foreach ($usernames as $name) {
    // Use the link from $$column for sorting on the user's name.
    $fullnamedisplay[] = ${$name};
}
// All of the names are in one column. Put them into a string and separate them with a /.
$fullnamedisplay = implode(' / ', $fullnamedisplay);
// If $sort = name then it is the default for the setting and we should use the first name to sort by.
if ($sort == "name") {
    // Use the first item in the array.
    $sort = reset($usernames);
}

list($extrasql, $params) = $ufiltering->get_sql_filter();

if ($userfilter === 'cohort')
{
    $cht = $DB->get_record('cohort', array('id' => $chtid));

    $cohort_members = $DB->get_records('cohort_members', array('cohortid' => $chtid));
    $usercount = count($cohort_members);

    if ($extrasql) $usersselect = ' AND ';
    else $usersselect = '';
    $usersselect .= cohort::form_cohort_members_select($cohort_members);

    $users = get_users_listing($sort, $dir, $page * $perpage, $perpage, '', '', '',
        $extrasql . $usersselect, $params, $context);

    $usersearchcount = count($users);
} else {
    $users = get_users_listing($sort, $dir, $page * $perpage, $perpage, '', '', '',
        $extrasql, $params, $context);

    $usercount = get_users(false);
    $usersearchcount = get_users(false, '', false, null, "", '', '', '', '', '*', $extrasql, $params);
}

// Запрос и формирование пользовотельских данных
$users_cohorts = db_request::get_users_cohorts($users);
$grouped_users_cohorts = cohort::group_users_cohorts_by_users($users_cohorts);

$users_courses = db_request::get_users_courses($users);
$grouped_users_courses = course::group_users_courses_by_users($users_courses);

if ($extrasql !== '') {
    if ($userfilter == 'cohort')
        echo $OUTPUT->heading(get_string('assignto', 'cohort', format_string($cht->name))." ($usersearchcount / $usercount)");
    else
        echo $OUTPUT->heading("$usersearchcount / $usercount ".get_string('users'));
    $usercount = $usersearchcount;
} else {
    if ($userfilter == 'cohort')
        echo $OUTPUT->heading(get_string('assignto', 'cohort', format_string($cht->name))." ($usercount)");
    else
        echo $OUTPUT->heading("$usercount ".get_string('users'));
}

$strall = get_string('all');
echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);

flush();

if (!$users) {
    $match = array();
    echo $OUTPUT->heading(get_string('nousersfound'));

    $table = NULL;

} else {
    //print_object($USER);

    $countries = get_string_manager()->get_list_of_countries(true);
    if (empty($mnethosts)) {
        $mnethosts = $DB->get_records('mnet_host', null, 'id', 'id,wwwroot,name');
    }

    foreach ($users as $key => $user) {
        if (isset($countries[$user->country])) {
            $users[$key]->country = $countries[$user->country];
        }
    }

    if ($sort == "country") {
        // Need to resort by full country name, not code.
        foreach ($users as $user) {
            $susers[$user->id] = $user->country;
        }
        // Sort by country name, according to $dir.
        if ($dir === 'DESC') {
            arsort($susers);
        } else {
            asort($susers);
        }
        foreach ($susers as $key => $value) {
            $nusers[] = $users[$key];
        }
        $users = $nusers;
    }

    echo '<link rel="stylesheet" href="'.new moodle_url('/blocks/user_manager/main.css').'">';

    $table = new html_table();
    $table->head = array ();
    $table->colclasses = array();
    $table->head[] = $fullnamedisplay . ' | ' . $lastaccess;
    $table->attributes['class'] = 'admintable generaltable um-generaltable';
    $table->colclasses[] = 'centeralign';

    $table->id = "users";

    $i = 0;

    foreach ($users as $user) {
        $buttons = array();
        $lastcolumn = '';

        // delete button
        if (has_capability('moodle/user:delete', $context)) {
            if (is_mnet_remote_user($user) or $user->id == $USER->id or is_siteadmin($user)) {
                // no deleting of self, mnet accounts or admins allowed
            } else {
                $url = new moodle_url($baseurl, array('delete'=>$user->id, 'sesskey'=>sesskey()));
                $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/delete', $strdelete));
            }
        }

        // suspend button
        if (has_capability('moodle/user:update', $context)) {
            if (is_mnet_remote_user($user)) {
                // mnet users have special access control, they can not be deleted the standard way or suspended
                $accessctrl = 'allow';
                if ($acl = $DB->get_record('mnet_sso_access_control', array('username'=>$user->username, 'mnet_host_id'=>$user->mnethostid))) {
                    $accessctrl = $acl->accessctrl;
                }
                $changeaccessto = ($accessctrl == 'deny' ? 'allow' : 'deny');
                $buttons[] = " (<a href=\"?acl={$user->id}&amp;accessctrl=$changeaccessto&amp;sesskey=".sesskey()."\">".get_string($changeaccessto, 'mnet') . " access</a>)";

            } else {
                if ($user->suspended) {
                    $url = new moodle_url($baseurl, array('unsuspend'=>$user->id, 'sesskey'=>sesskey()));
                    $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/show', $strunsuspend));
                } else {
                    if ($user->id == $USER->id or is_siteadmin($user)) {
                        // no suspending of admins or self!
                    } else {
                        $url = new moodle_url($baseurl, array('suspend'=>$user->id, 'sesskey'=>sesskey()));
                        $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/hide', $strsuspend));
                    }
                }

                if (login_is_lockedout($user)) {
                    $url = new moodle_url($baseurl, array('unlock'=>$user->id, 'sesskey'=>sesskey()));
                    $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/unlock', $strunlock));
                }
            }
        }

        // edit button
        if (has_capability('moodle/user:update', $context)) {
            // prevent editing of admins by non-admins
            if (is_siteadmin($USER) or !is_siteadmin($user)) {
                $url = new moodle_url('/user/editadvanced.php', array('id'=>$user->id, 'course'=>$site->id));
                $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/edit', $stredit));
            }
        }

        // the last column - confirm or mnet info
        if (is_mnet_remote_user($user)) {
            // all mnet users are confirmed, let's print just the name of the host there
            if (isset($mnethosts[$user->mnethostid])) {
                $lastcolumn = get_string($accessctrl, 'mnet').': '.$mnethosts[$user->mnethostid]->name;
            } else {
                $lastcolumn = get_string($accessctrl, 'mnet');
            }
        } else if ($user->confirmed == 0) {
            if (has_capability('moodle/user:update', $context)) {
                $lastcolumn = html_writer::link(new moodle_url($baseurl, array('confirmuser'=>$user->id, 'sesskey'=>sesskey())), $strconfirm);
            } else {
                $lastcolumn = "<span class=\"dimmed_text\">".get_string('confirm')."</span>";
            }

            $lastcolumn .= ' | ' . html_writer::link(new moodle_url($baseurl,
                    [
                        'resendemail' => $user->id,
                        'sesskey' => sesskey()
                    ]
                ), $strresendemail);
        }

        if ($user->lastaccess) {
            $strlastaccess = format_time(time() - $user->lastaccess);
        } else {
            $strlastaccess = get_string('never');
        }
        $fullname = fullname($user, true);

        $row = array ();

        if (isset($grouped_users_courses[$user->id]))
            $grouped_user_data = $grouped_users_courses[$user->id];
        else
            $grouped_user_data = course::get_empty_group_user_courses_obj();

        $courses_table = table::generate_table_from_object($grouped_user_data,
            [
                'courses' => [
                    'fieldname' => $course,
                    'type' => 'link',
                    'url' => '/course/view.php',
                    'urlparams' => [
                        'id' => [
                            'type'  => 'field',
                            'value' => 'courseids'
                        ]
                    ]
                ],
                'roles' => [
                    'fieldname' => $roles,
                    'type' => 'text'
                ],
                'enrol_methods' => [
                    'fieldname' => $enrol_method,
                    'type' => 'text'
                ],
            ]
        );

        $cohorts_actions = array();
        if (has_capability('moodle/cohort:manage', $context)) {
            $remove_params = new remove_entry_params($user->id, $baseurl);
            $cohorts_actions[] = array(
                'idfield' => 'chtids',
                'closure' => cohort::get_cohort_remove_member_link()->bindTo($remove_params)
            );
        }

        $cohorts_add = '';
        if (has_capability('moodle/cohort:manage', $context)) {
            $cohorts_add = html_writer::link(
                new moodle_url('/blocks/user_manager/cohort/addtocht.php', array(
                        'userid' => $user->id,
                        'returnurl' => $baseurl
                    )),
                '[' . get_string('add', 'block_user_manager') . ']'
            );
        }

        if (isset($grouped_users_cohorts[$user->id]))
            $grouped_user_data = $grouped_users_cohorts[$user->id];
        else
            $grouped_user_data = cohort::get_empty_group_user_cohorts_obj();

        $cohorts_table = table::generate_table_from_object($grouped_user_data,
            [
                'cht_codes_mdl' => [
                    'fieldname' => $cht_code_mdl,
                    'type' => 'link',
                    'url' => '/cohort/assign.php',
                    'urlparams' => [
                        'id' => [
                            'type' => 'field', // field - поле объекта, raw - заданное значение (любые данные)
                            'value' => 'chtids'
                        ],
                        'returnurl' => [
                            'type' => 'raw',
                            'value' => $baseurl
                        ],
                    ]
                ],

                // TODO: Связать добавление этих полей с наличием или отсутствием таблицы - block_cohort1c_synch
                'cht_codes' => [
                    'fieldname' => $cht_code,
                    'type' => 'link',
                    'url' => '/cohort/assign.php',
                    'urlparams' => [
                        'id' => [
                            'type' => 'field', // field - поле объекта, raw - заданное значение (любые данные)
                            'value' => 'chtids'
                        ],
                        'returnurl' => [
                            'type' => 'raw',
                            'value' => $baseurl
                        ],
                    ]
                ],
                'forms' => [
                    'fieldname' => $form,
                    'type' => 'text'
                ]
            ], $cohorts_actions, $cohorts_add
        );

        $i++;

        $card = '
            <div class="um-user">
                <div class="um-user__main-info">
                    <div class="um-user__fullname">
                        <a href=../../user/view.php?id='.$user->id.'&amp;course='.$site->id.'>'.$fullname.' | '. $user->username .'</a>
                    </div>
                    <div class="um-user__lastcolumn">'.$lastcolumn.'</div>
                    <div class="um-user__lastaccess um-badge um-badge-primary">'.$strlastaccess.'</div>
                    <div class="um-user__edit">'.implode(' ', $buttons).'</div>
                </div>
                <div class="um-user__additional-info">
                    <ul class="nav nav-tabs um-nav-tabs" id="tablist-'.$i.'" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link um-nav-link" id="courses-tab-'.$i.'" data-toggle="tab" href="#courses-'.$i.'" role="tab" aria-controls="courses" aria-selected="true">Courses</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link um-nav-link" id="cohorts-tab-'.$i.'" data-toggle="tab" href="#cohorts-'.$i.'" role="tab" aria-controls="cohorts" aria-selected="false">Cohorts</a>
                        </li>
                    </ul>
                    <div class="tab-content um-tab-content">
                        <div class="tab-pane fade um-tab-pane" id="courses-'.$i.'" role="tabpanel" aria-labelledby="courses-tab-'.$i.'">
                            <span class="um-tab-close" data-close="tab">
                                <img src="images/close.svg" alt="Close tab">
                            </span>
                            '.$courses_table.'
                        </div>
                        <div class="tab-pane fade um-tab-pane" id="cohorts-'.$i.'" role="tabpanel" aria-labelledby="cohorts-tab-'.$i.'">
                            <span class="um-tab-close" data-close="tab">
                                <img src="images/close.svg" alt="Close tab">
                            </span>
                            '.$cohorts_table.'
                        </div>
                    </div>
                </div>
            </div>   
        ';

        $row[] = $card;

        if ($user->suspended) {
            foreach ($row as $k => $v) {
                $row[$k] = html_writer::tag('span', $v, array('class'=>'usersuspended'));
            }
        }

        $table->data[] = $row;
    }

    $PAGE->requires->js_amd_inline("
        require(['jquery'], function($) {
            $(document).ready(function() {
                $('span[data-close=\"tab\"]').click(function() {
                    var tabpanel = $(this).parent();
                    var tabID = tabpanel.attr('aria-labelledby');
                    tabpanel.removeClass('active show');
                    $('#' + tabID).removeClass('active show');
                });
            });
        });"
    );
}

// add filters
$ufiltering->display_add();
$ufiltering->display_active();

if (!empty($table)) {
    echo html_writer::start_tag('div', array('class'=>'no-overflow'));
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
    echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
}


if (has_capability('moodle/user:create', $context) ) {
    if ($userfilter == 'cohort')
        $url = new moodle_url('/cohort/addtocht.php', array('id' => $chtid, 'returnurl' => $baseurl));
    else
        $url = new moodle_url('/user/editadvanced.php', array('id' => -1, 'returnto' => $baseurl));

    echo $OUTPUT->single_button($url, get_string('addnewuser'), 'get');
}

if ($userfilter == 'cohort') {
    $url = $returnurl;
    $btn_name = get_string('backtocohorts', 'cohort');
    echo $OUTPUT->single_button($url, $btn_name);
} /*else {
    $url = new moodle_url('/blocks/user_manager/cohort/index.php');
    $btn_name = get_string('cohorts', 'cohort');
}*/

//echo $OUTPUT->single_button($url, $btn_name);

echo $OUTPUT->footer();
