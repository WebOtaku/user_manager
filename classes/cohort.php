<?php
namespace block_user_manager;

use stdClass;
use html_writer;
use moodle_url;

class cohort
{
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

    public static function get_empty_group_user_cohorts_obj() {
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

    public static function get_cohort_remove_member_link() {
        return function ($chtid) {
            global $OUTPUT;
            return html_writer::link(new moodle_url($this->url, array(
                'func' => 'cohort_remove_member',
                'delchtid' => $chtid,
                'userid' => $this->id,
                'sesskey' => sesskey()
            )), $OUTPUT->pix_icon('t/delete', get_string('delete', 'block_user_manager')));
        };
    }

    public static function form_cohort_members_select($cohort_members) {

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
}
?>