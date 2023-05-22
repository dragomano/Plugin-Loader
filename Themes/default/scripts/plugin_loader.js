class PluginLoader {
	constructor() {
		this.workUrl = smf_scripturl + '?action=admin;area=plugins'
	}

	async toggle(target) {
		const plugin = target.dataset.id
		const status = target.dataset.status

		let response = await fetch(this.workUrl + ';toggle', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json; charset=utf-8'
			},
			body: JSON.stringify({
				plugin,
				status
			})
		})

		if (! response.ok)
			return console.error(response)

		if (status === 'on')
		{
			target.setAttribute('src', smf_images_url + '/admin/switch_off.png')
			target.setAttribute('alt', 'off')
			target.setAttribute('data-status', 'off')
			return
		}

		target.setAttribute('src', smf_images_url + '/admin/switch_on.png')
		target.setAttribute('alt', 'on')
		target.setAttribute('data-status', 'on')
	}
}
