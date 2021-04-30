<?php

namespace block_user_manager;

use moodle_url, SoapClient, SoapFault;

class cohort1c_lib1c
{
    public static function Connect1C()
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
                echo get_string('data_error', 'block_cohort1c');
                /*Вывод в случае проверки*/
                //print_object($e);
            }
        }
    }

    /**
     * Преобразовать структуру факультетов и групп
     */
    public static function FormStructure($structure){
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

    public static function GetFaculties(): array {
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

        return array_keys($tree);
    }
}