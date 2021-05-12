<?php

use block_user_manager\service;
use block_user_manager\uploaduser;
use block_user_manager\exportformat;
use block_user_manager\table;
use block_user_manager\cohort1c_lib1c;

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('user_form.php');
require_once('../locallib.php');

$iid         = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', null, PARAM_INT);
$delimiter_name = optional_param('delimiter_name', null, PARAM_TEXT);
$email_required = optional_param('email_required', null, PARAM_INT);

$returnurl = required_param('returnurl', PARAM_LOCALURL);

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

$basenode = $usermanagernode->add(get_string('uploadusers', 'tool_uploaduser'), $baseurl);

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

$emptystr = '<'.mb_strtolower(get_string('empty', 'block_user_manager')).'>';

$strings = [
    'emptystring' => $emptystr, 'emailkey' => $emailkey, 'usernamekey' => $usernamekey,
    'dnamekey' => $dnamekey, 'lastnamekey' => $lastnamekey, 'firstnamekey' => $firstnamekey,
    'middlenamekey' => $middlenamekey, 'facultykey' => $facultykey
];

$db_userfields = $DB->get_records("block_user_manager_ufields");

$REQUIRED_FIELDS = ['lastname' ,'firstname', 'middlename', 'username'];
//$AD_FIELDS = ['lastname', 'firstname', 'middlename', 'username', 'password', 'email', 'faculty'];

if ($email_required)
    array_push($REQUIRED_FIELDS, 'email');

$STD_FIELDS = uploaduser::get_stdfields($db_userfields, $REQUIRED_FIELDS);

$PRF_FIELDS = uploaduser::get_profile_fields();

//$FACULTIES = cohort1c_lib1c::GetFaculties();

// Заглушка. TODO: Получать данные из 1с
$FACULTIES = FACULTIES;

// Заглушка. TODO: Получать данные из 1с
$GROUPS = array(
    'ПИ-33' => [
        'Факультет' => 'Физико-математический',
        'Направление подготовки' => 'Прикладная математика и информатика',
        'Профиль' => 'Физика конденсированного состояния вещества',
        'Уровень подготовки' => 'Академический бакалавр',
        'Форма обучения' => 'Очная',
        'Год поступления' => '2018 год'
    ],
    'МР-14' => [
        'Факультет' => 'Физико-математический',
        'Направление подготовки' => 'Математика',
        'Профиль' => 'Физика конденсированного состояния вещества',
        'Уровень подготовки' => 'Академический бакалавр',
        'Форма обучения' => 'Очная',
        'Год поступления' => '2020 год'
    ],
    'СИ-35' => [
        'Факультет' => 'Физико-математический',
        'Направление подготовки' => 'Прикладная математика и информатика',
        'Профиль' => 'Физика конденсированного состояния вещества',
        'Уровень подготовки' => 'Академический бакалавр',
        'Форма обучения' => 'Очная',
        'Год поступления' => '2018 год'
    ],
    'ОС-23' => [
        'Факультет' => 'Физико-математический',
        'Направление подготовки' => 'Прикладная математика и информатика',
        'Профиль' => 'Физика конденсированного состояния вещества',
        'Уровень подготовки' => 'Академический бакалавр',
        'Форма обучения' => 'Очная',
        'Год поступления' => '2019 год'
    ]
);

