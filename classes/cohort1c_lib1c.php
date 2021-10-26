<?php

namespace block_user_manager;

use moodle_url, SoapClient, SoapFault;
use block_user_manager\service;

class cohort1c_lib1c
{
    public static function Connect1C(): SoapClient
    {
        $ini = parse_ini_file(new moodle_url('../conf.ini'), true);
        $soap = $ini["soap"];
        try {
            $client = new SoapClient($soap["wsdl"], $soap);
            return $client;
        } catch (SoapFault $e) {
            /*Вывод в случае проверки*/
            //print_object($e);
        }
    }

    /**
     * Получение всех групп.
     * @param object $idc client 1C
     * @return object (return => Struct => [0] =>
     *                    Наименование => АБ-41п
     *                    Родитель => Аграрно-технологический институт
     *                )
     */
    public static function GetStructure($idc) {
        if (is_object($idc)) {
            try {
                $result = $idc->GetStructure();
                return $result;
            } catch (SoapFault $e) {
                // echo get_string('data_error', 'block_cohort1c');
                /*Вывод в случае проверки*/
                //print_object($e);
            }
        }
    }

    /**
     * Преобразовать структуру факультетов и групп
     */
    public static function FormStructure($structure): array
    {
        //сортировка списка по факультетм и группам
        usort($structure, function ($a, $b){
            $encoding = mb_internal_encoding();
            $compare = strcmp(mb_strtoupper($a->Родитель,$encoding), mb_strtoupper($b->Родитель,$encoding));
            if ($compare==0){
                return strcmp(mb_strtoupper($a->Наименование,$encoding), mb_strtoupper($b->Наименование,$encoding));
            }
            return $compare;
        });

        $tree = array();

        foreach ($structure as $key => $struct) {
            if (!$struct->Родитель || $struct->Родитель == "Педагогический институт") {
                unset($structure[$key]);
                continue;
            }

            // TODO : создать структуру типа СЛ -> СЛ15, СЛ13, СЛ23, СЛ25
            $groupname = self::parse_group_name($struct->Наименование);
            if ($groupname->group && $groupname->code)
                /*$tree[$struct->Родитель][$groupname->group][] = $struct->Наименование;*/
                $tree[$struct->Родитель][] = $struct->Наименование;
        }

        return $tree;
    }

    /**
     * Парсится по шаблону: буквы-цифрацифрасимволы (названиегруппы-курс_код_подгруппа)
     */
    public static function parse_group_name($groupname){
        $groupname = preg_replace('/\s+/', '', $groupname);

        // preg_match('/(.+)\-(\d*)(\d*)(.*)/', $groupname, $array);print_object($array);
        // mb_ereg("/(.*)\-*(\d*)(\d*)(.*)/", $groupname, $array);;print_object($array);
        $name = preg_split('/\-/',$groupname, -1);
        $array = array();
        if (isset($name[1])) preg_match('/(\d{0,1})(\d{0,1})(.*)/', $name[1], $array);

        $return = (object)array(
            'group'=> isset($name[0]) ? $name[0] : '',
            'course'=> isset($array[1]) ? $array[1] : '',
            'code'=> isset($array[2]) ? $array[2] : '',
            'subgroup'=> isset($array[3]) ? $array[3] : '',
        );

        return $return;
    }

    /**
     * Получение студентов группы для учебного года.
     * @param object $idc - client 1C
     * @param array $param - array('group' =>$group, 'session' => $ini["session"] );
     * @return object - stdClass Object(return =>
     *    stdClass Object( Students => Array( [0] => stdClass Object(
     *        ЗачетнаяКнига: "18110761"
     *        Имя: "Анастасия"
     *        Курс: "Первый"
     *        Отчество: "Игоревна"
     *        Подгруппа: ""
     *        Состояние: "Является студентом"
     *        Специальность: "Продукты питания животного происхождения"
     *        Факультет: "Аграрно-технологический институт"
     *        Фамилия: "Бахтина"
     *        ФормаОбучения: "Заочная"
     *        Специализация: "Программирование и системный анализ (программа академического бакалавриата)"
     *    )))
     */
    public static function GetData($idc, $param): \stdClass {
        $result = new \stdClass();
        if (is_object($idc)) {
            try {
                $result = $idc->GetData($param);
                //return $result;
            } catch (SoapFault $e) {
                //print_error('data_error', 'block_cohort1c');
                //print_object($e);
            }
        }
        return $result;
    }

