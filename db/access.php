<?php
$capabilities = array(
    'block/user_manager:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_PROHIBIT
        ),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),

    'block/user_manager:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'manager'        => CAP_PROHIBIT,
//            'coursecreator'  => CAP_PROHIBIT,
            'editingteacher' => CAP_PROHIBIT,
//            'teacher'        => CAP_PROHIBIT,
//            'student'        => CAP_PROHIBIT,
//            'guest'          => CAP_PROHIBIT,
            'user'           => CAP_PROHIBIT,
//            'frontpage'      => CAP_PROHIBIT
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

    'block/user_manager:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_PROHIBIT
        )
    ),

    'block/user_manager:edit' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array()
    )
);