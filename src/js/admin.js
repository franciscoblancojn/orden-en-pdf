/* Orden en PDF — admin. ES5 + jQuery. */
(function ($) {
	'use strict';

	$(function () {
		var frame = null;
		var $logoId = $('#oep-logo-id');
		var $preview = $('#oep-logo-preview');
		var $remove = $('#oep-logo-remove');

		$('.oep-color').wpColorPicker();

		$('#oep-logo-select').on('click', function (event) {
			event.preventDefault();

			if (!frame) {
				frame = wp.media({
					title: OEP_ADMIN.choose,
					button: { text: OEP_ADMIN.use },
					multiple: false,
					library: { type: 'image' }
				});

				frame.on('select', function () {
					var attachment = frame.state().get('selection').first().toJSON();
					var url = attachment.url;

					if (attachment.sizes && attachment.sizes.medium) {
						url = attachment.sizes.medium.url;
					}

					$logoId.val(attachment.id);
					$preview.html('<img class="oep-logo__img" src="' + url + '" alt="">');
					$remove.show();
				});
			}

			frame.open();
		});

		$remove.on('click', function (event) {
			event.preventDefault();
			$logoId.val(0);
			$preview.html('<span class="oep-muted">' + OEP_ADMIN.siteLogo + '</span>');
			$remove.hide();
		});

		$('.nav-tab').on('click', function () {
			$('#oep-tab').val($(this).data('tab'));
		});
	});
})(jQuery);