    /**
     * Получение студента по номеру зачётной книжки
     * @param object $idc client 1C
     * @param array $param - array('number' => '18110761', 'session' => '2020 - 2021' );
     * @return object - stdClass Object(return =>
     *    stdClass Object(
     *        ЗачетнаяКнига: "18110761"
     *        Имя: "Анастасия"
     *        Курс: "Первый"
     *        Отчество: "Игоревна"
     *        Подгруппа: ""
     *        Состояние: "Является студентом"
     *        Специальность: "Продукты питания животного происхождения"
     *        Факультет: "Аграрно-технологический институт"
     *        Фамилия: "Бахтина"
     *        ФормаОбучения: "Заочная"
     *    ))
     */
    public static function GetStudent($idc, $param): \stdClass {
        $result = new \stdClass();
        if (is_object($idc)) {
            try {
                $result = $idc->GetStudent($param);
            } catch (SoapFault $e) {
                /*Вывод в случае проверки*/
                //print_error('data_error', 'block_cohort1c');
                //print_object($e);
            }
        }
        return $result;
    }

    public static function GetFormStructure(): array
    {
        $client = self::Connect1C();

        if (!$client) {
            return array();
        }

        // запрашиваем структуру университета
        $result = self::GetStructure($client);
        // структура университета
        $UniversityStructure = $result->return;
        // сортировка списка по факультетм и группам, удаление некорректных значений
        $tree = self::FormStructure($UniversityStructure->Struct);

        return $tree;
    }

    public static function GetFaculties(array $univ_form_struct = []): array {
        if (!count($univ_form_struct))
            $univ_form_struct = self::GetFormStructure();

        return array_keys($univ_form_struct);
    }

    public static function FindFaculty(string $group, array $univ_form_struct = []) {
        if (!count($univ_form_struct))
            $univ_form_struct = self::GetFormStructure();

        foreach ($univ_form_struct as $faculty => $groups) {
            if (in_array($group, $groups)) return $faculty;
        }
    }

    public static function GetGroups(array $univ_form_struct = []): array {
        if (!count($univ_form_struct))
            $univ_form_struct = self::GetFormStructure();

        $list_groups = array();

        foreach ($univ_form_struct as $groups) {
            foreach ($groups as $group) {
                array_push($list_groups, $group);
            }
        }

        return $list_groups;
    }

    /**
     * @param string $group - группа в которой состоят студенты (Например: "ПИ-33")
     * @param int $period_start - начало учебного года
     * @param int $period_end - конец учебного года
     * @return array - массив студентов указанной группы
     */
    public static function GetStudentsOfGroup(string $group, int $period_start, int $period_end, string $status): array {
        $client = self::Connect1C();

        if (!$client) {
            return array();
        }

        $session = $period_start . ' - ' . $period_end;

        $result = self::GetData($client, array(
            'group' => $group, // Группа
            'session' => $session // Учебный год
        ));

        $students = array();

        if (isset($result->return) && isset($result->return->Students)) {
            if (is_array($result->return->Students)) {
                $students = $result->return->Students;
            }

            if (is_object($result->return->Students)) {
                array_push($students, $result->return->Students);
            }
        }

        $students = self::SliceLastStudents($students);
        return service::filter_objs($students, 'Состояние', $status);
    }

    public static function SliceLastStudents(array $students): array
    {
        $tmp_students = array();
        $new_students = array();

        foreach ($students as $student) {
            if (!isset($tmp_students[$student->ЗачетнаяКнига])) {
                $tmp_students[$student->ЗачетнаяКнига] = $student;
                $new_students[] = $student;
            }
        }

        return $new_students;
    }

