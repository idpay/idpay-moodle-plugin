<?php
/**
 * @package    enrol_idpay
 * @copyright  IDPay
 * @author     Mohammad Nabipour
 * @license    https://idpay.ir/
 */

global $CFG;
require_once(dirname(__FILE__) . '/../../config.php');
require_once("lib.php");
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/filelib.php');
global $PAGE,$_SESSION, $USER, $DB, $OUTPUT;

$systemcontext = context_system::instance();
$plugininstance = new enrol_idpay_plugin();
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/enrol/idpay/verify.php');
echo $OUTPUT->header();
$Price = $_SESSION['totalcost'];
$Authority = $_GET['Authority'];
$plugin = enrol_get_plugin('idpay');
$today = date('Y-m-d');

// Check request method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $status = $_POST['status'];
  $order_id = $_POST['order_id'];
  $pid = $_POST['id'];
}elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $status = $_GET['status'];
  $order_id = $_GET['order_id'];
  $pid = $_GET['id'];
}

// Dosent exist order
if (!$order = $DB->get_record('enrol_idpay', ['id' => $order_id])) {
    $msg = other_status_messages();
    echo '<div style="color:red; font-family:tahoma; direction:rtl; text-align:right">' . $msg . '<br/></div>';
    die;
}

if ($status == '10') {

    // Set params
    $api_key = $plugininstance->get_config('api_key');
    $sandbox = $plugininstance->get_config('sandbox');
    $params = array('id' => $pid, 'order_id' => $order_id);

    // Send request to idpay and get response
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.idpay.ir/v1.1/payment/verify');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'X-API-KEY:' . $api_key,
        'X-SANDBOX: ' . $sandbox,
    ));
    $result = curl_exec($ch);
    $result = json_decode($result);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($http_status != 200) {
        $msg = sprintf('خطا هنگام ایجاد تراکنش. وضعیت خطا: %s - کد خطا: %s - پیام خطا: %s', $http_status, $result->error_code, $result->error_message);
        $order->log = $msg;
        $DB->update_record('enrol_idpay', $order);
        echo '<div style="color:red; font-family:tahoma; direction:rtl; text-align:right">' . $msg . '<br/></div>';
        exit();

    } else {

        $verify_status = empty($result->status) ? NULL : $result->status;
        $verify_track_id = empty($result->track_id) ? NULL : $result->track_id;
        $verify_order_id = empty($result->order_id) ? NULL : $result->order_id;
        $verify_amount = empty($result->amount) ? NULL : $result->amount;
        $hashed_card_no = empty($result->payment->hashed_card_no) ? NULL : $result->payment->hashed_card_no;
        $card_no = empty($result->payment->hashed_card_no) ? NULL : $result->payment->hashed_card_no;

        if (empty($verify_status) || empty($verify_track_id) || empty($verify_amount) || $verify_status < 100 || $verify_order_id !== $order_id) {
            $msg = other_status_messages();
            $order->log = $msg;
            $DB->update_record('enrol_idpay', $order);
            echo '<div style="color:red; font-family:tahoma; direction:rtl; text-align:right">' . $msg . '<br/></div>';
            die;

        } else {

            // Check double spending
            if ($verify_order_id !== $order_id or $order->idpay_id !== $result->id) {
                $msg = other_status_messages(0);
                $order->log = $msg;
                $DB->update_record('enrol_idpay', $order);
                echo '<div style="color:red; font-family:tahoma; direction:rtl; text-align:right">' . $msg . '<br/></div>';
                die;
            } else {

                $Refnumber = $res->RefID; //Transaction number
                $Resnumber = $res->RefID;//Your Order ID
                $Status = $res->Status;
                $PayPrice = ($Price / 10);
                $coursecost = $DB->get_record('enrol', ['enrol' => 'idpay', 'courseid' => $data->courseid]);
                $time = strtotime($today);
                $paidprice = $coursecost->cost;
                $order->payment_status = $status;
                $order->timeupdated = time();

                if (!$user = $DB->get_record("user", ["id" => $order->userid])) {
                    message_idpay_error_to_admin(other_status_messages(), $order);
                    die;
                }
                if (!$course = $DB->get_record("course", ["id" => $order->courseid])) {
                    message_idpay_error_to_admin(other_status_messages(), $order);
                    die;
                }
                if (!$context = context_course::instance($course->id, IGNORE_MISSING)) {
                    message_idpay_error_to_admin(other_status_messages(), $order);
                    die;
                }
                if (!$plugin_instance = $DB->get_record("enrol", ["id" => $order->instanceid, "status" => 0])) {
                    message_idpay_error_to_admin(other_status_messages(), $order);
                    die;
                }

                $coursecontext = context_course::instance($course->id, IGNORE_MISSING);

                if ((float)$plugin_instance->cost <= 0) {
                    $cost = (float)$plugin->get_config('cost');
                } else {
                    $cost = (float)$plugin_instance->cost;
                }

                // Use the same rounding of floats as on the enrol form.
                $cost = format_float($cost, 2, false);

                // Use the queried course's full name for the item_name field.
                $data->item_name = $course->fullname;

                // ALL CLEAR !
                $DB->update_record('enrol_idpay', $order);

                if ($plugin_instance->enrolperiod) {
                    $timestart = time();
                    $timeend = $timestart + $plugin_instance->enrolperiod;
                } else {
                    $timestart = 0;
                    $timeend = 0;
                }

                // Enrol user    die('s');
                $plugin->enrol_user($plugin_instance, $user->id, $plugin_instance->roleid, $timestart, $timeend);

                // Pass $view=true to filter hidden caps if the user cannot see them
                if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC', '', '', '', '', false, true)) {
                    $users = sort_by_roleassignment_authority($users, $context);
                    $teacher = array_shift($users);
                } else {
                    $teacher = false;
                }

                $mailstudents = $plugin->get_config('mailstudents');
                $mailteachers = $plugin->get_config('mailteachers');
                $mailadmins = $plugin->get_config('mailadmins');
                $shortname = format_string($course->shortname, true, array('context' => $context));

                if (!empty($mailstudents)) {
                    $a = new stdClass();
                    $a->coursename = format_string($course->fullname, true, array('context' => $coursecontext));
                    $a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id";
                    $eventdata = new \core\message\message();
                    $eventdata->courseid = $course->id;
                    $eventdata->modulename = 'moodle';
                    $eventdata->component = 'enrol_idpay';
                    $eventdata->name = 'idpay_enrolment';
                    $eventdata->userfrom = empty($teacher) ? core_user::get_noreply_user() : $teacher;
                    $eventdata->userto = $user;
                    $eventdata->subject = get_string("enrolmentnew", 'enrol', $shortname);
                    $eventdata->fullmessage = get_string('welcometocoursetext', '', $a);
                    $eventdata->fullmessageformat = FORMAT_PLAIN;
                    $eventdata->fullmessagehtml = '';
                    $eventdata->smallmessage = '';
                    message_send($eventdata);
                }

                if (!empty($mailteachers) && !empty($teacher)) {
                    $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
                    $a->user = fullname($user);

                    $eventdata = new \core\message\message();
                    $eventdata->courseid = $course->id;
                    $eventdata->modulename = 'moodle';
                    $eventdata->component = 'enrol_idpay';
                    $eventdata->name = 'idpay_enrolment';
                    $eventdata->userfrom = $user;
                    $eventdata->userto = $teacher;
                    $eventdata->subject = get_string("enrolmentnew", 'enrol', $shortname);
                    $eventdata->fullmessage = get_string('enrolmentnewuser', 'enrol', $a);
                    $eventdata->fullmessageformat = FORMAT_PLAIN;
                    $eventdata->fullmessagehtml = '';
                    $eventdata->smallmessage = '';
                    message_send($eventdata);
                }

                if (!empty($mailadmins)) {
                    $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
                    $a->user = fullname($user);
                    $admins = get_admins();

                    foreach ($admins as $admin) {
                        $eventdata = new \core\message\message();
                        $eventdata->courseid = $course->id;
                        $eventdata->modulename = 'moodle';
                        $eventdata->component = 'enrol_idpay';
                        $eventdata->name = 'idpay_enrolment';
                        $eventdata->userfrom = $user;
                        $eventdata->userto = $admin;
                        $eventdata->subject = get_string("enrolmentnew", 'enrol', $shortname);
                        $eventdata->fullmessage = get_string('enrolmentnewuser', 'enrol', $a);
                        $eventdata->fullmessageformat = FORMAT_PLAIN;
                        $eventdata->fullmessagehtml = '';
                        $eventdata->smallmessage = '';
                        message_send($eventdata);
                    }
                }

                $msgForSaveDataTDataBase = "کد پیگیری :  $verify_track_id " . "شماره کارت :  $card_no " . "شماره کارت رمزنگاری شده : $hashed_card_no ";
                $order->log = $msgForSaveDataTDataBase;
                $DB->update_record('enrol_idpay', $order);
                echo '<h3 style="text-align:center; color: green;">با تشکر از شما، پرداخت شما با موفقیت انجام شد و به  درس انتخاب شده افزوده شدید.</h3>';
                echo "<h3 style='text-align:center; color: green;'>کد پیگیری :  $verify_track_id  </h3>";
                echo '<div class="single_button" style="text-align:center;"><a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '"><button>ورود به درس خریداری شده</button></a></div>';
            }
        }

    }

} elseif ($status !== 10) {

    $msg = other_status_messages($status);
    $order->log = $msg;
    $msg = other_status_messages($status);
    $DB->update_record('enrol_idpay', $order);
    echo '<div style="color:red; font-family:tahoma; direction:rtl; text-align:right">' . $msg . '<br/></div>';
}

