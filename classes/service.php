<?php

namespace block_user_manager;

use context_system, admin_externalpage, moodle_url,
    print_object, tabobject, tabtree, context, csv_import_reader, stdClass;

class service
{
    /**
     * Initialise admin page - this function does require login and permission
     * checks specified in page definition.
     *
     * This function must be called on each admin page before other code.
     *
     * @global moodle_page $PAGE
     *
     * @param string $section name of page
     * @param string $extrabutton extra HTML that is added after the blocks editing on/off button.
     * @param array $extraurlparams an array paramname => paramvalue, or parameters that need to be
     *      added to the turn blocks editing on/off form, so this page reloads correctly.
     * @param string $actualurl if the actual page being viewed is not the normal one for this
     *      page (e.g. admin/roles/allow.php, instead of admin/roles/manage.php, you can pass the alternate URL here.
     * @param array $options Additional options that can be specified for page setup.
     *      pagelayout - This option can be used to set a specific pagelyaout, admin is default.
     */
    public static function admin_externalpage_setup($section, $extrabutton = '', array $extraurlparams = null, $actualurl = '', array $options = array()) {
        global $CFG, $PAGE, $USER, $SITE, $OUTPUT;

        $PAGE->set_context(null); // hack - set context to something, by default to system context

        $site = get_site();
        require_login();

        if (!empty($options['pagelayout'])) {
            // A specific page layout has been requested.
            $PAGE->set_pagelayout($options['pagelayout']);
        } else if ($section === 'upgradesettings') {
            $PAGE->set_pagelayout('maintenance');
        } else {
            $PAGE->set_pagelayout('admin');
        }

        $adminroot = admin_get_root(false, false); // settings not required for external pages
        $extpage = $adminroot->locate($section, true);

        if (empty($extpage) or !($extpage instanceof admin_externalpage)) {
            // The requested section isn't in the admin tree
            // It could be because the user has inadequate capapbilities or because the section doesn't exist

            if (!has_capability('moodle/site:config', context_system::instance())) {
                // The requested section could depend on a different capability
                // but most likely the user has inadequate capabilities
                print_error('accessdenied', 'admin');
            } else {
                print_error('sectionerror', 'admin', "$CFG->wwwroot/$CFG->admin/");
            }
        }

        // this eliminates our need to authenticate on the actual pages
        if (!$extpage->check_access()) {
            print_error('accessdenied', 'admin');
            die;
        }

        //navigation_node::require_admin_tree();

        // $PAGE->set_extra_button($extrabutton);

        if (!$actualurl) {
            $actualurl = $extpage->url;
        }

        $PAGE->set_url($actualurl, $extraurlparams);

        if (strpos($PAGE->pagetype, 'admin-') !== 0) {
            $PAGE->set_pagetype('admin-' . $PAGE->pagetype);
        }

        if (empty($SITE->fullname) || empty($SITE->shortname)) {
            // During initial install.
            $strinstallation = get_string('installation', 'install');
            $strsettings = get_string('settings');
            $PAGE->navbar->add($strsettings);
            $PAGE->set_title($strinstallation);
            $PAGE->set_heading($strinstallation);
            $PAGE->set_cacheable(false);
            return;
        }

        // Locate the current item on the navigation and make it active when found.
        /*$path = $extpage->path;
        $node = $PAGE->settingsnav;
        while ($node && count($path) > 0) {
            $node = $node->get(array_pop($path));
        }
        if ($node) {
            $node->make_active();
        }*/

        // Normal case.
        $adminediting = optional_param('adminedit', -1, PARAM_BOOL);
        if ($PAGE->user_allowed_editing() && $adminediting != -1) {
            $USER->editing = $adminediting;
        }

        $visiblepathtosection = array_reverse($extpage->visiblepath);

        if ($PAGE->user_allowed_editing()) {
            if ($PAGE->user_is_editing()) {
                $caption = get_string('blockseditoff');
                $url = new moodle_url($PAGE->url, array('adminedit'=>'0', 'sesskey'=>sesskey()));
            } else {
                $caption = get_string('blocksediton');
                $url = new moodle_url($PAGE->url, array('adminedit'=>'1', 'sesskey'=>sesskey()));
            }
            $PAGE->set_button($OUTPUT->single_button($url, $caption, 'get'));
        }

        $PAGE->set_title("$SITE->shortname: " . implode(": ", $visiblepathtosection));
        $PAGE->set_heading($SITE->fullname);

        // prevent caching in nav block
        // $PAGE->navigation->clear_cache();
    }

