<?php
namespace block_user_manager;

use stdClass;
use html_writer;
use moodle_url;

class cohort {
    
//    public static function prepare_group_users_cohorts_for_output($users_cohorts, $baseurl = '', $sitecontext = '') {
//        global $CFG, $OUTPUT;
//
//        $strdelete = get_string('delete');
//        $group_users_cohorts = array();
//
//        foreach ($users_cohorts as $user_cohort) {
//            $userid = $user_cohort->userid;
//            if (!isset($group_users_cohorts[$userid]))
//                $group_users_cohorts[$userid] = new stdClass();
//
//            if (isset($group_users_cohorts[$userid]->group_ids) && $user_cohort->chtid)
//                $group_users_cohorts[$userid]->group_ids[] = $user_cohort->chtid;
//            elseif (!isset($group_users_cohorts[$userid]->group_ids) && $user_cohort->chtid)
//                $group_users_cohorts[$userid]->group_ids = array($user_cohort->chtid);
//            elseif (!isset($group_users_cohorts[$userid]->group_ids))
//                $group_users_cohorts[$userid]->group_ids = array();
//
//            if (isset($group_users_cohorts[$userid]->cht_code_mdl) && $user_cohort->cht_code_mdl)
//                $group_users_cohorts[$userid]->cht_code_mdl .= '<br>';
//            elseif (!isset($group_users_cohorts[$userid]->cht_code_mdl))
//                $group_users_cohorts[$userid]->cht_code_mdl = '';
//
//            if ($user_cohort->cht_code_mdl) {
//                $group_users_cohorts[$userid]->cht_code_mdl .= "
//                    <a href=\"$CFG->wwwroot/cohort/assign.php?id=$user_cohort->chtid\">$user_cohort->cht_code_mdl</a>
//                ";
//
//                if (has_capability('moodle/cohort:manage', $sitecontext)) {
//                    $group_users_cohorts[$userid]->cht_code_mdl .= html_writer::link(new moodle_url($baseurl, array(
//                        'func' => 'cohort_remove_member',
//                        'chtid' => $user_cohort->chtid,
//                        'userid' => $userid,
//                        'sesskey' => sesskey()
//                    )), $OUTPUT->pix_icon('t/delete', $strdelete));
//                }
//            }
//
//            if (isset($group_users_cohorts[$userid]->cht_code) && $user_cohort->cht_code)
//                $group_users_cohorts[$userid]->cht_code .= '<br>';
//            elseif (!isset($group_users_cohorts[$userid]->cht_code))
//                $group_users_cohorts[$userid]->cht_code = '';
//
//            if ($user_cohort->cht_code) {
//                $group_users_cohorts[$userid]->cht_code .= "
//                    <a href=\"$CFG->wwwroot/cohort/assign.php?id=$user_cohort->chtid\">$user_cohort->cht_code</a>
//                ";
//
//                /*if (has_capability('moodle/cohort:manage', $sitecontext)) {
//                    $group_users_cohorts[$userid]->cht_code .= html_writer::link(new moodle_url($baseurl, array(
//                        'func' => 'cohort_remove_member',
//                        'chtid' => $user_cohort->chtid,
//                        'userid' => $userid,
//                        'sesskey' => sesskey()
//                    )), $OUTPUT->pix_icon('t/delete', $strdelete));
//                }*/
//            }
//
//            if (isset($group_users_cohorts[$userid]->form) && $user_cohort->form)
//                $group_users_cohorts[$userid]->form .= '<br>';
//            elseif (!isset($group_users_cohorts[$userid]->form))
//                $group_users_cohorts[$userid]->form = '';
//
//            if ($user_cohort->form)
//                $group_users_cohorts[$userid]->form .= $user_cohort->form;
//        }
//
//        return $group_users_cohorts;
//    }

