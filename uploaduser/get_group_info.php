<?php

use block_user_manager\cohort1c_lib1c;
use block_user_manager\service;
use block_user_manager\html;

require('../../../config.php');
require_once('../locallib.php');

require_login();

$context = context_system::instance();
require_capability('moodle/site:uploadusers', $context);

if (isset($_POST['group'])) {
    $group = $_POST['group'];

    if (!is_array($group)) {
        $ini = parse_ini_file('../conf.ini', true);
        $period_start = (int)$ini['period_start']; //$period_end - 1;
        $period_end = (int)$ini['period_end']; // date('Y');

        list($students, $group_info) = cohort1c_lib1c::GetGroupInfoByGroup($group, $period_start, $period_end, IS_STUDENT_STATUS_1C);

        if (count($students)) {
            $group_info = cohort1c_lib1c::FormatGroupInfo(
                $group_info, count($students), $period_end, 0, FORMAT_FIELDS
            );

            $message = html::generate_paragraph_list_from_arr($group_info);

            $response_code = 200;
            $data = array(
                'data' => [
                    'groupInfoStr' => $message,
                    'groupInfo' => $group_info
                ],
                'status' => $response_code
            );
            service::returnJsonHttpResponse($data, $response_code);
        } else {
            $response_code = 400;
            $data = array(
                'data' => get_string('nogroupinfo', 'block_user_manager'),
                'status' => $response_code
            );
            service::returnJsonHttpResponse($data, $response_code);
        }
    } else {
        $response_code = 400;
        $data = array(
            'data' => get_string('groupnotarray', 'block_user_manager'),
            'status' => $response_code
        );
        service::returnJsonHttpResponse($data, $response_code);
    }
} else {
    $response_code = 400;
    $data = array(
        'data' => get_string('nogroupspecified', 'block_user_manager'),
        'status' => $response_code
    );

    service::returnJsonHttpResponse($data, $response_code);
}
