<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $wpdb,$table_prefix,$util;
$table_name = $table_prefix . 'WP_SEO_Redirection';

$redirect_from="";
$redirect_to="";
$redirect_type="";
$redirect_from_type="";
$redirect_from_folder_settings="";
$redirect_from_subfolders="";
$redirect_to_type="";
$redirect_to_folder_settings="";
$enabled="";

if($util->get('add')!='')
 echo "<h3>".__('Add New Custom Redirection','wsr')."<hr></h3>";
else if(intval($util->get('edit'))>0){
echo "<h3>".__('Update Existing Redirection','wsr')."<hr></h3>";
$item = $wpdb->get_row($wpdb->prepare(" select * from $table_name where ID=%d ",$util->get('edit')));
    if($wpdb->num_rows==0)
    {
       $utilpro->info_option_msg(__("Sorry, this redirect rule is not found, it may deleted by the user!",'wsr'));
       exit(0);
    }
$redirect_from=$item->redirect_from;
$redirect_to=$item->redirect_to;
$redirect_type=$item->redirect_type;

$redirect_from_type=$item->redirect_from_type;
$redirect_from_folder_settings=$item->redirect_from_folder_settings;
$redirect_from_subfolders=$item->redirect_from_subfolders;

$redirect_to_type=$item->redirect_to_type;
$redirect_to_folder_settings=$item->redirect_to_folder_settings;

$enabled=$item->enabled;
	
}
else
header("location: .");


if($util->get('page404')!='')
{
	$table_name_404 = $table_prefix . 'WP_SEO_404_links';
	$i404 = $wpdb->get_row(" select link from $table_name_404 where ID=". intval($util->get('page404')));
	if($i404->link!='')
	$redirect_from=$i404->link;
	else
	$_GET['page404']='';
}

?>
<form onsubmit="return check_from();" method="POST" id="myform" action="<?php echo $util->get_current_parameters(array('add','edit','page404'));?>">
<table class="cform" width="100%">
	<tr>
	<td class="label"><?php _e('Redirection status:','wsr') ?></td>
	<td>		<select size="1" name="enabled"  id="enabled">
			<option value="1"><?php _e('Enabled','wsr') ?></option>
			<option value="0"><?php _e('Disabled','wsr') ?></option>
		</select>
		
		</td>
	</tr>
	<tr>
		
		<td class="label"><?php _e('Redirect from:','wsr') ?></td>
		<td>
		<div id="rfrom_div">
		<select onchange="redirect_from_type_change()" size="1" name="redirect_from_type"  id="redirect_from_type">
			<option value="Page"><?php _e('Page','wsr') ?></option>
			<option value="Folder"><?php _e('Folder','wsr') ?></option>
			<option value="Regex"><?php _e('Regex','wsr') ?></option>
		</select>
                    <input onblur="check_redirect_from()"  type="text" id="redirect_from" style="height: 40px;" name="redirect_from" size="45" value="<?php echo $redirect_from?>">
	      <select onchange="redirect_to_folder_settings_change()" size="1" name="redirect_from_folder_settings"  id="redirect_from_folder_settings">
			<option value="1"><?php _e('Only the folder','wsr') ?></option>
			<option value="2"><?php _e("The folder and it's content",'wsr') ?></option>
			<option value="3"><?php _e("Only the folder's content",'wsr') ?></option>
		</select>
		<select size="1" name="redirect_from_subfolders"  id="redirect_from_subfolders" class="cmb2_select">
			<option value="0"><?php _e("Include sub-folders",'wsr') ?></option>
			<option value="1"><?php _e("Do not include sub-folders",'wsr') ?></option>
		</select>
		
		 *  <font style="font-size: 12px;color:#a7a7a7"><?php _e("(Starting with 'http://')",'wsr') ?></font>
		 </div>
		 <?php if($util->get('page404')!='') echo $redirect_from; ?>
		</td>
	</tr>
	<tr>
		<td class="label"><?php _e("Redirect to:",'wsr') ?></td>
		<td>
                    <select onchange="redirect_to_type_change()" size="1" class="cmb2_select" name="redirect_to_type"  id="redirect_to_type">
			<option value="Page"><?php _e("Page:",'wsr') ?></option>
			<option value="Folder"><?php _e("Folder",'wsr') ?></option>
		</select>
		
		<input onblur="check_redirect_to()" type="text" id="redirect_to" class="regular-text" style="height: 40px;" name="redirect_to" size="45" value="<?php echo $redirect_to?>">
		
		<select size="1" name="redirect_to_folder_settings"  id="redirect_to_folder_settings">
			<option value="1"><?php _e("Normal",'wsr') ?></option>
			<option value="2"><?php _e("Wild Card Redirect",'wsr') ?></option>
		</select>
		
		 *
		</td>
	</tr>
	<tr>
		<td class="label"><?php _e("Redirection type:",'wsr') ?></td>
		<td>
		<select size="1" name="redirect_type"  id="redirect_type">
		<option value="301"><?php _e("301 (SEO)",'wsr') ?></option>
		<option value="302"><?php _e("302",'wsr') ?></option>
		<option value="307"><?php _e("307",'wsr') ?></option>
		</select>
		
		<script>
		
		function redirect_from_type_change()
		{
			if (document.getElementById('redirect_from_type').value=='Folder')
			{
			document.getElementById('redirect_from_folder_settings').style.display = 'inline';
			document.getElementById('redirect_from').className = 'Folder_background_1';
			redirect_to_folder_settings_change();
			}
			else if (document.getElementById('redirect_from_type').value=='Page')
			{
			document.getElementById('redirect_from_folder_settings').style.display = 'none';
			document.getElementById('redirect_from').className = 'Page_background_1 regular-text';
			document.getElementById('redirect_from_subfolders').style.display = 'none';
			}
			else if (document.getElementById('redirect_from_type').value=='Regex')
			{
			document.getElementById('redirect_from_folder_settings').style.display = 'none';
			document.getElementById('redirect_from').className = 'Regex_background_1';
			document.getElementById('redirect_from_subfolders').style.display = 'none';
			}
			
			check_redirect_from();
		}
		
		
		function redirect_to_type_change()
		{
			if (document.getElementById('redirect_to_type').value=='Folder')
			{
			document.getElementById('redirect_to_folder_settings').style.display = 'inline';
			document.getElementById('redirect_to').className = 'Folder_background_1';
			}
			else if (document.getElementById('redirect_to_type').value=='Page')
			{
			document.getElementById('redirect_to_folder_settings').style.display = 'none';
			document.getElementById('redirect_to').className = 'Page_background_1 regular-text';
			}
			
			check_redirect_to();
		}	
		
		function redirect_to_folder_settings_change()
		{
			if (document.getElementById('redirect_from_folder_settings').value=='1')
			{	
			    document.getElementById('redirect_from_subfolders').style.display = 'none';
			}else
			{
			    document.getElementById('redirect_from_subfolders').style.display = 'inline';
			}
		}
		
		function check_redirect_from()
		{
		 	
		 	var rfrom = document.getElementById('redirect_from').value;
				
			if(rfrom!=''){
			var cr= rfrom .substring(rfrom.length -1);
				if(document.getElementById('redirect_from_type').value=='Folder' && cr!='/' ) 
				  document.getElementById('redirect_from').value = rfrom + '/';
			}
		}
		
		
		function check_redirect_to()
		{
		 	
		 	var rto = document.getElementById('redirect_to').value;
			
			
			if(rto!=''){
			var cr= rto.substring(rto.length -1);
				if(document.getElementById('redirect_to_type').value=='Folder' && cr!='/' ) 
				  document.getElementById('redirect_to').value = rto + '/';
			}
		}	
		
