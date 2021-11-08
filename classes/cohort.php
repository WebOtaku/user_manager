<?php
namespace block_user_manager;

use stdClass, html_writer, moodle_url;

class cohort
{
    public static function group_users_cohorts_by_users(array $users_cohorts): array
    {
        $group_users_cohorts = array();

        foreach ($users_cohorts as $user_cohort) {
            $userid = $user_cohort->userid;
            if (!isset($group_users_cohorts[$userid]))
                $group_users_cohorts[$userid] = new stdClass();

            // Фамилия
            if (!isset($group_users_cohorts[$userid]->lastname) && $user_cohort->lastname)
                $group_users_cohorts[$userid]->lastname = $user_cohort->lastname;
            elseif (!isset($group_users_cohorts[$userid]->lastname))
                $group_users_cohorts[$userid]->lastname = '';

            // Имя
            if (!isset($group_users_cohorts[$userid]->firstname) && $user_cohort->firstname)
                $group_users_cohorts[$userid]->firstname = $user_cohort->firstname;
            elseif (!isset($group_users_cohorts[$userid]->firstname))
                $group_users_cohorts[$userid]->firstname = '';

            // Группируем chtid
            if (isset($group_users_cohorts[$userid]->chtids) && $user_cohort->chtid)
                $group_users_cohorts[$userid]->chtids[] = $user_cohort->chtid;
            elseif (!isset($group_users_cohorts[$userid]->chtids) && $user_cohort->chtid)
                $group_users_cohorts[$userid]->chtids = array($user_cohort->chtid);
            elseif (!isset($group_users_cohorts[$userid]->chtids))
                $group_users_cohorts[$userid]->chtids = array();

            // Группируем cht_code_mdl
            if (isset($group_users_cohorts[$userid]->cht_codes_mdl) && $user_cohort->cht_code_mdl)
                $group_users_cohorts[$userid]->cht_codes_mdl[] = $user_cohort->cht_code_mdl;
            elseif (!isset($group_users_cohorts[$userid]->cht_codes_mdl) && $user_cohort->cht_code_mdl)
                $group_users_cohorts[$userid]->cht_codes_mdl = array($user_cohort->cht_code_mdl);
            elseif (!isset($group_users_cohorts[$userid]->cht_codes_mdl))
                $group_users_cohorts[$userid]->cht_codes_mdl = array();

            // Группируем cht_code
            if (isset($group_users_cohorts[$userid]->cht_codes) && isset($user_cohort->cht_code))
                $group_users_cohorts[$userid]->cht_codes[] = $user_cohort->cht_code;
            elseif (!isset($group_users_cohorts[$userid]->cht_codes) && isset($user_cohort->cht_code))
                $group_users_cohorts[$userid]->cht_codes = array($user_cohort->cht_code);
            elseif (!isset($group_users_cohorts[$userid]->cht_codes))
                $group_users_cohorts[$userid]->cht_codes = array();

            // Группируем description
            if (isset($group_users_cohorts[$userid]->descriptions) && isset($user_cohort->description))
                $group_users_cohorts[$userid]->descriptions[] = $user_cohort->description;
            elseif (!isset($group_users_cohorts[$userid]->descriptions) && isset($user_cohort->description))
                $group_users_cohorts[$userid]->descriptions = array($user_cohort->description);
            elseif (!isset($group_users_cohorts[$userid]->descriptions))
                $group_users_cohorts[$userid]->descriptions = array();

            // Группируем form
            if (isset($group_users_cohorts[$userid]->forms) && isset($user_cohort->form))
                $group_users_cohorts[$userid]->forms[] = $user_cohort->form;
            elseif (!isset($group_users_cohorts[$userid]->forms) && isset($user_cohort->form))
                $group_users_cohorts[$userid]->forms = array($user_cohort->form);
            elseif (!isset($group_users_cohorts[$userid]->forms))
                $group_users_cohorts[$userid]->forms = array();
        }

        return $group_users_cohorts;
    }

    public static function get_empty_group_user_cohorts_obj(): stdClass
    {
        $group_user_cohorts = new stdClass();
        $group_user_cohorts->lastname = '';
        $group_user_cohorts->firstname = '';
        $group_user_cohorts->chtids = array();
        $group_user_cohorts->cht_codes_mdl = array();
        $group_user_cohorts->cht_codes = array();
        $group_user_cohorts->descriptions = array();
        $group_user_cohorts->forms = array();

        return $group_user_cohorts;
    }

