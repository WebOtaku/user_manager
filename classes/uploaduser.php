<?php

namespace block_user_manager;

use csv_import_reader, csv_export_writer,
    MoodleExcelWorkbook, moodle_url, core_text;

class uploaduser
{
    public static function um_validate_user_upload_columns(csv_import_reader $cir, $stdfields, $profilefields, moodle_url $returnurl): array
    {
        $columns = $cir->get_columns();

        if (empty($columns)) {
            $cir->close();
            $cir->cleanup();
            print_error('cannotreadtmpfile', 'error', $returnurl);
        }
        if (count($columns) < 2) {
            $cir->close();
            $cir->cleanup();
            print_error('csvfewcolumns', 'error', $returnurl);
        }

        // test columns
        $processed = array();
        foreach ($columns as $key=>$unused) {
            $field = $columns[$key];
            $field = trim($field);
            $lcfield = core_text::strtolower($field);

            //print_object(self::field_exist($field, $stdfields));

            /*if (array_key_exists($field, $stdfields) || array_key_exists($lcfield, $stdfields)) {
                // standard fields are only lowercase
                $newfield = $lcfield;
            }
            else if (in_array($field, $stdfields) || in_array($lcfield, $stdfields)) {
                $newfield = array_search($lcfield, $stdfields);
            }*/
            if ($newfield = self::field_exist($field, $stdfields)) {
                // empty
            }
            else if (in_array($field, $profilefields)) {
                // exact profile field name match - these are case sensitive
                $newfield = $field;

            } else if (in_array($lcfield, $profilefields)) {
                // hack: somebody wrote uppercase in csv file, but the system knows only lowercase profile field
                $newfield = $lcfield;

            } else if (preg_match('/^(sysrole|cohort|course|group|type|role|enrolperiod|enrolstatus)\d+$/', $lcfield)) {
                // special fields for enrolments
                $newfield = $lcfield;

            } else {
                /*$cir->close();
                $cir->cleanup();
                print_error('invalidfieldname', 'error', $returnurl, $field);*/
                continue;
            }

            if (in_array($newfield, $processed)) {
                $cir->close();
                $cir->cleanup();
                print_error('duplicatefieldname', 'error', $returnurl, $newfield);
            }
            $processed[$key] = $newfield;
        }

        return $processed;
    }

    public static function export_users_csv(
        array $users, array $fields, string $filename = 'users.csv',
        string $delimiter_name = 'semicolon', $download = false): csv_export_writer
    {
        $users_csv = new csv_export_writer($delimiter_name);
        $users_csv->set_filename($filename);

        $users_csv->add_data($fields);

        foreach ($users as $key => $user) {
            $row = array();
            foreach ($user as $value)
                $row[] = $value;
            $users_csv->add_data($row);
        }

        if ($download)
            $users_csv->download_file();

        return $users_csv;
    }

    public static function export_users_excel(
        array $users, array $fields, string $worksheet_name = 'users',
        string $filename = 'users.xls', bool $download = false): MoodleExcelWorkbook
    {
        $workbook = new MoodleExcelWorkbook('-');
        $workbook->send($filename);
        $users_excel = $workbook->add_worksheet($worksheet_name);

        foreach ($fields as $key => $field)
            $users_excel->write_string(0, $key, $field);

        foreach ($users as $key => $user) {
            $j = 0;
            foreach ($user as $keynum => $value) {
                $users_excel->write_string($key + 1, $j,  $user->$keynum);
                $j++;
            }
        }

        if ($download)
            $workbook->close();

        return $workbook;
    }

    public static function import_users_into_system(
        csv_export_writer $users_csv, moodle_url $returnurl, int $previewrows = 10,
        string $delimiter_name = 'semicolon', string $encoding = 'UTF-8')
    {
        $content = $users_csv->print_csv_data(true);

        $iid = csv_import_reader::get_new_iid('uploaduser');
        $cir = new csv_import_reader($iid, 'uploaduser');

        $cir->load_csv_content($content, $encoding, $delimiter_name);
        $csvloaderror = $cir->get_error();

        if (!is_null($csvloaderror)) {
            print_error('csvloaderror', '', $returnurl, $csvloaderror);
        }

        $useruploadurl = new moodle_url('/admin/tool/uploaduser/index.php', array(
            'iid' => $iid,
            'previewrows' => $previewrows,
        ));

        redirect($useruploadurl);
    }

    public static function get_profile_fields()
    {
        global $DB;

        $PRF_FIELDS = array();
        if ($proffields = $DB->get_records('user_info_field')) {
            foreach ($proffields as $key => $proffield) {
                $profilefieldname = 'profile_field_'.$proffield->shortname;
                $PRF_FIELDS[] = $profilefieldname;
                // Re-index $proffields with key as shortname. This will be
                // used while checking if profile data is key and needs to be converted (eg. menu profile field)
                $proffields[$profilefieldname] = $proffield;
                unset($proffields[$key]);
            }
        }

        return $PRF_FIELDS;
    }

    public static function field_exist($field, $stdfields)
    {
        $lcfield = core_text::strtolower($field);

        if (array_key_exists($field, $stdfields) || array_key_exists($lcfield, $stdfields))
            return $lcfield;

        if (in_array($field, $stdfields) || in_array($lcfield, $stdfields))
            return array_search($lcfield, $stdfields);


        foreach ($stdfields as $systemfield => $associatedfields) {
            if (is_array($associatedfields))
                if (in_array($field, $associatedfields) || in_array($lcfield, $associatedfields))
                    return $systemfield;
        }

        return false;
    }
}