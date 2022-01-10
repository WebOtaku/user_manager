<?php

namespace block_user_manager;

use html_writer, html_table, moodle_url, csv_import_reader, core_user, stdClass;

require_once($CFG->dirroot.'/admin/tool/uploaduser/locallib.php');

class table
{
    private static function field_takes_value(stdClass $grouped_user_data, string $field, int $index, $value): bool {
        $flag = false;

        if (isset($grouped_user_data->$field)) {
            if (is_array($grouped_user_data->$field) && isset($grouped_user_data->$field[$index]) &&
                !empty($value) && (strpos($grouped_user_data->$field[$index], $value) !== false)) $flag = true;
        }

        return $flag;
    }

    public static function generate_table_from_object(stdClass $grouped_user_data, array $object_fields_names = [],
                                                      array $actions = [], string $action_add = ''): string
    {
        $result_table_str = '<table class="table um-table">';

        $result_table_str .= '<thead class="um-table__head"><tr class="um-table__row">';

        foreach ($object_fields_names as $object_field_name) {
            $result_table_str .= '<th class="um-table__header-cell">';
            $result_table_str .= (isset($object_field_name['fieldname']))? $object_field_name['fieldname'] : '';
            $result_table_str .= '</th>';
        }
        if (count($actions))
            $result_table_str .= '<th class="um-table__header-cell"></th>';

        $result_table_str .= '</tr></thead>';

        $result_table_str .= '<tbody class="um-table__body">';

        $num_els = array();

        foreach ($object_fields_names as $field => $params) {
            if (isset($grouped_user_data->$field) && is_array($grouped_user_data->$field))
                $num_els[] = count($grouped_user_data->$field);
            else $num_els[] = 0;
        }

        $n = max($num_els);

        $fieldscount = (count($actions))? count($object_fields_names) + 1 : count($object_fields_names);

        if ($n) {
            for ($i = 0; $i < $n; $i++) {
                $result_table_str .= '<tr class="um-table__row">';

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
                            $result_table_str .= '<td class="um-table__cell">' . $data_str . '</td>';
                        }

                        if ($obj_field_params['type'] == 'text') {
                            $data_str = (isset($grouped_user_data->$obj_field[$i])) ? $grouped_user_data->$obj_field[$i] : '-';
                            $result_table_str .= '<td class="um-table__cell">' . $data_str . '</td>';
                        }
                    }
                }

                foreach ($actions as $action) {
                    $is_print = true;

                    if (isset($action['conds']) && is_array($action['conds'])) {
                        foreach ($action['conds'] as $field => $values) {
                            if (is_array($values)) {
                                foreach ($values as $value) {
                                    $is_print = self::field_takes_value($grouped_user_data, $field, $i, $value);
                                }
                            } else {
                                $is_print = self::field_takes_value($grouped_user_data, $field, $i, $values);
                            }
                        }
                    }

                    if ($is_print) {
                        if (isset($action['idfield'])) {
                            $idfield = $action['idfield'];
                            if (isset($grouped_user_data->$idfield) && is_array($grouped_user_data->$idfield)) {
                                if (isset($action['closure'])) {
                                    $closure = $action['closure'];
                                    $result_table_str .= '<td class="um-table__cell">' . $closure($grouped_user_data->$idfield[$i]) . '</td>';
                                }
                            }
                        }
                    }
                }

