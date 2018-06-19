// JavaScript Document


/*html埋め込み*/
$(function(){
    $("#update-embed").load("update.html");
  })
  
$(function(){
    $("#award-embed").load("http://www.graphis.ne.jp//files/htmlpage/award-vote.html");
  })

$(function(){
    $("#award-last-embedded").load("http://www.graphis.ne.jp//files/htmlpage/award-vote.html");
  })

/*html埋め込み*/


/*リンク画像切り替え*/
$(function(){
     $('a img').hover(function(){
        $(this).attr('src', $(this).attr('src').replace('_off', '_on'));
          }, function(){
             if (!$(this).hasClass('currentPage')) {
             $(this).attr('src', $(this).attr('src').replace('_on', '_off'));
        }
   });
});
/*リンク画像切り替え*/



/*ページ内リンクスクロール*/
$(function(){
   // #で始まるアンカーをクリックした場合に処理
   $('a[href^=#]').click(function() {
      // スクロールの速度
      var speed = 600; // ミリ秒
      // アンカーの値取得
      var href= $(this).attr("href");
      // 移動先を取得
      var target = $(href == "#" || href == "" ? 'html' : href);
      // 移動先を数値で取得
      var position = target.offset().top;
      // スムーススクロール
      $('body,html').animate({scrollTop:position}, speed, 'swing');
      return false;
   });
});
/*ページ内リンクスクロール*/


/*ページトップへスクロール*/
$(document).ready(function() {
    var pagetop = $('.scroll-top');
    $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
            pagetop.fadeIn();
        } else {
            pagetop.fadeOut();
        }
    });
    pagetop.click(function () {
        $('body, html').animate({ scrollTop: 0 }, 500);
        return false;
    });
});
/*ページトップへスクロール*/



/*日本語英語切り替え*/
$(function() {
    $('#switch-en').click(function(){
        $('.jp-on').css('display','none');
        $('.jp-txt').css('display','none');
		$('.en-on').css('display','block');
		$('.en-txt').css('display','inline');
		$(this).css({'display':'none'});
		$('#switch-jp').css('display','inline-block');
    });

    $('#switch-jp').click(function(){
		$('.en-on').css('display','none');
		$('.en-txt').css('display','none');
        $('.jp-on').css('display','block');
        $('.jp-txt').css('display','block');
		$(this).css({'display':'none'});
		$('#switch-en').css('display','inline-block');
    });
});


$(function() {
    $('.switch-en-text').click(function(){
        $('.jp-on').css('display','none');
        $('.jp-txt').css('display','none');
		$('.en-on').css('display','block');
		$('.en-txt').css('display','inline');
		$('#switch-jp').css('display','inline-block');
    });

    $('.switch-jp-text').click(function(){
		$('.en-on').css('display','none');
		$('.en-txt').css('display','none');
        $('.jp-on').css('display','block');
        $('.jp-txt').css('display','block');
		$('#switch-en').css('display','inline-block');
    });
});

/*日本語英語切り替え*/



/*htmlページを閉じる*/
$(function() {
  $('.close-button').on('click', function() {
    window.close();
    return false;
  });
});
/*htmlページを閉じる*/



/*アコーディオン*/
$(function() {
    function accordion() {
        $(this).toggleClass("active").next().slideToggle(300);
    }
    $(".accordion .toggle").click(accordion);
});
/*アコーディオン*/



/*ヘッダーナビアコーディオン*/
$(function() {
  var $subNav = $('.acd-models-box');
  $('.acd-models').hover(
    function(){
      // stop関数を追加
      $subNav.stop().slideDown();
    },
    function(){
      // stop関数を追加
      $subNav.stop().slideUp();
    }
  );
});/*ヘッダーナビアコーディオン*/



/*タブ切り替え*/
$(function() {
    //タブクリック時の処理
    $('.category-box .category-list li').click(function() {
        //.index()を使いクリックされたタブの順番を変数indexに代入する
        var index = $('.category-box .category-list li').index(this);
        //指定した全コンテンツを非表示にする
        $('.category-list-item >li').css('display','none');
        //クリックされたタブと同じ順番のコンテンツのみを表示させる
        $('.category-list-item >li').eq(index).css('display','block');
    });
});
/*タブ切り替え*/

/*タブ切り替え
$(function() {
    //タブクリック時の処理
    $('.category-box .category-list li').click(function() {
        //.index()を使いクリックされたタブの順番を変数indexに代入する
        var index = $('.category-box .category-list li').index(this);
        //クリックされたタブと同じ順番のコンテンツのみを表示させる
        $('.category-list-item >li').eq(index).toggleClass('on').slideToggle(300);
    });
});*/
/*タブ切り替え*/


/*タブ切り替え
$(function() {
    //タブクリック時の処理
    $('.category-box .category-list .category-list-01').click(function() {
        //クリックされたタブと同じ順番のコンテンツのみを表示させる
        $('.category-list-item .category-list-item-01').css('display','block').slideToggle(300);

    });
});*/
/*タブ切り替え*/



