(function( $ ) {
	'use strict';

	$(function () {
		let embed_code = "<iframe width=\"200\" height=\"120\" src=\"https://www.youtube.com/embed/REPLACE?autoplay=1\" frameborder=\"0\" allow=\"accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>";

		$('[id*="get_suggestions_"]').click(function (e) {
			e.preventDefault();

			let data = {
				'action': 'ajax_get_suggestions',
				'item_id': $(this).val()
			};

			$.post(ajaxurl, data, function(response) {

				if (IsJsonString(response)) {
					let suggestions =  JSON.parse(response);
					if (suggestions.status === "OK") {
						let count = 0;

						let block_html = "<div>Найдено SUGGESTIONS_COUNT похожих видео:</div><div class='suggestions-block'>";
						for(let i in suggestions) {
							if (i === "oldVideoId" || i === "postId" || i === "status") {continue;} // skip
							if (i > 9) {continue;} // show 10 results max
							let item_html = '';
							item_html = "<div class='suggestion-item' " +
								"data-post-id='"+suggestions.postId+"' " +
								"data-item-id='"+data.item_id+"' " +
								"data-video-id='"+suggestions[i].videoId+"' " +
								"data-old-video-id='"+suggestions.oldVideoId+"'>" +
								"<div class='media-container' title='"+suggestions[i].title+"'>" +
								"<img class='item-video' src='"+suggestions[i].preview+"'>" +
								"<span class=\"dashicons dashicons-video-alt3 play-overlay\"></span>" +
								"</div>" +
								"<div class='video-info'>" +
								"<div>Название: "+suggestions[i].title+"</div>" +
								"<div>Описание: "+suggestions[i].description+"</div>" +
								"<div class='suggestion-score'>Сходство: "+suggestions[i].score+"%</div>" +
								"<button class='btn-replace'>Заменить на это видео</button>"+
								"</div>" +
								"</div>";
							//
							block_html += item_html;
							count++;
						}
						block_html += "</div>";

						block_html = block_html.replace("SUGGESTIONS_COUNT", count);
						$('#suggestions_item_'+data.item_id).html(block_html);

						$('.suggestion-item').click(function (e) {
							let videoId = $(this).attr('data-video-id');
							let oldVideoId = 	$(this).attr('data-old-video-id');
							let postId = $(this).attr('data-post-id');
							let itemId = 	$(this).attr('data-item-id');

							if ($(e.target).hasClass("play-overlay") || $(e.target).hasClass("item-video")) {
								$(this).find('.media-container').html(embed_code.replace('REPLACE', videoId))
							}

							if ($(e.target).hasClass("btn-replace")) {
								replace_video(videoId, oldVideoId, postId, itemId);
							}

						});

					} else {
						$('#suggestions_item_'+data.item_id).html("ERROR: " + suggestions.msg);
					}

				} else {
					$('#suggestions_item_'+data.item_id).html("Server returned bad data: " + response);
				}

				$('#get_suggestions_'+data.item_id).toggleClass("ytlf-hide");
				$('#hide_suggestions_'+data.item_id).toggleClass("ytlf-hide");
				$('#hide_suggestions_'+data.item_id).click(function () {
					$('#suggestions_item_'+data.item_id).html('');
					$(this).toggleClass("ytlf-hide");
					$('#get_suggestions_'+data.item_id).toggleClass("ytlf-hide");
				})
			});

		});

		function replace_video(videoId, oldVideoId, postId, itemId) {
			let data = {
				'action': 'ajax_replace_link',
				'videoId': 	videoId	,
				'oldVideoId': oldVideoId,
				'postId': 		postId,
				'itemId': 		itemId
			};

			$.post(ajaxurl, data, function(response) {
				if(IsJsonString(response)) {
					response = JSON.parse(response);
					if (response.status === "OK") {
						// remove
						$("#data_item_id_"+itemId).remove();
						$("#sugg_item_id_"+itemId).remove();
					} else {
						console.log("ERROR: " + response.msg);
					}
				} else {
					console.log(response);
				}

			});

		}

		function IsJsonString(str) {
			try {
				var json = JSON.parse(str);
				return (typeof json === 'object');
			} catch (e) {
				return false;
			}
		}

	});

})( jQuery );
