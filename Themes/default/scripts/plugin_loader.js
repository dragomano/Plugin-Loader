class PluginLoader {
  constructor() {
    this.workUrl = smf_scripturl + '?action=admin;area=plugins';
  }

  async save(e) {
    e.preventDefault();

    const form = e.target.parentElement;
    const formData = new FormData(form),
      checkboxes = form.querySelectorAll('input[type=checkbox]');

    checkboxes.forEach(function (val) {
      formData.append(val.getAttribute('name'), val.matches(':checked'));
    });

    await fetch(this.workUrl + ';save', {
      method: 'POST',
      body: formData,
    });
  }

  async toggle(e) {
    const target = e.target;
    const plugin = target.dataset.id;
    const status = target.dataset.status;
    const removeButton = target.nextElementSibling;

    const response = await fetch(this.workUrl + ';toggle', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json; charset=utf-8',
      },
      body: JSON.stringify({
        plugin,
        status,
      }),
    });

    if (!response.ok) return console.error(response);

    if (status === 'on') {
      target.setAttribute('src', smf_images_url + '/admin/switch_off.png');
      target.setAttribute('alt', 'off');
      target.setAttribute('data-status', 'off');
      removeButton.style.display = 'inline-block';
      return;
    }

    target.setAttribute('src', smf_images_url + '/admin/switch_on.png');
    target.setAttribute('alt', 'on');
    target.setAttribute('data-status', 'on');
    removeButton.style.display = 'none';
  }

  async remove(e) {
    if (!confirm(smf_you_sure)) return false;

    const target = e.target;
    const plugin = target.previousElementSibling.dataset.id;

    if (!plugin) return false;

    const response = await fetch(this.workUrl + ';remove', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json; charset=utf-8',
      },
      body: JSON.stringify({
        plugin,
      }),
    });

    response.ok ? target.closest('div.windowbg').remove() : console.error(response);
  }
}
