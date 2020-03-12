<?php

script('files_frommail', 'admin');
style('files_frommail', 'admin');

/** @var \OCP\IL10N $l */
?>

<form id="files_frommail" class="section">
	<h2><?php p($l->t('Drop mailbox')); ?></h2>
	<p class="settings-hint"><?php p(
			$l->t(
				'Files FromMail allows you to configure drop mailbox to store mails and attachments into your Files.'
			)
		); ?></p>

	<div id="frommail_mailbox">
		<h3><?php p($l->t('Current mailbox')); ?></h3>
		<div id="frommail_list">
		</div>
	</div>

	<h3><?php p($l->t('Configure a new mailbox')); ?></h3>

	<div class="frommail-input">
		<input type="text" id="frommail_address" name="frommail_address"
			   placeholder="<?php p($l->t('mail address')); ?>">
		<input type="text" id="frommail_password" name="frommain_password"
			   placeholder="<?php p($l->t('password (optional)')); ?>">

		<a id="frommail_create" class="button"><span><?php p($l->t('Create')); ?></span></a>
	</div>

</form>
