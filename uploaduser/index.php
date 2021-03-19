<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Bulk user registration script from a comma separated file
 *
 * @package    tool
 * @subpackage uploaduser
 * @copyright  2004 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_user_manager\service;
use block_user_manager\transliteration;

require('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/admin/tool/uploaduser/locallib.php');
require_once('user_form.php');

$iid         = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);

core_php_time_limit::raise(60*60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);

service::admin_externalpage_setup('tooluploaduser');
require_capability('moodle/site:uploadusers', context_system::instance());

$returnurl = new moodle_url('/admin/tool/uploaduser/index.php');
$bulknurl  = new moodle_url('/admin/user/user_bulk.php');

// array of all valid fields for validation
$STD_FIELDS = array('id', 'username', 'email',
    'city', 'country', 'lang', 'timezone', 'mailformat',
    'maildisplay', 'maildigest', 'htmleditor', 'autosubscribe',
    'institution', 'department', 'idnumber', 'skype',
    'msn', 'aim', 'yahoo', 'icq', 'phone1', 'phone2', 'address',
    'url', 'description', 'descriptionformat', 'password',
    'auth',        // watch out when changing auth type or using external auth plugins!
    'oldusername', // use when renaming users - this is the original username
    'suspended',   // 1 means suspend user account, 0 means activate user account, nothing means keep as is for existing users
    'theme',       // Define a theme for user when 'allowuserthemes' is enabled.
    'deleted',     // 1 means delete user
    'mnethostid',  // Can not be used for adding, updating or deleting of users - only for enrolments, groups, cohorts and suspending.
    'interests',
);
// Include all name fields.
$STD_FIELDS = array_merge($STD_FIELDS, get_all_user_name_fields());

$PRF_FIELDS = array();
if ($proffields = $DB->get_records('user_info_field')) {
    foreach ($proffields as $key => $proffield) {
        $profilefieldname = 'profile_field_'.$proffield->shortname;
        $PRF_FIELDS[] = $profilefieldname;
        // Re-index $proffields with key as shortname. This will be
        // used while checking if profile data is key and needs to be converted (eg. menu profile field)
        $proffields[$profilefieldname] = $proffield;
        unset($proffields[$key]);
    }
}

$mform1 = new um_admin_uploaduser_form();

if ($formdata = $mform1->get_data()) {
    $iid = csv_import_reader::get_new_iid('uploaduser');
    $cir = new csv_import_reader($iid, 'uploaduser');

    $content = $mform1->get_file_content('userfile');

    $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
    $csvloaderror = $cir->get_error();
    unset($content);

    if (!is_null($csvloaderror)) {
        print_error('csvloaderror', '', $returnurl, $csvloaderror);
    }
    // test if columns ok
    $filecolumns = uu_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);

    echo $OUTPUT->header();

    echo $OUTPUT->heading_with_help(get_string('uploadusers', 'tool_uploaduser'), 'uploadusers', 'tool_uploaduser');

    $cir->init();

    $users = array();
    while ($line = $cir->next()) {

        $user = new stdClass();

        foreach ($line as $keynum => $value) {
            if (!isset($filecolumns[$keynum])) {
                // this should not happen
                continue;
            }
            $key = $filecolumns[$keynum];

            if ($key == 'username') {
                $user->$key = 'st'. trim($value);
            } else {
                $user->$key = trim($value);
            }
        }

        $user->password = service::generate_password($user->lastname);

        $users[] = $user;
    }
    // ovi&45Jr
    print_object($users);

    echo $OUTPUT->footer();
} else {
    echo $OUTPUT->header();

    echo $OUTPUT->heading_with_help(get_string('uploadusers', 'tool_uploaduser'), 'uploadusers', 'tool_uploaduser');

    $mform1->display();
    echo $OUTPUT->footer();
}