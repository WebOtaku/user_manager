<?php

namespace block_user_manager;

use moodle_url, SoapClient, SoapFault;

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

    public static function GetFaculties(): array {
        $tree = self::GetFormStructure();
        return array_keys($tree);
    }

    public static function GetGroups(): array {
        $tree = self::GetFormStructure();
        $list_groups = array();

        foreach ($tree as $groups) {
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
            $students = $result->return->Students;
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
     * @param string $status
     * @return \stdClass - массив студентов указанной группы
     */
    public static function GetGroupInfoByGroup(string $group, int $period_start, int $period_end, string $student_status): array
    {
        $students = self::GetStudentsOfGroup($group, $period_start, $period_end, $student_status);
        $group_fields = array('Факультет', 'Группа', 'Подгруппа', 'Курс', 'Специальность', 'ФормаОбучения', 'Специализация', 'УровеньПодготовки');

        $group_with_info = array();

        if (count($students)) {
            $student = $students[0];

            foreach ($student as $field => $value) {
                if (in_array($field, $group_fields)) {
                    $group_with_info[$field] = trim($value);
                }
            }
        }

        return $group_with_info;
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

        if (isset($student_info->Группа)) {
            $group_info = self::GetGroupInfoByGroup($student_info->Группа, $period_start, $period_end, $student_status);
        }

        return $group_info;
    }
}