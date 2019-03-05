var $ = jQuery;
var save_method; //for save method string
var host=window.location.hostname;
var fullpath=window.location.pathname;
var fullparam=window.location.search.split('&');

var firstparam=fullparam[0];
var secoundparam=fullparam[1];

jQuery(document).ready(function () {

    $(document).on('click', '.SwalBtn1', function() {
        swal.clickConfirm();
    });
    $(document).on('click', '.SwalBtn2', function() {
        window.open(
	  "http://www.clogica.com/product/seo-redirection-premium-wordpress-plugin",
	  '_blank'
	);
        //window.location.href="http://www.clogica.com/product/seo-redirection-premium-wordpress-plugin";
        swal.clickConfirm();
    });
    $(document).on('click', '.SwalBtn3', function() {
        sessionStorage.setItem("bool", "1");
        swal.clickConfirm();
    });
  
if(sessionStorage && (firstparam=="?page=seo-redirection.php" && !secoundparam) || secoundparam=="tab=cutom")
{

    if (seoredirection.msg.length > 0 && !sessionStorage.getItem("bool")==true)
    {
        swal({
        title:"Oops! Don't lose your web traffic!<br><br>"+
                seoredirection.msg,
        type: "warning",
        animation: "slide-from-top",
        html: '<center><button type="button" role="button"  class="SwalBtn1 customSwalBtn1">' + 'Close' + '</button>' +
            '<button type="button" role="button" class="SwalBtn2 customSwalBtn2">' + 'Fix now' + '</button>'+
            '<button type="button" role="button" class="SwalBtn3 customSwalBtn3">' + "Don't show again" + '</button></center>',
        showCancelButton: false,
        showConfirmButton: false
    });
  
//        swal({
//             title: seoredirection.msg,
//            type: "warning",
//	    showCancelButton: true,
//            confirmButtonColor: '#DD6B55',
//	    cancelButtonColor :'#5bc0de',
//            confirmButtonText: "Don't Show Again",
//            cancelButtonText: "    Ok    ",
//            closeOnConfirm: true,
//            closeOnCancel: true,
//            animation: "slide-from-top",
//            html:true,
//        },
//            function (isConfirm) {
//                if (isConfirm) {
//                        sessionStorage.setItem("bool", "1");
//                }      
//               }
//        
//        );
    }
}
    
//    swal("Warning!", seoredirection.msg, "warning");

    $('div.edit_template').click(function (event) {
        $('.loading').show();
        var ele = $(this);
        $(this).attr('disabled', true);
        save_method = 'Update';
        event.preventDefault();
        var parent = $(this).parent();
        var editID = parent.attr("href");
        $.ajax({
            url: seoredirection.ajax_url,
            type: "POST",
            data: {
                'action': 'customUpdateRec',
                'ID': editID,
            },
            success: function (data) {
                var record = $.parseJSON(data);
                if (record.status == 'suucess')
                {
                    clr();
                    $('#edit').val(editID);
                    $('#add_new').val("");
                    $('#edit_exist').val("1");
                    $('#myModal').modal('show'); // show bootstrap modal
                    $('.loading').hide();
                    $('.modal-title').text('Edit Custom Redirection'); // Set Title to Bootstrap modal title
                    $('#btnSave').val(save_method); //change button text
                    $('#btnSave').attr('disabled', false); //set button enable
                    var newdata = record.rec;
                    $('#redirect_from_type').val(newdata.redirect_from_type);
                    $('#redirect_from').val(newdata.redirect_from);
                    $('#redirect_from_folder_settings').val(newdata.redirect_from_folder_settings);
                    $('#redirect_from_subfolders').val(newdata.redirect_from_subfolders);
                    $('#redirect_to_type').val(newdata.redirect_to_type);
                    $('#redirect_to').val(newdata.redirect_to);
                    $('#redirect_to_folder_settings').val(newdata.redirect_to_folder_settings);
                    $('#redirect_type').val(newdata.redirect_type);
                }
            }
        });
    });

    $("input").change(function () {
        $(this).parent().parent().removeClass('has-error');
        $(this).next().empty();
    });
    $("textarea").change(function () {
        $(this).parent().parent().removeClass('has-error');
        $(this).next().empty();
    });
    $("select").change(function () {
        $(this).parent().parent().removeClass('has-error');
        $(this).next().empty();
    });
});
function clr() {
    $('.help-block').hide();
    $('#redirect_from_type').show();
    $('#redirect_from_folder_settings').hide();
    $('#redirect_from_subfolders').hide();
    $('#redirect_from').attr('class', 'Page_background_1 regular-text');
    $('#redirect_to_type').show();
    $('#redirect_to').attr('class', 'Page_background_1 regular-text');
    $('#redirect_to_folder_settings').hide();




}
function add_rec() {
    clr();

    $('.loading').show();
    save_method = 'Add New';
    $(this).attr('disabled', true);
    $('#add_new').val("1");
    $('#edit').val("");
    $('#edit_exist').val("");
    $('#myform')[0].reset(); // reset form on modals
    $('#myModal').modal('show'); // show bootstrap modal
    $('.loading').hide();
    $('.modal-title').text('Add Custom Redirection'); // Set Title to Bootstrap modal title
    $('#btnSave').val(save_method); //change button text
    $('#btnSave').attr('disabled', false); //set button enable
}
function save_function()
{
    $('.loading').show();
    $('#btnSave').val('Saving...'); //change button text
    $('#btnSave').attr('disabled', true); //set button disable
    var formData = $('#myform').serialize();
    msg = $("#msg_response");
    $.ajax({
        url: seoredirection.ajax_url,
        type: "POST",
        data: {
            'action': 'customAddUpdate',
            'formData': formData,
        },
        success: function (data) {
            
            data = $.parseJSON(data);
            if (data.status == 'error') //if success close modal and reload ajax table
            {
                $('.loading').hide();
                msg.html('<span style="color:red">' + data.msg + '</span>')

            }
            else if (data.status == 'success')
            {
                $('#myModal').modal('hide'); 
                $('.loading').hide();
                //msg.html('<span style="color:green">' + data.msg + '</span>')
                console.log(data.msg);
                swal({
                 title: data.msg,
                type: "success",
                confirmButtonColor: '#5cb85c',
                confirmButtonText: "ok",
                closeOnConfirm: true,
                animation: "slide-from-top",      
                }).then(function() {
			  $('.loading').show();
			window.location.href = data.url;
		});
            }
            if (data.inputerror) {
                $('.loading').hide();
                for (var i = 0; i < data.inputerror.length; i++) {
                    $('.help-block').show();
                    $('[name="' + data.inputerror[i] + '"]').next().text(data.error_string[i]);
                }
            }
            $('#btnSave').val(save_method); //change button text
            $('#btnSave').attr('disabled', false); //set button enable
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.log(jqXHR, textStatus, errorThrown);
            $('.loading').hide();
            $('#btnSave').val(save_method); //change button text
            $('#btnSave').attr('disabled', false); //set button enable
        }
    });
}

