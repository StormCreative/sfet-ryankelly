$(document).ready(function(){
    
    $('#signup-box').hide();
    
    $('#signup-btn').click(function(){
        $('#signup-box').show();
        $('#main-head').addClass('popup-background');
        $('#signup-box').addClass('position-form');
    });
    
    $('#popup-close').click(function(){
        $('#signup-box').hide();
        $('#main-head').removeClass('popup-background');
    });

});