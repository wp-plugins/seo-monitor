jQuery(document).ready(function($) {

	//single update
	$('.seomonitor_update_row').click( function() {

		var id 			= this.getAttribute('id');
		var	kw_class 	= 'seo_monitor_kw-';
		var keywordID	= id.substring(kw_class.length);

		$('.seo_monitor_spinner-'+ keywordID ).show();
		$('.seo_monitor_updated-'+ keywordID ).hide();

		$.ajax({
	        url: ajaxurl,
	        type: 'POST',

	        data: {
	            action: 'seo_monitor_update_keyword_rank',
	            itm: keywordID,
	        },

	        success:function(data){

				$('.seo_monitor_spinner-'+ keywordID ).hide();
				$('.seo_monitor_updated-'+ keywordID ).show();
				$('.seo_monitor_updated-'+ keywordID ).removeClass('dashicons-clock').addClass('dashicons-yes');

				var res = jQuery.parseJSON( data );

				var rank_class;

				if( res['status'] == 'success' ) {

					var tr_element = $('#' + id).closest('tr');

					if( res['previous'] != 0 && res['rank'] > res['previous'] ) {
						rank_class = ' rank_decrease';
					} else if( res['rank'] < res['previous'] || ( res['rank'] > 0 && res['previous'] == 0 )	) {
						rank_class = ' rank_increase';
					} else {
						rank_class = ' rank_same';
					}

					tr_element.find('td').each (function() {
						var td_class = this.className;
						td_class = td_class.split(' ');

						var obj_name = td_class[0];

						switch( obj_name ) {
							case 'rank':
								this.innerHTML = '<span class="' + rank_class + '">' + res[obj_name] + '</span>';
								break;
							case 'ranking_url':
								if( res[obj_name] !== undefined ) {
									this.innerHTML = '<a href="' + res[obj_name] + '">' + res[obj_name] + '</a>';
								}
								break;
							default:
								if( res[obj_name] !== undefined ) {
									this.innerHTML = res[obj_name];
								}
						}
					});
				}
			}
		});
	});

	//update All (first set event handler!!!)
	function updateAllKeywords() {

		var time 		= 0;

		// loop throug all not updated keywords
		$('.seomonitor_update_row').find('div:not(.seo_monitor_updated)').each(function() {
			time += 2500;
			var element = this;
			setTimeout(function() {
				element.click();
			}, time);
		})
	}

	updateAllKeywords();
});