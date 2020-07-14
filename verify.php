﻿<?php
/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Landing page of Organization Manager View (Approvels)
 *
 * @package    enrol
 * @subpackage idpay
 * @copyright  2018 SaeedSajadi <saeed.sajadi@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once("lib.php");
//require_once($CFG->libdir . '/eventslib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/filelib.php');
global $CFG, $_SESSION, $USER, $DB, $OUTPUT;
$systemcontext = context_system::instance();
$plugininstance = new enrol_idpay_plugin();
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/enrol/idpay/verify.php');
echo $OUTPUT->header();
$MerchantID = $plugininstance->get_config('merchant_id');
$testing = $plugininstance->get_config('checkproductionmode');
$Price = $_SESSION['totalcost'];
$Authority = $_GET['Authority'];


$data = new stdClass();
$plugin = enrol_get_plugin('idpay');
$today = date('Y-m-d');


$status = $_POST['status'];
$order_id = $_GET['order_id'];
$pid = $_POST['id'];


if ($status == '10') {
    $api_key = $plugininstance->get_config('api_key');
    $sandbox = $plugininstance->get_config('sand_box');
    $params = array('id' => $pid, 'order_id' => $order_id);


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

            $msgForSaveDataTDataBase = $this->otherStatusMessages(1000) . "کد پیگیری :  $verify_track_id " . "شماره کارت :  $card_no " . "شماره کارت رمزنگاری شده : $hashed_card_no ";


        } else {


            if ($verify_order_id !== $order_id /*or $data->reason_code !== $result->id*/) {
                $msgForSaveDataTDataBase = $this->otherStatusMessages(0) . "کد پیگیری :  $verify_track_id " . "شماره کارت :  $card_no " . "شماره کارت رمزنگاری شده : $hashed_card_no ";
                die($msg);

            } else {

                $Refnumber = $res->RefID; //Transaction number
                $Resnumber = $res->RefID;//Your Order ID
                $Status = $res->Status;
                $PayPrice = ($Price / 10);


                $data = $DB->get_record('enrol_idpay', ['id' => $order_id]);


                $coursename = $DB->get_field('course', 'fullname', ['id' => $_SESSION['courseid']]);
                $data->userid = $_SESSION['userid'];
                $data->courseid = $_SESSION['courseid'];
                $data->instanceid = $_SESSION['instanceid'];
                $coursecost = $DB->get_record('enrol', ['enrol' => 'idpay', 'courseid' => $data->courseid]);
                $time = strtotime($today);
                $paidprice = $coursecost->cost;
                $data->amount = $paidprice;
                $data->refnumber = $Refnumber;
                $data->orderid = $Resnumber;
                $data->payment_status = $Status;
                $data->timeupdated = time();
                $data->item_name = $coursename;
                $data->receiver_email = $USER->email;
                $data->receiver_id = $_SESSION['userid'];




                if (!$user = $DB->get_record("user", ["id" => $data->userid])) {
                    message_idpay_error_to_admin("Not a valid user id", $data);
                    die;
                }
                if (!$course = $DB->get_record("course", ["id" => $data->courseid])) {
                    message_idpay_error_to_admin("Not a valid course id", $data);
                    die;
                }
                if (!$context = context_course::instance($course->id, IGNORE_MISSING)) {
                    message_idpay_error_to_admin("Not a valid context id", $data);
                    die;
                }
                if (!$plugin_instance = $DB->get_record("enrol", ["id" => $data->instanceid, "status" => 0])) {
                    message_idpay_error_to_admin("Not a valid instance id", $data);
                    die;
                }

                $coursecontext = context_course::instance($course->id, IGNORE_MISSING);

                // Check that amount paid is the correct amount
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

                $DB->update_record('enrol_idpay', $data);

                if ($plugin_instance->enrolperiod) {
                    $timestart = time();
                    $timeend = $timestart + $plugin_instance->enrolperiod;
                } else {
                    $timestart = 0;
                    $timeend = 0;
                }

                // Enrol user
                $plugin->enrol_user($plugin_instance, $user->id, $plugin_instance->roleid, $timestart, $timeend);

                // Pass $view=true to filter hidden caps if the user cannot see them
                if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC',
                    '', '', '', '', false, true)) {
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

                echo '<h3 style="text-align:center; color: green;">با تشکر از شما، پرداخت شما با موفقیت انجام شد و به  درس انتخاب شده افزوده شدید.</h3>';
                echo '<div class="single_button" style="text-align:center;"><a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '"><button>ورود به درس خریداری شده</button></a></div>';

            }
        }

    }


} elseif ($status !== 10) {

    $msg = other_status_messages($status);
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
        case "3":
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
