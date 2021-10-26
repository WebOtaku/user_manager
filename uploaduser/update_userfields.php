<?php

use block_user_manager\service;

require('../../../config.php');
require_once('../locallib.php');

require_login();

$context = context_system::instance();
require_capability('moodle/site:uploadusers', $context);

$tablename = 'block_user_manager_ufields';

$db_fields = $DB->get_records($tablename, null, '', 'system_field');
$db_systemfields = array_keys($db_fields);

if (isset($_POST['systemfields']))
{
    $systemfields = $_POST['systemfields'];

    if ($systemfields !== '')
    {
        $associatedfields = [];
        if (isset($_POST['associatedfields'])) {
            $associatedfields = $_POST['associatedfields'];
        }

        list($systemfields, $associatedfields) = filterEmptyValues($systemfields, $associatedfields);
        $systemfieldscounter = array_count_values($systemfields);

        if (in_array(2, $systemfieldscounter)) {
            $response_code = 400;
            $data = array(
                'data' => get_string('uniquefields', 'block_user_manager'),
                'status' => $response_code
            );
            service::returnJsonHttpResponse($data, $response_code);
        }

        $fields = array_combine($systemfields, $associatedfields);
        $filterfields = [];

        foreach ($fields as $system => $associated) {
            if (in_array($system, STD_FIELDS_EN)) {
                $filterfields[$system] = $associated;

                $key = array_search($system, $db_systemfields);
                if ($key !== false) {
                    array_splice($db_systemfields, $key, 1);
                }

                if ($record = $DB->get_record($tablename, array('system_field' => $system))) {
                    $record->associated_fields = $associated;
                    $DB->update_record($tablename, $record);
                } else {
                    $record = new stdClass();
                    $record->system_field = $system;
                    $record->associated_fields = $associated;
                    $DB->insert_record($tablename, $record);
                }
            }
        }

        deleteSystemFields($tablename, $db_systemfields);

        $response_code = 200;
        $data = array(
            'data' => get_string('changessaved', 'block_user_manager'),
            'status' => $response_code
        );
        service::returnJsonHttpResponse($data, $response_code);
    }
    else {
        deleteSystemFields($tablename, $db_systemfields);

        $response_code = 200;
        $data = array(
            'data' => get_string('changessaved', 'block_user_manager'),
            'status' => $response_code
        );
        service::returnJsonHttpResponse($data, $response_code);
    }
} else {
    deleteSystemFields($tablename, $db_systemfields);

    $response_code = 400;
    $data = array(
        'data' => get_string('emptyrequest', 'block_user_manager'),
        'status' => $response_code
    );

    service::returnJsonHttpResponse($data, $response_code);
}


function filterEmptyValues($systemfields, $associatedfields) {
    $filtered_systemfields = [];
    $filtered_associatedfields = [];

    foreach ($systemfields as $key => $systemfield) {
        if ($systemfield !== '') {
            array_push($filtered_systemfields, $systemfield);
            array_push($filtered_associatedfields, $associatedfields[$key]);
        }
    }

    return [$filtered_systemfields, $filtered_associatedfields];
}

function deleteSystemFields($tablename, $db_systemfields) {
    global $DB;

    foreach ($db_systemfields as $db_systemfield) {
        $DB->delete_records($tablename, array('system_field' => $db_systemfield));
    }
}
