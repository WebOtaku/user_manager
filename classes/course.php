<?php

namespace block_user_manager;

use stdClass, html_writer, moodle_url;

class course
{
    public static function group_users_courses_by_users(array $users_courses): array
    {
        $group_users_courses = array();
        $group_users_courses_temp = array();
        $translations = array();

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
                // <span> c courseid - фикс для функции array_unique, т.к. названия курсов могут совпадать
                $group_users_courses[$userid]->courses[] =
                    $users_course->course.' <span style="display:none;">#'.$users_course->courseid.'</span>';
                $group_users_courses[$userid]->courses =
                    array_unique($group_users_courses[$userid]->courses);
            }
            elseif (!isset($group_users_courses[$userid]->courses) && $users_course->course)
                $group_users_courses[$userid]->courses = array(
                    $users_course->course.' <span style="display:none;">#'.$users_course->courseid.'</span>'
                );
            elseif (!isset($group_users_courses[$userid]->courses))
                $group_users_courses[$userid]->courses = array();

            // Группируем role
            if (isset($group_users_courses[$userid]->roles) &&
                isset($group_users_courses_temp[$userid]->roles) && $users_course->role)
            {
                $translation = self::get_role_localised_name($users_course->role);
                $translations['roles'][$users_course->role] = $translation;
                $group_users_courses_temp[$userid]->roles[$users_course->courseid][] = $translation;
                $group_users_courses_temp[$userid]->roles[$users_course->courseid] =
                    array_unique($group_users_courses_temp[$userid]->roles[$users_course->courseid]);
                $id_in_coursids = array_search($users_course->courseid, $group_users_courses[$userid]->courseids);
                $group_users_courses[$userid]->roles[$id_in_coursids] =
                    implode(', ', $group_users_courses_temp[$userid]->roles[$users_course->courseid]);
            }
            elseif (!(isset($group_users_courses[$userid]->roles) &&
                    isset($group_users_courses_temp[$userid]->roles)) && $users_course->role)
            {
                $translation = self::get_role_localised_name($users_course->role);
                $translations['roles'][$users_course->role] = $translation;
                $group_users_courses_temp[$userid]->roles = array($users_course->courseid => [$translation]);
                $id_in_coursids = array_search($users_course->courseid, $group_users_courses[$userid]->courseids);
                $group_users_courses[$userid]->roles = array($id_in_coursids => $translation);
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
                $translation = get_string('pluginname', 'enrol_'.$users_course->enrol_method);
                $translations['enrol_methods'][$users_course->enrol_method] = $translation;
                $group_users_courses_temp[$userid]->enrol_methods[$users_course->courseid][] = $translation;

                $group_users_courses_temp[$userid]->enrol_methods[$users_course->courseid] =
                    array_unique($group_users_courses_temp[$userid]->enrol_methods[$users_course->courseid]);
                $id_in_coursids = array_search($users_course->courseid, $group_users_courses[$userid]->courseids);
                $group_users_courses[$userid]->enrol_methods[$id_in_coursids] =
                    implode(', ', $group_users_courses_temp[$userid]->enrol_methods[$users_course->courseid]);
            }
            elseif (!(isset($group_users_courses[$userid]->enrol_methods) &&
                    isset($group_users_courses_temp[$userid]->enrol_methods)) && $users_course->enrol_method)
            {
                $translation = get_string('pluginname', 'enrol_'.$users_course->enrol_method);
                $translations['enrol_methods'][$users_course->enrol_method] = $translation;
                $group_users_courses_temp[$userid]->enrol_methods = array($users_course->courseid => [$translation]);
                $id_in_coursids = array_search($users_course->courseid, $group_users_courses[$userid]->courseids);
                $group_users_courses[$userid]->enrol_methods = array($id_in_coursids => ''.$translation);
            }
            elseif (!(isset($group_users_courses[$userid]->enrol_methods) &&
                isset($group_users_courses_temp[$userid]->enrol_methods)))
            {
                $group_users_courses_temp[$userid]->enrol_methods = array();
                $group_users_courses[$userid]->enrol_methods = array();
            }
        }

        return [$group_users_courses, $translations];
    }

    public static function get_empty_group_user_courses_obj(): stdClass
    {
        $group_user_courses = new stdClass();
        $group_user_courses->lastname = '';
        $group_user_courses->firstname = '';
        $group_user_courses->courseids = array();
        $group_user_courses->courses = array();
        $group_user_courses->roles = array();
        $group_user_courses->enrol_methods = array();

        return $group_user_courses;
    }

    public static function get_role_localised_name(string $archetype): string {
        switch ($archetype) {
            case 'manager':         $rolename = get_string('manager', 'role'); break;
            case 'coursecreator':   $rolename = get_string('coursecreators'); break;
            case 'editingteacher':  $rolename = get_string('defaultcourseteacher'); break;
            case 'teacher':         $rolename = get_string('noneditingteacher'); break;
            case 'student':         $rolename = get_string('defaultcoursestudent'); break;
            case 'guest':           $rolename = get_string('guest'); break;
            case 'user':            $rolename = get_string('authenticateduser'); break;
            case 'frontpage':       $rolename = get_string('frontpageuser', 'role'); break;
            // We should not get here, the role UI should require the name for custom roles!
            default:                $rolename = $archetype; break;
        }

        return $rolename;
    }

    public static function get_remove_manual_enrol_user_link(): \Closure
    {
        return function ($courseid) {
            global $OUTPUT;
            return html_writer::link(new moodle_url($this->url, array(
                'func' => 'remove_manual_enrol_user',
                'delcourseid' => $courseid,
                'userid' => $this->id,
                'sesskey' => sesskey()
            )), $OUTPUT->pix_icon('t/delete', get_string('removemanualenroluser_alt', 'block_user_manager')));
        };
    }
}