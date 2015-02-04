<div class="group-settings site">
	<p>
		Configure the way the app sends email.
	</p>

	<hr />

		<ul class="tabs">
			<?php $active = $this->input->post('update') == 'general' || ! $this->input->post() ? 'active' : ''?>
			<li class="tab <?=$active?>">
				<a href="#" data-tab="tab-general">General</a>
			</li>
		</ul>

		<section class="tabs pages">

			<?php $display = $this->input->post('update') == 'general' || ! $this->input->post() ? 'active' : ''?>
			<div id="tab-general" class="tab page <?=$display?>">
				<?=form_open(null, 'style="margin-bottom:0;"')?>
				<?=form_hidden('update', 'general')?>
				<fieldset id="email-settings-general">
					<legend>Sender Details</legend>
					<p>
						These fields allow you to set the sender details of email sent from the site.
					</p>
					<?php

						$_field					= array();
						$_field['key']			= 'from_name';
						$_field['label']		= 'From Name';
						$_field['default']		= app_setting($_field['key'], 'email') ? app_setting($_field['key'], 'email') : APP_NAME;
						$_field['placeholder']	= 'The name of the sender which recipients should see.';

						echo form_field($_field);

						// --------------------------------------------------------------------------

						$url		= parse_url(site_url());
						$default	= 'nobody@' . $url['host'];

						$_field					= array();
						$_field['key']			= 'from_email';
						$_field['label']		= 'From Email';
						$_field['default']		= app_setting($_field['key'], 'email') ? app_setting($_field['key'], 'email') : $default;
						$_field['placeholder']	= 'The email address of the sender which recipients should see.';
						$_field['info']			= '<strong>Note:</strong> If sending using SMTP to send email ensure this email is a valid account on the mail server. If it\'s not valid, some services will junk the email.';

						echo form_field($_field);

					?>
				</fieldset>
				<p style="margin-top:1em;margin-bottom:0;">
					<?=form_submit('submit', lang('action_save_changes'), 'class="awesome" style="margin-bottom:0;"')?>
				</p>
				<?=form_close()?>
			</div>

		</section>
</div>