    public static function get_remove_member_link(): \Closure
    {
        return function ($chtid) {
            global $OUTPUT;
            return html_writer::link(new moodle_url($this->url, array(
                'func' => 'cohort_remove_member',
                'delchtid' => $chtid,
                'userid' => $this->id,
                'sesskey' => sesskey()
            )), $OUTPUT->pix_icon('t/delete', get_string('removefromcht_alt', 'block_user_manager')));
        };
    }

    public static function form_cohort_members_select(array $cohort_members): string
    {
        if (count($cohort_members))
        {
            $extrasql = '(';
            $i = 0;
            foreach ($cohort_members as $cohort_member) {
                if ($i === 0) $extrasql .= 'id = ' . $cohort_member->userid;
                else $extrasql .= ' OR id = ' . $cohort_member->userid;
                $i++;
            }
            $extrasql .= ')';
        }
        else $extrasql = 'id = 0';

        return $extrasql;
    }

    /**
     * Метод создает/обновляет группу в мудл, регистрирует зависимость между группой в мудл и группой в 1с.
     * @param string $name имя группы для мудл
     * @param array $group_1c_info информация о группе из 1с (поля как в таблице cohort1c_synch)
     * @return int
     */
    public static function add_1c_cohort(string $name, array $group1c_info, string $description): int
    {
        global $DB;
        $cohort = new stdClass();
        $cohort->contextid = 1;
        $cohort->name = $name;
        /*$cohort->description =
            '<b>Факультет: </b>'.$group1c_info['faculty']
            .'<br><b>Направление: </b>'.$group1c_info['speciality']
            .'<br><b>Профиль: </b>'.$group1c_info['specialization']
            .'<br><b>Форма обучения: </b>'.$group1c_info['form']
            .'<br><b>Курс: </b>'.$group1c_info['course'];*/
        $cohort->description = $description;
        $params = array(
            'faculty' => $group1c_info['faculty'],
            'course' => $group1c_info['course'],
            'form' => $group1c_info['form'],
            'group1c' => $group1c_info['group1c'],
        );
        $synch = $DB->get_record('block_cohort1c_synch', $params);
        if ($synch){
            $cohort->id = $synch->cohortid;
            $group1c_info['id'] = $synch->id;
            $cohortid = self::cohort_check_1c($cohort);
            if ($cohortid){ //группа в мудл обновлена/создана
                $group1c_info['cohortid'] = $cohortid;
                $DB->update_record('block_cohort1c_synch', $group1c_info);
            } else {
                //удалить?
                $DB->delete_records('block_cohort1c_synch', array('id'=>$group1c_info));
            }
        } else {
            $cohortid = self::cohort_check_1c($cohort);
            if ($cohortid){ //группа в мудл обновлена/создана
                $group1c_info['cohortid'] = $cohortid;
                $DB->insert_record('block_cohort1c_synch', $group1c_info);
            }
        }

        return $cohortid;
    }

    /**
     * Сохранение группы в таблицу mdl_cohort. Если запись с таким $cohort->id или $cohort->name существует, то она обновляется, иначе создается новая.
     * Имя группы каждый раз обновляется
     * @param stdClass $cohort = object(id, contextid, name, description)
     * @return int
     */
    public static function cohort_check_1c(stdClass $cohort): int
    {
        global $DB;

        if (isset($cohort->id) && $cohort->id) {
            $cohortid = $DB->get_field('cohort', 'id', array('id' => $cohort->id));

            if ($cohortid) {
                $cohort->id = $cohortid;
                cohort_update_cohort($cohort);
            } else {
                $cohortid = cohort_add_cohort($cohort);
            }
        } else {
            $cohortid = cohort_add_cohort($cohort);
        }

        return $cohortid;
    }

    /*public static function delete_all_cohort_members(int $cohortid) {
        global $DB;
        $sql = 'SELECT id FROM {cohort_members} WHERE cohortid = $cohortid';
        $result = $DB->get_records_sql($sql);
        if ($result) {
            foreach ($result as $data) {
                $DB->delete_records("cohort_members", array('id' => $data->id));
            }
        }
    }*/

