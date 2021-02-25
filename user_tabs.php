<?php

use block_user_manager\db_request;
use block_user_manager\cohort;
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

$userfilter       = optional_param('userfilter', '', PARAM_TEXT);

$chtsreturnurl = optional_param('chtsreturnurl', null, PARAM_LOCALURL);

admin_externalpage_setup('editusers');

$sitecontext = context_system::instance();
$site = get_site();

if (!has_capability('moodle/user:update', $sitecontext) and !has_capability('moodle/user:delete', $sitecontext)) {
    print_error('nopermissions', 'error', '', 'edit/delete users');
}

$stredit   = get_string('edit');
$strdelete = get_string('delete');
$strdeletecheck = get_string('deletecheck');
$strshowallusers = get_string('showallusers');
$strsuspend = get_string('suspenduser', 'admin');
$strunsuspend = get_string('unsuspenduser', 'admin');
$strunlock = get_string('unlockaccount', 'admin');
$strconfirm = get_string('confirm');
$strresendemail = get_string('resendemail');

$pageurl = '/blocks/user_manager/user_tabs.php';
$urlparams = array('sort' => $sort, 'dir' => $dir);

// Добавления параметров в запрос для случая фильтрации по данным
if ($userfilter == 'cohort') {
    $urlparams['chtid'] = $chtid;
    $urlparams['userfilter'] = $userfilter;
}

if ($chtsreturnurl)
    $urlparams['chtsreturnurl'] = $chtsreturnurl;

$returnurl = new moodle_url($pageurl, $urlparams + array('perpage' => $perpage, 'page' => $page));
$baseurl = new moodle_url($pageurl, $urlparams + array('perpage' => $perpage));

$PAGE->set_url($returnurl);