    // TODO: Deprecated function - prepare_grouped_users_cohorts_for_output
    public static function prepare_grouped_users_cohorts_for_output($grouped_users_cohorts, $baseurl = '', $sitecontext = '', $chtsreturnurl = '') {
        global $OUTPUT;

        $strdelete = get_string('delete');
        $output_grouped_users_cohorts = array();

        foreach ($grouped_users_cohorts as $userid => $grouped_user_cohorts) {
            if (!isset($output_grouped_users_cohorts[$userid]))
                $output_grouped_users_cohorts[$userid] = new stdClass();

            if ($n = count($grouped_user_cohorts->cht_codes_mdl))
            {
                if (!isset($output_grouped_users_cohorts[$userid]->cht_codes_mdl))
                    $output_grouped_users_cohorts[$userid]->cht_codes_mdl = "";

                for ($i = 0; $i < $n; $i++) {
                    $url = new moodle_url('/cohort/assign.php', array(
                        'id' => $grouped_user_cohorts->chtids[$i],
                        'returnurl' => new moodle_url('/blocks/user_manager/group.php')
                    ));
                    $output_grouped_users_cohorts[$userid]->cht_codes_mdl .= html_writer::link($url, $grouped_user_cohorts->cht_codes_mdl[$i]);

                    if (has_capability('moodle/cohort:manage', $sitecontext)) {
                        $output_grouped_users_cohorts[$userid]->cht_codes_mdl .= html_writer::link(new moodle_url($baseurl, array(
                            'func' => 'cohort_remove_member',
                            'delchtid' => $grouped_user_cohorts->chtids[$i],
                            'userid' => $userid,
                            'sesskey' => sesskey()
                        )), $OUTPUT->pix_icon('t/delete', $strdelete));
                    }

                    if ($i < $n - 1)
                        $output_grouped_users_cohorts[$userid]->cht_codes_mdl .= '<br/>';
                }
            } else $output_grouped_users_cohorts[$userid]->cht_codes_mdl = "";

            if ($n = count($grouped_user_cohorts->cht_codes)) {
                if (!isset($output_grouped_users_cohorts[$userid]->cht_codes))
                    $output_grouped_users_cohorts[$userid]->cht_codes = "";

                for ($i = 0; $i < $n; $i++) {
                    $url = new moodle_url('/cohort/assign.php', array(
                        'id' => $grouped_user_cohorts->chtids[$i],
                        'returnurl' => new moodle_url('/blocks/user_manager/group.php')
                    ));
                    $output_grouped_users_cohorts[$userid]->cht_codes .= html_writer::link($url, $grouped_user_cohorts->cht_codes[$i]);

                    if ($i < $n - 1)
                        $output_grouped_users_cohorts[$userid]->cht_codes .= '<br/>';
                }
            } else $output_grouped_users_cohorts[$userid]->cht_codes = "";

            if ($n = count($grouped_user_cohorts->forms)) {
                if (!isset($output_grouped_users_cohorts[$userid]->forms))
                    $output_grouped_users_cohorts[$userid]->forms = "";

                for ($i = 0; $i < $n; $i++) {
                    $output_grouped_users_cohorts[$userid]->forms .= $grouped_user_cohorts->forms[$i];

                    if ($i < $n - 1)
                        $output_grouped_users_cohorts[$userid]->forms .= '<br/>';
                }
            } else $output_grouped_users_cohorts[$userid]->forms = "";
        }

        return $output_grouped_users_cohorts;
    }