if (!$iid) {
    $uploaduser_form = new um_admin_uploaduser_form($baseurl, array($STD_FIELDS, STD_FIELDS_EN, STD_FIELDS_RU, $REQUIRED_FIELDS, $PRF_FIELDS));

    if ($formdata = $uploaduser_form->get_data()) {
        $iid = csv_import_reader::get_new_iid('uploaduser');
        $cir = new csv_import_reader($iid, 'uploaduser');

        $content = $uploaduser_form->get_file_content('userfile');

        $delimiter = csv_import_reader::get_delimiter($formdata->delimiter_name);

        $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
        $csvloaderror = $cir->get_error();

        if (!is_null($csvloaderror)) {
            print_error('csvloaderror', '', $baseurl, $csvloaderror);
        }

        list($users, $filecolumns) = uploaduser::get_userlist($cir, $STD_FIELDS, $PRF_FIELDS, $baseurl, $passwordkey, $usernamekey, $emptystr);

        $missingfields = uploaduser::check_required_fields($filecolumns, $REQUIRED_FIELDS);

        if (!count($missingfields)) {
            if (count($users)) {
                $filename_csv = clean_filename(mb_strtolower(get_string('users')) . '_' . mb_strtolower(get_string('list')));
                $users_csv = exportformat::export_csv($users, $filecolumns, $filename_csv, $formdata->delimiter_name, false);

                $content = $users_csv->print_csv_data(true);

                $cir->load_csv_content($content, 'UTF-8', $formdata->delimiter_name);
                $csvloaderror = $cir->get_error();

                if (!is_null($csvloaderror)) {
                    print_error('csvloaderror', '', $baseurl, $csvloaderror);
                }

                $urlparams = $urlparams + array(
                    'iid' => $iid,
                    'previewrows' => $formdata->previewrows
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
    }
    else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading_with_help(get_string('uploadusers', 'tool_uploaduser'), 'uploadusers', 'tool_uploaduser');
        echo '<link rel="stylesheet" href="../css/uplodauser.css">';
        $uploaduser_form->display();
        echo $OUTPUT->footer();
    }
} else {
    $cir = new csv_import_reader($iid, 'uploaduser');
    $filecolumns = uploaduser::um_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $baseurl, $passwordkey);


    $selectaction_form = new um_select_selectaction_form($baseurl, array(
        STD_FIELDS_EN, STD_FIELDS_RU, $REQUIRED_FIELDS, $FACULTIES, array_keys($GROUPS)
    ));

    if ($selectaction_form->is_cancelled()) {
        $cir->cleanup(true);
        $baseurl->remove_params(['previewrows', 'iid', 'delimiter_name', 'email_required']);
        redirect($baseurl);
    }
    elseif ($formdata = $selectaction_form->get_data()) {
        list($users, $filecolumns) = uploaduser::get_userlist($cir, $STD_FIELDS, $PRF_FIELDS, $baseurl, $passwordkey, $usernamekey, $emptystr);
        $action = $formdata->action;

        /*
         * $action = 1 - Export in .csv format
         * $action = 2 - Export in .csv format (AD)
         * $action = 3 - Export in .xls (Excel) format
         * $action = 4 - Upload users
         */
        if ($action === "2") {
            $email_domain = 'no-email.local';

            if (!isset($formdata->faculty) || empty($formdata->faculty)) {
                uploaduser::print_error(get_string('emptyfaculty', 'block_user_manager'), $baseurl);
            }

            list($users, $filecolumns) = uploaduser::prepare_data_for_ad($users, $filecolumns, $formdata, $email_domain, $strings);
        }

        if ($action === "3") {
            if (!isset($formdata->group) || empty($formdata->group)) {
                uploaduser::print_error(get_string('emptygroup', 'block_user_manager'), $baseurl);
            }

            // Если выбран экспорт в формате .xls
            $filename_excel = clean_filename(mb_strtolower(get_string('users')) . '_' . mb_strtolower(get_string('list')) . '_' . gmdate("Ymd_Hi") . '.xls');
            $worksheet_name = get_string('users');
            $filecolumns = array_values(uploaduser::get_fields_helpers(STD_FIELDS_EN, STD_FIELDS_RU, $filecolumns));

            foreach ($filecolumns as $key => $filecolumn) {
                $filecolumns[$key] = mb_convert_case($filecolumn , MB_CASE_TITLE);
            }

            $header = $GROUPS[$formdata->group];

            $users_excel = uploaduser::export_excel($users, $filecolumns, $header, 1, $worksheet_name, $filename_excel, true);
        }

        if ($action === "1" || $action === "2" || $action === "4") {
            // Если выбран экспорт в формате .csv
            $filename_csv = clean_filename(mb_strtolower(get_string('users')) . '_' . mb_strtolower(get_string('list')));
            $users_csv = exportformat::export_csv($users, $filecolumns, $filename_csv, $delimiter_name, false);
        }

        if ($action === "1" || $action === "2") {
            $users_csv->download_file();
        }

        if ($action === "4") {
            // Если выбрана опция "Загрузка пользователей в систему"
            // будет выполнено переадресация

            if (!isset($formdata->previewrows) || empty($formdata->previewrows))
                $formdata->previewrows = 10;

            uploaduser::import_users_into_system($users_csv, $baseurl, $formdata->previewrows, $delimiter_name);
        }
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading_with_help(get_string('uploadusers', 'tool_uploaduser'), 'uploadusers', 'tool_uploaduser');

        echo table::generate_userspreview_table($cir, $filecolumns, $previewrows);

        $PAGE->requires->js_amd_inline("
            require(['jquery'], function($) {
                function elHide(id) {
                    $('#'+id).parent().parent().css({display: 'none'});
                }
                
                function elShow(id) {
                    $('#'+id).parent().parent().css({display: 'flex'});
                }
                
                var previewrowsId = 'id_previewrows';
                var facultyId = 'id_faculty';
                var groupId = 'id_group';
            
                if ($('#id_action').val() !== '2') elHide(facultyId);
                if ($('#id_action').val() !== '3') elHide(groupId);
                if ($('#id_action').val() !== '4') elHide(previewrowsId);
    
                $('#id_action').change(function() {
                    if ($('#id_action').val() === '2') elShow(facultyId); 
                    else elHide(facultyId);
                    
                    if ($('#id_action').val() === '3') elShow(groupId); 
                    else elHide(groupId);
                    
                    if ($('#id_action').val() === '4') elShow(previewrowsId); 
                    else elHide(previewrowsId);
                });
            });"
        );
        //print_object($FACULTIES);

        $selectaction_form->display();
        echo $OUTPUT->footer();
    }
}