<?php
/**
 * @package    enrol_idpay
 * @copyright  IDPay
 * @author     Mohammad Nabipour
 * @license    https://idpay.ir/
 */

global $CFG,$PAGE,$OUTPUT;
require_once(dirname(__FILE__) . '/../../config.php');
require_once("lib.php");
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/filelib.php');

$request =  $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$idpay = enrol_get_plugin('idpay');
$systemContext = context_system::instance();
$PAGE->set_context($systemContext);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/enrol/idpay/verify.php');

echo $OUTPUT->header();
echo $idpay->doCallback($request) ;
echo $OUTPUT->footer();