    public static function prepare_grouped_users_data_for_output($grouped_users_data, $baseurl = '', $sitecontext = '', $chtsreturnurl = '') {
        global $OUTPUT;

        $strdelete = get_string('delete');
        $output_grouped_users_data = array();

        foreach ($grouped_users_data as $userid => $grouped_user_data) {
            if (!isset($output_grouped_users_data[$userid]))
                $output_grouped_users_data[$userid] = new stdClass();

            // Курсы
            if ($n = count($grouped_user_data->courses)) {
                if (!isset($output_grouped_users_data[$userid]->courses))
                    $output_grouped_users_data[$userid]->courses = "";

                for ($i = 0; $i < $n; $i++) {
                    $url = new moodle_url('/course/view.php', array(
                        'id' => $grouped_user_data->courseids[$i]
                    ));
                    $output_grouped_users_data[$userid]->courses .= html_writer::link($url, $grouped_user_data->courses[$i]);

                    if ($i < $n - 1)
                        $output_grouped_users_data[$userid]->courses .= '<br/>';
                }
            } else $output_grouped_users_data[$userid]->courses = "";

            // Роли
            if ($n = count($grouped_user_data->roles)) {
                if (!isset($output_grouped_users_data[$userid]->roles))
                    $output_grouped_users_data[$userid]->roles = "";

                for ($i = 0; $i < $n; $i++) {
                    $output_grouped_users_data[$userid]->roles .= $grouped_user_data->roles[$i];

                    if ($i < $n - 1)
                        $output_grouped_users_data[$userid]->roles .= '<br/>';
                }
            } else $output_grouped_users_data[$userid]->roles = "";

            // Коды групп (мудл)
            if ($n = count($grouped_user_data->cht_codes_mdl))
            {
                if (!isset($output_grouped_users_data[$userid]->cht_codes_mdl))
                    $output_grouped_users_data[$userid]->cht_codes_mdl = "";

                for ($i = 0; $i < $n; $i++) {
                    $url = new moodle_url('/cohort/assign.php', array(
                        'id' => $grouped_user_data->chtids[$i],
                        'returnurl' => new moodle_url('/blocks/user_manager/group.php')
                    ));
                    $output_grouped_users_data[$userid]->cht_codes_mdl .= html_writer::link($url, $grouped_user_data->cht_codes_mdl[$i]);

                    if (has_capability('moodle/cohort:manage', $sitecontext)) {
                        $output_grouped_users_data[$userid]->cht_codes_mdl .= html_writer::link(new moodle_url($baseurl, array(
                            'func' => 'cohort_remove_member',
                            'delchtid' => $grouped_user_data->chtids[$i],
                            'userid' => $userid,
                            'sesskey' => sesskey()
                        )), $OUTPUT->pix_icon('t/delete', $strdelete));
                    }

                    if ($i < $n - 1)
                        $output_grouped_users_data[$userid]->cht_codes_mdl .= '<br/>';
                }
            } else $output_grouped_users_data[$userid]->cht_codes_mdl = "";

            // Коды групп
            if ($n = count($grouped_user_data->cht_codes)) {
                if (!isset($output_grouped_users_data[$userid]->cht_codes))
                    $output_grouped_users_data[$userid]->cht_codes = "";

                for ($i = 0; $i < $n; $i++) {
                    $url = new moodle_url('/cohort/assign.php', array(
                        'id' => $grouped_user_data->chtids[$i],
                        'returnurl' => new moodle_url('/blocks/user_manager/group.php')
                    ));
                    $output_grouped_users_data[$userid]->cht_codes .= html_writer::link($url, $grouped_user_data->cht_codes[$i]);

                    if ($i < $n - 1)
                        $output_grouped_users_data[$userid]->cht_codes .= '<br/>';
                }
            } else $output_grouped_users_data[$userid]->cht_codes = "";

            // Формы обучения
            if ($n = count($grouped_user_data->forms)) {
                if (!isset($output_grouped_users_data[$userid]->forms))
                    $output_grouped_users_data[$userid]->forms = "";

                for ($i = 0; $i < $n; $i++) {
                    $output_grouped_users_data[$userid]->forms .= $grouped_user_data->forms[$i];

                    if ($i < $n - 1)
                        $output_grouped_users_data[$userid]->forms .= '<br/>';
                }
            } else $output_grouped_users_data[$userid]->forms = "";
        }

        return $output_grouped_users_data;
    }

