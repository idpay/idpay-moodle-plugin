<?php
/**
 * @package    enrol_idpay
 * @copyright  IDPay
 * @author     Mohammad Nabipour
 * @license    https://idpay.ir/
 */

defined('MOODLE_INTERNAL') || die();

class enrol_idpay_plugin extends enrol_plugin
{

    private function doCheckout(stdClass $instance){
        global $CFG, $USER, $OUTPUT, $PAGE, $DB;
        ob_start();

        $startDateCondition = $instance->enrolstartdate != 0 && $instance->enrolstartdate > time() ;
        $endDateCondition = $instance->enrolenddate != 0 && $instance->enrolenddate < time() ;
        $beforePaid = $DB->record_exists('user_enrolments', ['userid' => $USER->id, 'enrolid' => $instance->id]);

        if ($startDateCondition || $endDateCondition || $beforePaid) {
            return ob_get_clean();
        }

        $courseId = $instance->courseid;
        $course = $DB->get_record('course', ['id' => $courseId]);
        $context = context_course::instance($course->id);
        $cost = (int)$instance->cost <= 0 ? (int)$this->get_config('cost') : (int)$instance->cost;

        if ($cost >= 10000) {

            if (!isguestuser()) {
                $courseFullName = format_string($course->fullname, true, array('context' => $context));
                $courseShortName = format_string($course->shortname, true, array('context' => $context));
                $userFullName = fullname($USER);
                $userFirstName = $USER->firstname;
                $userLastName = $USER->lastname;
                $userAddress = $USER->address;
                $userCity = $USER->city;
                $instanceName = $this->get_instance_name($instance);

                $pluginInstance = new enrol_idpay_plugin();
                $currency = $pluginInstance->get_config('currency');
                $paymentUrl = "{$CFG->wwwroot}/enrol/idpay/request.php";
                $notifyUrl = "{$CFG->wwwroot}/enrol/idpay/ipn.php";
                $returnUrl = "{$CFG->wwwroot}/enrol/idpay/return.php?id={$course->id}";
                $cancelUrl = "$CFG->wwwroot";

                $paymentRequiredTitle = get_string("paymentrequired");
                $paymentInstantTitle = get_string("paymentinstant");
                $costTitle = get_string("cost");
                $userTitle = get_string('user');
                $continueToCourseTitle = get_string('continuetocourse');
                $submitTitle = get_string("sendpaymentbutton", "enrol_idpay");

                echo "<div style='text-align: center'>";
                echo "<p>{$paymentRequiredTitle}</p>";
                echo "<p><b>{$instanceName}</b></p>";
                echo "<p><b>{$costTitle} : {$currency} {$cost}</b></p>";
                echo "<p>{$paymentInstantTitle}</p>";
                echo "<form action='{$paymentUrl}' method='post'>";
                echo "<input type='hidden' name='item_name' value='{$courseFullName}' />";
                echo "<input type='hidden' name='item_number' value='{$courseShortName}' />";
                echo "<input type='hidden' name='quantity' value='1' />";
                echo "<input type='hidden' name='on0' value='{$userTitle}' />";
                echo "<input type='hidden' name='os0' value='{$userFullName}' />";
                echo "<input type='hidden' name='course_id' value='{$course->id}' />";
                echo "<input type='hidden' name='instance_id' value='{$instance->id}' />";
                echo "<input type='hidden' name='amount' value='{$cost}' />";
                echo "<input type='hidden' name='notify_url' value='{$notifyUrl}' />";
                echo "<input type='hidden' name='return' value='{$returnUrl}' />";
                echo "<input type='hidden' name='cancel_return' value='{$cancelUrl}' />";
                echo "<input type='hidden' name='rm' value='2' />";
                echo "<input type='hidden' name='cbt' value='{$continueToCourseTitle}' />";
                echo "<input type='hidden' name='first_name' value='{$userFirstName}' />";
                echo "<input type='hidden' name='last_name' value='{$userLastName}' />";
                echo "<input type='hidden' name='address' value='{$userAddress}' />";
                echo "<input type='hidden' name='city' value='{$userCity}' />";
                echo "<input type='hidden' name='email' value='{$USER->email}' />";
                echo "<input type='hidden' name='country' value='{$USER->country}' />";
                echo "<input type='submit' value='{$submitTitle}' />";
                echo "</form>";
                echo "</div>";

            } else {
                $wwwroot = empty($CFG->loginhttps) ? $wwwroot = $CFG->wwwroot : str_replace("http://", "https://", $CFG->wwwroot);
                $text1 = get_string('paymentrequired');
                $text2 = get_string('cost');
                $text3 = get_string('loginsite');
                $urlLogin = "{$wwwroot}/login/";

                echo "<div class='mdl-align'><p>{$text1}</p>";
                echo "<p><b>{$text2} : {$instance->currency} {$cost}</b></p>";
                echo "<p><a href='{$urlLogin}'>{$text3}</a></p>";
                echo "</div>";
            }
        } else {
            $notSupportTitle = get_string('nocost', 'enrol_idpay');
            echo "<p>{$notSupportTitle}</p>";
        }
        return $OUTPUT->box(ob_get_clean());
    }

