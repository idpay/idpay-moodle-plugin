<?php
/**
 * @package    enrol_idpay
 * @copyright  IDPay
 * @author     Mohammad Nabipour
 * @license    https://idpay.ir/
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once("lib.php");
global $CFG,$PAGE;
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/filelib.php');

$systemContext = context_system::instance();
$PAGE->set_context($systemContext);
$idpay = new enrol_idpay_plugin();
$idpay->doPayment($_POST);