                $result_table_str .= '</tr>';
            }
        } else {
            $result_table_str .= '<tr class="um-table__row"><td class="um-table__cell" colspan="'. $fieldscount .'">'.get_string('noentries', 'block_user_manager').'</td></tr>';
        }

        if ($action_add)
            $result_table_str .= '<tr class="um-table__row"><td class="um-table__cell" colspan="'. $fieldscount .'">'.$action_add.'</td></tr>';

        $result_table_str .= '</tbody></table>';

        return $result_table_str;
    }

    private static function generate_system_userfields_selector(array $systemfields, string $systemfield, array $helpfields = [],
                                                               bool $defaultempty = false, string $id = ""): string
    {
        $html_str = '        
            <select class="um-validfields-select custom-select" name="umValidFieldsSelector_'.$id.'" id="umValidFieldsSelector_'.$id.'">';

        if ($defaultempty)
            $html_str .= '<option value="" selected></option>';

        for ($i = 0; $i < count($systemfields); $i++) {
            $helpfield = (isset($helpfields[$i]))? ' (' . $helpfields[$i] . ')' : '';
            if ($systemfields[$i] === $systemfield)
                $html_str .= '<option value="'.$systemfields[$i].'" selected>'.$systemfields[$i].$helpfield.'</option>';
            else
                $html_str .= '<option value="'.$systemfields[$i].'">'.$systemfields[$i].$helpfield.'</option>';
        }

        $html_str .= '        
            </select>';

        return $html_str;
    }

    public static function generate_valid_fields_table(array $stdfields, array $systemfields, array $helpfields = [],
                                                       array $prffields = [], array $prflabels = []): string
    {
        global $PAGE;

        $result_table_str = '
        <table class="table um-table" id="umValidFieldsTable">
            <thead class="um-table__head">
                <tr class="um-table__row">
                    <th class="um-table__header-cell">'.get_string('systemfields', 'block_user_manager').'</th>
                    <th class="um-table__header-cell">'.get_string('associatedfields', 'block_user_manager').'</th>
                    <th class="um-table__header-cell"></th>
                </tr>
            </thead>
            <tbody class="um-table__body">';
            $id = 0;
            foreach ($stdfields as $systemfield => $associatedfields) {
                $result_table_str .= '<tr class="um-table__row">';
                $result_table_str .= '<td class="um-table__cell">'. self::generate_system_userfields_selector($systemfields, $systemfield,  $helpfields, true, $id) .'</td>';

                $result_table_str .= '<td class="um-table__cell">';
                if (is_array($associatedfields)) {
                    $result_table_str .= '<textarea class="um-validfields-text" id="umValidFieldsText_'.$id.'" name="umValidFieldsText_'.$id.'"
          rows="2" cols="60">'.implode(',', $associatedfields).'</textarea>';
                } else $result_table_str .= '<textarea class="um-validfields-text" id="umValidFieldsText_'.$id.'" name="umValidFieldsText_'.$id.'"
          rows="2" cols="60">'.$associatedfields.'</textarea>';
                $result_table_str .= '<div class="um-input-info">'. get_string('inputdelimiter', 'block_user_manager') .'</div>';
                $result_table_str .= "</td>";
                $result_table_str .= '
                    <td class="um-table__cell">
                        <button type="button" class="btn btn-danger um-validfields-btn-del" id="umValidFieldsBtnDel_'.$id.'">x</button>
                    </td>';
                $result_table_str .= "</tr>";

                $id++;
            }
            $result_table_str .= '
                <tr class="um-table__row">
                    <td class="um-table__cell">
                        <button type="button" class="btn btn-primary um-validfields-btn-add" id="umValidFieldsBtnAdd">'. get_string('add') .'</button>
                        <button type="button" class="btn btn-secondary um-validfields-btn-save" id="umValidFieldsBtnSave">'. get_string('save') .'</button>
                    </td>
                    <td class="um-table__cell"><div id="umValidFieldsMessage" role="alert"></div></td>
                    <td class="um-table__cell"></td>
                </tr>';

            $result_table_str .= '
            </tbody>
        </table>';

        if (count($prffields)) {
            $result_table_str .= '
                <div class="um-prf-fields">
                    <h5>'.get_string('prffields', 'block_user_manager').'</h5>
                    <ul class="um-sublist um-list-ul">';
            foreach ($prffields as $key => $prffield) {
                $result_table_str .= '            
                    <li class="um-sublist__item">'.$prffield.' ('.$prflabels[$key].')'.'</li>';
            }

            $result_table_str .= '            
                    </ul>
                </div>';
        }

        // ------ Подключение JS модуля ------
        $PAGE->requires->js(new moodle_url('/blocks/user_manager/js/valid_fields_table.js?newversion'));
        $request_url = (string)(new moodle_url('/blocks/user_manager/uploaduser/update_userfields.php'));
        $PAGE->requires->js_init_call('M.block_user_manager_valid_fields_table.init',  array(
            $request_url, STD_FIELDS_EN, STD_FIELDS_RU
        ));
        $PAGE->requires->strings_for_js(
            array('inputdelimiter'), 'block_user_manager'
        );
        // -----------------------------------

        return $result_table_str;
    }

    public static function generate_userspreview_table(csv_import_reader $cir, array $filecolumns, int $previewrows): string {
        global $DB, $CFG;
        $stremailduplicate = get_string('useremailduplicate', 'error');
        // preview table data
        $data = array();
        $cir->init();
        $linenum = 1; //column header is first line
        $noerror = true; // Keep status of any error.
        while ($linenum <= $previewrows and $fields = $cir->next()) {
            $linenum++;
            $rowcols = array();
            $rowcols['line'] = $linenum;
            foreach($fields as $key => $field) {
                $rowcols[$filecolumns[$key]] = s(trim($field));
            }
            $rowcols['status'] = array();

            if (isset($rowcols['username'])) {
                $stdusername = core_user::clean_field($rowcols['username'], 'username');
                if ($rowcols['username'] !== $stdusername) {
                    $rowcols['status'][] = get_string('invalidusernameupload');
                }
                if ($userid = $DB->get_field('user', 'id', array('username'=>$stdusername, 'mnethostid'=>$CFG->mnet_localhost_id))) {
                    $rowcols['username'] = html_writer::link(new moodle_url('/user/profile.php', array('id'=>$userid)), $rowcols['username']);
                }
            } else {
                $rowcols['status'][] = get_string('missingusername');
            }

            if (isset($rowcols['email'])) {
                if (!validate_email($rowcols['email'])) {
                    $rowcols['status'][] = get_string('invalidemail');
                }

                $select = $DB->sql_like('email', ':email', false, true, false, '|');
                $params = array('email' => $DB->sql_like_escape($rowcols['email'], '|'));
                if ($DB->record_exists_select('user', $select , $params)) {
                    $rowcols['status'][] = $stremailduplicate;
                }
            }

            if (isset($rowcols['city'])) {
                $rowcols['city'] = $rowcols['city'];
            }

            if (isset($rowcols['theme'])) {
                list($status, $message) = field_value_validators::validate_theme($rowcols['theme']);
                if ($status !== 'normal' && !empty($message)) {
                    $rowcols['status'][] = $message;
                }
            }

            // Check if rowcols have custom profile field with correct data and update error state.
            $noerror = uu_check_custom_profile_data($rowcols) && $noerror;
            $rowcols['status'] = implode('<br />', $rowcols['status']);
            $data[] = $rowcols;
        }
        if ($fields = $cir->next()) {
            $data[] = array_fill(0, count($fields) + 2, '...');
        }
        $cir->close();

        $table = new html_table();
        $table->id = "uupreview";
        $table->attributes['class'] = 'generaltable';
        $table->tablealign = 'center';
        $table->summary = get_string('uploaduserspreview', 'tool_uploaduser');
        $table->head = array();
        $table->data = $data;

        $table->head[] = get_string('uucsvline', 'tool_uploaduser');

        foreach ($filecolumns as $column) {
            $table->head[] = $column;
        }

        $table->head[] = get_string('status');

        return html_writer::tag('div', html_writer::table($table), array('class'=>'flexible-wrap'));
    }

    public static function generate_example_csv_table(array $header_fields, int $num_empty_rows = 3, string $empty_value = '...',
                                                      string $delimiter = ';', string $header = '', string $description = '')
    {
        $table = '';

        if (count($header_fields))
        {
            if ($header) {
                $table .= "<h5 class='um-example-table-header'>$header</h5>";
            }

            if ($description) {
                $table .= "<div class='um-example-table-desc'>$description</div>";
            }

            $table .= "<table class='um-example-table'>";
            $table .= "<tr class='um-example-table__row'>";

            $j = 0;
            foreach ($header_fields as $header_field) {
                if (!empty($header_field)) {
                    $table .= "<th class='um-example-table__h-cell'>$header_field";
                } else {
                    $table .= "<th class='um-example-table__h-cell'>-";
                }
                if ($j < count($header_fields) - 1) $table .= $delimiter;
                $table .= "</th>";
                $j++;
            }
            $table .= "</tr>";

            for ($i = 0; $i < $num_empty_rows; $i++) {
                $table .= "<tr class='um-example-table__row'>";
                for ($j = 0; $j < count($header_fields); $j++) {
                    $table .= "<td class='um-example-table__cell'>
                " . htmlspecialchars($empty_value);
                    if ($j < count($header_fields) - 1) $table .= $delimiter;
                    $table .= "</td>";
                }
                $table .= "</tr>";
            }

            $table .= "</table>";
        }

        return $table;
    }
}