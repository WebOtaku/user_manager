<?php
function xmldb_block_user_manager_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    $result = TRUE;

    if ($oldversion < 2021041620) {

        // Define table block_user_manager_ufields to be created.
        $table = new xmldb_table('block_user_manager_ufields');

        // Adding fields to table block_user_manager_ufields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('system_field', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('associated_fields', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table block_user_manager_ufields.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('unique', XMLDB_KEY_UNIQUE, ['system_field']);

        // Conditionally launch create table for block_user_manager_ufields.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // User_manager savepoint reached.
        upgrade_block_savepoint(true, 2021041620, 'user_manager');
    }


    return $result;
}
?>