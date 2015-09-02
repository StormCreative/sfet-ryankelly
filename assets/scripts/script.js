$(document).ready(function(){
    
    var signup_box = $('#signup-box');
    var main_head = $('#main-head');
    var last_form_field = $('#message-box');
    
    signup_box.hide();
    
    $('#signup-btn').click(function(){
        signup_box.show();
        main_head.addClass('popup-background');
        signup_box.addClass('position-form');
    });
    
    $('#popup-close').click(function(){
        signup_box.hide();
        main_head.removeClass('popup-background');
    });

    
    var userResult = window.location.pathname;
    
    if(userResult == "/success"){
        signup_box.show();
        main_head.addClass('popup-background');
        signup_box.addClass('position-form');
        last_form_field.html('<span class="success-span">Success! You have been signed up</span>');
    } else if(userResult == "/failure"){
        signup_box.show();
        main_head.addClass('popup-background');
        signup_box.addClass('position-form');
        last_form_field.html('<span class="failure-span">Failure! You have not been signed up</span>');
    }
});