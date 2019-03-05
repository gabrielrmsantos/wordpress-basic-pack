<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
global $wpdb, $table_prefix, $util;
$table_name = $table_prefix . 'WP_SEO_Redirection';

if ($util->get('del') != '') {
    $delid = intval($util->get('del'));
    $wpdb->query($wpdb->prepare(" delete from $table_name where ID=%d ", $delid));

    if ($util->there_is_cache() != '')
        $util->info_option_msg(__("You have a cache plugin installed", 'wsr') . " <b>'" . $util->there_is_cache() . "'</b>, " . __("you have to clear cache after any changes to get the changes reflected immediately! ", 'wsr'));

    $SR_redirect_cache = new free_SR_redirect_cache();
    $SR_redirect_cache->free_cache();
}

$rlink = $util->get_current_parameters(array('del', 'search', 'page_num', 'add', 'edit', 'tab'));

$redirect_from = isset($redirect_from) ? $redirect_from : '';
$redirect_to = isset($redirect_to) ? $redirect_to : '';
?>
<br/>

<script type="text/javascript">

//---------------------------------------------------------

    function go_search() {
<?php
isset($_REQUEST['tab']) ? $url_op = $_REQUEST['tab'] : $url_op = "";
?>
        var sword = document.getElementById('search').value;
        if (sword != '') {

            var url = "<?php echo $rlink . '&tab=' . $url_op ?>&search=" + sword;
            url = decodeURIComponent(url);


            window.location = url;
        } else
        {
            alert('<?php _e("Please input any search words!", 'wsr') ?>');
            document.getElementById('search').focus();
        }

    }

</script>
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Add Custom Redirects</h4>
                </div>
                <div class="modal-body">

                    <form onsubmit="return check_from();" method="POST" id="myform" action="<?php echo $util->get_current_parameters(array('add', 'edit', 'page404')); ?>">
                        <table class="cform" width="100%">
                            <tr>
                                <td class="label"><?php _e('Redirection status:', 'wsr') ?></td>
                                <td>		<select size="1" name="enabled"  id="enabled">
                                        <option value="1"><?php _e('Enabled', 'wsr') ?></option>
                                        <option value="0"><?php _e('Disabled', 'wsr') ?></option>
                                    </select>

                                </td>
                            </tr>
                            <tr>

                                <td class="label"><?php _e('Redirect from:', 'wsr') ?></td>
                                <td>
                                    <div id="rfrom_div">
                                        <select onchange="redirect_from_type_change()" size="1" name="redirect_from_type"  id="redirect_from_type">
                                            <option value="Page"><?php _e('Page', 'wsr') ?></option>
                                            <option value="Folder"><?php _e('Folder', 'wsr') ?></option>
                                            <option value="Regex"><?php _e('Regex', 'wsr') ?></option>
                                        </select>
                                        <input onblur="check_redirect_from()"  type="text" id="redirect_from" style="height: 40px;" placeholder="<?php _e("(Starting with 'http://')", 'wsr') ?>" name="redirect_from" size="45" value="<?php echo $redirect_from; ?>">
                                        <span class="help-block"></span>
                                        <select onchange="redirect_to_folder_settings_change()" size="1" name="redirect_from_folder_settings"  id="redirect_from_folder_settings">
                                            <option value="1"><?php _e('Only the folder', 'wsr') ?></option>
                                            <option value="2"><?php _e("The folder and it's content", 'wsr') ?></option>
                                            <option value="3"><?php _e("Only the folder's content", 'wsr') ?></option>
                                        </select>
                                        <br>
                                        <select size="1" name="redirect_from_subfolders"  id="redirect_from_subfolders" class="cmb2_select">
                                            <option value="0"><?php _e("Include sub-folders", 'wsr') ?></option>
                                            <option value="1"><?php _e("Do not include sub-folders", 'wsr') ?></option>
                                        </select>


                                    </div>
                                    <?php if ($util->get('page404') != '') echo $redirect_from; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="label"><?php _e("Redirect to:", 'wsr') ?></td>
                                <td>
                                    <select onchange="redirect_to_type_change()" size="1" class="cmb2_select" name="redirect_to_type"  id="redirect_to_type">
                                        <option value="Page"><?php _e("Page:", 'wsr') ?></option>
                                        <option value="Folder"><?php _e("Folder", 'wsr') ?></option>
                                    </select>

                                    <input onblur="check_redirect_to()" onblur="if (this.value == 'http://') {
                                                this.value = ''
                                            }" onfocus="(this.value == '') && (this.value = 'http://')" type="text" id="redirect_to" placeholder="<?php _e("(Starting with 'http://')", 'wsr') ?>" class="regular-text" style="height: 40px;" name="redirect_to" size="45" value="<?php echo $redirect_to ?>">
                                    <span class="help-block"></span>
                                    <select size="1" name="redirect_to_folder_settings"  id="redirect_to_folder_settings">
                                        <option value="1"><?php _e("Normal", 'wsr') ?></option>
                                        <option value="2"><?php _e("Wild Card Redirect", 'wsr') ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="label"><?php _e("Redirection type:", 'wsr') ?></td>
                                <td>
                                    <select size="1" name="redirect_type"  id="redirect_type">
                                        <option value="301"><?php _e("301 (SEO)", 'wsr') ?></option>
                                        <option value="302"><?php _e("302", 'wsr') ?></option>
                                        <option value="307"><?php _e("307", 'wsr') ?></option>
                                    </select>
                                    <script type="text/javascript">
<?php
if ($redirect_type != '')
    echo "document.getElementById('redirect_type').value='$redirect_type';";

if ($redirect_from_type != '')
    echo "document.getElementById('redirect_from_type').value='$redirect_from_type';";

