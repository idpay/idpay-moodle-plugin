<div align="center">
<?php $plugininstance = new enrol_idpay_plugin(); ?>
<p><?php print_string("paymentrequired") ?></p>
<p><b><?php echo $instancename; ?></b></p>
<p><b><?php echo get_string("cost").": {$plugininstance->get_config('currency')} {$cost}"; ?></b></p>
<p><?php print_string("paymentinstant") ?></p>
<?php  $idpayurl = $CFG->wwwroot.'/enrol/idpay/request.php'; ?>

<form action="<?php echo $idpayurl ?>" method="post">
<input type="hidden" name="item_name" value="<?php p($coursefullname) ?>" />
<input type="hidden" name="item_number" value="<?php p($courseshortname) ?>" />
<input type="hidden" name="quantity" value="1" />
<input type="hidden" name="on0" value="<?php print_string('user') ?>" />
<input type="hidden" name="os0" value="<?php p($userfullname) ?>" />
<input type="hidden" name="course_id" value="<?php echo $course->id; ?>" />
<input type="hidden" name="instance_id" value="<?php echo $instance->id; ?>" />
<input type="hidden" name="amount" value="<?php p($cost) ?>" />
<input type="hidden" name="notify_url" value="<?php echo "$CFG->wwwroot/enrol/idpay/ipn.php"?>" />
<input type="hidden" name="return" value="<?php echo "$CFG->wwwroot/enrol/idpay/return.php?id=$course->id" ?>" />
<input type="hidden" name="cancel_return" value="<?php echo $CFG->wwwroot ?>" />
<input type="hidden" name="rm" value="2" />
<input type="hidden" name="cbt" value="<?php print_string("continuetocourse") ?>" />
<input type="hidden" name="first_name" value="<?php p($userfirstname) ?>" />
<input type="hidden" name="last_name" value="<?php p($userlastname) ?>" />
<input type="hidden" name="address" value="<?php p($useraddress) ?>" />
<input type="hidden" name="city" value="<?php p($usercity) ?>" />
<input type="hidden" name="email" value="<?php p($USER->email) ?>" />
<input type="hidden" name="country" value="<?php p($USER->country) ?>" />

<input type="submit" value="<?php print_string("sendpaymentbutton", "enrol_idpay") ?>" />

</form>

</div>