    /**
     * Returns navigation controls (tabtree) to be displayed on cohort management pages
     *
     * @param context $context system or category context where cohorts controls are about to be displayed
     * @param moodle_url $currenturl
     * @return null|renderable
     */
    public static function cohort_edit_controls(context $context, moodle_url $currenturl) {
        $tabs = array();
        $currenttab = 'view';
        $viewurl = new moodle_url('/blocks/user_manager/cohort/index.php', array('contextid' => $context->id));

        $returnurl = $currenturl->get_param('returnurl');
        $blockurl = $currenturl->get_param('blockurl');
        $viewurl->param('returnurl', $returnurl);

        if (($searchquery = $currenturl->get_param('search'))) {
            $viewurl->param('search', $searchquery);
        }

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $tabs[] = new tabobject('view', new moodle_url($viewurl, array('showall' => 0)), get_string('systemcohorts', 'cohort'));
            $tabs[] = new tabobject('viewall', new moodle_url($viewurl, array('showall' => 1)), get_string('allcohorts', 'cohort'));
            if ($currenturl->get_param('showall')) {
                $currenttab = 'viewall';
            }
        } else {
            $tabs[] = new tabobject('view', $viewurl, get_string('cohorts', 'cohort'));
        }

        if (has_capability('moodle/cohort:manage', $context)) {
            $addurl = new moodle_url('/blocks/user_manager/cohort/edit.php', array('contextid' => $context->id));

            if ($returnurl)
                $addurl->param('returnurl', $returnurl);
            else
                $addurl->param('returnurl', $currenturl);

            if ($blockurl)
                $addurl->param('blockurl', $blockurl);
            else
                $addurl->param('blockurl', $currenturl);

            $tabs[] = new tabobject('addcohort', $addurl, get_string('addcohort', 'cohort'));
            if ($currenturl->get_path() === $addurl->get_path() && !$currenturl->param('id')) {
                $currenttab = 'addcohort';
            }

            $uploadurl = new moodle_url('/blocks/user_manager/cohort/upload.php', array('contextid' => $context->id));

            if ($returnurl)
                $uploadurl->param('returnurl', $returnurl);
            else
                $uploadurl->param('returnurl', $currenturl);

            if ($blockurl)
                $uploadurl->param('blockurl', $blockurl);
            else
                $uploadurl->param('blockurl', $currenturl);

            $tabs[] = new tabobject('uploadcohorts', $uploadurl, get_string('uploadcohorts', 'cohort'));
            if ($currenturl->get_path() === $uploadurl->get_path()) {
                $currenttab = 'uploadcohorts';
            }
        }

        if (count($tabs) > 1) {
            return new tabtree($tabs, $currenttab);
        }
        return null;
    }

    public static function generate_password(stdClass $user, string $emptystr = ''): string
    {
        if (empty($emptystr))
            $emptystr = mb_strtolower(get_string('empty', 'block_user_manager'));

        $symbols = array('#', '$', '%', '&');
        $en_alphabet_capitals = range('A', 'Z');
        $en_alphabet_lowercase = range('a', 'z');

        // Порядок полей в массиве влияет на порядок инициалов
        $initials_fields = array('lastname', 'firstname', 'middlename');
        $initials = '';

        foreach ($initials_fields as $initials_field) {
            if (isset($user->$initials_field) && !empty($user->$initials_field) && $user->$initials_field !== $emptystr) {
                $initial = strtolower(transliteration::translit_ru_en($user->$initials_field));
                $initials .= $initial[0];
            }
        }

        $rand_symbol = '';
        $rand_number = '';
        $rand_capital_en = '';
        $rand_lowercase_en = '';

        try {
            $rand_symbol = $symbols[random_int(0, 3)];
            $rand_number = random_int(0, 9) . random_int(0, 9);
            $rand_capital_en = $en_alphabet_capitals[random_int(0, count($en_alphabet_capitals) - 1)];
            $rand_lowercase_en = $en_alphabet_lowercase[random_int(0, count($en_alphabet_lowercase) - 1)];
        } catch (\Exception $e) {
            print_error($e->getMessage());
        }

        return $initials.$rand_symbol.$rand_number.$rand_capital_en.$rand_lowercase_en;
    }

    public static function print_error(string $message, moodle_url $baseurl)
    {
        echo '
            <div class="alert alert-danger um-alert-inform" role="alert">'. $message .'</div>
            <div class="um-alert-link"><a href="'.$baseurl.'">'.get_string('continue').'</a></div>
        ';
    }
}