
function insertParam(key, value){
    key = encodeURI(key); 
    value = encodeURI(value);
    var kvp = document.location.search.substr(1).split('&');
    var i=kvp.length; var x; while(i--){
        x = kvp[i].split('=');
        if (x[0]==key){
            x[1] = value;
            kvp[i] = x.join('=');
            break;
        }
    }
    if(i<0){ kvp[kvp.length] = [key,value].join('='); }
    document.location.search = kvp.join('&'); 
}

jQuery(document).ready(function($){
	
    $('.ilgen-toggle').click(function(e){
        e.preventDefault();
        var box = $('#' + $(this).attr('data'));
        
        if($(this).hasClass('closed')){
            $(this).removeClass('closed');
            box.fadeIn();
        }else{
            $(this).addClass('closed');
            box.fadeOut();
        }
    });

    $('.check_all').change(function() {
        var checkboxes = $(this).closest('form').find(':checkbox');
        if($(this).is(':checked')) {
            checkboxes.prop('checked', true);
        } else {
            checkboxes.prop('checked', false);
        }
    });
    
    $('.ilgen-watch-input').change(function(e){
        e.preventDefault();
        $(this).closest('tr').find(':checkbox').prop('checked', true);
        $('select[name=bulk_action] option[value=update]').attr("selected", "selected");
        $('.ilgen-watch-notification').css('display','inline-block');
    });
	
    $('input[name="ilgen_bulk"]').on('click', function(e){
        e.preventDefault();
        var form = $(this).closest('form');
        var loader = form.find('.ilgen-loader');
        var act = form.find('select[name="bulk_action"] option:selected').val();

        form.find('input[name="ids[]"]:checked').each(function(){
            var obj = $(this);
            var id = obj.val();

            loader.css('display', 'inline-block');
            form.find('.ilgen-watch-notification').css('display', 'none');

            jQuery.ajax({
                url : ilgenBulkUrl,
                type : 'post',
                data : {
                    action   	: 'bulk_actions',
                    subAction 	: act,
                    _wpnonce 	: ilgenBulkNonce,
                    postdata	: {
                        'id' 	: id, 
                        'target': form.find('input[name="targets['+id+']"]').val(),
                        'limit' : form.find('input[name="limits['+id+']"]').val(),
                        'tag'	: form.find('select[name="tags['+id+']"] option:selected').val()
                    },  
                }, success : function(res){
                    switch(act){
                        case 'delete': if(res > 0) obj.parent('td').parent('tr').remove(); break;
                        case 'recount': form.find('.td_recount_'+id).html(res).css('font-weight', 'bold'); break;
                        case 'linking': case 'unlinking': form.find('.td_linked_'+id).html(res).css('font-weight', 'bold'); break;
                    }
                }
            }).always(function(){
                obj.prop('checked', false);
                loader.css('display', 'none');
            });
        });
        return false;
    });
    
    $('input[name="ilgen_grabb"]').on('click', function(e){
		
        var form = $(this).closest('form');
        var items = form.find('input[name="ids[]"]:checked');
        var loader = form.find('.ilgen-loader').css('display', 'inline-block');
        var ind = 0;
               
        items.each(function(){
            var obj = $(this);
            ind ++;
            setTimeout(function(){
                loader.css('display', 'inline-block');
                jQuery.ajax({
                    url : ilgenBulkUrl,
                    type : 'post',
                    data : {
                        action    : 'bulk_actions',
                        subAction : 'grab',
                        _wpnonce  : ilgenBulkNonce,
                        postdata  : obj.attr('data'),  
                    }, success : function(res){
                        if(res > 0) obj.parent('td').parent('tr').remove();
                    }
                }).always(function(){
                    obj.prop('checked', false);
                    loader.css('display', 'none');
                });
            }, ind * 3000);
        });
        return false;
    });
});