//----------------------------------------------------- HELPER FUNCTIONS --------------------------------------------------------------------------


function message_idpay_error_to_admin($subject, $data)
{
    echo $subject;
    $admin = get_admin();
    $site = get_site();
    $message = "$site->fullname:  Transaction failed.\n\n$subject\n\n";
    foreach ($data as $key => $value) {
        $message .= "$key => $value\n";
    }
    $eventdata = new \core\message\message();
    $eventdata->modulename = 'moodle';
    $eventdata->component = 'enrol_idpay';
    $eventdata->name = 'idpay_enrolment';
    $eventdata->userfrom = $admin;
    $eventdata->userto = $admin;
    $eventdata->subject = "idpay ERROR: " . $subject;
    $eventdata->fullmessage = $message;
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml = '';
    $eventdata->smallmessage = '';
    message_send($eventdata);

}

function other_status_messages($msgNumber = null)
{
    switch ($msgNumber) {
        case "1":
            $msg = "پرداخت انجام نشده است";
            break;
        case "2":
            $msg = "پرداخت ناموفق بوده است";
            break;
        case "3":
            $msg = "خطا رخ داده است";
            break;
        case "4":
            $msg = "بلوکه شده";
            break;
        case "5":
            $msg = "برگشت به پرداخت کننده";
            break;
        case "6":
            $msg = "برگشت خورده سیستمی";
            break;
        case "7":
            $msg = "انصراف از پرداخت";
            break;
        case "8":
            $msg = "به درگاه پرداخت منتقل شد";
            break;
        case "10":
            $msg = "در انتظار تایید پرداخت";
            break;
        case "100":
            $msg = "پرداخت تایید شده است";
            break;
        case "101":
            $msg = "پرداخت قبلا تایید شده است";
            break;
        case "200":
            $msg = "به دریافت کننده واریز شد";
            break;
        case "0":
            $msg = "سواستفاده از تراکنش قبلی";
            break;
        case "404":
            $msg = "واحد پول انتخاب شده پشتیبانی نمی شود.";
            $msgNumber = '404';
            break;
        case "405":
            $msg = "کاربر از انجام تراکنش منصرف شده است.";
            $msgNumber = '404';
            break;
        case "1000":
            $msg = "خطا دور از انتظار";
            $msgNumber = '404';
            break;
        case null:
            $msg = "خطا دور از انتظار";
            $msgNumber = '1000';
            break;
    }

    return $msg . ' -وضعیت: ' . "$msgNumber";

}

echo $OUTPUT->footer();
