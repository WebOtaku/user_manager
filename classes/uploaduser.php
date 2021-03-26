<?php

namespace block_user_manager;

use csv_import_reader, csv_export_writer, moodle_url;

class uploaduser
{
    public static function um_validate_user_upload_columns(csv_import_reader $cir, $stdfields, moodle_url $returnurl): array
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
        foreach ($columns as $key => $unused)
        {
            $field = $columns[$key];
            $field = trim($field);
            $lcfield = mb_strtolower($field);

            if (in_array($field, $stdfields) or in_array($lcfield, $stdfields)) {
                $newfield = $lcfield;
            } else {
                $cir->close();
                $cir->cleanup();
                print_error('invalidfieldname', 'error', $returnurl, $field);
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

    public static function export_users_csv(array $users, array $fields, moodle_url $returnurl,
                                            string $filename = 'users.csv', string $delimiter_name = ';', $download = false): csv_export_writer
    {
        $users_csv = new csv_export_writer($delimiter_name);
        $users_csv->set_filename($filename);

        print_object($fields);

        $users_csv->add_data($fields);

        foreach ($users as $key => $user) {
            $row = array();
            foreach ($user as $value)
                $row[] = $value;
            $users_csv->add_data($row);
        }

        print_object($users_csv->print_csv_data());

        if ($download)
            $users_csv->download_file();

        return $users_csv;
    }

    public static function export_excel() {

    }
}