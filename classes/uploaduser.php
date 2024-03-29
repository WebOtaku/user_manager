<?php

namespace block_user_manager;

require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->libdir.'/excellib.class.php');

use csv_import_reader, csv_export_writer,
    MoodleExcelWorkbook, moodle_url, core_text, stdClass, core_component;

class uploaduser
{
    public static function um_validate_user_upload_columns(csv_import_reader $cir, array $stdfields,array $profilefields,
        moodle_url $returnurl, string $passwordkey = ''): array
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
        foreach ($columns as $key => $unused) {
            $field = $columns[$key];
            $field = trim($field);
            $lcfield = core_text::strtolower($field);

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
                continue;
            }

            if (in_array($newfield, $processed)) {
                $cir->close();
                $cir->cleanup();
                print_error('duplicatefieldname', 'error', $returnurl, $newfield);
            }
            $processed[$key] = $newfield;
        }

        /*if (!in_array($usernamekey, $processed))
            array_push($processed, $usernamekey);*/

        if (!in_array($passwordkey, $processed))
            array_push($processed, $passwordkey);

        return $processed;
    }

    public static function import_users_into_system(
        csv_export_writer $users_csv, moodle_url $returnurl, string $group = '', string $eduform = '', int $previewrows = 10,
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

        $urlparams = array(
            'iid' => $iid,
            'previewrows' => $previewrows,
            'returnurl' => $returnurl,
            'eduform' => $eduform
        );

        if ($group) {
            $urlparams['group'] = $group;
        }

        $useruploadurl = new moodle_url('/blocks/user_manager/uploaduser/uploaduser.php', $urlparams);

        redirect($useruploadurl);
    }

    public static function get_profile_fields(): array
    {
        global $DB;

        $PRF_FIELDS = array();
        $proflabels = array();

        if ($proffields = $DB->get_records('user_info_field')) {
            foreach ($proffields as $key => $proffield) {
                $profilefieldname = 'profile_field_'.$proffield->shortname;
                $PRF_FIELDS[] = $profilefieldname;
                // Re-index $proffields with key as shortname. This will be
                // used while checking if profile data is key and needs to be converted (eg. menu profile field)
                $proffields[$profilefieldname] = $proffield;
                unset($proffields[$key]);
                array_push($proflabels, $proffield->name);
            }
        }

        return [$PRF_FIELDS, $proffields, $proflabels];
    }

    public static function field_exist(string $field, array $stdfields, string $key = '')
    {
        $lcfield = core_text::strtolower($field);

        if (!$key) {
            if (array_key_exists($field, $stdfields) || array_key_exists($lcfield, $stdfields))
                return $lcfield;

            if (in_array($field, $stdfields) || in_array($lcfield, $stdfields))
                return array_search($lcfield, $stdfields);

            foreach ($stdfields as $systemfield => $associatedfields) {
                if (is_array($associatedfields))
                    if (in_array($field, $associatedfields) || in_array($lcfield, $associatedfields))
                        return $systemfield;
            }
        } else {
            $lckey = core_text::strtolower($key);

            // $key == 'username' || (isset($STD_FIELDS['username']) && ($key == $STD_FIELDS['username']))

            if (array_key_exists($key, $stdfields) || array_key_exists($lckey, $stdfields)) {
                if ($field === $key) return $lckey;

                if ($stdfields[$key] === $field || $stdfields[$key] === $lcfield ||
                    $stdfields[$lckey] === $field || $stdfields[$lckey] === $lcfield) return $lckey;

                if (is_array($stdfields[$key]))
                    if (in_array($field, $stdfields[$key]) || in_array($lcfield, $stdfields[$key]))
                        return $lckey;
            }
        }

        return false;
    }

    /* Вынесена на отдельную страницу и редактируется через глобальные настройки*/
    /*public static function get_uploaduser_instruction(array $stdfields, array $stdfields_assoc, array $required_fields): string
    {
        $required_fields = self::get_fields_with_helper($stdfields, $stdfields_assoc, $required_fields);

        $instruction = '
            <div class="um-instruction">
                <h5>Форма выбора способа загрузки</h5>
                <ol class="um-list um-list-ol">
                    <li class="um-list__item">
                        <p>Выберите способок загрузки пользователей: <b>из файла</b> или <b>из 1С</b></p>
                    </li>
                    <li class="um-list__item">
                        <p>Если вы выбрали <b>из файла</b>, то просто нажмите кнопку <b>Далее</b></p>
                    </li>
                    <li class="um-list__item">
                        <p>Если вы выбрали <b>из 1c</b>, то вам нужно будет указать группу и кол-во строк предпросмотра, а затем нажать кнопку <b>Далее</b></p>
                    </li>
                </ol>
                <h5>Работа с таблицей "Допустимые поля"</h5>
                <ol class="um-list um-list-ol">
                    <li class="um-list__item">
                        <p>Если таблица пуста или вам необходимо добавить новую запись нажмите на кнокпу "Добавить"</p>
                    </li>
                    <li class="um-list__item">
                        <p>Выберите необходимое системное поле из выпадающего списка <b>(обязательно)</b></p>
                        <p>Cистемные поля в разных записях повторятся не должны.</p>
                    </li>
                    <li class="um-list__item">
                        <p>Перечислите через запятую все ассоциированные, с выбраным системным полем, названия полей <b>(необязательно)</b></p>
                    </li>
                    <li class="um-list__item">
                        <p>Продолжайте выполнять действия 1 - 3 до тех пор пока не добавить все необходимые вам поля</p>
                    </li>
                    <li class="um-list__item">
                        <p>Запись можно удалить нажав на соотвествующую кнопку справа от записи</p>
                    </li>
                    <li class="um-list__item">
                        <p>Чтобы сохранить изменения внесённые в таблицу <b/> нажмите на кнопку "Сохранить"</b></p>
                        <p>Если изменения были успешно/не успешно сохранены вам выведится соотвествующее сообщение внизу таблицы.</p>
                    </li>
                </ol>
                <h5>Форма загрузки файла</h5>
                <ol class="um-list um-list-ol">
                    <li class="um-list__item">
                        <p><b>Укажите</b> является ли поле "еmail (электронная почта)" обязательным (файл будет проверен на его наличие)</p>
                    </li>
                    <li class="um-list__item">
                        <p><b>Выберите файл</b></p>
                        <p><b>Требования к формату файла (.csv):</b></p>
                        <ul class="um-sublist um-list-ul">
                            <li class="um-sublist__item">
                                <p>Каждая строка файла содержит одну запись</p>
                            </li>
                            <li class="um-sublist__item">
                                <p>Каждая запись - ряд данных, разделенных запятыми (или другими разделителями)</p>
                            </li>
                            <li class="um-sublist__item">
                                <p>Первая запись содержит список имен полей, определяющих формат остальной части файла</p>
                            </li>
                        </ul>
                        <p><b>Требования к содержимому файла:</b></p>
                        <ul class="um-sublist um-list-ul">
                            <li class="um-sublist__item">
                                <p>Файл должен содержать следующие обязательные поля (включая "еmail ('.self::get_field_helper($stdfields, $stdfields_assoc, 'email').')", если выбрано): '. implode(', ',$required_fields) .'. Данные поля необходимы для регистрации пользователей в системе</p>
                            </li>
                            <li class="um-sublist__item">
                                <p>Названия полей в файле должны соотвествовать названиям из таблицы "Допустимые поля", иначе они будут проигнорированы</p>
                            </li>
                            <li class="um-sublist__item">
                                <p>Старайтесь избегать полей без данных (т.е. пустых столбцов) в файле</p>
                            </li>
                        </ul>
                    </li>
                    <li class="um-list__item">
                        <p><b>Выберите разделитель</b> используемый в загружаемом файле</p>
                    </li>
                    <li class="um-list__item">
                        <p><b>Выберите кодировку</b> используемую в загружаемом файле (для .csv созданного с помощью Excel это обычно будет - WINDOWS-1251)</p>
                    </li>
                    <li class="um-list__item">
                        <p><b>Выберите количество строк предпросмотра</b> - кол-во записей из файла которое будет показано на форме выбора действий</p>
                    </li>
                    <li class="um-list__item">
                        <p><b>Нажмите кнопку "Загрузить"</b></p>
                        <p>После нажатия на кнопку "Загрузить" будет выполнена обработа данных и вы будете переадресованы на страницу с формой выбора действий, где вы сможете увидеть данные прошедшие обработку и выбрать соотвествующие действия надо ними.</p>
                        <p><b>Обработка загруженного файла</b> включает в себя:</p>
                        <ul class="um-sublist um-list-ul">
                            <li class="um-sublist__item">
                                <p>Добавление к данным из поля ассоцириованного с именем пользователя (например: номер зачётной книжки, логин) префикса "st"</p>
                            </li>
                            <li>
                                <p>Генерацию пароля для каждого пользователя и добавление соотвествущего поля "password"</p>
                            </li>
                        </ul>
                    </li>
                </ol>
                <h5>Форма выбора действий</h5>         
                <ol class="um-list um-list-ol">
                    <li class="um-list__item">
                        <p><b>Выберите действие</b> которое необходимо выполнить над файлом:</p>
                        <p><b>Экспорт в формате .csv</b> - данные будут экспортированны в формате .csv (кодировка UTF-8) <b>(начнётся скачивание файла)</b></p>
                        <p><b>Экспорт в формате .csv (AD)</b> - данные будут подготовлены и экспортированны в формате .csv (кодировка UTF-8) для загрузки в AD <b>(начнётся скачивание файла)</b></p> 
                        <p><b>Экспорт в формате .xls (Excel)</b> - данные будут экспортированны в формате .xls (.xlsx) <b>(начнётся скачивание файла)</b></p>
                        <p><b>Загрузка пользователей </b> - будет выполнена переадресация на стандартную форму загрузки пользователей с текущими данными <b>(будет выполнена переадресация)</b></p>
                    </li>
                    <li class="um-list__item">
                        <p><b>Выберите факультет</b> (если выбран пункт "Экспорт в формате .csv (AD)") - поле с указанным факультетом будет добавлено всем записям</p>
                    </li>
                    <li class="um-list__item">
                        <p><b>Выберите количество строк предпросмотра</b> (если выбран пункт "Загрузка пользователей") - кол-во записей из файла которое будет показано на форме загрузки пользователей</p>
                    </li>
                    <li class="um-list__item">
                        <p><b>Нажмите кнопку "Выполнить"</b></p>
                    </li>
                    <li class="um-list__item">
                        <p>Если хотите обратно вернуться на форму загрузки <b>нажмите на кнопку "Отмена"</b></p>
                    </li>
                </ol>
                <h5>Действия после загрузки пользователей в систему</h5>
                <ol class="um-list um-list-ol">
                    <li class="um-list__item">
                        <p><b>Добавить/Обновить глобальную группу.</b> Отвечает за добавлние/обновление глобальной группы и зачисления в неё загруженных пользоветелей.</p>
                        <p>Перед выполнением действия вам будет показано окно с предупреждением, где вы сможете как потвердить, так и отменить его. В любом из случаев будет выполнено перенаправление на страницу с формой выбора действий над загруженными пользователями.</p>
                    </li>
                    <li class="um-list__item">
                        <p><b>Продолжить.</b> Будет выполнено перенаправление на страницу с формой выбора действий над загруженными пользователями.</p>
                    </li>
                </ol>
            </div>
        ';

        return $instruction;
    }*/

    public static function get_userlist_from_file(csv_import_reader $cir, array $stdfields, array $prffields,
        moodle_url $baseurl, string $passwordkey, string $usernamekey, string $emptystr = '', string $username_prefix = ''): array
    {
        global $USER;

        $filecolumns = self::um_validate_user_upload_columns($cir, $stdfields, $prffields, $baseurl, $passwordkey);

        //if (empty($emptystr))
            //$emptystr = mb_strtolower(get_string('empty', 'block_user_manager'));
 
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

                if (strpos($key, 'profile_field_') === 0) {
                    //NOTE: bloody mega hack alert!!
                    if (isset($USER->$key) and is_array($USER->$key)) {
                        // this must be some hacky field that is abusing arrays to store content and format
                        $user->$key = array();
                        $user->{$key['text']}   = $value;
                        $user->{$key['format']} = FORMAT_MOODLE;
                    } else {
                        $user->$key = trim($value);
                    }
                    continue;
                }

                if ($key === $usernamekey) {
                    if (!empty(trim($value))) {
                        // Проверка на то, что префикс уже есть
                        if (!preg_match('/^('.$username_prefix.').*?$/', trim($value))) {
                            $user->$key = $username_prefix . trim($value);
                            continue;
                        } else {
                            $user->$key = $value;
                        }
                    } else $user->$key = $emptystr;
                    continue;
                }

                if (empty(trim($value)))
                    $user->$key = $emptystr;
                else
                    $user->$key = trim($value);
            }

            if (!isset($user->password))
                $user->password = service::generate_password($user, $emptystr);

            $users[] = $user;
        }

        $cir->close();

        return array($users, $filecolumns);
    }

    public static function get_userlist_from_1c(array $users1c, string $emptystr = '', string $username_prefix = ''): array {
        $users = array();

        foreach ($users1c as $user) {
            $newuser = new stdClass();
            $newuser->lastname = $emptystr;
            $newuser->firstname = $emptystr;
            $newuser->middlename = $emptystr;
            $newuser->username = $emptystr;

            foreach ($user as $key => $value) {
                if (empty($value)) $value = $emptystr;

                switch ($key) {
                    case 'Фамилия':
                        $newuser->lastname = trim($value);
                        break;
                    case 'Имя':
                        $newuser->firstname = trim($value);
                        break;
                    case 'Отчество':
                        $newuser->middlename = trim($value);
                        break;
                    case 'ЗачетнаяКнига':
                        $newuser->username = $username_prefix . trim($value);
                        break;
                }
            }

            $newuser->password = service::generate_password($newuser, $emptystr);

            $users[] = $newuser;
        }

        return $users;
    }

    public static function get_userlist_from_cir(csv_import_reader $cir, array $filecolumns): array {
        $cir->init();
        $users = array();

        while ($line = $cir->next()) {
            $user = new stdClass();

            // add fields to user object
            foreach ($line as $keynum => $value)
            {
                if (!isset($filecolumns[$keynum])) {
                    // this should not happen
                    continue;
                }
                $key = $filecolumns[$keynum];
                if (strpos($key, 'profile_field_') === 0) {
                    //NOTE: bloody mega hack alert!!
                    if (isset($USER->$key) and is_array($USER->$key)) {
                        // this must be some hacky field that is abusing arrays to store content and format
                        $user->$key = array();
                        $user->{$key['text']} = $value;
                        $user->{$key['format']} = FORMAT_MOODLE;
                    } else {
                        $user->$key = trim($value);
                    }
                } else {
                    $user->$key = trim($value);
                }
            }

            $users[] = $user;
        }

        $cir->close();

        return $users;
    }

    public static function get_stdfields(array $db_userfields, array $required_fields = []): array
    {
        $stdfileds = array();

        foreach ($db_userfields as $userfield) {
            $associated_fields = explode(',', $userfield->associated_fields);

            foreach ($associated_fields as $key => $associated_field) {
                $associated_fields[$key] = mb_strtolower(trim($associated_field));
            }

            $stdfileds[$userfield->system_field] = $associated_fields;
        }

        foreach ($required_fields as $required_field) {
            if (!array_key_exists($required_field, $stdfileds))
                $stdfileds[$required_field] = array();
        }

        return $stdfileds;
    }

    public static function check_required_fields(array $filecolumns, array $required_fields): array
    {
        $missingfields = array();

        foreach ($required_fields as $required_field)
            if (!in_array($required_field, $filecolumns))
                array_push($missingfields, $required_field);

        return $missingfields;
    }

    public static function prepare_data_for_ad(array $users, array $filecolumns, stdClass $formdata, string $email_domain, array $strings = [
        'emptystring' => '', 'emailkey' => '', 'usernamekey' => '', 'dnamekey' => '',
        'lastnamekey' => '', 'firstnamekey' => '', 'middlenamekey' => '', 'facultykey' => '']
    ): array
    {
        $newusers = array();

        foreach ($users as $user) {
            $newuser = new stdClass();
            foreach ($user as $key => $value) {
                $value = trim($value);

                if (!in_array($key, AD_FIELDS)) continue;
                //if (!empty($value) && $value !== $emptystr) $newuser->$key = $value;
                $newuser->$key = $value;
            }

            if (!isset($user->{$strings['emailkey']}) || $user->{$strings['emailkey']} === $strings['emptystring']) {
                $newuser->{$strings['emailkey']} = trim($user->{$strings['usernamekey']}) . '@' . $email_domain;
            }

            $newuser->{$strings['dnamekey']} = '';

            if (!isset($newuser->{$strings['lastnamekey']}) || $newuser->{$strings['lastnamekey']} !== $strings['emptystring'])
                $newuser->{$strings['dnamekey']} .= $newuser->{$strings['lastnamekey']};
            if (!isset($newuser->{$strings['firstnamekey']}) || $newuser->{$strings['firstnamekey']} !== $strings['emptystring'])
                $newuser->{$strings['dnamekey']} .= ' '.$newuser->{$strings['firstnamekey']};
            if (!isset($newuser->{$strings['middlenamekey']}) || $newuser->{$strings['middlenamekey']} !== $strings['emptystring'])
                $newuser->{$strings['dnamekey']} .= ' '.$newuser->{$strings['middlenamekey']};

            $newuser->{$strings['dnamekey']} = trim($newuser->{$strings['dnamekey']});

            $newuser->{$strings['facultykey']} = trim($formdata->faculty);

            $newusers[] = $newuser;
        }

        $newfilecolumns = array();
        foreach ($filecolumns as $filecolumn) {
            if (in_array($filecolumn, AD_FIELDS))
                $newfilecolumns[] = $filecolumn;
        }

        $newfilecolumns = array_merge($newfilecolumns, array_diff(AD_FIELDS, $newfilecolumns));
        $newfilecolumns = array_values(self::get_fields_helpers(AD_FIELDS, AD_FIELDS_ASSOC, $newfilecolumns));

        return [$newusers, $newfilecolumns];
    }

    public static function get_field_helper(array $stdfields, array $stdfields_assoc, string $field) {
        $key = array_search($field, $stdfields);
        return ($key >= 0)? $stdfields_assoc[$key] : '';
    }

    public static function get_fields_helpers(array $stdfields, array $stdfields_assoc, array $fields): array
    {
        $helpers = array();

        foreach ($fields as $field) {
            $helpers[$field] = uploaduser::get_field_helper($stdfields, $stdfields_assoc, $field);
        }

        return $helpers;
    }

    public static function get_fields_with_helper(array $stdfields, array $stdfields_assoc, array $fields): array
    {
        foreach ($fields as $key => $field) {
            $fields[$key] = $field . ' (' . uploaduser::get_field_helper($stdfields, $stdfields_assoc, $field) . ')';
        }

        return $fields;
    }

    public static function print_error(string $message, moodle_url $baseurl) {
        global $OUTPUT;

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('uploaduser', 'block_user_manager'));
        echo '<link rel="stylesheet" href="../css/uplodauser.css">';
        service::print_error($message, $baseurl);
        echo $OUTPUT->footer();
        die;
    }

    /* Заменена функцие cohort1c_lib1c::FormatGroupInfo */
    /*public static function form_excel_header(array $group_info, int $period_end): array {
        $excel_header = array(
            'Факультет' => '',
            'Направление подготовки' => '',
            'Профиль' => '',
            'Уровень подготовки' => '',
            'Форма обучения' => '',
            'Год поступления' => ''
        );

        foreach ($group_info as $field => $value) {
            switch ($field) {
                case 'Факультет':
                    $excel_header['Факультет'] = $value;
                    break;
                case 'Специальность':
                    $excel_header['Направление подготовки'] = $value;
                    break;
                case 'Специализация':
                    $excel_header['Профиль'] = $value;
                    break;
                case 'УровеньПодготовки':
                    $excel_header['Уровень подготовки'] = $value;
                    break;
                case 'ФормаОбучения':
                    $excel_header['Форма обучения'] = $value;
                    break;
                case 'Курс':
                    if (is_string($value)) {
                        $lcourse = mb_convert_case($value, MB_CASE_LOWER);
                        $course = (isset(COURSE_STRING[$lcourse]))? COURSE_STRING[$lcourse] : 0;
                    } else if (is_int($value)) {
                        $course = $value;
                    } else {
                        $course = 0;
                    }

                    if ($course >= 1) {
                        $excel_header['Год поступления'] = ($period_end - $course) . ' год';
                    }

                    break;
            }
        }

        return $excel_header;
    }*/

    public static function export_excel(
        array $objects, array $fields, array $required_fields, array $header = [], int $header_offset = 1, string $worksheet_name = 'default',
        string $filename = 'default.xls', bool $download = false): MoodleExcelWorkbook
    {
        $workbook = new MoodleExcelWorkbook('-');
        $workbook->send($filename);
        $worksheet = $workbook->add_worksheet($worksheet_name);

        $i = 0;

        $format = array('size' => 11);
        foreach ($header as $key => $value) {
            $worksheet->write_string($i, 0, $key, $format + array('bold' => 500));
            $worksheet->write_string($i, 1, $value, $format);
            $i++;
        }

        if (count($header)) $i += $header_offset;

        $format = array('size' => 11, 'bold' => 500, 'border' => 1, 'align' => 'center');
        foreach ($fields as $key => $field) {
            $worksheet->write_string($i, $key, $field, $format);
        }

        if (count($fields)) $i++;

        $format = array('border' => 1);
        foreach ($objects as $key => $object) {
            $j = 0;
            foreach ($object as $keynum => $value) {
                if (in_array($keynum, $required_fields)) {
                    $worksheet->write_string($key + $i, $j, $object->$keynum, $format);
                    $j++;
                }
            }
        }

        if ($download)
            $workbook->close();

        return $workbook;
    }
}