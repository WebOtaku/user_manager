<?php

namespace block_user_manager;

require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->libdir.'/excellib.class.php');

use csv_import_reader, csv_export_writer,
    MoodleExcelWorkbook, moodle_url, core_text, stdClass;

class uploaduser
{
    public static function um_validate_user_upload_columns(csv_import_reader $cir, array $stdfields,array $profilefields, moodle_url $returnurl): array
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

    public static function get_uploaduser_instruction()
    {
       $instruction = '
            <div class="um-instruction">
                <ol class="um-instruction__list">
                    <li class="um-instruction__list-item">
                        <p><b>Выберите действие</b> которое необходимо выполнить над файлом:</p>
                        <p><b>Экспорт в формате .csv</b> - данные из загруженного файла будут обработаны и экспортированны в формате .csv (кодировка UTF-8) <b>(начнётся скачивание файла)</b></p>
                        <p><b>Экспорт в формате .xls (Excel)</b> - данные из загруженного файла будут обработаны и экспортированны в формате .xls (.xlsx) <b>(начнётся скачивание файла)</b></p>
                        <p><b>Загрузка пользователей в систему</b> - данные из загруженного файла будут обработаны и выполнится переадресация на стандартную форму загрузки пользователей <b>(будет выполнена переадресация)</b></p>
                        <p><b>Обработка загруженного файла</b> включает в себя:</p>
                        <ul class="um-instruction__sublist">
                            <li class="um-instruction__sublist-item">
                                <p>Добавление к данным из поля ассоцириованного с именем пользователя (например: номер зачётной книжки, логин) приставки "st"</p>
                            </li>
                            <li>
                                <p>Генерацию пароля для каждого пользователя и добавление соотвествущего поля "password"</p>
                            </li>
                        </ul>
                    </li>
                    <li class="um-instruction__list-item">
                        <p><b>Выберите файл</b></p>
                        <p><b>Требования к формату файла (.csv):</b></p>
                        <ul class="um-instruction__sublist">
                            <li class="um-instruction__sublist-item">
                                <p>Каждая строка файла содержит одну запись</p>
                            </li>
                            <li class="um-instruction__sublist-item">
                                <p>Каждая запись - ряд данных, разделенных запятыми (или другими разделителями)</p>
                            </li>
                            <li class="um-instruction__sublist-item">
                                <p>Первая запись содержит список имен полей, определяющих формат остальной части файла</p>
                            </li>
                        </ul>
                        <p><b>Требования к содержимому файла:</b></p>
                        <ul class="um-instruction__sublist">
                            <li class="um-instruction__sublist-item">
                                <p>Названия полей должны соотвествовать названиям из таблицы "Допустимые поля", иначе они будут проигнорированы</p>
                            </li>
                            <li class="um-instruction__sublist-item">
                                <p>Рекомендуемыми именами полей являются: username, password, firstname, lastname, middlename, email (логин, пароль, фамилия, имя, отчество, адрес электронной почты)</p>
                                <p>Фамилия, имя и отчество необходимы для генерации пароля пользователя и регистрации его в системе</p>
                                <p>Логин (или например: номер зачётной книжки) и адрес электронной почты необходимы для регистрации пользователя в системе</p>
                            </li>
                        </ul>
                    </li>
                    <li class="um-instruction__list-item">
                        <p><b>Выберите разделитель</b> используемый в загружаемом файле</p>
                    </li>
                    <li class="um-instruction__list-item">
                        <p><b>Выберите кодировку</b> используемую в загружаемом файле (для .csv созданного с помощью Excel это обычно будет - WINDOWS-1251)</p>
                    </li>
                    <li class="um-instruction__list-item">
                        <p><b>Выберите количество строк предпросмотра</b> кол-во записей из файла которое будет показано на форме загрузки пользователей (в случае выбора пункта <b>"Загрузка пользователей в систему"</b>)</p>
                    </li>
                </ol>
            </div>
        ';

        return $instruction;
    }

    public static function get_userlist(csv_import_reader $cir, array $stdfields, array $prffields,
        moodle_url $baseurl, string $passwordkey, string $usernamekey): array
    {
        $filecolumns = self::um_validate_user_upload_columns($cir, $stdfields, $prffields, $baseurl);

        if (!in_array($passwordkey, $filecolumns))
            $filecolumns[] = $passwordkey;

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

                if ($key === $usernamekey) {
                    if (!preg_match('/^(st).*?$/', trim($value), $matches)) {
                        $user->$key = 'st'. trim($value);
                        continue;
                    }
                }

                $user->$key = trim($value);
            }

            if (!isset($user->password))
                $user->password = service::generate_password($user);

            $users[] = $user;
        }

        return array($users, $filecolumns);
    }
}