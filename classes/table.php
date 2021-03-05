<?php

namespace block_user_manager;

use html_writer;
use moodle_url;

class table
{
    public static function generate_table_from_object($grouped_user_data = [], $object_fields_names = [], $actions = [], $action_add = '')
    {
        $result_table_str = '<table class="table um-table">';

        $result_table_str .= '<thead><tr>';

        foreach ($object_fields_names as $object_field_name) {
            $result_table_str .= '<th>';
            $result_table_str .= (isset($object_field_name['fieldname']))? $object_field_name['fieldname'] : '';
            $result_table_str .= '</th>';
        }
        if (count($actions))
            $result_table_str .= '<th></th>';

        $result_table_str .= '</tr></thead>';

        $result_table_str .= '<tbody>';

        $num_els = array();

        foreach ($object_fields_names as $field => $params) {
            if (isset($grouped_user_data->$field) && is_array($grouped_user_data->$field))
                $num_els[] = count($grouped_user_data->$field);
            else $num_els[] = 0;
        }

        $n = max($num_els);

        if ($n) {
            for ($i = 0; $i < $n; $i++) {
                $result_table_str .= '<tr>';

                foreach ($object_fields_names as $obj_field => $obj_field_params) {
                    if (isset($obj_field_params['type'])) {
                        if ($obj_field_params['type'] === 'link') {
                            $urlparams = array();

                            if (isset($obj_field_params['urlparams']) && is_array($obj_field_params['urlparams'])) {
                                foreach ($obj_field_params['urlparams'] as $field => $params) {
                                    if (isset($params['type'])) {
                                        $value = (isset($params['value'])) ? $params['value'] : '';

                                        if ($params['type'] === 'field') {
                                            if (isset($grouped_user_data->$value)) {
                                                if (is_array($grouped_user_data->$value))
                                                    $urlparams[$field] = (isset($grouped_user_data->$value[$i])) ?
                                                        $grouped_user_data->$value[$i] : '';
                                                else $urlparams[$field] = $grouped_user_data->$value;
                                            }
                                        }

                                        if ($params['type'] === 'raw') {
                                            $urlparams[$field] = $value;
                                        }
                                    }
                                }
                            }

                            if (isset($obj_field_params['url'])) {
                                $url = new moodle_url($obj_field_params['url'], $urlparams);
                            } else {
                                $url = new moodle_url('', $urlparams);
                            }

                            $data_str = (isset($grouped_user_data->$obj_field[$i])) ?
                                html_writer::link($url, $grouped_user_data->$obj_field[$i]) : '-';
                            $result_table_str .= '<td>' . $data_str . '</td>';
                        }

                        if ($obj_field_params['type'] == 'text') {
                            $data_str = (isset($grouped_user_data->$obj_field[$i])) ? $grouped_user_data->$obj_field[$i] : '-';
                            $result_table_str .= '<td>' . $data_str . '</td>';
                        }
                    }
                }

                foreach ($actions as $action) {
                    if (isset($action['idfield'])) {
                        $field = $action['idfield'];
                        if (isset($grouped_user_data->$field) && is_array($grouped_user_data->$field)) {
                            if (isset($action['closure'])) {
                                $closure = $action['closure'];
                                $result_table_str .= '<td>' . $closure($grouped_user_data->$field[$i]) . '</td>';
                            }
                        }
                    }
                }

                $result_table_str .= '</tr>';
            }
        } else {
            $result_table_str .= '<tr><td colspan="'. count($object_fields_names) .'">'.get_string('noentries', 'block_user_manager').'</td></tr>';
        }

        if ($action_add)
            $result_table_str .= '<tr><td colspan="'. count($object_fields_names) .'">'.$action_add.'</td></tr>';

        $result_table_str .= '</tbody></table>';

        return $result_table_str;
    }
}