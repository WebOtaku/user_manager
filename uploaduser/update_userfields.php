<?php
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
            returnJsonHttpResponse($data, $response_code);
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
        returnJsonHttpResponse($data, $response_code);
    }
    else {
        deleteSystemFields($tablename, $db_systemfields);

        $response_code = 200;
        $data = array(
            'data' => get_string('changessaved', 'block_user_manager'),
            'status' => $response_code
        );
        returnJsonHttpResponse($data, $response_code);
    }
} else {
    deleteSystemFields($tablename, $db_systemfields);

    $response_code = 400;
    $data = array(
        'data' => get_string('emptyrequest', 'block_user_manager'),
        'status' => $response_code
    );

    returnJsonHttpResponse($data, $response_code);
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

/*
 * returnJsonHttpResponse
 * @param $success: Boolean
 * @param $data: Object or Array
 */
function returnJsonHttpResponse($data, $response_code = 200)
{
    // remove any string that could create an invalid JSON
    // such as PHP Notice, Warning, logs...
    ob_clean();

    // this will clean up any previously added headers, to start clean
    header_remove();

    // Set the content type to JSON and charset
    // (charset can be set to something else)
    header("Content-type: application/json; charset=utf-8");

    // Set your HTTP response code, 2xx = SUCCESS,
    // anything else will be error, refer to HTTP documentation

    // encode your PHP Object or Array into a JSON string.
    // stdClass or array
    $json = json_encode($data);

    if ($json === false) {
        // Set HTTP response status code to: 500 - Internal Server Error
        $response_code = 500;
        // Avoid echo of empty string (which is invalid JSON), and
        // JSONify the error message instead:
        $data = array(
            'data' => json_last_error_msg(),
            'status' => $response_code
        );
        $json = json_encode($data);

        if ($json === false) {
            // This should not happen, but we go all the way now:
            $data = array(
                'data' => get_string('unknown', 'block_user_manager'),
                'status' => $response_code
            );
            $json = json_encode($data);
        }
    }

    http_response_code($response_code);
    echo $json;

    // making sure nothing is added
    exit();
}
