// JavaScript Document


$(function(){
var menu = $('#slide-menu'), // スライドインするメニューを指定
	menuBtn = $('#btn-menu'), // トリガーとなるボタンを指定
	body = $('.slide'), 	
    menuWidth = menu.outerWidth();	            

    menuBtn.on('click', function(){
	body.toggleClass('open');
        if(body.hasClass('open')){
			body.animate({'left' : menuWidth }, 300);			
			menu.animate({'left' : 0 }, 300);					
		} else {
			menu.animate({'left' : -menuWidth }, 300);
			body.animate({'left' : 0 }, 300);			
		}		     
    });
});    

