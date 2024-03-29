<?php
/**
 * @package    enrol_idpay
 * @copyright  IDPay
 * @author     Mohammad Nabipour
 * @license    https://idpay.ir/
 */

global $CFG;
const CLI_SCRIPT = true;
require(__DIR__.'/../../../config.php');
require_once("{$CFG->libdir}/clilib.php");
list($options, $unrecognized) = cli_get_params(array('verbose'=>false, 'help'=>false), array('v'=>'verbose', 'h'=>'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
$help =
"Process idpay expiration sync
Options:
-v, --verbose         Print verbose progress information
-h, --help            Print out this help
Example:
\$ sudo -u www-data /usr/bin/php enrol/idpay/cli/sync.php";
echo $help;
die;
}

if (!enrol_is_enabled('idpay')) {
    echo('enrol_idpay plugin is disabled'."\n");
    exit(2);
}

if (empty($options['verbose'])) { $trace = new null_progress_trace(); }
else {  $trace = new text_progress_trace(); }

/** @var $plugin enrol_idpay_plugin */
$plugin = enrol_get_plugin('idpay');
$result = $plugin->sync($trace);
exit($result);
