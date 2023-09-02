<?php
/**
 * @package    enrol_idpay
 * @copyright  IDPay
 * @author     Mohammad Nabipour
 * @license    https://idpay.ir/
 */

global $CFG,$DB,$PAGE,$OUTPUT;
require('../../config.php');
require_once($CFG->libdir.'/formslib.php');

class enrol_idpay_edit_form extends moodleform {

    function definition() {
        $mform = $this->_form;

        list($instance, $plugin, $context) = $this->_customdata;

        $mform->addElement('header', 'header', get_string('pluginname', 'enrol_idpay'));

        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));
        $mform->setType('name', PARAM_TEXT);
        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'), ENROL_INSTANCE_DISABLED => get_string('no'));
        $mform->addElement('select', 'status', get_string('status', 'enrol_idpay'), $options);
        $mform->setDefault('status', $plugin->get_config('status'));

        $mform->addElement('text', 'cost', get_string('cost', 'enrol_idpay'), array('size'=>4));
        $mform->setType('cost', PARAM_RAW);
        $mform->setDefault('cost', (int) $plugin->get_config('cost'));

        if ($instance->id) {
            $roles = get_default_enrol_roles($context, $instance->roleid);
        } else {
            $roles = get_default_enrol_roles($context, $plugin->get_config('roleid'));
        }
        $mform->addElement('select', 'roleid', get_string('assignrole', 'enrol_idpay'), $roles);
        $mform->setDefault('roleid', $plugin->get_config('roleid'));

        $mform->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol_idpay'), array('optional' => true, 'defaultunit' => 86400));
        $mform->setDefault('enrolperiod', $plugin->get_config('enrolperiod'));
        $mform->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_idpay');

        $mform->addElement('date_time_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_idpay'), array('optional' => true));
        $mform->setDefault('enrolstartdate', 0);
        $mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_idpay');

        $mform->addElement('date_time_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_idpay'), array('optional' => true));
        $mform->setDefault('enrolenddate', 0);
        $mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_idpay');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        if (enrol_accessing_via_instance($instance)) {
            $mform->addElement('static', 'selfwarn', get_string('instanceeditselfwarning', 'core_enrol'), get_string('instanceeditselfwarningtext', 'core_enrol'));
        }

        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        $this->set_data($instance);
    }

    function validation($data, $files) {
        global $DB, $CFG;
        $errors = parent::validation($data, $files);

        list($instance, $plugin, $context) = $this->_customdata;

        if (!empty($data['enrolenddate']) and $data['enrolenddate'] < $data['enrolstartdate']) {
            $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_idpay');
        }

        $cost = str_replace(get_string('decsep', 'langconfig'), '.', $data['cost']);
        if (!is_numeric($cost)) {
            $errors['cost'] = get_string('costerror', 'enrol_idpay');
        }

        return $errors;
    }
}

$courseid   = required_param('courseid', PARAM_INT);
$instanceid = optional_param('id', 0, PARAM_INT);

$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
require_capability('enrol/idpay:config', $context);

$PAGE->set_url('/enrol/idpay/edit.php', ['courseid'=>$course->id, 'id'=>$instanceid]);
$PAGE->set_pagelayout('admin');

$return = new moodle_url('/enrol/instances.php', ['id'=>$course->id]);
if (!enrol_is_enabled('idpay')) {
    redirect($return);
}

$plugin = enrol_get_plugin('idpay');

if ($instanceid) {
    $instance = $DB->get_record('enrol',
        [
            'courseid'=>$course->id,
            'enrol'=>'idpay',
            'id'=>$instanceid,
        ],
    '*',
    MUST_EXIST);
    $instance->cost = (int) $instance->cost;
} else {
    require_capability('moodle/course:enrolconfig', $context);
    navigation_node::override_active_url(new moodle_url('/enrol/instances.php', array('id'=>$course->id)));
    $instance = new stdClass();
    $instance->id       = null;
    $instance->courseid = $course->id;
}

$mform = new enrol_idpay_edit_form(NULL, array($instance, $plugin, $context));

if ($mform->is_cancelled()) {
    redirect($return);

} else if ($data = $mform->get_data()) {
    if ($instance->id) {
        $reset = ($instance->status != $data->status);

        $instance->status         = $data->status;
        $instance->name           = $data->name;
        $instance->cost           = (int) $data->cost;
        $instance->roleid         = $data->roleid;
        $instance->enrolperiod    = $data->enrolperiod;
        $instance->enrolstartdate = $data->enrolstartdate;
        $instance->enrolenddate   = $data->enrolenddate;
        $instance->timemodified   = time();
        $DB->update_record('enrol', $instance);

        if ($reset) {
            $context->mark_dirty();
        }

    } else {
        $fields = [
            'status'=>$data->status,
            'name'=>$data->name,
            'cost'=> (int) $data->cost,
            'roleid'=>$data->roleid,
            'enrolperiod'=>$data->enrolperiod,
            'enrolstartdate'=>$data->enrolstartdate,
            'enrolenddate'=>$data->enrolenddate
        ];
        $plugin->add_instance($course, $fields);
    }
    redirect($return);
}

$pluginTitle = get_string('pluginname', 'enrol_idpay');
$PAGE->set_heading($course->fullname);
$PAGE->set_title($pluginTitle);
$headingTitle = get_string('pluginname', 'enrol_idpay');
echo $OUTPUT->header();
echo $OUTPUT->heading($headingTitle);
$mform->display();
echo $OUTPUT->footer();