<?php 

if($redirect_type!='')
echo "document.getElementById('redirect_type').value='$redirect_type';"; 

if($redirect_from_type!='')
echo "document.getElementById('redirect_from_type').value='$redirect_from_type';";

if($redirect_from_folder_settings!='' && $redirect_from_type=='Folder' )
echo "document.getElementById('redirect_from_folder_settings').value='$redirect_from_folder_settings';";

if($redirect_from_subfolders!='' && $redirect_from_type=='Folder' )
echo "document.getElementById('redirect_from_subfolders').value='$redirect_from_subfolders';";

if($redirect_to_type!='')
echo "document.getElementById('redirect_to_type').value='$redirect_to_type';";

if($redirect_to_folder_settings!='' && $redirect_to_type=='Folder' )
echo "document.getElementById('redirect_to_folder_settings').value='$redirect_to_folder_settings';";

if($enabled!='')
echo "document.getElementById('enabled').value='$enabled';";



if($util->get('page404')!='')
echo "document.getElementById('rfrom_div').style.display = 'none';";



?>
				
		redirect_from_type_change();
		redirect_to_type_change();
		
		</script>
		
		
		</td>
	</tr>
</table>
<br/>

<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo $nonce = wp_create_nonce('seoredirection'); ?>" />
<input type="hidden" id="edit" name="edit" value="<?php echo intval($util->get('edit'))?>">

<?php echo "<b>Note</b>:".__(" When you move your site to another domain, the new domain name will be reflected to all links automatically.","wsr"); ?>
	<br/><br/>
<?php

if($util->get('add')!='')
echo '<input  class="button-primary" type="submit" value="'.__("Add New","wsr").'" name="add_new">';
else if($util->get('edit')!='')
   echo '<input  class="button-primary" type="submit" value="'.__("Update","wsr").'" name="edit_exist">';

?>
 <input onclick="window.location='<?php echo $util->get_current_parameters(array('add','edit'));?>';"  class="button-primary" type="button" value="<?php _e("Cancel",'wsr') ?>" name="cancel">
<br/></form>

<script>

function check_from()
{

var rfrom=document.getElementById('redirect_from').value;	
var rto=document.getElementById('redirect_to').value;

	if( rfrom=='')
	{
		alert("<?php _e("You must input the 'Redirect From' URL","wsr") ?>");
		document.getElementById('redirect_from').focus();
		return false;		
	}
	
	
	if( rto=='')
	{
            alert("<?php _e("You must input the 'Redirect To' URL","wsr") ?>");
		
		document.getElementById('redirect_to').focus();
		return false;		
	}

	<?php if($util->get('add')!='')
	{?>
	if(!(rto.indexOf('://')!=-1 || rto.substr(0,1)=='/'))
	{
            alert("<?php _e("Invalid redirect target URL!","wsr") ?>");
		document.getElementById('redirect_to').focus();
		return false;
	}
	<?php }?>
	
	return true;
}
</script>