    public static function user_check_user($user) {
        global $DB;
        $userid = -1;

        if (isset($user->username)) {
            $userdata = $DB->get_record('user', array('username' => $user->username));
            if ($userdata) {
                $userid = $userdata->id;
            }
        }

        return $userid;
    }

    public static function user_in_cohort($userid, $cohortid): bool
    {
        global $DB;
        return !!$DB->get_record('cohort_members', array('cohortid' => $cohortid, 'userid' => $userid));
    }

    public static function add_1c_users(int $cohortid, array $users) {
        global $DB;

        if ($cohortid && count($users)) {
            $cohort_members = $DB->get_records('cohort_members', array('cohortid' => $cohortid));

            foreach ($cohort_members as $cohort_member) {
                $user_match = false;

                foreach ($users as $user) {
                    $userid = self::user_check_user($user);

                    if ($userid === $cohort_member->userid) {
                        $user_match = true;
                        break;
                    }
                }

                if (!$user_match) {
                    cohort_remove_member($cohortid, $cohort_member->userid);
                }
            }

            foreach ($users as $user) {
                $userid = self::user_check_user($user);

                //Добавление пользователя в глобальную группу
                if ($userid != -1) {
                    if (!self::user_in_cohort($userid, $cohortid)) {
                        cohort_add_member($cohortid, $userid);
                    }
                }
            }
        }
    }

    /** Использ.?
     * Формирует имя для группы из 1С формата:
     *   st_Факультет_Группа-КурсКод(Подгруппа)_ФормаОбучения
     * |об|_| обяз  |_|обяз|-|обяз  | необяз  |   необяз    |
     * st_FMF_СИ-15_зу st_MedF_ЛД-121(1)
     * @param array $nameparam = array(
    'faculty' => $chosenfaculty, //fmf
    'form' => $group->ФормаОбучения,
    'group' => $group->Группа,
    'subgroup' => $group->Подгруппа,
    );
     * @return string
     */
    public static function get_cohort_name(array $nameparam): string
    {
        $form = self::get_form_short($nameparam['form']);
        if (mb_strlen($form)) $form = '('.$form.')';
        $name = 'группа_' . $nameparam['faculty'] . '_' . $nameparam['group'] . $nameparam['subgroup'].$form;
        return $name;
    }

    public static function get_form_short(string $form): string
    {
        $newform = '';
        if (mb_stripos($form, 'очно-заочн') !== false) { $newform .= 'оз'; }
        else if (mb_stripos($form, 'заочн') !== false) { $newform .= 'з'; }
        else if (mb_stripos($form, 'очн') !== false) { $newform .= 'o'; }
        if (mb_stripos($form, 'ускорен') !== false) { $newform .= 'у'; }
        $newform .= '/о';
        return $newform;
    }

    public static function get_faculty_short($faculty): string
    {
        $faculty = preg_replace('/[-]+/', ' ', $faculty);
        $faculty = mb_convert_case($faculty, MB_CASE_TITLE);
        $faculty = explode(' ', $faculty);

        // Функция substr() 1 букву кириллицы воспринимает как 2 символа, поэтому в примере стоит 0, 2,
        // то есть начиная с нулевого символа, печатать 2 символа(т.е. 1 букву).
        $shortfaculty = '';
        foreach ($faculty as $part) {
            if ($part === 'И') $letter = mb_convert_case($part, MB_CASE_LOWER);
            else $letter = substr($part, 0, 2);
            $shortfaculty .= $letter;
        }

        return $shortfaculty;
    }

    /**
     * Извлекает номер курса из названия группы (например ПИ-43 => 4 или Четвёртый)
     * @param string $group - название группы (например ПИ-43)
     * @param int $format - формат номера курса: 0 - число (напр. 4),  1 - строка (напр. Четвёртый)
     * @return string - курс в выбранном формате
     */
    public static function get_course_from_group(string $group, int $format = 1)
    {
        $course_num = (int) explode('-', $group)[1][0];
        list($course_num, $course_str) = cohort1c_lib1c::GetCourseRepresent($course_num);

        return ($format)? $course_str : $course_num;
    }
}
?>