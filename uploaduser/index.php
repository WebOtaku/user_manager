<?php

use block_user_manager\service;
use block_user_manager\uploaduser;
use block_user_manager\exportformat;
use block_user_manager\table;
use block_user_manager\cohort1c_lib1c;
use block_user_manager\string_operation;

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('user_form.php');
require_once('../locallib.php');

$iid            = optional_param('iid', '', PARAM_INT);
$previewrows    = optional_param('previewrows', null, PARAM_INT);
$delimiter_name = optional_param('delimiter_name', null, PARAM_TEXT);
$email_required = optional_param('email_required', null, PARAM_INT);
$upload_method  = optional_param('upload_method', null, PARAM_TEXT);
$group          = optional_param('group', null, PARAM_TEXT);
$from           = optional_param('from', null, PARAM_TEXT);
$action         = optional_param('action', null, PARAM_TEXT);

$returnurl      = required_param('returnurl', PARAM_LOCALURL);

core_php_time_limit::raise(60 * 60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);

service::admin_externalpage_setup('tooluploaduser');
require_capability('moodle/site:uploadusers', context_system::instance());

$pageurl = '/blocks/user_manager/uploaduser/index.php';
$urlparams = array('returnurl' => $returnurl);

if ($iid) {
    $urlparams['iid'] = $iid;
}

if ($previewrows) {
    $urlparams['previewrows'] = $previewrows;
}

if ($delimiter_name) {
    $urlparams['delimiter_name'] = $delimiter_name;
}

if ($upload_method) {
    $urlparams['upload_method'] = $upload_method;
}

if ($group) {
    $urlparams['group'] = $group;
}

if ($from) {
    $urlparams['from'] = $from;
}

$baseurl = new moodle_url($pageurl, $urlparams);

$pagetitle = get_string('uploadusers', 'tool_uploaduser');
$PAGE->set_url($baseurl);

// Навигация: Начало
$backnode = $PAGE->navigation->add(get_string('back'), $returnurl);
$usermanagernode = $backnode->add(get_string('user_manager', 'block_user_manager'));

$userstableurl_params = array('returnurl' => $returnurl);
$userstableurl = new moodle_url('/blocks/user_manager/user.php', $userstableurl_params);
$userstablenode = $usermanagernode->add(get_string('users_table', 'block_user_manager'), $userstableurl);

$chtstableurl_params = array('returnurl' => $returnurl);
$chtstableurl = new moodle_url('/blocks/user_manager/cohort/index.php', $chtstableurl_params);
$chtstablenode = $usermanagernode->add(get_string('chts_table', 'block_user_manager'), $chtstableurl);

$new_baseurl = new moodle_url($pageurl, array('returnurl' => $returnurl));
$basenode = $usermanagernode->add(get_string('uploadusers', 'tool_uploaduser'), $new_baseurl);

$basenode->make_active();
// Навигация: Конец

$firstnamekey = 'firstname';
$lastnamekey = 'lastname';
$middlenamekey = 'middlename';
$passwordkey = 'password';
$usernamekey = 'username';
$emailkey = 'email';
$facultykey = 'faculty';
$dnamekey = 'dname';
//$authkey = 'auth';

$username_prefix = 'st';

//$emptystr = '<'.mb_strtolower(get_string('empty', 'block_user_manager')).'>';
$emptystr = '';

$strings = [
    'emptystring' => $emptystr, 'emailkey' => $emailkey, 'usernamekey' => $usernamekey,
    'dnamekey' => $dnamekey, 'lastnamekey' => $lastnamekey, 'firstnamekey' => $firstnamekey,
    'middlenamekey' => $middlenamekey, 'facultykey' => $facultykey
];

$db_userfields = $DB->get_records("block_user_manager_ufields");

$REQUIRED_FIELDS = ['lastname' ,'firstname', 'middlename', 'username'];

if ($email_required) {
    array_push($REQUIRED_FIELDS, 'email');
}

$STD_FIELDS = uploaduser::get_stdfields($db_userfields, $REQUIRED_FIELDS);
list($PRF_FIELDS, $proffields) = uploaduser::get_profile_fields();

// TODO: Заглушка.  Получать данные из 1с
//$FACULTIES = FACULTIES;
$FACULTIES = cohort1c_lib1c::GetFaculties();

