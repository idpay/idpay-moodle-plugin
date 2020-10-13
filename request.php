<?php
/**
 * @package    enrol_idpay
 * @copyright  IDPay
 * @author     Mohammad Nabipour
 * @license    https://idpay.ir/
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once("lib.php");
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/filelib.php');
global $CFG, $_SESSION, $USER, $DB,$OUTPUT;

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$plugininstance = new enrol_idpay_plugin();

if ($plugininstance->get_config('currency') !== "IRR") {
  echo $OUTPUT->header();
  echo '<h3 dir="rtl" style="text-align:center; color: red;">' . 'واحد پولی پشتیبانی نمیشود.' . '</h3>';
  echo '<div class="single_button" style="text-align:center;"><a href="' . $CFG->wwwroot . '/enrol/index.php?id=' . $_POST['course_id'] . '"><button> بازگشت به صفحه قبلی  </button></a></div>';
  echo $OUTPUT->footer();
  exit;
}

if (!empty($_POST['multi'])) {
    $instance_array = unserialize($_POST['instances']);
    $ids_array = unserialize($_POST['ids']);
    $_SESSION['idlist'] = implode(',', $ids_array);
    $_SESSION['inslist'] = implode(',', $instance_array);
    $_SESSION['multi'] = $_POST['multi'];
} else {
    $_SESSION['courseid'] = $_POST['course_id'];
    $_SESSION['instanceid'] = $_POST['instance_id'];
}
$_SESSION['totalcost'] = $_POST['amount'];
$_SESSION['userid'] = $USER->id;

//make information
$api_key = $plugininstance->get_config('api_key');
$sandbox = $plugininstance->get_config('sandbox');
$amount = (float)$_POST['amount'];
$mail = $USER->email;
$callback = $CFG->wwwroot . "/enrol/idpay/verify.php?order_id=$order_id";
$description = 'پرداخت شهریه ' . $_POST['item_name'];
$phone = $USER->phone1;
$user_name = $USER->firstname . ' ' . $USER->lastname;
$item_name = $_POST['item_name'];
$course_id = (int)$_POST['course_id'];

$data = new stdClass();
$data->amount = $amount;
$data->courseid = $course_id;
$data->userid = $USER->id;
$data->username = $user_name;
$data->item_name = $item_name;
$data->receiver_email = $mail;
$data->instanceid = $_POST['instance_id'];
$data->log = "در حال انتقال به بانک";
$order_id = $DB->insert_record("enrol_idpay", $data);

$params = array(
    'order_id' => $order_id,
    'amount' => $amount,
    'name' => $user_name,
    'phone' => $Mobile,
    'mail' => $Email,
    'desc' => $Description,
    'callback' => $callback,
    'reseller' => null,
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.idpay.ir/v1.1/payment');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'X-API-KEY:' . $api_key,
    'X-SANDBOX:' . $sandbox
));

$result = curl_exec($ch);
$result = json_decode($result);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = $DB->get_record('enrol_idpay', ['id' => $order_id]);
$data->idpay_id = $result->id;

if ($http_status != 201 || empty($result) || empty($result->id) || empty($result->link)) {
    $msg = sprintf('خطا هنگام ایجاد تراکنش. وضعیت خطا: %s - کد خطا: %s - پیام خطا: %s', $http_status, $result->error_code, $result->error_message);
    $data->log = $msg;
    $DB->update_record('enrol_idpay', $data);
    echo $OUTPUT->header();
    echo '<h3 dir="rtl" style="text-align:center; color: red;">' . $msg . '</h3>';
    echo '<div class="single_button" style="text-align:center;"><a href="' . $CFG->wwwroot . '/enrol/index.php?id=' . $_POST['course_id'] . '"><button> بازگشت به صفحه قبلی  </button></a></div>';
    echo $OUTPUT->footer();
    exit;
} else {
    $DB->update_record('enrol_idpay', $data);
    Header("Location: $result->link");
    exit;
}
