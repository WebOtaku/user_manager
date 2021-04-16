<?php

namespace block_user_manager;

require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->libdir.'/excellib.class.php');

use csv_import_reader, csv_export_writer,
    MoodleExcelWorkbook, moodle_url, core_text;

class exportformat
{
	public static function export_csv(
        array $objects, array $fields, string $filename = 'default.csv',
        string $delimiter_name = 'semicolon', $download = false): csv_export_writer
    {
        $csv = new csv_export_writer($delimiter_name);
        $csv->set_filename($filename);

        $csv->add_data($fields);

        foreach ($objects as $key => $object) {
            $row = array();
            foreach ($object as $value)
                $row[] = $value;
            $csv->add_data($row);
        }

        if ($download)
            $csv->download_file();

        return $csv;
    }


    public static function export_excel(
        array $objects, array $fields, string $worksheet_name = 'default',
        string $filename = 'default.xls', bool $download = false): MoodleExcelWorkbook
    {
        $workbook = new MoodleExcelWorkbook('-');
        $workbook->send($filename);
        $worksheet = $workbook->add_worksheet($worksheet_name);

        foreach ($fields as $key => $field)
            $worksheet->write_string(0, $key, $field);

        foreach ($objects as $key => $object) {
            $j = 0;
            foreach ($object as $keynum => $value) {
                $worksheet->write_string($key + 1, $j, $object->$keynum);
                $j++;
            }
        }

        if ($download)
            $workbook->close();

        return $workbook;
    }
}