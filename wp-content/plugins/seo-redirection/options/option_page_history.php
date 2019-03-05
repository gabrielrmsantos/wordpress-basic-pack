<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $wpdb,$table_prefix,$util;
$table_name = $table_prefix . 'WP_SEO_Redirection_LOG'; 
$rlink=$util->get_current_parameters(array('del','search','page_num','add','edit'));

if($util->get('del')!=''){	
	if($util->get('del')=='all'){
		c_clear_redirection_history();
	
		if($util->there_is_cache()!='') 
                    $util->info_option_msg(__("You have a cache plugin installed",'wsr')." <b>'" . $util->there_is_cache() . "'</b>, ".__("you have to clear cache after any changes to get the changes reflected immediately! ",'wsr'));
	}
}

if($util->get_option_value('history_status')!='1')
		$util->info_option_msg(__("Redirection history property is disabled now!, you can re-enable it from options tab.",'wsr'));

?>

<script type="text/javascript">
//---------------------------------------------------------
function go_search(){
var sword = document.getElementById('search').value;
	if(sword!=''){
		window.location = "<?php echo esc_url($rlink)?>&search=" + sword ;
	}else
	{
		alert('<?php _e("Please input any search words!","wsr") ?>');
		document.getElementById('search').focus();
	}
	
}

</script>

<h3><?php _e("Redirection History","wsr"); ?><hr></h3>
<div class="link_buttons">
<table border="0" width="100%">
	<tr>
            <td width="150"><a href="<?php echo $rlink?>&del=all" class="button"><span style="padding-top: 3px;" class="dashicons dashicons-trash"></span>&nbsp;<?php _e("Clear History","wsr"); ?></a></td>
		<td align="right">
		<input onkeyup="if (event.keyCode == 13) go_search();" style="height: 30px;" id="search" type="text" name="search" value="<?php echo htmlentities($util->get('search'),ENT_QUOTES)?>" size="30">
                <a onclick="go_search()" href="#" class="button"><span style="padding-top: 3px;" class="dashicons dashicons-search"></span>&nbsp;<?php _e("Search","wsr"); ?></a> 
		<a href="<?php echo $util->get_current_parameters('search')?>" class="button"><span style="padding-top: 3px;" class="dashicons dashicons-screenoptions"></span>&nbsp;<?php _e("Show All","wsr"); ?></a>
		</td>
	</tr>
</table>
</div>

<?php
	
	
	$grid = new datagrid();
	$grid->set_data_source($table_name);
        
	$grid->set_order(" ID desc "); 

	if($util->get('search')!='')
	{
		$search=$util->get('search');
		
		$grid->set_filter(" rfrom like '%%$search%%' or rto like '%%$search%%' or ctime like '%%$search%%'
		or referrer like '%%$search%%'   or country like '%%$search%%'   or ip like '%%$search%%'
		or os like '%%$search%%' or browser like '%%$search%%' or rsrc like '%%$search%%' or rtype like '%%$search%%' 
		 ");
	}
		
	$grid->add_select_field('rID');
	$grid->add_select_field('postID');
	$grid->add_select_field('referrer');
	$grid->add_select_field('country');
	$grid->add_select_field('ip');
	$grid->add_select_field('os');
	$grid->add_select_field('browser');
	$grid->add_select_field('rsrc');
	$grid->add_select_field('rfrom');
	$grid->add_select_field('rto');
	$grid->add_select_field('ctime');
        
	$grid->set_table_attr('width','100%');
	$grid->set_col_attr(1,'width','120px');
	$grid->set_col_attr(3,'width','20px'); 
	$grid->set_col_attr(3,'align','center');
	$grid->set_col_attr(4,'width','20px'); 
	$grid->set_col_attr(4,'align','center');
	$grid->set_col_attr(7,'width','30px'); 
	$grid->set_col_attr(7,'align','center');   
	$grid->set_col_attr(6,'width','75px');  
	$grid->set_col_attr(5,'width','130px');  

	$grid->set_col_attr(1, 'width', '90px', 'header');
	$grid->set_col_attr(3, 'width', '40px', 'header');
	$grid->set_col_attr(4, 'width', '40px', 'header');
	$grid->set_col_attr(5, 'width', '125px', 'header');
	$grid->set_col_attr(6, 'width', '120px', 'header');
	$grid->set_col_attr(7, 'width', '50px', 'header');	

	$grid->add_php_col('echo date(\'Y-n-j\',strtotime($db_ctime)) . \'<br/>\' .  date(\'H:i:s\',strtotime($db_ctime));  ',__('Time','wsr'));
	
	$grid->add_php_col(' echo "<div class=\'arrow_from\'><a target=\'_blank\' href=\'" . SEOR_make_absolute_url(esc_url($db_rfrom)) ."\'>" .SR_cut_string(esc_url($db_rfrom),0,120) ."</a></div><div class=\'arrow_to\'><a target=\'_blank\' href=\'" . SEOR_make_absolute_url(esc_url($db_rto)) ."\'>" .SR_cut_string(esc_url($db_rto),0,120) ."</a></div>" ;',__('Redirection','wsr'));
	$grid->add_data_col('rtype','Type');
	$grid->add_php_col('if($db_referrer !="") echo "<a target=\'_blank\' title=\'$db_referrer\' href=\'$db_referrer\'><span class=\'link\'></span></a>" ;',__('Ref','wsr')); 
	

	
	if($util->get_option_value('ip_logging_status') == 0)
	{
		$grid->add_html_col('{db_country}',__('Address','wsr'));
	}else if($util->get_option_value('ip_logging_status') == 1)
	{
		$grid->add_html_col('{db_country}<br/>{db_ip}',__('Address','wsr'));
	}else{
		
		$grid->add_php_col('echo $db_country." <br>".preg_replace(\'/([0-9]+\\.[0-9]+\\.[0-9]+)\\.[0-9]+/\', \'\\1.***\', $db_ip); ',__('Address','wsr')); 
	
	}
	
	
	$grid->add_html_col('{db_os}<br/>{db_browser}',__('Agent','wsr'));
	
	$grid->add_php_col('	
	if($db_rsrc==\'404\') echo $db_rsrc;
	if($db_rsrc==\'Custom\') echo "<a target=\'blank\' href=\'?page=wp-seo-redirection.php&edit=$db_rID\'>{$db_rsrc}</a>";
	if($db_rsrc==\'Post\') echo "<a target=\'blank\' href=\'post.php?action=edit&post=$db_postID\'>{$db_rsrc}</a>";
	',__('Class','wsr'));

        
	$grid->run();
	

?>