    /**
     * @param string $group - группа в которой состоят студенты (Например: "ПИ-33")
     * @param int $period_start - начало учебного года
     * @param int $period_end - конец учебного года
     * @param string $student_status - статус студента в 1с
     * @return array - массив студентов и информации указанной группы
     */
    public static function GetGroupInfoByGroup(string $group, int $period_start, int $period_end, string $student_status): array
    {
        $students = self::GetStudentsOfGroup($group, $period_start, $period_end, $student_status);
        $group_fields = array('Факультет', 'Группа', 'Подгруппа', 'Курс', 'Специальность', 'УровеньПодготовки');
        $group_arr_fields = array('Специализация', 'ФормаОбучения');
        //$group_arr_fields = array('ФормаОбучения');

        $group_fields_values = array_fill(0, count($group_fields + $group_arr_fields), '');
        $group_info = array_combine($group_fields + $group_arr_fields, $group_fields_values);

        foreach ($group_arr_fields as $group_arr_field) {
            $group_info[$group_arr_field] = [];
        }

        if (count($students)) {
            $student = $students[0];

            foreach ($student as $field => $value) {
                if (in_array($field, $group_fields)) {
                    $group_info[$field] = trim($value);
                }
            }

            foreach ($students as $student) {
                foreach ($group_arr_fields as $group_arr_field) {
                    if (isset($student->$group_arr_field)) {
                        if (!in_array($student->$group_arr_field, $group_info[$group_arr_field]))
                            array_push($group_info[$group_arr_field], $student->$group_arr_field);
                    }
                }
            }
        }

        return [$students, $group_info];
    }

    /**
     * @param \stdClass $student - объект студента из 1с
     * @return array - массив информации о группе
     */
    public static function GetGroupInfoFromStudent(\stdClass $student): array
    {
        $group_fields = array('Факультет', 'Группа', 'Подгруппа', 'Курс', 'Специальность', 'Специализация', 'УровеньПодготовки', 'ФормаОбучения');
        $group_fields_values = array_fill(0, count($group_fields), '');
        $group_info = array_combine($group_fields, $group_fields_values);

        foreach ($student as $field => $value) {
            if (in_array($field, $group_fields)) {
                $group_info[$field] = trim($value);
            }
        }

        return $group_info;
    }

    public static function GetGroupInfoByUsername(string $username, int $period_start, int $period_end, string $student_status): array
    {
        $client = self::Connect1C();

        if (!$client) {
            return array();
        }

        $session = $period_start . ' - ' . $period_end;

        $result = self::GetStudent($client, array(
            'number' => $username,
            'session' => $session
        ));

        $student_info = new \stdClass();

        if (isset($result->return)) {
            $student_info = $result->return;
        }

        $group_info = array();
        $students = array();

        if (isset($student_info->Группа)) {
            list($students, $group_info) = self::GetGroupInfoByGroup($student_info->Группа, $period_start, $period_end, $student_status);
        }

        return [$students, $group_info];
    }

    /**
     * Приводит массив с информацией о группе к требуемому виду
     * @param array $group_info - ассоциативный массив с информацией о группе
     * @param int $period_end - конец учебного года
     * @param int $course_format - формат номера курса: 0 - число (напр. 4),  1 - строка (напр. Четвёртый)
     * @param array $filter_fields - поля по которым будет отфильтрован итоговый массив
     * @return array - ассоциативный массив с информацией о группе
     */
    public static function FormatGroupInfo(array $group_info, int $num_students, int $period_end, int $course_format = 1,
                                           array $format_fields = [], array $filter_fields = []): array
    {
        $format_group_info = array();
        $ffield = '';

        foreach ($group_info as $field => $value) {
            if (array_key_exists($field, $format_fields)) {
                $ffield = $format_fields[$field];
            }

            if (count($filter_fields)) {
                $is_in_array = false;
                if (in_array($field, $filter_fields) ||
                    in_array($ffield, $filter_fields)) $is_in_array = true;
                if (!$is_in_array) continue;
            }

            if ($field === 'Курс') {
                list($course_num, $course_str) = cohort1c_lib1c::GetCourseRepresent($value);

                switch ($course_format) {
                    case 0:
                        $format_group_info[$field] = ($course_num >= 1) ? $course_num : '';
                        break;
                    case 1:
                        $format_group_info[$field] = $course_str;
                        break;
                    default:
                        $format_group_info[$field] = '';
                        break;
                }
            } else {
                if ($ffield) $field = $ffield;
                $format_group_info[$field] = $value;
            }
        }

        list($course_num, $course_str) = cohort1c_lib1c::GetCourseRepresent($group_info['Курс']);

        $format_group_info['Год поступления'] =
            ($course_num >= 1 && $period_end > 0) ? ($period_end - $course_num) . ' год' : '';

        $format_group_info['Количество cтудентов'] = ($num_students >= 0)? $num_students : '';

        if (count($filter_fields)) {
            if (!in_array('Количество cтудентов', $filter_fields))
                unset($format_group_info['Количество cтудентов']);
            if (!in_array('Год поступления', $filter_fields))
                unset($format_group_info['Год поступления']);
        }


        return $format_group_info;
    }

