<?php

use block_user_manager\service;
use block_user_manager\uploaduser;
use block_user_manager\userfields;

require('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/admin/tool/uploaduser/locallib.php');
require_once('user_form.php');
require_once('../locallib.php');
require_once($CFG->libdir.'/excellib.class.php');

$returnurl = required_param('returnurl', PARAM_LOCALURL);

core_php_time_limit::raise(60 * 60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);

service::admin_externalpage_setup('tooluploaduser');
require_capability('moodle/site:uploadusers', context_system::instance());


$pageurl = '/blocks/user_manager/uploaduser/index.php';
$urlparams = array('returnurl' => $returnurl);

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

$STD_FIELDS = array_combine(STD_FIELDS_EN, STD_FIELDS_RU);
$PRF_FIELDS = uploaduser::get_profile_fields();

$uploaduser_form = new um_admin_uploaduser_form($baseurl, array($STD_FIELDS));

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
    // test if columns ok
    $columns = $cir->get_columns();
    $filecolumns = uploaduser::um_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $baseurl);
    $filecolumns[] = 'password';
    $cir->init();

    $users = array();
    while ($line = $cir->next()) {

        $user = new stdClass();

        foreach ($line as $keynum => $value)
        {
            if (!isset($filecolumns[$keynum])) {
                // this should not happen
                continue;
            }

            $key = $filecolumns[$keynum];

            if ($key == 'username' || (isset($STD_FIELDS['username']) && ($key == $STD_FIELDS['username']))) {
                $user->$key = 'st'. trim($value);
            } else {
                $user->$key = trim($value);
            }
        }

        $user->password = service::generate_password($user);
        $users[] = $user;
    }

    $action = $formdata->action;

    if ($action === "2") {
        // Если выбран экспорт в формате Excel
        $filename_excel = clean_filename(mb_strtolower(get_string('users')) . '_' . mb_strtolower(get_string('list')) . '_' . gmdate("Ymd_Hi") . '.xls');
        $worksheet_name = get_string('users');
        $users_excel = uploaduser::export_users_excel($users, $filecolumns, $worksheet_name, $filename_excel, true);
    }

    if ($action === "1" || $action === "3") {
        // Если выбран экспорт в формате Csv
        $filename_csv = clean_filename(mb_strtolower(get_string('users')) . '_' . mb_strtolower(get_string('list')));
        $users_csv = uploaduser::export_users_csv($users, $filecolumns, $filename_csv, $formdata->delimiter_name, false);
    }

    if ($action === "1") {
        $users_csv->download_file();
    }

    if ($action === "3") {
        // Если выбрана опция "Загрузка пользователей в систему"
        // будет выполнено переадресация
        uploaduser::import_users_into_system($users_csv, $baseurl, $formdata->previewrows, $formdata->delimiter_name);
    }
}
else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading_with_help(get_string('uploadusers', 'tool_uploaduser'), 'uploadusers', 'tool_uploaduser');
    $uploaduser_form->display();

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