    private function getThumbnailImage(array $instances): array
    {
        $found = false;
        foreach ($instances as $instance) {
            $startDateCondition = $instance->enrolstartdate != 0 && $instance->enrolstartdate > time();
            $endDateCondition = $instance->enrolenddate != 0 && $instance->enrolenddate < time();
            if ($startDateCondition || $endDateCondition) {
                continue;
            }
            $found = true;
            break;
        }
        if ($found) {
            $title = get_string('pluginname', 'enrol_idpay') ;
            $pixIcon = new pix_icon('icon',$title , 'enrol_idpay',['class' => 'iconsize-big']);
            return [$pixIcon];
        }
        return [];
    }


    /* ------------------------------- Built In Funcs ----------------------------------- */
    public function get_info_icons(array $instances)
    {
        return $this->getThumbnailImage($instances);
    }






/* ---------------------------------------- Not Refactor ----------------------------------------------------- */


    public function roles_protected()
    {
        // users with role assign cap may tweak the roles later
        return false;
    }

    public function allow_unenrol(stdClass $instance)
    {
        // users with unenrol cap may unenrol other users manually - requires enrol/idpay:unenrol
        return true;
    }

    public function allow_manage(stdClass $instance)
    {
        // users with manage cap may tweak period and status - requires enrol/idpay:manage
        return true;
    }

    public function show_enrolme_link(stdClass $instance)
    {
        return ($instance->status == ENROL_INSTANCE_ENABLED);
    }

    /**
     * @param navigation_node $instancesnode
     * @param stdClass $instance
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function add_course_navigation($instancesnode, stdClass $instance)
    {
        if ($instance->enrol !== 'idpay') {
            throw new coding_exception('Invalid enrol instance type!');
        }

        $context = context_course::instance($instance->courseid);
        if (has_capability('enrol/idpay:config', $context)) {
            $managelink = new moodle_url('/enrol/idpay/edit.php', ['courseid' => $instance->courseid, 'id' => $instance->id]);
            $instancesnode->add($this->get_instance_name($instance), $managelink, navigation_node::TYPE_SETTING);
        }
    }

    /**
     * @param stdClass $instance
     * @return array
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function get_action_icons(stdClass $instance)
    {
        global $OUTPUT;

        if ($instance->enrol !== 'idpay') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = context_course::instance($instance->courseid);

        $icons = array();

        if (has_capability('enrol/idpay:config', $context)) {
            $editlink = new moodle_url("/enrol/idpay/edit.php", array('courseid' => $instance->courseid, 'id' => $instance->id));
            $icons[] = $OUTPUT->action_icon($editlink, new pix_icon('t/edit', get_string('edit'), 'core',
                array('class' => 'iconsmall')));
        }

        return $icons;
    }

    /**
     * @param int $courseid
     * @return moodle_url|null
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function get_newinstance_link($courseid)
    {
        $context = context_course::instance($courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/idpay:config', $context)) {
            return NULL;
        }

        // multiple instances supported - different cost for different roles
        return new moodle_url('/enrol/idpay/edit.php', array('courseid' => $courseid));
    }

    /**
     * Creates course enrol form, checks if form submitted
     * and enrols user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     * @throws coding_exception
     */
    function enrol_page_hook(stdClass $instance)
    {
       return $this->doCheckout($instance);
    }


    /**
     * Restore instance and map settings.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid)
    {
        global $DB;
        if ($step->get_task()->get_target() == backup::TARGET_NEW_COURSE) {
            $merge = false;
        } else {
            $merge = array(
                'courseid' => $data->courseid,
                'enrol' => $this->get_name(),
                'roleid' => $data->roleid,
                'cost' => $data->cost,
                'currency' => $data->currency,
            );
        }
        if ($merge and $instances = $DB->get_records('enrol', $merge, 'id')) {
            $instance = reset($instances);
            $instanceid = $instance->id;
        } else {
            $instanceid = $this->add_instance($course, (array)$data);
        }
        $step->set_mapping('enrol', $oldid, $instanceid);
    }

    /**
     * Restore user enrolment.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $instance
     * @param int $oldinstancestatus
     * @param int $userid
     */
    public function restore_user_enrolment(restore_enrolments_structure_step $step, $data, $instance, $userid, $oldinstancestatus)
    {
        $this->enrol_user($instance, $userid, null, $data->timestart, $data->timeend, $data->status);
    }

    /**
     * Gets an array of the user enrolment actions
     *
     * @param course_enrolment_manager $manager
     * @param stdClass $ue A user enrolment object
     * @return array An array of user_enrolment_actions
     */
    public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue)
    {
        $actions = array();
        $context = $manager->get_context();
        $instance = $ue->enrolmentinstance;
        $params = $manager->get_moodlepage()->url->params();
        $params['ue'] = $ue->id;
        if ($this->allow_unenrol($instance) && has_capability("enrol/idpay:unenrol", $context)) {
            $url = new moodle_url('/enrol/unenroluser.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/delete', ''), get_string('unenrol', 'enrol'), $url, array('class' => 'unenrollink', 'rel' => $ue->id));
        }
        if ($this->allow_manage($instance) && has_capability("enrol/idpay:manage", $context)) {
            $url = new moodle_url('/enrol/editenrolment.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/edit', ''), get_string('edit'), $url, array('class' => 'editenrollink', 'rel' => $ue->id));
        }
        return $actions;
    }

    public function cron()
    {
        $trace = new text_progress_trace();
        $this->process_expirations($trace);
    }

    /**
     * Execute synchronisation.
     * @param progress_trace $trace
     * @return int exit code, 0 means ok
     */
    public function sync(progress_trace $trace)
    {
        $this->process_expirations($trace);
        return 0;
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance)
    {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/idpay:config', $context);
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance)
    {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/idpay:config', $context);
    }
}
