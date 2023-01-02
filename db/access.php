<?php
/**
 * @package    enrol_idpay
 * @copyright  IDPay
 * @author     Mohammad Nabipour
 * @license    https://idpay.ir/
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    'enrol/idpay:config' =>
        [
            'captype' => 'write',
            'contextlevel' => CONTEXT_COURSE,
            'archetypes' => ['manager' => CAP_ALLOW]
        ],

    'enrol/idpay:manage' =>
        [
            'captype' => 'write',
            'contextlevel' => CONTEXT_COURSE,
            'archetypes' => ['manager' => CAP_ALLOW,'editingteacher' => CAP_ALLOW]
       ],

    'enrol/idpay:unenrol' =>
        [
            'captype' => 'write',
            'contextlevel' => CONTEXT_COURSE,
            'archetypes' => ['manager' => CAP_ALLOW]
        ],

    'enrol/idpay:unenrolself' =>
        [
            'captype' => 'write',
            'contextlevel' => CONTEXT_COURSE,
            'archetypes' => []
       ],
];

