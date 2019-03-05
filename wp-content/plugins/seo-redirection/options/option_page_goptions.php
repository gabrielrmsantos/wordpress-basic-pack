<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $util;

$nonce="";
if(isset($_REQUEST['_wpnonce']))
$nonce = $_REQUEST['_wpnonce'];

if(isset($_POST) && wp_verify_nonce( $nonce, 'seoredirection' ) ){
if($util->post('reset_all_options')!='')
{
	c_init_my_options();
	$util->success_option_msg(__('All Options Restored to Defaults','wsr'));

}else if($util->post('Save_general_options')!='')
{
	c_save_redirection_general_options();
	$util->success_option_msg(__('General Options Saved!','wsr'));

}else if($util->post('save_history_options')!='')
{
	c_save_redirection_history_options();
	$util->success_option_msg(__('History Options Saved!','wsr'));
}
else if($util->post('clear_history')!='')
{
	c_clear_redirection_history();
	$util->success_option_msg(__('History Cleared!','wsr'));
}
else if($util->post('save_404_options')!='')
{
	c_save_404_redirection_options();
	$util->success_option_msg(__('404 Redirection Options Saved!','wsr'));
}
else if($util->post('clear_all_404')!='')
{
	c_clear_all_404();
	$util->success_option_msg(__('All Discovered 404 Pages Cleared!','wsr'));
}
else if($util->post('save_data_options')!='')
{
	c_save_keep_data();
	$util->success_option_msg(__('Data Options Saved!','wsr'));
}
else if($util->post('optimize_tables')!='')
{
	c_optimize_tables();
	$util->success_option_msg(__('Data Tables Optimized!','wsr'));
}
else if($util->post('save_all_options'))
{
	c_save_redirection_general_options();
	c_save_redirection_history_options();
	c_save_404_redirection_options();
	c_save_keep_data();

	$util->success_option_msg(__('All options saved!','wsr'));

}






if($util->there_is_cache()!='')

$util->info_option_msg(__("You have a cache plugin installed",'wsr')." <b>'" . $util->there_is_cache() . "'</b>, ".__("you have to clear cache after any changes to get the changes reflected immediately! ",'wsr'));
}

$options= $util->get_my_options();

?>
<form method="POST">
<h3><?php _e("General Options","wsr") ?><hr></h3>

<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo $nonce = wp_create_nonce('seoredirection'); ?>" />

<table class="cform" align="center" width="100%">
	<tr><td>
	<?php _e("Plugin Status:","wsr") ?>
	<?php
		$drop = new dropdown('plugin_status');
		$drop->add(__('Enabled','wsr'),'1');
		$drop->add(__('Disabled','wsr'),'0');
		$drop->add(__('Disabled for admin only','wsr'),'2');
		$drop->dropdown_print();
		$drop->select($options['plugin_status']);
	?>

	</td></tr>
	
	
<tr><td>
	<?php _e("IP Logging:","wsr") ?>
	<?php
		$drop = new dropdown('ip_logging_status');
		$drop->add(__('No IP logging','wsr'),'0');
		$drop->add(__('Full IP logging','wsr'),'1');
		$drop->add(__('Anonymize IP (mask last part)','wsr'),'2');
		$drop->dropdown_print();
		$drop->select($options['ip_logging_status']);
	?>
<small>&nbsp; used for <a href="https://eugdpr.org/" target="_blank">GDPR</a> compliance</small>
	</td></tr>

	<tr><td>
	<?php $check = new checkoption('redirect_control_panel',$options['redirect_control_panel']); ?>
         <?php _e("Do not redirect control panel links (This will be usefull when making wrong expressions that may cause an infinit redirection loop).","wsr"); ?>
	
	<br/>
	<?php $check = new checkoption('show_redirect_box',$options['show_redirect_box']); ?>
        <?php _e("Show redirect box in posts & pages edit page (Important to set up redirection for posts and pages easily).","wsr"); ?>
	

	<br/>
	<?php $check = new checkoption('reflect_modifications',$options['reflect_modifications']); ?>
           <?php _e("Reflect any modifications in the post permalink to all redirection links (Mostly Recommended).","wsr"); ?>
	
	


<script type="text/javascript">

</script>
	</td></tr>

</table>
	<br/><input style="margin-left:5px" class="button-primary" type="submit" value="<?php _e("Save General Options","wsr") ?>" name="Save_general_options">


<br/><br/>
<h3><?php _e("Redirection History Options","wsr") ?><hr></h3>
<table class="cform" align="center" width="100%">
	<tr><td>
            <?php _e("Redirection History Status:","wsr") ?>
	<?php
		$drop = new dropdown('history_status');
		$drop->add(__('Enabled','wsr'),'1');
		$drop->add(__('Disabled','wsr'),'0');
		$drop->dropdown_print();
		$drop->select($options['history_status']);
	?>

	</td></tr>
		<tr><td>
                        <?php _e("Redirection History Limit:","wsr") ?>
	<?php
		$drop = new dropdown('history_limit');
		$drop->add(__('7 days','wsr'),'7');
		$drop->add(__('1 month','wsr'),'30');
		$drop->add(__('2 months','wsr'),'60');
		$drop->add(__('3 months','wsr'),'90');
		$drop->dropdown_print();
		$drop->select($options['history_limit']);
	?>

	</td></tr>

</table>
<br/>
<input style="margin-left:5px" class="button-primary" type="submit" value="Save History Options" name="save_history_options">
<input style="margin-left:5px" class="button-primary" type="submit" value="Clear History" name="clear_history">

<br/><br/>

<h3><?php _e("Redrection Data Options","wsr"); ?><hr></h3>
<table class="cform" align="center" width="100%">
	<tr><td>
	<?php $check = new checkoption('keep_data',$options['keep_data'],'1'); ?>
         <?php _e("Keep redirection data after uninstall the plugin, this will be useful when you install it later.","wsr"); ?>
	
	</td></tr>
</table>
<br/>
<input style="margin-left:5px" class="button-primary" type="submit" value="<?php _e("Save Data Options","wsr") ?>" name="save_data_options">
<input style="margin-left:5px" class="button-primary" type="submit" value="<?php _e("Optimize Data Tables","wsr") ?>" name="optimize_tables">
<br/><br/><br/>
<hr>
<input style="margin-left:5px" class="button-primary" type="submit" value="<?php _e("Save All Options","wsr") ?>" name="save_all_options">
<input style="margin-left:5px" class="button-primary" type="submit" value="<?php _e("Restore Default Settings","wsr") ?>" name="reset_all_options">

</form>