<?php

function template_main()
{
	global $txt, $context, $settings;

	echo '
	<div class="cat_bar">
		<h3 class="catbg">', $txt['pl_title'], '</h3>
	</div>
	<div class="information">', sprintf($txt['pl_info'], PLUGINS_DIR), '</div>
	<div class="plugins">';

	foreach ($context['pl_plugins'] as $id => $plugin) {
		if (empty($plugin)) {
			echo '
		<div class="noticebox">', sprintf($txt['pl_loading_error'], $id), '</div>';

			continue;
		}

		$toggle = in_array($id, $context['pl_enabled_plugins']) ? 'on' : 'off';

		echo '
		<div class="windowbg">
			<div class="sub_bar">
				<h4 class="subbg">
					', empty($plugin['website']) ? '' : ('<a class="bbc_link" href="' . $plugin['website'] . '">'), $plugin['name'], empty($plugin['website']) ? '' : '</a>', ' <span class="amt">', $plugin['version'], '</span>
					<span class="floatright">
						', empty($plugin['license']['@attributes']['url']) ? '' : ('<a class="bbc_link" href="' . $plugin['license']['@attributes']['url'] . '">'), $plugin['license']['value'], empty($plugin['license']['@attributes']['url']) ? '' : '</a>', '
					</span>
				</h4>
			</div>
			<div class="floatleft">
				<div>', $plugin['description'][$context['user']['language']] ?? $plugin['description']['english'], '</div>';

		if (!empty($plugin['settings'])) {
			echo '
				<br class="clear">
				<div class="title_bar"><h5 class="titlebg">', $txt['settings'], '</h5></div>
				<form id="', $id, '_form_', $context['session_id'], '" class="noticebox settings" accept-charset="', $context['character_set'], '" method="post">
					<input type="hidden" name="plugin_name" value="', $id, '">
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">';

			foreach ($plugin['settings'] as $conf_id => $setting) {
				if (empty($setting['type']))
					continue;

				echo '
					<div class="windowbg">';

				if ($setting['type'] === 'text') {
					echo '
						<label for="', $conf_id, '">', $setting['name'], '</label>
						<input id="', $conf_id, '" name="', $conf_id, '" type="text" value="', $setting['value'], '">';
				}

				if ($setting['type'] === 'large_text') {
					echo '
						<label for="', $conf_id, '">', $setting['name'], '</label><br>
						<textarea id="', $conf_id, '" name="', $conf_id, '">', $setting['value'], '</textarea>';
				}

				if ($setting['type'] === 'check') {
					echo '
						<label for="', $conf_id, '">', $setting['name'], '</label>
						<input id="', $conf_id, '" name="', $conf_id, '" type="checkbox"', $setting['value'] ? ' checked' : '', '>';
				}

				if ($setting['type'] === 'int') {
					echo '
						<label for="', $conf_id, '">', $setting['name'], '</label>
						<input id="', $conf_id, '" name="', $conf_id, '" type="number" value="', $setting['value'], '">';
				}

				echo '
					</div>';
			}

			echo '
					<button form="', $id, '_form_', $context['session_id'], '" class="button plugin_save" type="submit">', $txt['save'], '</button>
					<br class="clear">
				</form>';
		}

		echo '
				<div class="smalltext">
					<span class="author_info">
						', $txt['author'], ': ',
						empty($plugin['author']['@attributes']['url']) ? '' : ('<a class="bbc_link" href="' . $plugin['author']['@attributes']['url'] . '">'), $plugin['author']['value'], empty($plugin['author']['@attributes']['url']) ? '' : '</a>', '
					</span>
				</div>
			</div>
			<div class="floatright">
				<img class="plugin_toggle" data-id="', $id, '" data-status="', $toggle, '" src="', $settings['default_images_url'], '/admin/switch_', $toggle, '.png" alt="', $toggle, '">
				<span class="main_icons plugin_remove" title="', $txt['remove'], '"', $toggle === 'on' ? ' style="display: none"' : '', '></span>
			</div>
		</div>';
	}

	echo '
	</div>

	<script>
		const plugin = new PluginLoader();

		const toggleButtons = document.querySelectorAll(".plugin_toggle");
		toggleButtons.forEach(function (button) {
			button.addEventListener("click", (e) => plugin.toggle(e));
			const removeButton = button.nextElementSibling;
			removeButton.addEventListener("click", (e) => plugin.remove(e));
		});

		const saveButtons = document.querySelectorAll(".plugin_save");
		saveButtons.forEach(function (button) {
			button.addEventListener("click", (e) => plugin.save(e));
		});
	</script>';
}

function template_upload()
{
	global $context, $txt, $scripturl;

	if (!empty($context['upload_error']))
		echo '
	<div class="errorbox">', $context['upload_error'], '</div>';

	if (!empty($context['upload_success']))
		echo '
	<div class="infobox">', $context['upload_success'], '</div>';

	echo '
	<div id="admin_form_wrapper">
		<div class="cat_bar">
			<h3 class="catbg">', $txt['package_upload_title'], '</h3>
		</div>
		<div class="windowbg">
			<form action="', $scripturl, '?action=admin;area=plugins;get;sa=upload" method="post" accept-charset="', $context['character_set'], '" enctype="multipart/form-data">
				<dl class="settings">
					<dt>
						<strong>', $txt['package_upload_select'], ':</strong>
					</dt>
					<dd>
						<input type="hidden" name="MAX_FILE_SIZE" value="', $context['max_file_size'], '">
						<input type="file" name="package" accept="application/zip">
					</dd>
				</dl>
				<input type="submit" value="', $txt['upload'], '" class="button">
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
			</form>
		</div>
	</div>';
}
