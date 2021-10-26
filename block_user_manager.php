<?php

use block_user_manager\html_list_view;

class block_user_manager extends block_base {
    public function init() {
        $this->title = get_string('user_manager', 'block_user_manager');
    }

    // Задаёт содержимое для блоков
    public function get_content()
    {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        $links = [];
        $linksparams = [];
        $keyslangfile = [];

        if (self::has_access_to_users())
        {
            $links[] = '/blocks/user_manager/user.php';
            $linksparams[] = ['returnurl' => $this->page->url];
            $keyslangfile[] = 'users';
        }

        if (self::has_access_to_cohorts())
        {
            $links[] = '/blocks/user_manager/cohort/index.php';
            $linksparams[] = ['returnurl' => $this->page->url];
            $keyslangfile[] = 'cohorts';
        }


        if (self::has_access_to_uploadusers())
        {
            $links[] = '/blocks/user_manager/uploaduser/index.php';
            $linksparams[] = ['returnurl' => $this->page->url];
            $keyslangfile[] = 'uploaduser';
        }

        $langfile = 'block_user_manager';

        if (count($links))
            $this->content->text .= html_list_view::get_html_list_links($links, $linksparams, $langfile, $keyslangfile);

        return $this->content;
    }

    // Позволяет ограничить отображение блока конкретными форматами страниц
    public function applicable_formats() {
        return array(
            'all' => false,
            'site' => true,
            'site-index' => true,
            'admin-index' => true,
            'my' => true
        );
    }

    public function has_access_to_cohorts() {
        $context = context_system::instance();

        return (
            has_capability('moodle/cohort:manage', $context) ||
            has_capability('moodle/cohort:assign', $context) ||
            has_capability('moodle/cohort:view', $context)
        );
    }

    public function has_access_to_users() {
        $context = context_system::instance();

        return (
            has_capability('moodle/user:update', $context) &&
            has_capability('moodle/user:delete', $context)
        );
    }

    public function has_access_to_uploadusers() {
        $context = context_system::instance();

        return (
            has_capability('moodle/site:uploadusers', $context)
        );
    }

    public function user_can_addto($page) {
        if (is_siteadmin()){
            return true;
        }

        if (self::has_access_to_cohorts() || self::has_access_to_users())
        {
            return true;
        }

        return false;
    }

    // Позволяет добавлять несколько таких блоков в один курс
    public function instance_allow_multiple() {
        return false;
    }

    function has_config() {return true;}
}