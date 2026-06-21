(function() {
	tinymce.PluginManager.add( 'btn_trigger', function( editor ){

		editor.addButton( 'btn_trigger', {
			title: 'HashBar Shortcodes',
			type: 'menubutton',
			text: 'HashBar Shortcodes',
			menu: [{
			  text: '[hashbar_btn]',
			  onclick: function(){
			  	editor.windowManager.open({
			  		title: 'HashBar Button Shortcode',
			  		body: [
			  		{
			  			type: 'textbox',
			  			name: 'btn_text',
			  			label: 'Button Text'
			  		},
			  		{
			  			type: 'textbox',
			  			name: 'btn_link',
			  			label: 'Button Link'
			  		},
			  		{
			  			type: 'textbox',
			  			name: 'btn_bg_color',
			  			label: 'Button background color'
			  		},
			  		{
			  			type: 'textbox',
			  			name: 'btn_text_color',
			  			label: 'Button text color'
			  		},
			  		{
			  			type: 'listbox',
			  			name: 'btn_style',
			  			label: 'Button Style',
			  			values: [
			  				{text: 'Style 1', value: 'style_1'}, 
			  				{text: 'Style 2', value: 'style_2'},
			  			]
			  		},
			  		{
			  			type: 'checkbox',
			  			name: 'btn_target',
			  			label: 'Open in new window'
			  		},
			  		{
			  			type: 'checkbox',
			  			name: 'add_nofollow',
			  			label: 'Add Nofollow'
			  		}],
			  		onsubmit: function(e){
			  			var tag = 'hashbar_btn';
			  			var btn_text = e.data.btn_text !== '' ? ' btn_text="'+ e.data.btn_text +'"' : '';
			  			var btn_link = e.data.btn_link !== '' ? ' btn_link="'+ e.data.btn_link +'"' : '';
			  			var btn_bg_color = e.data.btn_bg_color !== '' ? ' btn_bg_color="'+ e.data.btn_bg_color +'"' : '';
			  			var btn_text_color = e.data.btn_text_color !== '' ? ' btn_text_color="'+ e.data.btn_text_color +'"' : '';
			  			var btn_target = e.data.btn_target == true ? ' target="_blank"' : '';
			  			var add_nofollow = e.data.add_nofollow == true ? ' add_nofollow="yes"' : '';
			  			var btn_style = ' btn_style="'+ e.data.btn_style +'"';

			  			editor.insertContent('['+ tag + btn_text + btn_link + btn_target + add_nofollow + btn_bg_color + btn_text_color + btn_style +']')
			  		}
			  	})
			  }
			},
			{
			  text: '[hashbar_socials]',
			  onclick: function(){
			  	editor.windowManager.open({
			  		title: 'Social Bookmark Shortcode ',
			  		body: [
			  		{
			  			type: 'textbox',
			  			name: 'facebook',
			  			label: 'Facebook'
			  		},
			  		{
			  			type: 'textbox',
			  			name: 'twitter',
			  			label: 'Twitter'
			  		},
			  		{
			  			type: 'textbox',
			  			name: 'google_plus',
			  			label: 'Google Plus'
			  		},
			  		{
			  			type: 'textbox',
			  			name: 'instagram',
			  			label: 'Instagram'
			  		},
			  		{
			  			type: 'textbox',
			  			name: 'pinterest',
			  			label: 'Pinterest'
			  		},
			  		{
			  			type: 'textbox',
			  			name: 'youtube',
			  			label: 'Youtube'
			  		},
			  		{
			  			type: 'textbox',
			  			name: 'vimeo',
			  			label: 'Vimeo'
			  		},
			  		{
			  			type: 'checkbox',
			  			name: 'target',
			  			label: 'Open links in new tab'
			  		},
			  		],
			  		onsubmit: function(e){
			  			var tag = 'hashbar_socials';

			  			var facebook = e.data.facebook !== '' ? ' facebook="'+ e.data.facebook +'"' : '';
			  			var twitter = e.data.twitter !== '' ? ' twitter="'+ e.data.twitter +'"' : '';
			  			var google_plus = e.data.google_plus !== '' ? ' google_plus="'+ e.data.google_plus +'"' : '';
			  			var instagram = e.data.instagram !== '' ? ' instagram="'+ e.data.instagram +'"' : '';
			  			var pinterest = e.data.pinterest !== '' ? ' pinterest="'+ e.data.pinterest +'"' : '';
			  			var youtube = e.data.youtube !== '' ? ' youtube="'+ e.data.youtube +'"' : '';
			  			var vimeo = e.data.vimeo !== '' ? ' vimeo="'+ e.data.vimeo +'"' : '';

			  			var target = e.data.target == true ? ' target="_blank"' : '';

			  			editor.insertContent('['+ tag + target + facebook + twitter + google_plus +  instagram +  pinterest +  youtube + vimeo +']')
			  		}
			  	})
			  }
			},
			{
			  text: '[embed][/embed]',
			  onclick: function(){
			  	editor.windowManager.open({
			  		title: 'WordPress embed Shortcode',
			  		body: [
			  		{
			  			type: 'textbox',
			  			name: 'embed_url',
			  			label: 'Embed Url'
			  		},
			  		{
			  			type: 'textbox',
			  			name: 'width',
			  			label: 'Width'
			  		},
			  		{
			  			type: 'textbox',
			  			name: 'height',
			  			label: 'Height'
			  		},
			  		],
			  		onsubmit: function(e){
			  			var tag = 'embed';

			  			var width = e.data.width !== '' ? ' width="'+ e.data.width +'"' : '';
			  			var height = e.data.height !== '' ? ' height="'+ e.data.height +'"' : '';

			  			var embed_url = e.data.embed_url !== '' ? e.data.embed_url : '';

			  			editor.insertContent('['+ tag + width + height +']' + embed_url +'[/embed]' )
			  		}
			  	})
			  }
			},
			{
			  text: '[hashbar_fb_likebox]',
			  onclick: function(){
			  	editor.windowManager.open({
			  		title: 'Facebook Likebox Shortcode',
			  		body: [
			  		{
			  			type: 'textbox',
			  			name: 'page_name',
			  			label: 'Page Username'
			  		},
			  		{
			  			type: 'textbox',
			  			name: 'width',
			  			label: 'Width'
			  		},
			  		{
			  			type: 'textbox',
			  			name: 'height',
			  			label: 'Height'
			  		},
			  		],
			  		onsubmit: function(e){
			  			var tag = 'hashbar_fb_likebox';

			  			var page_name = e.data.page_name !== '' ? ' page_name="'+ e.data.page_name +'"' : '';
			  			var width = e.data.width !== '' ? ' width="'+ e.data.width +'"' : '';
			  			var height = e.data.height !== '' ? ' height="'+ e.data.height +'"' : '';

			  			editor.insertContent('['+ tag + page_name + width + height +']')
			  		}
			  	})
			  }
			}
			],
		});
	});
})();
