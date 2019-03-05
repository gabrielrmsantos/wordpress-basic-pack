<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $wpdb,$table_prefix,$util;
$table_name = $table_prefix . 'WP_SEO_Redirection';

	if($util->get('del')!='')
	{
		$delid=intval($util->get('del'));
                
		$wpdb->query($wpdb->prepare(" delete from $table_name where ID=%s ",$delid));
		
		
		if($util->there_is_cache()!='') 
                    $util->info_option_msg(__("You have a cache plugin installed",'wsr')." <b>'" . $util->there_is_cache() . "'</b>, ".__("you have to clear cache after any changes to get the changes reflected immediately! ",'wsr'));

		$SR_redirect_cache = new free_SR_redirect_cache();
		$SR_redirect_cache->free_cache();
	}
	
	$rlink=$util->get_current_parameters(array('del','search','page_num','add','edit','tab'));
        
?>
<br/>

<script type="text/javascript">

//---------------------------------------------------------

function go_search(){
<?php
isset($_REQUEST['tab'])?$url_op=$_REQUEST['tab']:$url_op="";
?>
var sword = document.getElementById('search').value;
	if(sword!=''){
            
                var url= "<?php echo $rlink.'&tab='.$url_op?>&search=" + sword;
                url=decodeURIComponent(url);
                
		window.location =url;
	}else
	{
		alert('<?php _e("Please input any search words!",'wsr') ?>');
		document.getElementById('search').focus();
	}
	
}


</script>

<div class="link_buttons">
<table border="0" width="100%">
	<tr>
		<td align="right">
		<input onkeyup="if (event.keyCode == 13) go_search();" style="height: 30px;" id="search" type="text" name="search" value="<?php echo htmlentities($util->get('search','title'))?>" size="30">
                <!--<a onclick="go_search()" href="#"><div class="search_link"><?php _e("Search","wsr"); ?></div></a>--> 
                <a onclick="go_search()" href="#" class="button"><span style="padding-top: 3px;" class="dashicons dashicons-search"></span>&nbsp;<?php _e("Search","wsr"); ?></a> 
		
                <a href="<?php echo esc_url($util->get_current_parameters('search'))?>" class="button"><span style="padding-top: 3px;" class="dashicons dashicons-screenoptions"></span>&nbsp;<?php _e("Show All","wsr"); ?></a>
		</td>
	</tr>
</table>
</div>
<?php

	$grid = new datagrid();
	$grid->set_data_source($table_name);
	$grid->add_select_field('ID');
	$grid->add_select_field('postID');
	$grid->add_select_field('redirect_from');
	$grid->add_select_field('redirect_from_type');
	$grid->add_select_field('redirect_to');
	$grid->add_select_field('redirect_to_type');
        
	$grid->set_table_attr('width','100%');
	$grid->set_col_attr(4,'width','50px');
        $grid->set_col_attr(5,'width','50px');
        $grid->set_col_attr(6,'width','100px');
	$grid->set_col_attr(2,'width','40%');
	$grid->set_col_attr(3,'width','40%');
	$grid->set_col_attr(4,'width','50px','header');
	$grid->set_col_attr(1,'width','30px','header');
	$grid->set_col_attr(5,'width','30px','header');
        $grid->set_col_attr(6,'width','150px','header');
        $grid->set_col_attr(7,'width','50px','header');
        $grid->set_col_attr(8,'width','20px','header');
	$grid->set_order(" ID desc ");
	
	$grid->set_filter("url_type=2");
	
	if($util->get('search')!='')
	{
		$search=$util->get('search');
		$grid->set_filter("url_type!=1 and (redirect_from like '%%$search%%' or redirect_to like '%%$search%%' or redirect_type like '%%$search%%'  )");
	}
	
	$grid->add_data_col('',__('No','wsr'));
	
	
	//$grid->add_data_col('redirect_from','Redirect from');
	//$grid->add_data_col('redirect_to','Redirect to');
	$grid->add_php_col(' echo "<div class=\'{$db_redirect_from_type}_background_{$db_enabled}\'><a target=\'_blank\' href=\'" . SEOR_make_absolute_url($db_redirect_from) ."\'>{$db_redirect_from}</a></div>" ;',__('Redirect from ','wsr'));
	$grid->add_php_col(' echo "<div class=\'{$db_redirect_to_type}_background_{$db_enabled}\'><a target=\'_blank\' href=\'" . SEOR_make_absolute_url($db_redirect_to) ."\'>{$db_redirect_to}</a></div>"; ',__('Redirect to ','wsr'));
	$grid->add_data_col('redirect_type',__('Type','wsr'));
        $grid->add_data_col('',__('Hits','wsr'));
        $grid->add_data_col('',__('Last Access','wsr'));
        $grid->add_template_col('go_link','post.php?post={db_postID}&action=edit','Actions');
        $grid->add_template_col('del', $util->get_current_parameters('del') . '&del={db_ID}','');
        
	$grid->run();
	
		

?>
<?php echo "<b>Note</b>:".__("To add a redirection for any post or page , use custom redirection or go to the edit page, you will find the redirection box, use it to set your redirection.","wsr")."<br/>" ?>
