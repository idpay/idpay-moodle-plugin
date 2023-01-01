<?php
/**
 * @package    enrol_idpay
 * @copyright  IDPay
 * @author     Mohammad Nabipour
 * @license    https://idpay.ir/
 */

defined('MOODLE_INTERNAL') || die();
global $PAGE, $ADMIN;

if ($ADMIN->fulltree) {

    /* create link for go to page history */
    $pageTitle = get_string('idpay_history', 'enrol_idpay');
    $pageLink = new moodle_url('/enrol/idpay/idpay_log.php');
    $previewNode = $PAGE->navigation->add($pageTitle, $pageLink, navigation_node::TYPE_CONTAINER);
    $previewNode->make_active();

    /* make setting plugin generator */

    /** @var admin_settingpage $settings * */
    $headingText = get_string('pluginname_desc', 'enrol_idpay');
    $heading = new admin_setting_heading('enrol_idpay_settings', '', $headingText);
    $settings->add($heading);

    $apiKeyTitle = get_string('api_key', 'enrol_idpay');
    $apiKeyConfig = new admin_setting_configtext('enrol_idpay/api_key', $apiKeyTitle, 'Api-Key', '', PARAM_RAW);
    $settings->add($apiKeyConfig);

    $currencyTitle = get_string('currency', 'enrol_idpay');
    $currencyConfig = new admin_setting_configtext('enrol_idpay/currency', $currencyTitle, 'Currency', '', PARAM_RAW);
    $settings->add($currencyConfig);

    $sandboxTitle = get_string('sandbox', 'enrol_idpay');
    $sandboxConfig = new admin_setting_configcheckbox('enrol_idpay/sandbox', $sandboxTitle, 'Sandbox', 0);
    $settings->add($sandboxConfig);

    $mailStudentsTitle = get_string('mailstudents', 'enrol_idpay');
    $mailStudentsConfig = new admin_setting_configcheckbox('enrol_idpay/mailstudents', $mailStudentsTitle, 'Mail-Students', 0);
    $settings->add($mailStudentsConfig);

    $mailTeachersTitle = get_string('mailteachers', 'enrol_idpay');
    $mailTeachersConfig = new admin_setting_configcheckbox('enrol_idpay/mailteachers', $mailTeachersTitle, 'Mail-Teachers', 0);
    $settings->add($mailTeachersConfig);

    $mailAdminsTitle = get_string('mailadmins', 'enrol_idpay');
    $mailAdminsConfig = new admin_setting_configcheckbox('enrol_idpay/mailadmins', $mailAdminsTitle, 'Mail-Admins', 0);
    $settings->add($mailAdminsConfig);

    /* After expire Action type config */
    $expiredOptions = [
        ENROL_EXT_REMOVED_KEEP => get_string('extremovedkeep', 'enrol'),
        ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol'),
        ENROL_EXT_REMOVED_UNENROL => get_string('extremovedunenrol', 'enrol'),
    ];

    $expiredActionTitle = get_string('expiredaction', 'enrol_idpay');
    $expiredActionDescription = get_string('expiredaction_help', 'enrol_idpay');
    $expiredActionConfig = new admin_setting_configselect('enrol_idpay/expiredaction',
        $expiredActionTitle,
        $expiredActionDescription,
        ENROL_EXT_REMOVED_SUSPENDNOROLES,
        $expiredOptions);
    $settings->add($expiredActionConfig);
    
    
    /* Add Default Heading */
    $headingText = get_string('enrolinstancedefaults', 'admin');
    $headingDescription = get_string('enrolinstancedefaults_desc', 'admin');
    $heading = new admin_setting_heading('enrol_idpay_defaults', $headingText, $headingDescription);
    $settings->add($heading);


    $statusOptions = [
        ENROL_INSTANCE_ENABLED => get_string('yes'),
        ENROL_INSTANCE_DISABLED => get_string('no')
    ];
    
    $statusTitle = get_string('status', 'enrol_idpay');
    $statusDescription = get_string('status_desc', 'enrol_idpay');
    $statusConfig = new admin_setting_configselect('enrol_idpay/status',
        $statusTitle,
        $statusDescription,
        ENROL_INSTANCE_DISABLED,
        $statusOptions);
    $settings->add($statusConfig);

    
    $costTitle = get_string('cost', 'enrol_idpay');
    $costConfig =  new admin_setting_configtext('enrol_idpay/cost', $costTitle, '', 0, PARAM_FLOAT, 4);
    $settings->add($costConfig);
    
    
    /* make currencies select box */
    $currenciesOptions = [
        'IRR' => new lang_string('IRR', 'core_currencies')
    ];

    $currenciesTitle = get_string('currency', 'enrol_idpay');
    $currenciesConfig =  new admin_setting_configselect('enrol_idpay/currency',
        $currenciesTitle ,
        '',
        'IRR',
        $currenciesOptions);
    $settings->add($currenciesConfig);


    $periodDurationTitle = get_string('enrolperiod', 'enrol_idpay');
    $periodDurationDescription = get_string('enrolperiod_desc', 'enrol_idpay');
    $periodDurationConfig =  new admin_setting_configduration('enrol_idpay/enrolperiod',$periodDurationTitle,$periodDurationDescription , 0);
    $settings->add($periodDurationConfig);


    /* added config if not in prepare initial Status */
    if (!during_initial_install()) {
        $context = context_system::instance();
        $options = get_default_enrol_roles($context);
        $student = get_archetype_roles('student');
        $student = reset($student);

        $defaultRoleTitle = get_string('defaultrole', 'enrol_idpay');
        $defaultRoleDescription = get_string('defaultrole_desc', 'enrol_idpay');
        $defaultRoleConfig = new admin_setting_configselect('enrol_idpay/roleid',
            $defaultRoleTitle ,
            $defaultRoleDescription,
            $student->id,
            $options);
        $settings->add($defaultRoleConfig);

    }
    /* End */


}
