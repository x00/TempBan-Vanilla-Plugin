jQuery(document).ready(function($){
    $('#Form_Banned').livequery(function(){
        $.getJSON(gdn.url('/dashboard/user/tempbanstatus/'+parseInt($('#Form_UserID').val())),{}, function(data){
            if(data['TempBanExpires']){
                var expire = $('<div class="TempBanExpire">'+data['TempBanExpires']+'</div>');
                $('#Form_Banned').parent('label').after(expire);
            }
        });
    });
});