function redirect_from_type_change()
{
    if (document.getElementById('redirect_from_type').value == 'Folder')
    {
        document.getElementById('redirect_from_folder_settings').style.display = 'inline';
        document.getElementById('redirect_from').className = 'Folder_background_1';
        redirect_to_folder_settings_change();
    }
    else if (document.getElementById('redirect_from_type').value == 'Page')
    {
        document.getElementById('redirect_from_folder_settings').style.display = 'none';
        document.getElementById('redirect_from').className = 'Page_background_1 regular-text';
        document.getElementById('redirect_from_subfolders').style.display = 'none';
    }
    else if (document.getElementById('redirect_from_type').value == 'Regex')
    {
        document.getElementById('redirect_from_folder_settings').style.display = 'none';
        document.getElementById('redirect_from').className = 'Regex_background_1';
        document.getElementById('redirect_from_subfolders').style.display = 'none';
    }

    check_redirect_from();
}


function redirect_to_type_change()
{
    if (document.getElementById('redirect_to_type').value == 'Folder')
    {
        document.getElementById('redirect_to_folder_settings').style.display = 'inline';
        document.getElementById('redirect_to').className = 'Folder_background_1';
    }
    else if (document.getElementById('redirect_to_type').value == 'Page')
    {
        document.getElementById('redirect_to_folder_settings').style.display = 'none';
        document.getElementById('redirect_to').className = 'Page_background_1 regular-text';
    }

    check_redirect_to();
}

function redirect_to_folder_settings_change()
{

    if (document.getElementById('redirect_from_folder_settings').value == '1')
    {
        document.getElementById('redirect_from_subfolders').style.display = 'none';
    } else
    {
        document.getElementById('redirect_from_subfolders').style.display = 'block';
    }
}

function check_redirect_from()
{

    var rfrom = document.getElementById('redirect_from').value;

    if (rfrom != '') {
        var cr = rfrom.substring(rfrom.length - 1);
        if (document.getElementById('redirect_from_type').value == 'Folder' && cr != '/')
            document.getElementById('redirect_from').value = rfrom + '/';
    }
}


function check_redirect_to()
{

    var rto = document.getElementById('redirect_to').value;


    if (rto != '') {
        var cr = rto.substring(rto.length - 1);
        if (document.getElementById('redirect_to_type').value == 'Folder' && cr != '/')
            document.getElementById('redirect_to').value = rto + '/';
    }
}

if((firstparam=="?page=seo-redirection.php" && !secoundparam) || secoundparam=="tab=cutom")
{
    redirect_from_type_change();
    redirect_to_type_change();
}