// The $user variable is also used outside of these if statements.
$user = null;
if ($confirmuser and confirm_sesskey()) {
    require_capability('moodle/user:update', $sitecontext);
    if (!$user = $DB->get_record('user', array('id'=>$confirmuser, 'mnethostid'=>$CFG->mnet_localhost_id))) {
        print_error('nousers');
    }

    $auth = get_auth_plugin($user->auth);

    $result = $auth->user_confirm($user->username, $user->secret);

    if ($result == AUTH_CONFIRM_OK or $result == AUTH_CONFIRM_ALREADY) {
        redirect($returnurl);
    } else {
        echo $OUTPUT->header();
        redirect($returnurl, get_string('usernotconfirmed', '', fullname($user, true)));
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

    redirect($returnurl, $returnmsg, null, $messagetype);
} else if ($delete and confirm_sesskey()) {              // Delete a selected user, after confirmation
    require_capability('moodle/user:delete', $sitecontext);

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
        $deleteurl = new moodle_url($returnurl, $optionsyes);
        $deletebutton = new single_button($deleteurl, get_string('delete'), 'post');

        echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$fullname'"), $deletebutton, $returnurl);
        echo $OUTPUT->footer();
        die;
    } else if (data_submitted()) {
        if (delete_user($user)) {
            \core\session\manager::gc(); // Remove stale sessions.
            redirect($returnurl);
        } else {
            \core\session\manager::gc(); // Remove stale sessions.
            echo $OUTPUT->header();
            echo $OUTPUT->notification($returnurl, get_string('deletednot', '', fullname($user, true)));
        }
    }
} else if ($acl and confirm_sesskey()) {
    if (!has_capability('moodle/user:update', $sitecontext)) {
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
    redirect($returnurl);

} else if ($suspend and confirm_sesskey()) {
    require_capability('moodle/user:update', $sitecontext);

    if ($user = $DB->get_record('user', array('id'=>$suspend, 'mnethostid'=>$CFG->mnet_localhost_id, 'deleted'=>0))) {
        if (!is_siteadmin($user) and $USER->id != $user->id and $user->suspended != 1) {
            $user->suspended = 1;
            // Force logout.
            \core\session\manager::kill_user_sessions($user->id);
            user_update_user($user, false);
        }
    }
    redirect($returnurl);

} else if ($unsuspend and confirm_sesskey()) {
    require_capability('moodle/user:update', $sitecontext);

    if ($user = $DB->get_record('user', array('id'=>$unsuspend, 'mnethostid'=>$CFG->mnet_localhost_id, 'deleted'=>0))) {
        if ($user->suspended != 0) {
            $user->suspended = 0;
            user_update_user($user, false);
        }
    }
    redirect($returnurl);

} else if ($unlock and confirm_sesskey()) {
    require_capability('moodle/user:update', $sitecontext);

    if ($user = $DB->get_record('user', array('id'=>$unlock, 'mnethostid'=>$CFG->mnet_localhost_id, 'deleted'=>0))) {
        login_unlock_account($user);
    }
    redirect($returnurl);
}
else if (isset($_GET['func']) and confirm_sesskey()) {
    require_capability('moodle/cohort:manage', $sitecontext);

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

                $deleteurl = new moodle_url($returnurl, $optionsyes);
                $deletebutton = new single_button($deleteurl, get_string('delete'), 'get');

                $messagedata = new stdClass();
                $messagedata->lastname = $user->lastname;
                $messagedata->firstname = $user->firstname;
                $messagedata->middlename = $user->middlename;
                $messagedata->chtname = $cht->name;

                echo $OUTPUT->confirm(
                    get_string('removeuserchtwarning', 'block_user_manager', $messagedata),
                    $deletebutton, $returnurl
                );
                echo $OUTPUT->footer();
                die;
            } else  {
                cohort_remove_member($_GET['delchtid'], $_GET['userid']);
                redirect($returnurl);
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

$ufiltering = new user_filtering($fieldnames, $baseurl);
echo $OUTPUT->header();

// Carry on with the user listing
$context = context_system::instance();
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

// Запрос и формирование пользовотельских данных
$users_cohorts = db_request::get_users_cohorts();
$grouped_users_cohorts = cohort::group_users_cohorts_by_users($users_cohorts);

$users_courses = db_request::get_users_courses();
$grouped_users_courses = cohort::group_users_courses_by_users($users_courses);

print_object(db_request::new_get_users_courses($sort, $dir, $page * $perpage, $perpage, $extrasql, $params));

//$grouped_users_data = cohort::group_users_data($users_courses, $users_cohorts);

//$filtered_grouped_users_cohorts = array();
$filtered_grouped_users_data = array();

if ($userfilter === 'cohort')
{
    $cht = $DB->get_record('cohort', array('id' => $chtid));

    $usercount = $DB->count_records('cohort_members', array('cohortid' => $chtid));
    //$filtered_grouped_users_cohorts = cohort::filter_grouped_users_cohorts($grouped_users_cohorts, 'chtids', $chtid);
    //$users = cohort::filter_users_by_cohorts($users, $filtered_grouped_users_cohorts);
    $grouped_users_cohorts = cohort::filter_grouped_users_data($grouped_users_cohorts, 'chtids', $chtid);

    if ($extrasql) $extrasql .= ' AND ';

    $extrasql .= '(';
    $i = 0;
    foreach ($grouped_users_cohorts as $userid => $grouped_user_cohorts) {
        if ($i === 0) $extrasql .= 'id = ' . $userid;
        else $extrasql .= ' OR id = ' . $userid;
        $i++;
    }
    $extrasql .= ')';

    /* *
     * TODO: Отказаться от использования функции get_users_listing()
     * TODO: Добавить аналогичный поиск/фильтрацию и пагинацию для функций get_users_cohorts() и get_users_courses()
     */

    $users = get_users_listing($sort, $dir, $page*$perpage, $perpage, '', '', '',
        $extrasql, $params, $context);

    $usersearchcount = count($users);
} else {
    $users = get_users_listing($sort, $dir, $page * $perpage, $perpage, '', '', '',
        $extrasql, $params, $context);

    $usercount = get_users(false);
    $usersearchcount = get_users(false, '', false, null, "", '', '', '', '', '*', $extrasql, $params);
}

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

    // Подговотовка пользовательских данных для вывода
    /*if ($userfilter == 'cohort')
        //$output_grouped_users_cohorts = cohort::prepare_grouped_users_cohorts_for_output($filtered_grouped_users_cohorts, $baseurl, $sitecontext);
        $output_grouped_users_data = cohort::prepare_grouped_users_data_for_output($filtered_grouped_users_data, $baseurl, $sitecontext);
    else
        //$output_grouped_users_cohorts = cohort::prepare_grouped_users_cohorts_for_output($grouped_users_cohorts, $baseurl, $sitecontext);
        $output_grouped_users_data = cohort::prepare_grouped_users_data_for_output($grouped_users_data, $baseurl, $sitecontext);*/

    /* $PAGE->requires->event_handler('.delete_from_cohort', 'click', 'M.util.show_confirm_dialog',
        array('message' => 'Удалить?'));*/

    //print_object($output_grouped_users_cohorts);

    $course_table_rows = array();

    /*if ($userfilter == 'cohort') {

        foreach ($filtered_grouped_users_data as $userid => $filtered_grouped_user_data) {
            $courses_fields = array('courses', 'roles');

            $amount_els = array();

            foreach ($courses_fields as $field)
                if (is_array($filtered_grouped_user_data->$field))
                    $amount_els[] = count($filtered_grouped_user_data->$field);

            $n = max($amount_els);

            if (!isset($course_table_rows[$userid]))
                $course_table_rows[$userid] = '';

            for ($i = 0; $i < $n; $i++) {
                $course_table_rows[$userid]  .= '<tr>';

                if (isset($filtered_grouped_user_data->courseids[$i]))
                    $url = new moodle_url('/course/view.php', array(
                        'id' => $filtered_grouped_user_data->courseids[$i]
                    ));
                else $url = '#';

                $user_course = (isset($filtered_grouped_user_data->courses[$i]))?
                    html_writer::link($url, $filtered_grouped_user_data->courses[$i]) : "";
                $course_table_rows[$userid]  .= '<td>'. $user_course .'</td>';

                $user_role = (isset($filtered_grouped_user_data->roles[$i]))?
                    $filtered_grouped_user_data->roles[$i] : "";
                $course_table_rows[$userid]  .= '<td>'. $user_role .'</td>';

                $course_table_rows[$userid]  .= '</tr>';
            }
        }
    } else {
        foreach ($grouped_users_data as $userid => $grouped_user_data) {

            $courses_fields_new = array('courses' => [
                'type' => 'link',
                'url' => '/course/view.php',
                'params' => ['id' => 'courseids']
            ], 'roles');

            $courses_fields = array('courses', 'roles');

            $amount_els = array();

            foreach ($courses_fields as $field)
                if (is_array($grouped_user_data->$field))
                    $amount_els[] = count($grouped_user_data->$field);

            $n = max($amount_els);

            if (!isset($course_table_rows[$userid]))
                $course_table_rows[$userid] = '';

            for ($i = 0; $i < $n; $i++) {
                $course_table_rows[$userid]  .= '<tr>';

                if (isset($grouped_user_data->courseids[$i]))
                    $url = new moodle_url('/course/view.php', array(
                        'id' => $grouped_user_data->courseids[$i]
                    ));
                else $url = '#';

                $user_course = (isset($grouped_user_data->courses[$i]))?
                    html_writer::link($url, $grouped_user_data->courses[$i]) : "";
                $course_table_rows[$userid]  .= '<td>'. $user_course .'</td>'; // $course_table_rows[$userid]->courseids

                $user_role = (isset($grouped_user_data->roles[$i]))?
                    $grouped_user_data->roles[$i] : "";
                $course_table_rows[$userid]  .= '<td>'. $user_role .'</td>';

                $course_table_rows[$userid]  .= '</tr>';
            }
        }
    }*/

    echo '<link rel="stylesheet" href="'.new moodle_url('/blocks/user_manager/main.css').'">';

    $table = new html_table();
    $table->head = array ();
    $table->colclasses = array();
    $table->head[] = $fullnamedisplay . ' | ' . $lastaccess;
    $table->attributes['class'] = 'admintable generaltable';
    $table->colclasses[] = 'centeralign';

    $table->id = "users";

    $i = 0;

    foreach ($users as $user) {
        $buttons = array();
        $lastcolumn = '';

        // delete button
        if (has_capability('moodle/user:delete', $sitecontext)) {
            if (is_mnet_remote_user($user) or $user->id == $USER->id or is_siteadmin($user)) {
                // no deleting of self, mnet accounts or admins allowed
            } else {
                $url = new moodle_url($returnurl, array('delete'=>$user->id, 'sesskey'=>sesskey()));
                $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/delete', $strdelete));
            }
        }

        // suspend button
        if (has_capability('moodle/user:update', $sitecontext)) {
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
                    $url = new moodle_url($returnurl, array('unsuspend'=>$user->id, 'sesskey'=>sesskey()));
                    $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/show', $strunsuspend));
                } else {
                    if ($user->id == $USER->id or is_siteadmin($user)) {
                        // no suspending of admins or self!
                    } else {
                        $url = new moodle_url($returnurl, array('suspend'=>$user->id, 'sesskey'=>sesskey()));
                        $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/hide', $strsuspend));
                    }
                }

                if (login_is_lockedout($user)) {
                    $url = new moodle_url($returnurl, array('unlock'=>$user->id, 'sesskey'=>sesskey()));
                    $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/unlock', $strunlock));
                }
            }
        }

        // edit button
        if (has_capability('moodle/user:update', $sitecontext)) {
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
            if (has_capability('moodle/user:update', $sitecontext)) {
                $lastcolumn = html_writer::link(new moodle_url($returnurl, array('confirmuser'=>$user->id, 'sesskey'=>sesskey())), $strconfirm);
            } else {
                $lastcolumn = "<span class=\"dimmed_text\">".get_string('confirm')."</span>";
            }

            $lastcolumn .= ' | ' . html_writer::link(new moodle_url($returnurl,
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

        $addmemberurl = html_writer::link(
            new moodle_url('/blocks/user_manager/cohort/add_member_view.php', $urlparams + array(
                    'userid'=> $user->id,
                    'page' => $page
                )),
            '['.get_string('add', 'block_user_manager').']'
        );

        /*$cohorts__list = ($output_grouped_users_data[$user->id]->cht_codes_mdl)?
            $output_grouped_users_data[$user->id]->cht_codes_mdl . '<br>' . $addmemberurl :
            $output_grouped_users_data[$user->id]->cht_codes_mdl . $addmemberurl;*/

        /*$card_old = '
            <div class="user">
                <div class="user__main-info">
                    <div class="user__fullname">
                        <a href=../../user/view.php?id='.$user->id.'&amp;course='.$site->id.'>'.$fullname.'</a>
                        <div class="user__lastaccess um-badge um-badge-primary">'.$strlastaccess.'</div>
                    </div>
                    <div class="user__edit">'.implode(' ', $buttons).'</div>
                </div>
                <div class="user__addition-info">
                    <ul class="nav nav-tabs um-nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link um-nav-link" id="courses-tab" data-toggle="tab" href="#courses-'.$i.'" role="tab" aria-controls="courses" aria-selected="true">Courses</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link um-nav-link" id="cohorts-tab" data-toggle="tab" href="#cohorts-'.$i.'" role="tab" aria-controls="cohorts" aria-selected="false">Cohorts</a>
                        </li>
                    </ul>
                    <div class="tab-content um-tab-content">
                        <div class="tab-pane fade um-tab-pane" id="courses-'.$i.'" role="tabpanel" aria-labelledby="courses-tab">
                            <table class="table um-table">
                                <thead>
                                    <tr>
                                        <th scope="col">'.$course.'</th>
                                        <th scope="col">'.$roles.'</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    '.$course_table_rows[$user->id].'
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade um-tab-pane" id="cohorts-'.$i++.'" role="tabpanel" aria-labelledby="cohorts-tab">
                            <table class="table um-table">
                                <thead>
                                    <tr>
                                        <th scope="col">'.$cht_code_mdl.'</th>
                                        <th scope="col">'.$cht_code.'</th>
                                        <th scope="col">'.$form.'</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>'.$cohorts__list.'</td>
                                        <td>'.$output_grouped_users_data[$user->id]->cht_codes.'</td>
                                        <td>'.$output_grouped_users_data[$user->id]->forms.'</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div> 
        ';*/

        $courses_table = cohort::generate_table_from_object($grouped_users_courses[$user->id],
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
        if (has_capability('moodle/cohort:manage', $sitecontext)) {
            $remove_params = new remove_entry_params($user->id, $baseurl);
            $cohorts_actions[] = array(
                'idfield' => 'chtids',
                'closure' => cohort::get_cohort_remove_member_link()->bindTo($remove_params)
            );
        }

        $cohorts_add = '';
        if (has_capability('moodle/cohort:manage', $sitecontext))
            $cohorts_add = $addmemberurl;

        $cohorts_table = cohort::generate_table_from_object($grouped_users_cohorts[$user->id],
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
                            'value' => new moodle_url('/blocks/user_manager/user_tabs.php')
                        ],
                    ]
                ],
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
                            'value' => new moodle_url('/blocks/user_manager/user_tabs.php')
                        ],
                    ]
                ],
                'forms' => [
                    'fieldname' => $form,
                    'type' => 'text'
                ]
            ], $cohorts_actions, $cohorts_add
        );

        $card = '
            <div class="um-user">
                <div class="um-user__main-info">
                    <div class="um-user__fullname">
                        <a href=../../user/view.php?id='.$user->id.'&amp;course='.$site->id.'>'.$fullname.'</a>
                    </div>
                    <div class="um-user__lastcolumn">'.$lastcolumn.'</div>
                    <div class="um-user__lastaccess um-badge um-badge-primary">'.$strlastaccess.'</div>
                    <div class="um-user__edit">'.implode(' ', $buttons).'</div>
                </div>
                <div class="um-user__additional-info">
                    <ul class="nav nav-tabs um-nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link um-nav-link" id="courses-tab" data-toggle="tab" href="#courses-'.$i.'" role="tab" aria-controls="courses" aria-selected="true">Courses</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link um-nav-link" id="cohorts-tab" data-toggle="tab" href="#cohorts-'.$i.'" role="tab" aria-controls="cohorts" aria-selected="false">Cohorts</a>
                        </li>
                    </ul>
                    <div class="tab-content um-tab-content">
                        <div class="tab-pane fade um-tab-pane" id="courses-'.$i.'" role="tabpanel" aria-labelledby="courses-tab">
                            '. $courses_table .'
                        </div>
                        <div class="tab-pane fade um-tab-pane" id="cohorts-'.$i++.'" role="tabpanel" aria-labelledby="cohorts-tab">
                            '. $cohorts_table .'
                        </div>
                    </div>
                </div>
            </div>   
        ';

        $row[] = $card;

        // <span class="lastcolumn">'.$lastcolumn.'</span>

        if ($user->suspended) {
            foreach ($row as $k => $v) {
                $row[$k] = html_writer::tag('span', $v, array('class'=>'usersuspended'));
            }
        }

        /*foreach ($extracolumns as $field) {
            $row[] = $user->{$field};
        }*/
        /*$row[] = $user->city;
        $row[] = $user->country;*/

        /*if ($output_grouped_users_data) {
            if ($output_grouped_users_data[$user->id]->cht_codes_mdl)
                $row[] = $output_grouped_users_data[$user->id]->cht_codes_mdl . '<br>' . $addmemberurl;
            else
                $row[] = $output_grouped_users_data[$user->id]->cht_codes_mdl . $addmemberurl;

            $row[] = $output_grouped_users_data[$user->id]->cht_codes;
            $row[] = $output_grouped_users_data[$user->id]->forms;
        }
        else {
            $row[] = $addmemberurl;
            $row[] = '';
            $row[] = '';
        }

        $row[] = $strlastaccess;*/

        $table->data[] = $row;
    }
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

if (has_capability('moodle/user:create', $sitecontext) ) {
    if ($userfilter == 'cohort')
        $url = new moodle_url('/cohort/assign.php', array('id' => $chtid, 'returnurl' => $returnurl));
    else
        $url = new moodle_url('/user/editadvanced.php', array('id' => -1, 'returnto' => $returnurl));

    echo $OUTPUT->single_button($url, get_string('addnewuser'), 'get');
}

if ($userfilter == 'cohort') {
    $url = $chtsreturnurl;
    $btn_name = get_string('backtocohorts', 'cohort');
} else {
    $url = new moodle_url('/blocks/user_manager/group.php');
    $btn_name = get_string('cohorts', 'cohort');
}

echo $OUTPUT->single_button($url, $btn_name);

echo $OUTPUT->footer();
?>
