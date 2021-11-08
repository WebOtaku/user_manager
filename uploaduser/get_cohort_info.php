<?php

use block_user_manager\service;

require('../../../config.php');
require_once('../locallib.php');

require_login();

$context = context_system::instance();
require_capability('moodle/site:uploadusers', $context);

if (isset($_POST['cohort'])) {
    $cohort = $_POST['cohort'];

    if (!is_array($cohort)) {
        $cohort_info = $DB->get_record('cohort', ['id' => (int)$cohort]);

        if (isset($cohort_info->description) && $cohort_info->description) {
            $response_code = 200;
            $data = array(
                'data' => $cohort_info->description,
                'status' => $response_code
            );
            service::returnJsonHttpResponse($data, $response_code);
        } else {
            $response_code = 400;
            $data = array(
                'data' => get_string('nocohortinfo', 'block_user_manager'),
                'status' => $response_code
            );
            service::returnJsonHttpResponse($data, $response_code);
        }
    } else {
        $response_code = 400;
        $data = array(
            'data' => get_string('cohortnotarray', 'block_user_manager'),
            'status' => $response_code
        );
        service::returnJsonHttpResponse($data, $response_code);
    }
} else {
    $response_code = 400;
    $data = array(
        'data' => get_string('nocohortspecified', 'block_user_manager'),
        'status' => $response_code
    );

    service::returnJsonHttpResponse($data, $response_code);
}