    // TODO: Deprecated function - prepare_grouped_users_data_for_table
    public static function prepare_grouped_users_data_for_table($grouped_users_data, $baseurl = '', $sitecontext = '', $chtsreturnurl = '') {
        global $OUTPUT;

        $strdelete = get_string('delete');
        $output_grouped_users_data = array();

        foreach ($grouped_users_data as $userid => $grouped_user_data) {
            if (!isset($output_grouped_users_data[$userid]))
                $output_grouped_users_data[$userid] = new stdClass();

            // Курсы
            if ($n = count($grouped_user_data->courses)) {
                if (!isset($output_grouped_users_data[$userid]->courses))
                    $output_grouped_users_data[$userid]->courses = "";

                for ($i = 0; $i < $n; $i++) {
                    $url = new moodle_url('/course/view.php', array(
                        'id' => $grouped_user_data->courseids[$i]
                    ));
                    $output_grouped_users_data[$userid]->courses .= html_writer::link($url, $grouped_user_data->courses[$i]);

                    if ($i < $n - 1)
                        $output_grouped_users_data[$userid]->courses .= '<br/>';
                }
            } else $output_grouped_users_data[$userid]->courses = "";

            // Роли
            if ($n = count($grouped_user_data->roles)) {
                if (!isset($output_grouped_users_data[$userid]->roles))
                    $output_grouped_users_data[$userid]->roles = "";

                for ($i = 0; $i < $n; $i++) {
                    $output_grouped_users_data[$userid]->roles .= $grouped_user_data->roles[$i];

                    if ($i < $n - 1)
                        $output_grouped_users_data[$userid]->roles .= '<br/>';
                }
            } else $output_grouped_users_data[$userid]->roles = "";

            // Коды групп (мудл)
            if ($n = count($grouped_user_data->cht_codes_mdl))
            {
                if (!isset($output_grouped_users_data[$userid]->cht_codes_mdl))
                    $output_grouped_users_data[$userid]->cht_codes_mdl = "";

                for ($i = 0; $i < $n; $i++) {
                    $url = new moodle_url('/cohort/assign.php', array(
                        'id' => $grouped_user_data->chtids[$i],
                        'returnurl' => new moodle_url('/blocks/user_manager/group.php')
                    ));
                    $output_grouped_users_data[$userid]->cht_codes_mdl .= html_writer::link($url, $grouped_user_data->cht_codes_mdl[$i]);

                    if (has_capability('moodle/cohort:manage', $sitecontext)) {
                        $output_grouped_users_data[$userid]->cht_codes_mdl .= html_writer::link(new moodle_url($baseurl, array(
                            'func' => 'cohort_remove_member',
                            'delchtid' => $grouped_user_data->chtids[$i],
                            'userid' => $userid,
                            'sesskey' => sesskey()
                        )), $OUTPUT->pix_icon('t/delete', $strdelete));
                    }

                    if ($i < $n - 1)
                        $output_grouped_users_data[$userid]->cht_codes_mdl .= '<br/>';
                }
            } else $output_grouped_users_data[$userid]->cht_codes_mdl = "";

            // Коды групп
            if ($n = count($grouped_user_data->cht_codes)) {
                if (!isset($output_grouped_users_data[$userid]->cht_codes))
                    $output_grouped_users_data[$userid]->cht_codes = "";

                for ($i = 0; $i < $n; $i++) {
                    $url = new moodle_url('/cohort/assign.php', array(
                        'id' => $grouped_user_data->chtids[$i],
                        'returnurl' => new moodle_url('/blocks/user_manager/group.php')
                    ));
                    $output_grouped_users_data[$userid]->cht_codes .= html_writer::link($url, $grouped_user_data->cht_codes[$i]);

                    if ($i < $n - 1)
                        $output_grouped_users_data[$userid]->cht_codes .= '<br/>';
                }
            } else $output_grouped_users_data[$userid]->cht_codes = "";

            // Формы обучения
            if ($n = count($grouped_user_data->forms)) {
                if (!isset($output_grouped_users_data[$userid]->forms))
                    $output_grouped_users_data[$userid]->forms = "";

                for ($i = 0; $i < $n; $i++) {
                    $output_grouped_users_data[$userid]->forms .= $grouped_user_data->forms[$i];

                    if ($i < $n - 1)
                        $output_grouped_users_data[$userid]->forms .= '<br/>';
                }
            } else $output_grouped_users_data[$userid]->forms = "";
        }

        return $output_grouped_users_data;
    }

