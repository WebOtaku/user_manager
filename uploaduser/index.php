<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Bulk user registration script from a comma separated file
 *
 * @package    tool
 * @subpackage uploaduser
 * @copyright  2004 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_user_manager\service;
use block_user_manager\uploaduser;

require('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/admin/tool/uploaduser/locallib.php');
require_once('user_form.php');
require_once($CFG->libdir.'/excellib.class.php');

$iid         = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);

core_php_time_limit::raise(60 * 60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);

service::admin_externalpage_setup('tooluploaduser');
require_capability('moodle/site:uploadusers', context_system::instance());

$returnurl = new moodle_url('/blocks/user_manager/uploaduser/index.php');
$bulknurl  = new moodle_url('/admin/user/user_bulk.php');

// Сответствие 1 к  1
$input_fields = array(
    'фамилия', 'имя', 'отчество', 'номер зачётной книжки', 'пароль'
);

$output_fields = array(
    'lastname', 'firstname', 'middlename', 'username', 'password'
);


$uploaduser_form = new um_admin_uploaduser_form();

if ($formdata = $uploaduser_form->get_data()) {
    $iid = csv_import_reader::get_new_iid('uploaduser');
    $cir = new csv_import_reader($iid, 'uploaduser');

    $content = $uploaduser_form->get_file_content('userfile');

    //print_object($content);

    $delimiter = csv_import_reader::get_delimiter($formdata->delimiter_name);

    $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
    $csvloaderror = $cir->get_error();

    if (!is_null($csvloaderror)) {
        print_error('csvloaderror', '', $returnurl, $csvloaderror);
    }
    // test if columns ok
    $columns = $cir->get_columns();
    $filecolumns = uploaduser::um_validate_user_upload_columns($cir, $input_fields, $returnurl);
    $cir->init();

    $users = array();
    while ($line = $cir->next()) {

        $user = new stdClass();

        foreach ($line as $keynum => $value) {
            if (!isset($filecolumns[$keynum])) {
                // this should not happen
                continue;
            }

            $inkey = $filecolumns[$keynum];
            $outkey = $output_fields[$keynum];

            if ($inkey == 'номер зачётной книжки') {
                $user->$outkey = 'st'. trim($value);
            } else {
                $user->$outkey = trim($value);
            }
        }

        $user->password = service::generate_password($user);
        //print_object($user);
        $users[] = $user;
    }

    $filename_excel = clean_filename(mb_strtolower(get_string('users')) . '_' . mb_strtolower(get_string('list')) . '_' . gmdate("Ymd_Hi") . '.xls');

    /*$users_csv = new csv_export_writer($formdata->delimiter_name);
    $users_csv->set_filename($filename);

    $users_csv->add_data($output_fields);

    foreach ($users as $key => $user) {
        $row = array();
        foreach ($user as $value)
            $row[] = $value;
        $users_csv->add_data($row);
    }

    $content = $users_csv->print_csv_data(true);

    $readcount = $cir->load_csv_content($content, 'utf-8', $formdata->delimiter_name);
    $csvloaderror = $cir->get_error();

    if (!is_null($csvloaderror)) {
        print_error('csvloaderror', '', $returnurl, $csvloaderror);
    }*/


    $filename_csv = clean_filename(mb_strtolower(get_string('users')) . '_' . mb_strtolower(get_string('list')));
    $users_csv = uploaduser::export_users_csv($users, $output_fields, $returnurl, $filename_csv, $formdata->delimiter_name, true);

    $workbook = new MoodleExcelWorkbook('-');
    $workbook->send($filename_excel);
    $users_excel = $workbook->add_worksheet(get_string('users'));

    foreach ($output_fields as $key => $output_field)
        $users_excel->write_string(0, $key, $output_field);

    foreach ($users as $key => $user) {
        $j = 0;
        foreach ($user as $keynum => $value) {
            $users_excel->write_string($key + 1, $j,  $user->$keynum);
            $j++;
        }
    }

    $content = $users_csv->print_csv_data(true);

    $iid = csv_import_reader::get_new_iid('uploaduser');
    $cir = new csv_import_reader($iid, 'uploaduser');

    $cir->load_csv_content($content, 'utf-8', $formdata->delimiter_name);
    $csvloaderror = $cir->get_error();

    if (!is_null($csvloaderror)) {
        print_error('csvloaderror', '', $returnurl, $csvloaderror);
    }

    //$cir->close();

    //$workbook->close();

//    redirect(new moodle_url('/admin/tool/uploaduser/index.php', array(
//        'iid' => $iid,
//        'previewrows' => $formdata->previewrows,
//    )));

    //exit;
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading_with_help(get_string('uploadusers', 'tool_uploaduser'), 'uploadusers', 'tool_uploaduser');
    $uploaduser_form->display();
    echo $OUTPUT->footer();
}
