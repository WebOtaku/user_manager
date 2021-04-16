<?php
namespace block_user_manager;

class db_request {
    /**
     * Запрашивает всех пользователей и глобальные группы в которых они состоят
     * (если пользователь в них состоит).
     *
     * Структура возвращаемого результата:
     *     array(
     *         [id] => object {
     *             [id]             => <integer> - временное поле (номер строки) (для уникальности записей)
     *             [userid]         => <integer> - user.id
     *             [lastname]       => <string>  - user.lastname
     *             [firstname]      => <string>  - user.firstname
     *             [chtid]          => <integer> - cohort.id
     *             [cht_code_mdl] => <string>  - cohort.name
     *             [cht_code]     => <string>  - block_cohort1c_synch.group1c
     *             [description]    => <string>  - cohort.description
     *             [form]           => <string>  - block_cohort1c_synch.form
     *         }
     *     )
     * @return array массив ассоциативных записей, согласно структуре возвращаемого результата.
     * */
    public static function get_users_cohorts(array $users = null) {
        global $DB;


        $dbman = $DB->get_manager();

        $table1c = "block_cohort1c_synch";

        $select = self::form_select($users);

        $sql_request = "
            SELECT (@cnt := @cnt + 1) AS id, u.id AS userid, u.lastname, u.firstname,
                   cht.id AS chtid, cht.name AS cht_code_mdl";

        if ($dbman->table_exists($table1c)) {
            $sql_request .= ", cht1c.group1c AS cht_code,
                   cht.description, cht1c.form";
        }

        $sql_request .= "
            FROM {user} AS u
            INNER JOIN {cohort_members} AS chtm ON chtm.userid = u.id
            LEFT JOIN (
                SELECT cht.id, cht.name, cht.description
                FROM {cohort} AS cht
            ) AS cht ON cht.id = chtm.cohortid";

        if ($dbman->table_exists($table1c)) {
            $sql_request .= "
                LEFT JOIN {block_cohort1c_synch} AS cht1c ON cht1c.cohortid = cht.id";
        }

        $sql_request .= "
            CROSS JOIN (SELECT @cnt := 0) AS dummy
            WHERE $select";

        return $DB->get_records_sql($sql_request);
    }

    /**
     * Запрашивает из БД курсы на которые записан пользователь
     *
     * Структура возвращаемого результата:
     *     array(
     *         [id] => object {
     *             [id]         => <integer> - временное поле (номер строки) (для уникальности записей)
     *             [userid]     => <integer> - user.id
     *             [lastname]   => <string>  - user.lastname
     *             [firstname]  => <string>  - user.firstname
     *             [courseid]   => <integer> - crs.id
     *             [course]     => <string>  - crs.fullname
     *             [role]       => <integer> - r.shortname
     *         }
     *     )
     * @return array массив ассоциативных записей, согласно структуре возвращаемого результата.
     * */
    public static function get_users_courses(array $users = null)
    {
        global $DB;

        $select = self::form_select($users);

        $sql_request = "
            SELECT (@cnt := @cnt + 1) AS id, u.id AS userid, u.firstname, u.lastname,
            ra.courseid, ra.course, ra.role, uenr.enrol AS enrol_method
            FROM {user} AS u
            INNER JOIN (
                SELECT ra.userid AS userid, crs.id AS courseid, r.id AS roleid,
                crs.fullname AS course, r.shortname AS role
                FROM {role_assignments} AS ra
                INNER JOIN {context} AS c ON c.id = ra.contextid
                INNER JOIN {course} AS crs ON crs.id = c.instanceid
                INNER JOIN {role} AS r ON r.id = ra.roleid
                GROUP BY ra.userid, crs.id, r.id
            ) AS ra ON ra.userid = u.id
            LEFT JOIN (
                SELECT uenr.userid AS userid, enr.courseid AS courseid, enr.enrol AS enrol
                FROM {user_enrolments} AS uenr
                INNER JOIN {enrol} AS enr ON enr.id = uenr.enrolid
            ) AS uenr ON uenr.userid = u.id AND uenr.courseid = ra.courseid
            CROSS JOIN (SELECT @cnt := 0) AS dummy
            WHERE $select";

        return $DB->get_records_sql($sql_request);
    }

    private static function form_select($users) {
        global $CFG;

        $select = "u.deleted <> 1 AND u.id <> $CFG->siteguest";

        if (count($users))
        {
            $select .= ' AND (';
            $i = 0;
            foreach ($users as $user) {
                if ($i === 0) $select .= 'u.id = ' . $user->id;
                else $select .= ' OR u.id = ' . $user->id;
                $i++;
            }
            $select .= ')';
        }
        else $select .= ' AND u.id = 0';

        return $select;
    }
}
?>