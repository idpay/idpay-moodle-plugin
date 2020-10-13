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
global $CFG, $_SESSION, $USER, $DB, $OUTPUT;
$systemcontext = context_system::instance();
$plugininstance = new enrol_idpay_plugin();
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/enrol/idpay/verify.php');

if (!is_siteadmin()) {
  header("HTTP/1.0 404 Not Found");
  die;
}

echo $OUTPUT->header();
$Authority = $_GET['Authority'];

$prifix = $DB->get_prefix();
$sql = "select * from mdl_enrol_idpay";
$db = $DB->get_records_sql($sql);

echo '<table class="flexible table table-striped table-hover reportlog generaltable generalbox table-sm">';
echo '<thead>';
echo '<tr>';
echo ' <th class="header c0">شماره سفارش</th>';
echo ' <th class="header c0">نام کاربر</th>';
echo ' <th class="header c0">نام درس</th>';
echo ' <th class="header c0">قیمت</th>';
echo ' <th class="header c0">توضیحات</th>';
echo '</tr>';
echo '</thead>';

echo "<tbody>";
foreach ($db as $value) {
    echo '<tr>';

    echo ' <td>' . $value->id . ' </td>';
    echo ' <td>' . $value->username . ' </td>';
    echo ' <td>' . $value->item_name . ' </td>';
    echo ' <td>' . $value->amount . ' </td>';
    echo ' <td>' . $value->log . ' </td>';

    echo '</tr>';
}
echo "</tbody>";

echo "</table>";
echo $OUTPUT->footer();