    /**
     * Возвращает значение для поля "Специализация" (Профиль) в зависимости от выбранной формы обучения
     * @param array $specializations - массив специализаций группый полученный из 1с
     * @param array $eduforms - массив форм обучения группый полученный из 1с
     * @return string - значение для поля "Специализация" (Профиль)
     */
    public static function GetGroupInfoSpec(array $specializations, array $eduforms, string $eduform): string
    {
        $specialization = '';

        if (($eduformkey = array_search($eduform, $eduforms)) !== false) {
            if (array_key_exists($eduformkey, $specializations)) {
                $specialization = $specializations[$eduformkey];
            } else {
                for (; $eduformkey >= 0; $eduformkey -= 1) {
                    if (array_key_exists($eduformkey, $specializations)) {
                        $specialization = $specializations[$eduformkey];
                        break;
                    }
                }
            }
        }

        return $specialization;
    }

    /**
     * Задаёт значения по умолчанию для ассоциоативного массива с информацией о группе
     * @param array $group_info - ассоциативный массив с информацией о группе
     * @param string $eduform - форма обучения (Например: "Очно-заочная")
     * @param string $group - название группы (Например: "ПИ-43")
     * @param int $course_format - формат номера курса: 0 - число (напр. 4),  1 - строка (напр. Четвёртый)
     * @return array - ассоциативный массив с информацией о группе
     */
    public static function SetGroupInfoDefaults(array $group_info, string $eduform, string $group, int $course_format): array
    {
        $group_info['ФормаОбучения'] = $eduform;

        if (empty($group_info['Группа'])) {
            $group_info['Группа'] = $group;
        }

        if (empty($group_info['Факультет'])) {
            $group_info['Факультет'] = cohort1c_lib1c::FindFaculty($group);
        }

        if (empty($group_info['Специализация'])) {
            $group_info['Специализация'] = '';
        }

        if (empty($group_info['Курс'])) {
            $course_num = cohort::get_course_from_group($group, $course_format);
            $group_info['Курс'] = ($course_num >= 1) ? $course_num : '';
        }

        return $group_info;
    }

    /**
     * Возвращает курс в виде числа (1, 2, 3, ...) и строки (Первый, Второй, Третий, ...)
     * @param $value - значение курса (в виде числа 1, 2, 3, ... или
     * в виде строки (Первый (первый), Второй (второй), Третий (третий), ...)
     * @return array - представления курса в виде числа и строки
     */
    public static function GetCourseRepresent($value): array {
        $course_num = 0;
        $course_str = '';

        if (is_string($value) && !is_numeric($value)) {
            $lcourse = mb_convert_case($value, MB_CASE_LOWER);
            if (isset(COURSE_STRING[$lcourse])) {
                $course_num = COURSE_STRING[$lcourse];
                $course_str = $value;
            }
        } else if (is_int($value)) {
            if (($course_str = array_search($value, COURSE_STRING)) !== false) {
                $course_num = $value;
                $course_str = string_operation::capitalize_first_letter_cyrillic($course_str);
            }
        }

        return [$course_num, $course_str];
    }
}