// TODO: Заглушка. Получать данные из 1с
/*$GROUPS = array(
    'ПИ-33' => [
        'Факультет' => 'Физико-математический',
        'Специальность' => 'Прикладная математика и информатика',
        'Специализация' => 'Физика конденсированного состояния вещества',
        'УровеньПодготовки' => 'Академический бакалавр',
        'ФормаОбучения' => 'Очная',
        'Курс' => 'Третий'
    ]
);*/

$GROUPS = cohort1c_lib1c::GetGroups();

$period_end = date("Y");
$period_start = $period_end - 1;

$upload_method_form = new um_select_upload_method_form($baseurl, array(STD_FIELDS_EN, STD_FIELDS_RU, $REQUIRED_FIELDS, $GROUPS));

if (!$upload_method) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading_with_help(get_string('uploadusers', 'tool_uploaduser'), 'uploadusers', 'tool_uploaduser');
    echo '<link rel="stylesheet" href="../css/uplodauser.css">';
    $upload_method_form->display();
    echo $OUTPUT->footer();
    die;
}

if ($upload_method === UPLOAD_METHOD_FILE) {
    if (!$iid) {
        $uploaduser_form = new um_admin_uploaduser_form($baseurl, array(
            $STD_FIELDS, STD_FIELDS_EN, STD_FIELDS_RU, $REQUIRED_FIELDS, $PRF_FIELDS
        ));

        if ($uploaduser_form->is_cancelled()) {
            $baseurl->remove_params(['upload_method', 'previewrows', 'delimiter_name', 'from']);
            redirect($baseurl);
        } else if ($formdata = $uploaduser_form->get_data()) {
            // Изменение названия каталога для временного файла для устранения ошибки с доступок к одному и тому же временному файлу
            $iid = csv_import_reader::get_new_iid('uploaduser_tmp');
            $cir = new csv_import_reader($iid, 'uploaduser_tmp');

            $content = $uploaduser_form->get_file_content('userfile');

            $delimiter = csv_import_reader::get_delimiter($formdata->delimiter_name);

            $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
            $csvloaderror = $cir->get_error();

            if (!is_null($csvloaderror)) {
                print_error('csvloaderror', '', $baseurl, $csvloaderror);
            }

            list($users, $filecolumns) = uploaduser::get_userlist_from_file(
                $cir, $STD_FIELDS, $PRF_FIELDS, $baseurl, $passwordkey, $usernamekey, $emptystr, $username_prefix
            );

            $missingfields = uploaduser::check_required_fields($filecolumns, $REQUIRED_FIELDS);

            if (!count($missingfields)) {
                if (count($users)) {
                    // TODO: возможно стоит сделать в виде одной функции
                    $filename_csv = clean_filename(mb_strtolower(get_string('users')) . '_' . mb_strtolower(get_string('list')));
                    $users_csv = exportformat::export_csv($users, $filecolumns, $filename_csv, $formdata->delimiter_name, false);

                    $content = $users_csv->print_csv_data(true);

                    $cir->load_csv_content($content, 'UTF-8', $formdata->delimiter_name);
                    $csvloaderror = $cir->get_error();

                    if (!is_null($csvloaderror)) {
                        print_error('csvloaderror', '', $baseurl, $csvloaderror);
                    }

                    if (!isset($formdata->previewrows) || empty($formdata->previewrows))
                        $formdata->previewrows = 10;

                    $urlparams = $urlparams + array(
                        'iid' => $iid,
                        'previewrows' => $formdata->previewrows,
                        'from' => UPLOAD_METHOD_FILE
                    );

                    if (isset($formdata->email_required) && $formdata->email_required)
                        $urlparams['email_required'] = $formdata->email_required;

                    $baseurl = new moodle_url($pageurl, $urlparams);

                    redirect($baseurl);
                } else {
                    uploaduser::print_error(get_string('emptyfile', 'block_user_manager'), $baseurl);
                }
            } else {
                $cir->cleanup(true);

                $a = new stdClass();
                $a->missingfields = implode(', ', uploaduser::get_fields_with_helper(STD_FIELDS_EN, STD_FIELDS_RU, $missingfields));
                uploaduser::print_error(get_string('norequiredfields', 'block_user_manager', $a), $baseurl);
            }
        } else {
            echo $OUTPUT->header();
            echo $OUTPUT->heading_with_help(get_string('uploadusers', 'tool_uploaduser'), 'uploadusers', 'tool_uploaduser');
            echo '<link rel="stylesheet" href="../css/uplodauser.css">';
            $uploaduser_form->display();
            echo $OUTPUT->footer();
        }
    } else {
        $cir = new csv_import_reader($iid, 'uploaduser_tmp');
        $filecolumns = uploaduser::um_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $baseurl, $passwordkey);

        $group_info = array();

        // Получение информации о группе при загрузке из 1С
        if ($from === UPLOAD_METHOD_1C && !$action) {
            $group_info = cohort1c_lib1c::GetGroupInfoByGroup($group, $period_start, $period_end, IS_STUDENT_STATUS_1C);
        }

        list($users, $filecolumns) = uploaduser::get_userlist_from_file(
            $cir, $STD_FIELDS, $PRF_FIELDS, $baseurl, $passwordkey, $usernamekey, $emptystr, $username_prefix
        );

        // Получение информации о группе при загрузке из файла
        if (count($users) && $from === UPLOAD_METHOD_FILE && !$action) {
            foreach ($users as $user) {
                if (isset($user->{$usernamekey})) {
                    $username = $user->{$usernamekey};
                    $username = string_operation::remove_prefix($username, $username_prefix);
                    $group_info = cohort1c_lib1c::GetGroupInfoByUsername($username, $period_start, $period_end, IS_STUDENT_STATUS_1C);

                    if (isset($group_info['Группа'])) $group = $group_info['Группа'];
                    if (count($group_info)) break;
                }
            }
        }

        $selectaction_form = new um_select_action_form($baseurl, array(
            STD_FIELDS_EN, STD_FIELDS_RU, $REQUIRED_FIELDS, $FACULTIES, $GROUPS,
            $from, $group, $group_info
        ));

        if ($selectaction_form->is_cancelled()) {
            $cir->cleanup(true);
            $baseurl->remove_params(['previewrows', 'iid', 'delimiter_name', 'email_required', 'group']);

            if ($from === UPLOAD_METHOD_1C) {
                $baseurl->remove_params(['upload_method', 'from']);
            }

            redirect($baseurl);
        } elseif ($formdata = $selectaction_form->get_data()) {
            $action = $formdata->action;

            /*
             * ACTION_EXPORTCSV - Export in .csv format
             * ACTION_EXPORTCSVAD - Export in .csv format (AD)
             * ACTION_EXPORTXLS - Export in .xls (Excel) format
             * ACTION_UPLOADUSER - Upload users
             */
            if ($action === ACTION_EXPORTCSVAD) {
                $email_domain = 'no-email.local';

                if (!isset($formdata->faculty) || empty($formdata->faculty)) {
                    uploaduser::print_error(get_string('nofacultyspecified', 'block_user_manager'), $baseurl);
                }

                list($users, $filecolumns) = uploaduser::prepare_data_for_ad($users, $filecolumns, $formdata, $email_domain, $strings);
                $filename_csv = clean_filename(mb_strtolower(get_string('users')) . '_' . mb_strtolower(get_string('list')) . '_AD');
            }

            if ($action === ACTION_EXPORTXLS) {
                if (!isset($formdata->group) || empty($formdata->group)) {
                    uploaduser::print_error(get_string('nogroupspecifed', 'block_user_manager'), $baseurl);
                }

                $group_info = cohort1c_lib1c::GetGroupInfoByGroup($formdata->group, $period_start, $period_end, IS_STUDENT_STATUS_1C);

                if (!count($group_info)) {
                    uploaduser::print_error(get_string('nogroupinfo', 'block_user_manager'), $baseurl);
                }

                // Если выбран экспорт в формате .xls
                $filename_excel = clean_filename(mb_strtolower(get_string('users')) . '_' . mb_strtolower(get_string('list')) . '_' . $formdata->group . '_' . gmdate("Ymd_Hi") . '.xls');
                $worksheet_name = get_string('users');

                $filecolumns = $REQUIRED_FIELDS;
                array_push($filecolumns, $passwordkey);

                $filecolumns = array_values(uploaduser::get_fields_helpers(STD_FIELDS_EN, STD_FIELDS_RU, $filecolumns));

                foreach ($filecolumns as $key => $filecolumn) {
                    $filecolumns[$key] = string_operation::capitalize_first_letter_cyrillic($filecolumn);
                }

                //$group_info = (object)$GROUPS[$formdata->group]; // TODO: Заглушка
                $header = uploaduser::form_excel_header_from_group_info($group_info, $period_end);

                $users_excel = uploaduser::export_excel($users, $filecolumns, $header, 1, $worksheet_name, $filename_excel, true);
            }

            if ($action === ACTION_EXPORTCSV || $action === ACTION_UPLOADUSER) {
                // list($users, $filecolumns) = uploaduser::prepare_data_for_upload($users, $filecolumns, $formdata, array('authkey' => $authkey));
            }

            if ($action === ACTION_EXPORTCSV || $action === ACTION_UPLOADUSER) {
                $filename_csv = clean_filename(mb_strtolower(get_string('users')) . '_' . mb_strtolower(get_string('list')));
            }

            if ($action === ACTION_EXPORTCSV || $action === ACTION_EXPORTCSVAD || $action === ACTION_UPLOADUSER) {
                // Если выбран экспорт в формате .csv
                $users_csv = exportformat::export_csv($users, $filecolumns, $filename_csv, $delimiter_name, false);
            }

            if ($action === ACTION_EXPORTCSV || $action === ACTION_EXPORTCSVAD) {
                $users_csv->download_file();
            }

            if ($action === ACTION_UPLOADUSER) {
                // Если выбрана опция "Загрузка пользователей в систему"
                // будет выполнено переадресация

                if (!isset($formdata->previewrows) || empty($formdata->previewrows))
                    $formdata->previewrows = 10;

                uploaduser::import_users_into_system($users_csv, $baseurl, $group, $formdata->previewrows, $delimiter_name);
            }
        } else {
            echo $OUTPUT->header();
            echo $OUTPUT->heading_with_help(get_string('uploadusers', 'tool_uploaduser'), 'uploadusers', 'tool_uploaduser');
            echo table::generate_userspreview_table($cir, $filecolumns, $previewrows);
            $selectaction_form->display();
            echo $OUTPUT->footer();
        }
    }
}