    public static function group_users_cohorts_by_users($users_cohorts) {
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
            if (isset($group_users_cohorts[$userid]->cht_codes) && $user_cohort->cht_code)
                $group_users_cohorts[$userid]->cht_codes[] = $user_cohort->cht_code;
            elseif (!isset($group_users_cohorts[$userid]->cht_codes) && $user_cohort->cht_code)
                $group_users_cohorts[$userid]->cht_codes = array($user_cohort->cht_code);
            elseif (!isset($group_users_cohorts[$userid]->cht_codes))
                $group_users_cohorts[$userid]->cht_codes = array();

            // Группируем description
            if (isset($group_users_cohorts[$userid]->descriptions) && $user_cohort->description)
                $group_users_cohorts[$userid]->descriptions[] = $user_cohort->description;
            elseif (!isset($group_users_cohorts[$userid]->descriptions) && $user_cohort->description)
                $group_users_cohorts[$userid]->descriptions = array($user_cohort->description);
            elseif (!isset($group_users_cohorts[$userid]->descriptions))
                $group_users_cohorts[$userid]->descriptions = array();

            // Группируем form
            if (isset($group_users_cohorts[$userid]->forms) && $user_cohort->form)
                $group_users_cohorts[$userid]->forms[] = $user_cohort->form;
            elseif (!isset($group_users_cohorts[$userid]->forms) && $user_cohort->form)
                $group_users_cohorts[$userid]->forms = array($user_cohort->form);
            elseif (!isset($group_users_cohorts[$userid]->forms))
                $group_users_cohorts[$userid]->forms = array();
        }

