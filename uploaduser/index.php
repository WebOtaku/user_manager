<?php

use block_user_manager\service;
use block_user_manager\uploaduser;
use block_user_manager\exportformat;
use block_user_manager\table;

require('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('user_form.php');
require_once('../locallib.php');

$iid         = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', null, PARAM_INT);
$delimiter_name = optional_param('delimiter_name', null, PARAM_TEXT);

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

$passwordkey = 'password';
$usernamekey = 'username';

$db_userfields = $DB->get_records("block_user_manager_ufields");

$STD_FIELDS = uploaduser::get_stdfields($db_userfields);

// TODO: Надо вывести под таблицей допустимых полей
$PRF_FIELDS = uploaduser::get_profile_fields();

$REQUIRED_FIELDS = array(
    'lastname' ,'firstname', 'middlename', 'username'
);

// 'email'

if (!$iid) {
    $uploaduser_form = new um_admin_uploaduser_form($baseurl, array($STD_FIELDS, STD_FIELDS_EN, STD_FIELDS_RU, $baseurl));

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

        list($users, $filecolumns) = uploaduser::get_userlist($cir, $STD_FIELDS, $PRF_FIELDS, $baseurl, $passwordkey, $usernamekey);

        print_object($users);

        /*if (count($users)) {
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

            $baseurl = new moodle_url($pageurl, $urlparams);

            redirect($baseurl);
        }*/
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
    $filecolumns = uploaduser::um_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $baseurl);

    $selectaction_form = new um_select_selectaction_form($baseurl);

    if ($selectaction_form->is_cancelled()) {
        $cir->cleanup(true);
        $baseurl->remove_params(['previewrows', 'iid', 'delimiter_name']);
        redirect($baseurl);
    }
    elseif ($formdata = $selectaction_form->get_data()) {
        list($users, $filecolumns) = uploaduser::get_userlist($cir, $STD_FIELDS, $PRF_FIELDS, $baseurl, $passwordkey, $usernamekey);

        $action = $formdata->action;

        if ($action === "2") {
            // Если выбран экспорт в формате .xls
            $filename_excel = clean_filename(mb_strtolower(get_string('users')) . '_' . mb_strtolower(get_string('list')) . '_' . gmdate("Ymd_Hi") . '.xls');
            $worksheet_name = get_string('users');
            $users_excel = exportformat::export_excel($users, $filecolumns, $worksheet_name, $filename_excel, true);
        }

        if ($action === "1" || $action === "3") {
            // Если выбран экспорт в формате .csv
            $filename_csv = clean_filename(mb_strtolower(get_string('users')) . '_' . mb_strtolower(get_string('list')));
            $users_csv = exportformat::export_csv($users, $filecolumns, $filename_csv, $delimiter_name, false);
        }

        if ($action === "1") {
            $users_csv->download_file();
        }

        if ($action === "3") {
            // Если выбрана опция "Загрузка пользователей в систему"
            // будет выполнено переадресация
            uploaduser::import_users_into_system($users_csv, $baseurl, $formdata->previewrows, $delimiter_name);
        }
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading_with_help(get_string('uploadusers', 'tool_uploaduser'), 'uploadusers', 'tool_uploaduser');

        echo table::generate_userspreview_table($cir, $filecolumns, $previewrows);

        $selectaction_form->display();

        $PAGE->requires->js_amd_inline("
            require(['jquery'], function($) {
                if ($('#id_action').val() !== '3') {
                    $('#id_previewrows').parent().parent().css({display: 'none'});
                }

                $('#id_action').change(function() {
                    if ($('#id_action').val() === '3') {
                        $('#id_previewrows').parent().parent().css({display: 'flex'});
                    } else {
                        $('#id_previewrows').parent().parent().css({display: 'none'});
                    }
                });
            });"
        );

        echo $OUTPUT->footer();
    }
}