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
global $_SESSION, $USER, $DB, $OUTPUT, $PAGE;

$systemContext = context_system::instance();
$PAGE->set_context($systemContext);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/enrol/idpay/verify.php');

if (!is_siteadmin()) {
    header("HTTP/1.0 404 Not Found");
    die;
}
echo $OUTPUT->header();
$rows = $DB->get_records_sql("select * from {$DB->get_prefix()}enrol_idpay");

echo '<table class="flexible table table-striped table-hover reportlog generaltable generalbox table-sm" style=" direction: rtl;">';
echo '<thead>';
echo '<tr>';
echo ' <th class="header c0">شماره سفارش</th>';
echo ' <th class="header c0">کاربر</th>';
echo ' <th class="header c0">درس</th>';
echo ' <th class="header c0">قیمت</th>';
echo ' <th class="header c0">وضعیت پرداخت</th>';
echo ' <th class="header c0">توضیح وضعیت</th>';
echo ' <th class="header c0">نتیجه نهایی</th>';
echo '</tr>';
echo '</thead>';
echo "<tbody>";
foreach ($rows as $row) {
    echo '<tr>';
    echo ' <td>' . $row->id . ' </td>';
    echo ' <td>' . $row->username . ' </td>';
    echo ' <td>' . $row->item_name . ' </td>';
    echo ' <td>' . $row->amount . ' </td>';
    echo ' <td>' . $row->payment_status . ' </td>';
    echo ' <td>' . $row->pending_reason . ' </td>';
    echo ' <td>' . $row->log . ' </td>';
    echo '</tr>';
}
echo "</tbody>";
echo "</table>";
echo $OUTPUT->footer();
