<?php

function template_main()
{
	global $txt, $context, $settings;

	echo '
	<div class="cat_bar">
		<h3 class="catbg">', $txt['pl_plugin_manager'], '</h3>
	</div>
	<div class="information">', $txt['pl_plugin_manager_info'], '</div>
	<div class="plugins" x-data>';

	foreach ($context['pl_plugins'] as $id => $plugin)
	{
		if (empty($plugin))
		{
			echo '
		<div class="noticebox">', sprintf($txt['pl_loading_error'], $id), '</div>';

			continue;
		}

		$toggle = in_array($id, $context['pl_enabled_plugins']) ? 'on' : 'off';

		echo '
		<div class="windowbg">
			<div class="sub_bar">
				<h4 class="subbg">
					', empty($plugin['website']) ? '' : '<a class="bbc_link" href="', $plugin['website'], '">', $plugin['name'], empty($plugin['website']) ? '' : '</a>', ' <span class="amt">', $plugin['version'], '</span>
					<span class="floatright">
						', empty($plugin['license']['@attributes']['url']) ? '' : '<a class="bbc_link" href="', $plugin['license']['@attributes']['url'], '">', $plugin['license']['value'], empty($plugin['license']['@attributes']['url']) ? '' : '</a>', '
					</span>
				</h4>
			</div>
			<div class="floatleft">
				<div>', $plugin['description'][$context['user']['language']] ?? $plugin['description']['english'], '</div>
				<div class="smalltext">
					<span class="author_info">
						', $txt['author'], ': ',
						empty($plugin['author']['@attributes']['url']) ? '' : '<a class="bbc_link" href="', $plugin['author']['@attributes']['url'], '">', $plugin['author']['value'], empty($plugin['author']['@attributes']['url']) ? '' : '</a>', '
					</span>
				</div>
			</div>
			<div class="floatright">
				<img @click.self="plugin.toggle($event.target)" data-id="', $id, '" data-status="', $toggle, '" src="', $settings['default_images_url'], '/admin/switch_', $toggle, '.png" alt="', $toggle, '">
			</div>
		</div>';
	}

	echo '
	</div>

	<script defer>
		const plugin = new PluginLoader();
	</script>';
}
