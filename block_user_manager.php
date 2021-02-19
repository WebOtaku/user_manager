<?php

use block_user_manager\db_request;
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

        if ($this->check_capability()) {

            $links = [
                '/blocks/user_manager/user.php',
                '/blocks/user_manager/group.php',
                '/blocks/user_manager/user_tabs.php'
            ];

            $linksparams = [[],[], ['returnurl' => $this->page->url]];

            $langfile = 'block_user_manager';
            $keyslangfile = ['users_table', 'chts_table', 'users_table_tabs'];

            $this->content->text .= html_list_view::get_html_list_links($links, $linksparams, $langfile, $keyslangfile);
        }

        return $this->content;
    }

    /**
     * Проверяет есть ли право "moodle/cohort:manage" у текущего пользователя
     * @return bool да/нет
     * */
    private function check_capability() {

        $context = context_system::instance();

        return has_capability('moodle/cohort:manage', $context);
    }

    // Позволяет ограничить отображение блока конкретными форматами страниц
    public function applicable_formats() {
        return array(
            'site' => true,
            'site-index' => true,
            'admin-index' => true
        );
    }

    // Позволяет добавлять несколько таких блоков в один курс
    public function instance_allow_multiple() {
        return false;
    }
}