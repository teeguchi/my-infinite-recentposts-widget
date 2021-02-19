/** mircp-widget.js
 */

jQuery(function($) {
	$('.mircp-btn a').click(function(e) {
		e.preventDefault();
		const mircpPosts = $(this).parent().siblings('.mircp-posts'); // ul要素
		const mircpValAry = mircpPosts.data('mircp-val').split('_'); // ページデータの取得
		const number = parseInt(mircpValAry[0], 10); // 1ページに含める投稿数
		const pages = parseInt(mircpValAry[1], 10); // ページの合計数
		const showdate = parseInt(mircpValAry[2], 10); // 日付の表示
		let count = parseInt(mircpValAry[3], 10); // ページ番号
		count++;
		const mircpLoading = $(this).siblings('.mircp-loading').children('.spinner'); // ローディング要素
		const mircpLast = $(this).parent().siblings('.mircp-last'); // 最後の表示
		
		/* Ajax処理 */
		$.ajax({
			url: mircp_widget_data.api,
			type: 'POST',
			data: {
				'action': 'view_my_recentposts',
				'number': number,
				'count': count,
				'showdate': showdate
			},
			beforeSend: function(){
				mircpLoading.addClass('active'); // ローディングアニメーション
			}
		})
		.done((data) => {
			mircpLoading.removeClass('active');
			if (data) {
				mircpPosts.data('mircp-val', number + '_' + pages + '_' + showdate + '_' + count); // ページデータを変更
				mircpPosts.append(data); // 投稿記事データを追加
				if (pages == count) {
					// 最後のページの処理
					mircpLast.addClass('active');
					$(this).css('display', 'none');
				}
			}
		})
		.fail((data) => {
			// 読み込みエラー
			console.error('Failure');
		});
	});
});