        return $group_users_cohorts;
    }

    // TODO: Deprecated function - group_users_courses_by_users
    /*public static function group_users_courses_by_users($users_courses) {
        $group_users_courses = array();

        foreach ($users_courses as $users_course) {
            $userid = $users_course->userid;
            if (!isset($group_users_courses[$userid]))
                $group_users_courses[$userid] = new stdClass();

            // Фамилия
            if (!isset($group_users_courses[$userid]->lastname) && $users_course->lastname)
                $group_users_courses[$userid]->lastname = $users_course->lastname;
            elseif (!isset($group_users_courses[$userid]->lastname))
                $group_users_courses[$userid]->lastname = '';

            // Имя
            if (!isset($group_users_courses[$userid]->firstname) && $users_course->firstname)
                $group_users_courses[$userid]->firstname = $users_course->firstname;
            elseif (!isset($group_users_courses[$userid]->firstname))
                $group_users_courses[$userid]->firstname = '';

            // Группируем courseids
            if (isset($group_users_courses[$userid]->courseids) && $users_course->courseid)
                $group_users_courses[$userid]->courseids[] = $users_course->courseid;
            elseif (!isset($group_users_courses[$userid]->courseids) && $users_course->courseid)
                $group_users_courses[$userid]->courseids = array($users_course->courseid);
            elseif (!isset($group_users_courses[$userid]->courseids))
                $group_users_courses[$userid]->courseids = array();

            // Группируем course
            if (isset($group_users_courses[$userid]->courses) && $users_course->course)
                $group_users_courses[$userid]->courses[] = $users_course->course;
            elseif (!isset($group_users_courses[$userid]->courses) && $users_course->course)
                $group_users_courses[$userid]->courses = array($users_course->course);
            elseif (!isset($group_users_courses[$userid]->courses))
                $group_users_courses[$userid]->courses = array();

            // Группируем role
            if (isset($group_users_courses[$userid]->roles) && $users_course->role)
                $group_users_courses[$userid]->roles[] = $users_course->role;
            elseif (!isset($group_users_courses[$userid]->roles) && $users_course->role)
                $group_users_courses[$userid]->roles = array($users_course->role);
            elseif (!isset($group_users_courses[$userid]->roles))
                $group_users_courses[$userid]->roles = array();

            // Группируем enrol_method
            if (isset($group_users_courses[$userid]->enrol_methods) && $users_course->enrol_method)
                $group_users_courses[$userid]->enrol_methods[] = $users_course->enrol_method;
            elseif (!isset($group_users_courses[$userid]->enrol_methods) && $users_course->enrol_method)
                $group_users_courses[$userid]->enrol_methods = array($users_course->enrol_method);
            elseif (!isset($group_users_courses[$userid]->enrol_methods))
                $group_users_courses[$userid]->enrol_methods = array();

        }

        return $group_users_courses;
    }*/

    private static function custom_array_key_last($array) {
        return array_keys($array)[count($array) - 1];
    }

    public static function group_users_courses_by_users($users_courses) {
        $group_users_courses = array();
        $group_users_courses_temp = array();

        foreach ($users_courses as $users_course) {
            $userid = $users_course->userid;
            if (!(isset($group_users_courses[$userid]) &&
                isset($group_users_courses_temp[$userid]) ))
            {
                $group_users_courses[$userid] = new stdClass();
                $group_users_courses_temp[$userid] = new stdClass();
            }

            // Фамилия
            if (!isset($group_users_courses[$userid]->lastname) && $users_course->lastname)
                $group_users_courses[$userid]->lastname = $users_course->lastname;
            elseif (!isset($group_users_courses[$userid]->lastname))
                $group_users_courses[$userid]->lastname = '';

            // Имя
            if (!isset($group_users_courses[$userid]->firstname) && $users_course->firstname)
                $group_users_courses[$userid]->firstname = $users_course->firstname;
            elseif (!isset($group_users_courses[$userid]->firstname))
                $group_users_courses[$userid]->firstname = '';

            // Группируем courseids
            if (isset($group_users_courses[$userid]->courseids) && $users_course->courseid) {
                $group_users_courses[$userid]->courseids[] = $users_course->courseid;
                $group_users_courses[$userid]->courseids =
                    array_unique($group_users_courses[$userid]->courseids);
            }
            elseif (!isset($group_users_courses[$userid]->courseids) && $users_course->courseid)
                $group_users_courses[$userid]->courseids = array($users_course->courseid);
            elseif (!isset($group_users_courses[$userid]->courseids))
                $group_users_courses[$userid]->courseids = array();

            // Группируем course
            if (isset($group_users_courses[$userid]->courses) && $users_course->course) {
                $group_users_courses[$userid]->courses[] = $users_course->course;
                $group_users_courses[$userid]->courses =
                    array_unique($group_users_courses[$userid]->courses);
            }
            elseif (!isset($group_users_courses[$userid]->courses) && $users_course->course)
                $group_users_courses[$userid]->courses = array($users_course->course);
            elseif (!isset($group_users_courses[$userid]->courses))
                $group_users_courses[$userid]->courses = array();

            // Группируем role
            if (isset($group_users_courses[$userid]->roles) &&
                isset($group_users_courses_temp[$userid]->roles) && $users_course->role)
            {
                $group_users_courses_temp[$userid]->roles[$users_course->courseid][] =
                    $users_course->role;
                $group_users_courses_temp[$userid]->roles[$users_course->courseid] =
                    array_unique($group_users_courses_temp[$userid]->roles[$users_course->courseid]);
                $id_in_coursids = array_search($users_course->courseid, $group_users_courses[$userid]->courseids);
                $group_users_courses[$userid]->roles[$id_in_coursids] =
                    implode(', ', $group_users_courses_temp[$userid]->roles[$users_course->courseid]);
            }
            elseif (!(isset($group_users_courses[$userid]->roles) &&
                    isset($group_users_courses_temp[$userid]->roles)) && $users_course->role)
            {
                $group_users_courses_temp[$userid]->roles =
                    array($users_course->courseid => [$users_course->role]);
                $id_in_coursids = array_search($users_course->courseid, $group_users_courses[$userid]->courseids);
                $group_users_courses[$userid]->roles =
                    array($id_in_coursids => ''.$users_course->role);
            }
            elseif (!(isset($group_users_courses[$userid]->roles) &&
                    isset($group_users_courses_temp[$userid]->roles)))
            {
                $group_users_courses_temp[$userid]->roles = array();
                $group_users_courses[$userid]->roles = array();
            }

            // Группируем enrol_method
            if (isset($group_users_courses[$userid]->enrol_methods) &&
                isset($group_users_courses_temp[$userid]->enrol_methods) && $users_course->enrol_method)
            {
                $group_users_courses_temp[$userid]->enrol_methods[$users_course->courseid][] =
                    $users_course->enrol_method;
                $group_users_courses_temp[$userid]->enrol_methods[$users_course->courseid] =
                    array_unique($group_users_courses_temp[$userid]->enrol_methods[$users_course->courseid]);
                $id_in_coursids = array_search($users_course->courseid, $group_users_courses[$userid]->courseids);
                $group_users_courses[$userid]->enrol_methods[$id_in_coursids] =
                    implode(', ', $group_users_courses_temp[$userid]->enrol_methods[$users_course->courseid]);
            }
            elseif (!(isset($group_users_courses[$userid]->enrol_methods) &&
                    isset($group_users_courses_temp[$userid]->enrol_methods)) && $users_course->enrol_method)
            {
                $group_users_courses_temp[$userid]->enrol_methods =
                    array($users_course->courseid => [$users_course->enrol_method]);
                $id_in_coursids = array_search($users_course->courseid, $group_users_courses[$userid]->courseids);
                $group_users_courses[$userid]->enrol_methods =
                    array($id_in_coursids => ''.$users_course->enrol_method);
            }
            elseif (!(isset($group_users_courses[$userid]->enrol_methods) &&
                    isset($group_users_courses_temp[$userid]->enrol_methods)))
            {
                $group_users_courses_temp[$userid]->enrol_methods = array();
                $group_users_courses[$userid]->enrol_methods = array();
            }
        }

        return $group_users_courses;
    }

    public static function group_users_data($users_courses, $users_cohorts) {
        $grouped_users_courses = self::group_users_courses_by_users($users_courses);
        $grouped_users_cohorts = self::group_users_cohorts_by_users($users_cohorts);

        $userids = array_keys($grouped_users_courses);

        $groped_users_data = array();

        foreach ($userids as $userid) {
            if (!isset($groped_users_data[$userid]))
                $groped_users_data[$userid] = new stdClass();

            // Общая информация о пользователе
            $groped_users_data[$userid]->firstname = $grouped_users_courses[$userid]->firstname;
            $groped_users_data[$userid]->lastname = $grouped_users_courses[$userid]->lastname;

            // Информация о курсах на которые записан пользователь
            $groped_users_data[$userid]->courseids = $grouped_users_courses[$userid]->courseids;
            $groped_users_data[$userid]->courses = $grouped_users_courses[$userid]->courses;
            $groped_users_data[$userid]->roles = $grouped_users_courses[$userid]->roles;
            $groped_users_data[$userid]->enrol_methods = $grouped_users_courses[$userid]->enrol_methods;

            // Информация о глобальных группах в которых состоит пользователь
            $groped_users_data[$userid]->chtids = $grouped_users_cohorts[$userid]->chtids;
            $groped_users_data[$userid]->cht_codes_mdl = $grouped_users_cohorts[$userid]->cht_codes_mdl;
            $groped_users_data[$userid]->cht_codes = $grouped_users_cohorts[$userid]->cht_codes;
            $groped_users_data[$userid]->descriptions = $grouped_users_cohorts[$userid]->descriptions;
            $groped_users_data[$userid]->forms = $grouped_users_cohorts[$userid]->forms;
        }

        return $groped_users_data;
    }

    // TODO: Deprecated function - filter_grouped_users_cohorts
    public static function filter_grouped_users_cohorts($grouped_users_cohorts, $field, $value) {
        $filtered_grouped_users_cohorts = array();

        foreach ($grouped_users_cohorts as $userid => $grouped_user_cohorts) {
            if (is_array($grouped_user_cohorts->$field))
                if (in_array($value, $grouped_user_cohorts->$field))
                    $filtered_grouped_users_cohorts[$userid] = $grouped_user_cohorts;
            else
                if ($grouped_user_cohorts->$field == $value)
                    $filtered_grouped_users_cohorts[$userid] = $grouped_user_cohorts;
        }

        return $filtered_grouped_users_cohorts;
    }

    public static function filter_grouped_users_data($grouped_users_data, $field, $value) {
        $filtered_grouped_users_data = array();

        foreach ($grouped_users_data as $userid => $grouped_user_data) {
            if (is_array($grouped_user_data->$field))
                if (in_array($value, $grouped_user_data->$field))
                    $filtered_grouped_users_data[$userid] = $grouped_user_data;
                else
                    if ($grouped_user_data->$field == $value)
                        $filtered_grouped_users_data[$userid] = $grouped_user_data;
        }

        return $filtered_grouped_users_data;
    }

    public static function filter_users_by_cohorts($users, $grouped_users_cohorts) {
        $filtered_users = array();

        foreach ($users as $user) {
            if (array_key_exists($user->id, $grouped_users_cohorts)) {
                $filtered_users[] = $user;
            }
        }

        return $filtered_users;
    }

    public static function generate_table_from_object($grouped_user_data = [], $object_fields_names = [], $table_fields_names = []) {
        $result_table_str = '<table class="table um-table">';

        $result_table_str .= '<thead><tr>';
        $i = 0;
        foreach ($object_fields_names as $object_field_name) {
            $result_table_str .= '<th>';
            $result_table_str .= (isset($table_fields_names[$i]))? $table_fields_names[$i] : '';
            $result_table_str .= '</th>';
            $i++;
        }
        $result_table_str .= '</tr></thead>';

        $result_table_str .= '<tbody>';

        $num_els = array();

        foreach ($object_fields_names as $field => $params) {
            if (isset($grouped_user_data->$field) && is_array($grouped_user_data->$field))
                $num_els[] = count($grouped_user_data->$field);
            else $num_els[] = 0;
        }

        $n = max($num_els);

        if ($n) {
            for ($i = 0; $i < $n; $i++) {
                $result_table_str .= '<tr>';

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
                            $result_table_str .= '<td>' . $data_str . '</td>';
                        }

                        if ($obj_field_params['type'] == 'text') {
                            $data_str = (isset($grouped_user_data->$obj_field[$i])) ? $grouped_user_data->$obj_field[$i] : '-';
                            $result_table_str .= '<td>' . $data_str . '</td>';
                        }
                    }
                }

                $result_table_str .= '</tr>';
            }
        } else {
            $result_table_str .= '<tr><td colspan="'. count($object_fields_names) .'">'.get_string('noentries', 'block_user_manager').'</td></tr>';
        }

        $result_table_str .= '</tbody></table>';

        return $result_table_str;
    }
}
?>