if ($redirect_from_folder_settings != '' && $redirect_from_type == 'Folder')
    echo "document.getElementById('redirect_from_folder_settings').value='$redirect_from_folder_settings';";

if ($redirect_from_subfolders != '' && $redirect_from_type == 'Folder')
    echo "document.getElementById('redirect_from_subfolders').value='$redirect_from_subfolders';";

if ($redirect_to_type != '')
    echo "document.getElementById('redirect_to_type').value='$redirect_to_type';";

if ($redirect_to_folder_settings != '' && $redirect_to_type == 'Folder')
    echo "document.getElementById('redirect_to_folder_settings').value='$redirect_to_folder_settings';";

if ($enabled != '')
    echo "document.getElementById('enabled').value='$enabled';";


if ($util->get('page404') != '')
    echo "document.getElementById('rfrom_div').style.display = 'none';";
?>
</script>
                                </td>
                            </tr>
                        </table>
                        <br/>
                        <?php echo "<b>Note</b>:" . __(" When you move your site to another domain, the new domain name will be reflected to all links automatically.", "wsr"); ?>
                        <br/>
                        <label id="msg_response">
                        </label>
                        <br/>
                </div>
                <div class="modal-footer">
                        <?php
                        echo '<input  class="button-primary" id="btnSave" type="button" value="' . __("Add New", "wsr") . '"  onclick="return save_function()">';
                        ?>
                        <input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo $nonce = wp_create_nonce('seoredirection'); ?>" />
                        <input type="hidden" id="edit" name="edit" value="<?php echo intval($util->get('edit')) ?>">
                        <input type="hidden" id="add_new" name="add_new" value="">
                        <input type="hidden" id="edit_exist" name="edit_exist" value="">
                        <input type="hidden" id="action" name="action" value="customAddUpdate">
                        <input data-dismiss="modal" aria-label="Close" class="button-primary " type="button" value="<?php _e("Cancel", 'wsr') ?>" name="cancel">
                    

                </div>
            </div>
            </form>
        </div>
    </div>
<div class="link_buttons">
    
    <table border="0" width="100%">
        <tr>
            <td > <button type="button" class="button-secondary" onclick="add_rec()" >
                    <span style="padding-top: 5px;" class="dashicons dashicons-plus"></span><?php _e('Add New', 'wsr') ?>
                </button>
            
                <label id="waiting_lbl"><div class="loading" style="display: none">Loading&#8230;</div>
</label>
            </td>
            <td align="right">
                <input onkeyup="if (event.keyCode == 13)
                            go_search();" style="height: 30px;" id="search" type="text" name="search" value="<?php echo htmlentities($util->get('search'), ENT_QUOTES) ?>" size="30">
                <a class="button" onclick="go_search()" href="#" ><span style="padding-top: 3px;" class="dashicons dashicons-search"></span>&nbsp;<?php _e("Search", 'wsr') ?></a> 
                <a class="button" href="<?php echo esc_url(htmlentities($util->get_current_parameters('search'))) ?>"><span style="padding-top: 3px;" class="dashicons dashicons-screenoptions"></span>&nbsp;<?php _e("Show All", 'wsr') ?></a>
            </td>
        </tr>
    </table>
</div>
<?php
$grid = new datagrid();
$grid->set_data_source($table_name);
$grid->add_select_field('ID');
$grid->add_select_field('redirect_from');
$grid->add_select_field('redirect_from_type');
$grid->add_select_field('redirect_to');
$grid->add_select_field('redirect_to_type');
$grid->add_select_field('enabled');
$grid->set_table_attr('width', '100%');
$grid->set_col_attr(4, 'width', '50px');
$grid->set_col_attr(5, 'width', '50px');
$grid->set_col_attr(6, 'width', '100px');
$grid->set_col_attr(7, 'width', '20px');
$grid->set_col_attr(8, 'width', '20px');
$grid->set_col_attr(2, 'width', '40%');

$grid->set_col_attr(3, 'width', '40%');
$grid->set_col_attr(4, 'width', '50px', 'header');
$grid->set_col_attr(1, 'width', '30px', 'header');
$grid->set_col_attr(5, 'width', '30px', 'header');
$grid->set_col_attr(6, 'width', '150px', 'header');
$grid->set_col_attr(7, 'width', '50px', 'header');
$grid->set_col_attr(8, 'width', '20px', 'header');

$grid->set_order(" ID desc ");

$grid->set_filter("url_type=1");

if ($util->get('search') != '') {
    $search = $util->get('search');
    $grid->set_filter("url_type=1 and (redirect_from like '%%$search%%' or redirect_to like '%%$search%%' or redirect_type like '%%$search%%'  )");
}

$grid->add_data_col('', __('No', 'wsr'));
$grid->add_php_col(' echo "<div class=\'{$db_redirect_from_type}_background_{$db_enabled}\'><a target=\'_blank\' href=\'" . SEOR_make_absolute_url(esc_url($db_redirect_from)) ."\'>{$db_redirect_from}</a></div>" ;', __('Redirect from ', 'wsr'));
$grid->add_php_col(' echo "<div class=\'{$db_redirect_to_type}_background_{$db_enabled}\'><a target=\'_blank\' href=\'" . SEOR_make_absolute_url(esc_url($db_redirect_to)) ."\'>{$db_redirect_to}</a></div>"; ', __('Redirect to ', 'wsr'));
$grid->add_data_col('redirect_type', __('Type', 'wsr'));
$grid->add_data_col('', __('Hits', 'wsr'));
$grid->add_data_col('', __('Last Access', 'wsr'));
$grid->add_template_col('del', $util->get_current_parameters('del') . '&del={db_ID}', __('Actions', 'wsr'));
$grid->add_template_col('edit', '{db_ID}', __('', 'wsr'));
$grid->run();
?>