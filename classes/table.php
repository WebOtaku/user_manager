<?php

namespace block_user_manager;

use html_writer, html_table, moodle_url, csv_import_reader, core_user;

require_once($CFG->dirroot.'/admin/tool/uploaduser/locallib.php');

class table
{
    public static function generate_table_from_object(array  $grouped_user_data = [], array $object_fields_names = [],
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
                    if (isset($action['idfield'])) {
                        $field = $action['idfield'];
                        if (isset($grouped_user_data->$field) && is_array($grouped_user_data->$field)) {
                            if (isset($action['closure'])) {
                                $closure = $action['closure'];
                                $result_table_str .= '<td class="um-table__cell">' . $closure($grouped_user_data->$field[$i]) . '</td>';
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

    public static function generate_system_userfileds_selector(array $systemfields, string $systemfield, array $helpfields = [],
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

    public static function generate_valid_fields_table(array $stdfields, array $systemfields, array $helpfields = [], array $prffields = []): string
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
                $result_table_str .= '<td class="um-table__cell">'. self::generate_system_userfileds_selector($systemfields, $systemfield,  $helpfields, true, $id) .'</td>';

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
            foreach ($prffields as $prffield) {
                $result_table_str .= '            
                    <li class="um-sublist__item">'.$prffield.'</li>';
            }

            $result_table_str .= '            
                    </ul>
                </div>';
        }

        $PAGE->requires->js_amd_inline("
            require(['jquery'], function($) {
                const delBtnClass = 'um-validfields-btn-del';
              
                const addBtnId = 'umValidFieldsBtnAdd';
                const saveBtnId = 'umValidFieldsBtnSave';
                
                const cellClass = 'um-table__cell';
                const rowClass = 'um-table__row';
                
                const textClass = 'um-validfields-text';
                const textId = 'umValidFieldsText';
                const selectClass = 'um-validfields-select';
                const selectorId = 'umValidFieldsSelector';
                
                const btnDelClass = 'um-validfields-btn-del';
                const btnDelId = 'umValidFieldsBtnDel';
                
                const options = [
                    'id', 'username', 'email',
                    'city', 'country', 'lang', 'timezone', 'mailformat',
                    'maildisplay', 'maildigest', 'htmleditor', 'autosubscribe',
                    'institution', 'department', 'idnumber', 'skype',
                    'msn', 'aim', 'yahoo', 'icq', 'phone1', 'phone2', 'address',
                    'url', 'description', 'descriptionformat', 'password',
                    'auth', 'oldusername', 'suspended', 'theme', 'deleted', 'mnethostid',  
                    'interests', 'firstnamephonetic', 'lastnamephonetic', 'middlename',
                    'alternatename', 'firstname', 'lastname'
                ];
                
                const helpers = [
                    'ид', 'логин', 'электронная почта', 'город', 'страна',
                    'язык', 'часовой пояс', 'формат электронной почты', 'отображение электронной почты',
                    'дайджест электронной почты', 'html-редактор', 'автоматическая подписка',
                    'организация', 'отдел', 'идентификатор', 'skype', 'msn', 'aim', 'yahoo', 'icq',
                    'телефон 1', 'телефон 2', 'адрес', 'url-адрес', 'описание', 'формат описания',
                    'пароль', 'аутентификация', 'старое имя пользователя', 'заблокированный',
                    'тема', 'удаленный', 'идентификатор хоста mnet', 'интересы', 'имя фонетическое',
                    'фамилия фонетическая', 'отчество', 'aльтернативное имя', 'имя', 'фамилия'
                ];
                
                function generateSelector(options, helpers = [], defaultempty = false) {
                    var newSelector = $(document.createElement('select'));
                    if (defaultempty) {
                        var newOption = $(document.createElement('option'));
                        newOption.attr({
                            selected: true
                        });
                        newOption.text('');
                        newSelector.append(newOption);
                    }
                    
                    $.each(options, function(key, value) {
                        var helper = ([...helpers.keys()].indexOf(key) !== -1)? ' (' + helpers[key] + ')': '';
                        var newOption = $(document.createElement('option'));
                        newOption.attr({
                            value: value
                        });
                        newOption.text(value + helper);
                        newSelector.append(newOption);
                    });
                    
                    return newSelector;
                }
                   
                $('#'+addBtnId).click(function() {
                    var rowEl = $(document.createElement('tr'));
                    var cellSelectEl = $(document.createElement('td'));
                    var cellTextEl = $(document.createElement('td'));
                    var cellBtnDelEl = $(document.createElement('td'));
                    
                    var selectEl = generateSelector(options, helpers, true);
                    var textareaEl = $(document.createElement('textarea'));
                    var btnDelEl = $(document.createElement('button'));
                    
                    var textareaInfoEl = $(document.createElement('div'));
                    textareaInfoEl.addClass('um-input-info');
                    textareaInfoEl.text('". get_string('inputdelimiter', 'block_user_manager') ."');
                    
                    var allRows = $('#umValidFieldsTable tbody tr').get();
                    allRows.pop();
                    
                    var id = 0;
                    if (allRows[allRows.length - 1] !== undefined) {
                        var prevSelectEl = $(allRows[allRows.length - 1]).children('.' + cellClass + ':first-child').children().get()[0] ;
                        id = +uniqId(prevSelectEl) + 1;
                    }
                    
                    rowEl.addClass(rowClass);
                    cellSelectEl.addClass(cellClass);
                    cellTextEl.addClass(cellClass);
                    
                    selectEl.addClass(selectClass + ' custom-select');
                    selectEl.attr({
                        name: selectorId + '_' + id,
                        id: selectorId + '_' + id
                    });
                    
                    textareaEl.addClass(textClass);
                    textareaEl.attr({
                        name: textId + '_' + id,
                        id: textId + '_' + id,
                        rows: 2,
                        cols: 60
                    });
                    
                    btnDelEl.addClass(btnDelClass + ' btn btn-danger');
                    btnDelEl.attr({
                        type: 'button',
                        id: btnDelId + '_' + id
                    });
                    
                    btnDelEl.text('x');
                    
                    cellSelectEl.append(selectEl);
                    cellTextEl.append(textareaEl);
                    cellTextEl.append(textareaInfoEl);
                    cellBtnDelEl.append(btnDelEl);
                    rowEl.append(cellSelectEl, cellTextEl, cellBtnDelEl);
                    
                    $(rowEl).insertBefore($(this).parent().parent());
                    
                    bindDelBtns();
                });
 
                function uniqId(el) {
                    var underpos = -1;
                    
                    if (el.id)
                       underpos = el.id.indexOf('_');

                    if (underpos != -1)
                        return el.id.slice(underpos + 1);

                    return underpos;
                }
                
                function bindDelBtns() {
                    $('.'+delBtnClass).click(function() {
                        $(this).parent().parent().remove();
                    });
                };
                
                bindDelBtns();
                
                $('#'+saveBtnId).click(function() {
                    // соотвествие 1 к 1
                    var selectedvalues = getFieldsValues('.'+selectClass);
                    var textvalues = getFieldsValues('.'+textClass);
                    textvalues = formatTextValues(textvalues);
                    
                    /*selectedvalues = selectedvalues.filter(val => val.length);
                    textvalues = textvalues.filter(val => val.length);*/
                    
                    function getFieldsValues(identifier) {
                        return $(identifier).map(function() {
                            return $(this).val();
                        }).get();
                    }
                    
                    function printMessage(message, messageClass, id) {
                        var messageEl = $('#'+id);
                        messageEl.removeClass();
                        messageEl.css({textAlign: 'center'});
                        messageEl.addClass(messageClass);
                        messageEl.text(message);
                        setTimeout(function() {
                            messageEl.empty();
                            messageEl.removeClass();
                        }, 2000)
                    }
                    
                    function formatTextValues(textvalues) {
                        return textvalues.map((item, i) => {
                            return item.split(',').map((val) => val.trim().toLowerCase());
                        });
                    }
                    
                    $.post( '". (new moodle_url('/blocks/user_manager/uploaduser/update_userfields.php')) ."', {
                        'systemfields[]': selectedvalues,
                        'associatedfields[]': textvalues
                    })
                        .done(function(response) {
                            console.log(response.data);
                            printMessage(response.data, 'alert alert-success', 'umValidFieldsMessage');
                        })
                        .fail(function(error) {
                            if (error.responseJSON?.data) {
                               console.log(error.responseJSON.data);
                               printMessage(error.responseJSON.data, 'alert alert-danger', 'umValidFieldsMessage');
                            }
                            else console.log(error.statusText);
                        })
                });
            });"
        );

        return $result_table_str;
    }

    public static function generate_userspreview_table(csv_import_reader $cir, array $filecolumns, int $previewrows) {
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
}