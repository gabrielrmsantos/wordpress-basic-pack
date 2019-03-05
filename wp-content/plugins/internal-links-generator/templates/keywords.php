<div class="container keywords">
    <h4><?php _e('Keywords list', 'ilgen')?></h4>
    <?php if(!empty($template_data['keywords'])):?>
        <form name="" action="" method="post">
            <?php wp_nonce_field( 'internal_link_generator-bulk' );?>
            <input type="hidden" name="action" value="bulk">
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="bulk_action">
                        <option><?php _e('Bulk Actions', 'ilgen')?></option>
                        <option value="update"><?php _e('Update', 'ilgen')?></option>
                        <option value="recount"><?php _e('ReCount', 'ilgen')?></option>
                        <option value="linking"><?php _e('Link all', 'ilgen')?></option>
                        <option value="unlinking"><?php _e('Unlink all', 'ilgen')?></option>
                        <option value="delete"><?php _e('Delete', 'ilgen')?></option>
                    </select>
                    <input type="button" class="button button-primary" name="ilgen_bulk" value="<?php _e('Apply', 'ilgen')?>">
                    <img src="<?= plugins_url( '../images/loader.gif', plugin_basename( __FILE__ ) )?>" class="ilgen-loader">
                    <span class="ilgen-watch-notification"><?php _e('Click "Apply" to save changes!')?></span>
                </div>
                <div class="alignright actions">
                    <select id="ilgenSearchField">
                        <?php foreach(array('keyword'=>'', 'target'=>'', 'limit' => __('Links Limit', 'ilgen'), 'count' => __('Found on Site', 'ilgen'), 'linked'=>'') as $k => $v){
                            $sel = ($k === $template_data['filter'][0]) ? 'selected' : '';
                            printf('<option value="%s" %s>%s</option>', $k, $sel, ($v) ? $v : ucfirst($k));
                        }?>
                    </select>
                    <input type="search" id="ilgenSearchInput" value="<?= $template_data['filter'][1]?>">
                    <input type="button" id="ilgenSearchBtn" class="button" value="<?php _e('Filter')?>">
                    <?php if($template_data['filter'][1]):?>
                        <a href="<?php menu_page_url('internal_links_generator')?>" class="button ilgen-button-delete"><?php _e('Flush')?></a>
                    <?php endif;?>
                </div>
            </div>
            <div class="keywords-inner">
                <table>
                    <thead><tr>
                        <th><input type="checkbox" class="check_all"></th>
                        <th>
                            <?php _e('Keyword', 'ilgen')?>&nbsp;
                            <a href="" onclick="insertParam('order', 'keyword__ASC'); return false;">&uarr;</a>&nbsp;
                            <a href="" onclick="insertParam('order', 'keyword__DESC'); return false;">&darr;</a>&nbsp;
                        </th>
                        <th>
                            <?php _e('Target URL', 'ilgen')?>&nbsp;
                            <a href="" onclick="insertParam('order', 'target__ASC'); return false;">&uarr;</a>&nbsp;
                            <a href="" onclick="insertParam('order', 'target__DESC'); return false;">&darr;</a>&nbsp;
                        </th>
                        <th>
                            <?php _e('Links Limit', 'ilgen')?>&nbsp;
                            <a href="" onclick="insertParam('order', 'limit__ASC'); return false;">&uarr;</a>&nbsp;
                            <a href="" onclick="insertParam('order', 'limit__DESC'); return false;">&darr;</a>&nbsp;
                        </th>
                        <th>
                            <?php _e('Found on Site', 'ilgen')?>&nbsp;
                            <a href="" onclick="insertParam('order', 'count__ASC'); return false;">&uarr;</a>&nbsp;
                            <a href="" onclick="insertParam('order', 'count__DESC'); return false;">&darr;</a>&nbsp;
                        </th>
                        <th>
                            <?php _e('Linked', 'ilgen')?>&nbsp;
                            <a href="" onclick="insertParam('order', 'linked__ASC'); return false;">&uarr;</a>&nbsp;
                            <a href="" onclick="insertParam('order', 'linked__DESC'); return false;">&darr;</a>&nbsp;
                        </th>
                        <th><?php _e('Outer Tag', 'ilgen')?></th>
                    </tr></thead>
                    <tbody>
                        <?php foreach($template_data['keywords'] as $key):?>
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="<?= $key->id?>"></td>
                                <td><?= stripslashes($key->keyword)?></td>
                                <td><input type="text" name="targets[<?= $key->id?>]" value="<?= $key->target?>" size="7" class="ilgen-watch-input"></td>
                                <td><input type="text" name="limits[<?= $key->id?>]" value="<?= $key->limit?>" size="3" class="ilgen-watch-input"></td>
                                <td class="td_recount_<?= $key->id?>"><?= $key->count?></td>
                                <td class="td_linked_<?= $key->id?>"><?= $key->linked?></td>
                                <td><select name="tags[<?= $key->id?>]" class="ilgen-watch-input">
                                    <option></option>
                                    <?php foreach(array('strong', 'b', 'i', 'u') as $tag){
                                        $sel = ($key->tag == $tag) ? 'selected' : '';
                                        printf('<option %s>%s</option>', $sel, $tag);     
                                    }?>
                                </select></td>
                            </tr>
                        <?php endforeach;?>
                    </tbody>
                </table>
            </div>
        </form>
        <div class="box">
            <h4  class="ilgen-toggle closed" data="box_0"><?php _e('Add Keywords', 'ilgen')?><span class="plus"></span></h4>
            <div class="box-inner" id="box_0">
                <form method="post" action="">
                    <?php wp_nonce_field( 'internal_link_generator-simple_import' );?>
                    <input type="hidden" name="action" value="simple_import">
                    <div class="ilgen-container">
                        <h4><?php _e('Simple keywords import', 'ilgen')?></h4>
                        <p class="ilgen-notification">
                            <?php _e('Put each keyword on a separate line or separate them by commas.', 'ilgen')?>
                        </p>
                        <textarea rows="5" name="import_string"></textarea>
                        <p>
                            <input type="submit" name="ilgen_simple_import" value="<?php _e('Import', 'ilgen')?>" class="button button-primary">
                        </p>
                    </div>
                </form>
            </div>
        </div>
    <?php else:?>
        <p class="ilgen-notification"><?php printf('In order to add keywords, use %s tab.', '<a href="options-general.php?page=internal_links_generator&tab=impex">' . __('Import/Export', 'ilgen') . '</a>');?></p>
    <?php endif;?>
    <script>
        jQuery(document).ready(function($){
            $('#ilgenSearchBtn').bind('click keyEnter', function(e){
                e.preventDefault();
                var query = [ $('#ilgenSearchField').val(), $('#ilgenSearchInput').val() ];
                insertParam('filter', query.join('__'));
                return false;
            });
            $('#ilgenSearchInput').keyup(function(e){
                if(e.keyCode == 13)	$('#ilgenSearchBtn').trigger("keyEnter");
            });
        });
    </script>
</div>