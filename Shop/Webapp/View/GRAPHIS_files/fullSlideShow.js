// JavaScript Document

$(function(){
    setElm = $('.fullSlideShow');
    fadeSpeed = 1000;
    switchDelay = 5000;
    easing = 'linear';
    sideNavi = 'off'; // 'on' or 'off'
    sideHide = 'hide'; // 'hide' or 'show'
    naviOpc = 0.5;
    pnOpacity = 0.5;
 
    ua = navigator.userAgent;
 
    $(window).load(function(){
        setElm.each(function(){
            var targetObj = $(this),
            findUl = targetObj.find('ul'),
            findLi = findUl.find('li'),
            findLiCount = findLi.length,
            findLiFirst = findUl.find('li:first');
 
            findLi.each(function(i){
                $(this).attr('class','viewList' + (i + 1).toString());
            });
 
            findLi.css({display:'block',opacity:'0',zIndex:'99'});
            findLiFirst.addClass('mainActive').css({zIndex:'100'}).stop().animate({opacity:'1'},fadeSpeed);
 
            if(findLiCount > 1){
                // ページネーションSET
                var pagination = $('<div class="pagiNation"></div>');
                targetObj.append(pagination);
 
                findLi.each(function(i){
                    pagination.append('<a href="javascript:void(0);" class="pn'+(i+1)+'"></a>');
                });
 
                var pnPoint = pagination.children('a'),
                pnFirst = pagination.children('a:first'),
                pnLast = pagination.children('a:last'),
                pnCount = pagination.children('a').length;
                 
                pnFirst.addClass('pnActive');
 
                if(ua.search(/iPhone/) != -1 || ua.search(/iPad/) != -1 || ua.search(/iPod/) != -1 || ua.search(/Android/) != -1){
                    pnPoint.css({opacity:(pnOpacity)});
                } else {
                    pnPoint.css({opacity:(pnOpacity)}).hover(function(){
                        $(this).stop().animate({opacity:'1'},300);
                    }, function(){
                        $(this).stop().animate({opacity:(pnOpacity)},300);
                    });
                }
 
                pnPoint.click(function(){
                    clearInterval(setAutoTimer);
                    var setNum = pnPoint.index(this),
                    showCont = setNum+1;
                    findUl.find('.viewList' + (showCont)).siblings().removeClass('mainActive').stop().animate({opacity:'0'},fadeSpeed,function(){$(this).css({zIndex:'99'});});
                    findUl.find('.viewList' + (showCont)).addClass('mainActive').stop().animate({opacity:'1'},fadeSpeed,function(){$(this).css({zIndex:'100'});});
                    pnPoint.removeClass('pnActive');
                    $(this).addClass('pnActive');
                    fadeTimer();
                });
 
 
                // ボタン移動動作
                function switchNext(){
                    var setActive = pagination.find('.pnActive');
                    setActive.each(function(){
                        var pnLengh = pnPoint.length,
                        pnIndex = pnPoint.index(this),
                        pnCount = pnIndex+1;
 
                        if(pnLengh == pnCount){
                            pnFirst.click();
                        } else {
                            $(this).next('a').click();
                        }
                    });
                }
                function switchPrev(){
                    var setActive = pagination.find('.pnActive');
                    setActive.each(function(){
                        var pnLengh = pnPoint.length,
                        pnIndex = pnPoint.index(this),
                        pnCount = pnIndex+1;
 
                        if(1 == pnCount){
                            pnLast.click();
                        } else {
                            $(this).prev('a').click();
                        }
                    });
                }
 
                function fadeTimer(){
                    setAutoTimer = setInterval(function(){
                        switchNext();
                    },switchDelay);
                }
                fadeTimer();
 
                // サイドナビボタン（有り無し）
                if(sideNavi == 'on'){
                    targetObj.append('<a href="javascript:void(0);" class="btnPrev"></a><a href="javascript:void(0);" class="btnNext"></a>');
                    var btnPrev = targetObj.find('.btnPrev'),btnNext = targetObj.find('.btnNext'),btnPrevNext = targetObj.find('.btnPrev,.btnNext');
 
                    if(ua.search(/iPhone/) != -1 || ua.search(/iPad/) != -1 || ua.search(/iPod/) != -1 || ua.search(/Android/) != -1){
                        btnPrevNext.css({opacity:naviOpc});
                    } else {
                        btnPrevNext.css({opacity:naviOpc}).hover(function(){
                            $(this).stop().animate({opacity:'1'},200);
                        },function(){
                            $(this).stop().animate({opacity:naviOpc},200);
                        });
                    }
 
                    if(sideHide == 'hide'){
                        if(ua.search(/iPhone/) != -1 || ua.search(/iPad/) != -1 || ua.search(/iPod/) != -1 || ua.search(/Android/) != -1){
                            btnPrevNext.css({visibility:'visible'});
                        } else {
                            btnPrevNext.css({visibility:'hidden'});
                            targetObj.hover(function(){
                                btnPrevNext.css({visibility:'visible'});
                            },function(){
                                btnPrevNext.css({visibility:'hidden'});
                            });
                        }
                    }
 
                    btnPrev.click(function(){switchPrev();});
                    btnNext.click(function(){switchNext();});
                }
            }
 
            // メイン画像をベースにエリアの幅と高さを設定
            var setLiImg = findLi.find('img'),
            baseWidth = setLiImg.width(),
            baseHeight = setLiImg.height(),
            selfWH = baseWidth / baseHeight;
 
            // フルスクリーン（レスポンシブ）動作メイン
            function setArea(){
                var wdWidth = $(window).width(),
                wdHeight = $(window).height(),
                rwdHeight = wdWidth / selfWH;
 
                if(rwdHeight < $(window).height()){
                    rwdHeight = $(window).height();
                    wdWidth = rwdHeight * selfWH;
                }
                targetObj.css({height:wdHeight});
                findUl.css({marginTop:-rwdHeight/2,marginLeft:-wdWidth/2,width:wdWidth,height:rwdHeight});
                findLi.css({height:rwdHeight});
            }
            $(window).resize(function(){setArea();}).resize();
 
            $('body').css({visibility:'visible'});
        });
    });
});
