const frame = wp.media.view.MediaFrame.Post;
wp.media.view.MediaFrame.Post = frame.extend({
	initialize: function () {
		frame.prototype.initialize.apply(this, arguments);

		this.states.add([
			new wp.media.controller.State({
				id: 'gl_html_to_svg',
				title: 'HTML to SVG',
				search: false
			})
		]);

		this.on(
			'content:render:gl_html_to_svg',
			this.contentGlHtmlToSvg,
			this
		);
	},

	browseRouter: function (routerView) {
		routerView.set({
			upload: {
				text: wp.media.view.l10n.uploadFilesTitle,
				priority: 20
			},
			browse: {
				text: wp.media.view.l10n.mediaLibraryTitle,
				priority: 40
			},
			gl_html_to_svg: {
				text: 'HTML to SVG',
				priority: 60
			}
		});
	},

	contentGlHtmlToSvg: function () {
		let GlHtmlToSvgContent = wp.Backbone.View.extend({
			tagName: 'div',
			className: 'html-to-svg-tab',
			template: wp.template('gl_html_to_svg'),
			active: false,
			toolbar: null,
			frame: null,
			events: {
				'click .html-to-svg__actions__convert': 'convertToSvg'
			},
			convertToSvg: function(e) {
				e.preventDefault();

				const html = document.getElementById('html-to-svg__editor__textarea').value;
				const name = document.getElementById('html-to-svg__editor__name').value;
				const allowStyleScript = document.getElementById('html-to-svg__editor__allow_style_script').checked;

				const data = new FormData();
				data.append('action', 'convert_html_to_svg');
				data.append('nonce', wp_html_to_svg.nonce);
				data.append('html', html);
				data.append('allow_style_script', allowStyleScript);

				if (name.length !== 0) {
					data.append('name', name);
				}

				fetch(ajaxurl, {
					method: 'POST',
					body: data
				})
				.then(response => response.json())
				.then(response => {
					if (response.success) {
						// refresh media content
						if (wp.media.frame.content.get() !== null) {
							wp.media.frame.content.mode('browse').get().collection.props.set({
								ignore: (+new Date())
							});
							wp.media.frame.state().get('selection').add(wp.media.attachment(response.data.svg));
						} else {
							wp.media.frame.library.props.set({
								ignore: (+new Date())
							});
						}
					} else {
						alert(response.data);
					}
				})
				.catch(error => {
					console.error('Error:', error);
					alert('An error occurred while processing your request.');
				});
			}
		});

		let view = new GlHtmlToSvgContent();
		this.content.set(view);
	}
});