if ($upload_method === UPLOAD_METHOD_1C) {
    if ($group) {
        $users1c = cohort1c_lib1c::GetStudentsOfGroup($group, $period_start, $period_end, IS_STUDENT_STATUS_1C);
        $users = uploaduser::get_userlist_from_1c($users1c, $emptystr, $username_prefix);

        $filecolumns = $REQUIRED_FIELDS;
        array_push($filecolumns, $passwordkey);

        if (count($users)) {
            // TODO: возможно стоит сделать в виде одной функции
            $iid = csv_import_reader::get_new_iid('uploaduser_tmp');
            $cir = new csv_import_reader($iid, 'uploaduser_tmp');
            $delimiter_name = 'semicolon';

            $filename_csv = clean_filename(mb_strtolower(get_string('users')) . '_' . mb_strtolower(get_string('list')));
            $users_csv = exportformat::export_csv($users, $filecolumns, $filename_csv, $delimiter_name, false);

            $content = $users_csv->print_csv_data(true);

            $cir->load_csv_content($content, 'UTF-8', $delimiter_name);
            $csvloaderror = $cir->get_error();

            if (!is_null($csvloaderror)) {
                print_error('csvloaderror', '', $baseurl, $csvloaderror);
            }

            if (!isset($previewrows) || empty($previewrows))
                $previewrows = 10;

            $urlparams = $urlparams + array(
                'iid' => $iid,
                'from' => UPLOAD_METHOD_1C,
                'delimiter_name' => $delimiter_name
            );

            $urlparams['upload_method'] = UPLOAD_METHOD_FILE;

            $baseurl = new moodle_url($pageurl, $urlparams);

            redirect($baseurl);
        } else {
            $baseurl->remove_params(['previewrows', 'upload_method', 'group']);
            uploaduser::print_error(get_string('emptygroup', 'block_user_manager'), $baseurl);
        }
    } else {
        $baseurl->remove_params(['previewrows', 'upload_method', 'group']);
        uploaduser::print_error(get_string('nogroupspecifed', 'block_user_manager'), $baseurl);
    }
}