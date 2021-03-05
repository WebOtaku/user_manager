<?php

namespace block_user_manager;

use stdClass;
use html_writer;

class course
{
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

    public static function get_empty_group_user_courses_obj() {
        $group_user_courses = new stdClass();
        $group_user_courses->lastname = '';
        $group_user_courses->firstname = '';
        $group_user_courses->courseids = array();
        $group_user_courses->courses = array();
        $group_user_courses->roles = array();
        $group_user_courses->enrol_methods = array();

        return $group_user_courses;
    }
}