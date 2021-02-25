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
    public static function get_users_cohorts($chtid = 0) {
        global $DB;

        $sql_request = '
            SELECT (@cnt := @cnt + 1) AS id, u.id AS userid, u.lastname, u.firstname,
                   cht.id AS chtid, cht.name AS cht_code_mdl, cht1c.group1c AS cht_code, 
                   cht.description, cht1c.form
            FROM {user} AS u
            LEFT JOIN {cohort_members} AS chtm ON chtm.userid = u.id
            LEFT JOIN (
                SELECT cht.id, cht.name, cht.description 
                FROM {cohort} AS cht
            ) AS cht ON cht.id = chtm.cohortid
            LEFT JOIN {block_cohort1c_synch} AS cht1c ON cht1c.cohortid = cht.id
            CROSS JOIN (SELECT @cnt := 0) AS dummy';
        
        $sql_params = array();

        if ($chtid > 0) {
            $sql_request .= ' WHERE cht.id = :chtid ';
            $sql_params['chtid'] = $chtid;
        }

        $sql_request .= ' ORDER BY u.id';
            
        if (count($sql_params))
            $query_result = $DB->get_records_sql($sql_request, $sql_params);
        else 
            $query_result = $DB->get_records_sql($sql_request);

        return $query_result;
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
//    public static function get_users_courses() {
//        global $DB;
//
//        $sql_request = '
//            SELECT (@cnt := @cnt + 1) AS id, u.id AS userid, u.firstname AS firstname, u.lastname AS lastname,
//            crs.id AS courseid, crs.fullname AS course, r.shortname AS role
//            FROM {user} AS u
//            LEFT JOIN {role_assignments} AS ra ON ra.userid = u.id
//            LEFT JOIN {context} AS c ON c.id = ra.contextid
//            LEFT JOIN {course} AS crs ON crs.id = c.instanceid
//            LEFT JOIN {role} AS r ON r.id = ra.roleid
//            CROSS JOIN (SELECT @cnt := 0) AS dummy
//            GROUP BY u.id, crs.id, r.id
//            ORDER BY u.id
//        ';
//
//        $query_result = $DB->get_records_sql($sql_request);
//
//        return $query_result;
//    }

    public static function get_users_courses() {
        global $DB;

        $sql_request = '
            SELECT (@cnt := @cnt + 1) AS id, u.id AS userid, u.firstname, u.lastname, 
            ra.courseid, ra.course, ra.role, uenr.enrol AS enrol_method
            FROM {user} AS u
            LEFT JOIN (
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
            ORDER BY u.id
        ';

        $query_result = $DB->get_records_sql($sql_request);

        return $query_result;
    }

    public static function new_get_users_courses($sort = 'lastaccess', $dir = 'ASC', $page = 0, $recordsperpage = 0,
                                                 $extraselect = '', array $extraparams = null)
    {
        global $DB, $CFG;

        $select = "deleted <> 1 AND id <> :guestid";
        $params = array('guestid' => $CFG->siteguest);

        if ($extraselect) {
            $select .= " AND $extraselect";
            $params = $params + (array)$extraparams;
        }

        if ($sort) {
            $sort = "ORDER BY $sort $dir";
        }

        $sql_request = "
            SELECT (@cnt := @cnt + 1) AS id, u.id AS userid, u.firstname, u.lastname, 
            ra.courseid, ra.course, ra.role, uenr.enrol AS enrol_method
            FROM {user} AS u
            LEFT JOIN (
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
            WHERE $select
            $sort";

        print_object($sql_request);

        return $DB->get_records_sql($sql_request, $params, $page, $recordsperpage);
    }

    public static function new_get_users_cohorts($chtid = 0) {
        global $DB;

        $sql_request = '
            SELECT (@cnt := @cnt + 1) AS id, u.id AS userid, u.lastname, u.firstname,
                   cht.id AS chtid, cht.name AS cht_code_mdl, cht1c.group1c AS cht_code, 
                   cht.description, cht1c.form
            FROM {user} AS u
            LEFT JOIN {cohort_members} AS chtm ON chtm.userid = u.id
            LEFT JOIN (
                SELECT cht.id, cht.name, cht.description 
                FROM {cohort} AS cht
            ) AS cht ON cht.id = chtm.cohortid
            LEFT JOIN {block_cohort1c_synch} AS cht1c ON cht1c.cohortid = cht.id
            CROSS JOIN (SELECT @cnt := 0) AS dummy';

        $sql_params = array();

        if ($chtid > 0) {
            $sql_request .= ' WHERE cht.id = :chtid ';
            $sql_params['chtid'] = $chtid;
        }

        $sql_request .= ' ORDER BY u.id';

        if (count($sql_params))
            $query_result = $DB->get_records_sql($sql_request, $sql_params);
        else
            $query_result = $DB->get_records_sql($sql_request);

        return $query_result;
    }
}
?>