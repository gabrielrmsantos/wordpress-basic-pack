<div class="container stat">
    <h4><?php _e('Statistics', 'ilgen')?></h4>
	<div class="top" style="display: block; overflow: hidden; padding: 5px 0;">
		<div class="alignleft">
			<input type="button" id="ilgenCheckLinks" class="button button-primary" value="<?php _e('Check Links')?>">
			<img src="<?= plugins_url( '../images/loader.gif', plugin_basename( __FILE__ ) )?>" class="ilgen-loader">
			<p class="ilgen-notification ilgen-ajax-res"></p>
		</div>
		<div class="alignright">
			<form action="" method="post">
				<?php wp_nonce_field( 'internal_link_generator-statexport' );?>
				<input type="hidden" name="action" value="statexport">
				<input type="submit" name="ilgen_statexport" class="button" value="<?php _e('Export')?>">
			</form>
			<?php if(isset($_POST['ilgen_statexport'])):?>
                <a href="<?= plugins_url('../statistics-export.csv', __FILE__)?>" target="_blank"><?php _e('Download File')?></a>
            <?php endif;?>
		</div>
	</div>
    <div class="tablenav top">
        <div class="alignleft">
            <span><?php _e('URIs')?></span>&nbsp;
            <a href="" onclick="insertParam('order', 'targetByASC'); return false;">&uarr;</a>&nbsp;
            <a href="" onclick="insertParam('order', 'targetByDESC'); return false;">&darr;</a>&nbsp;
        </div>
        <div class="alignleft">
            <div class="search-box">
                <input type="search" id="filterInput" value="<?= $_GET['filter']?>">
                <input type="button" class="button" onclick="insertParam('filter', document.getElementById('filterInput').value);" value="<?php _e('Filter', 'ilgen')?>">               
            </div>
        </div>
        <div class="alignright">
            <span><?php _e('Int.Links')?></span>&nbsp;
            <a href="" onclick="insertParam('order', 'countByASC'); return false;">&uarr;</a>&nbsp;
            <a href="" onclick="insertParam('order', 'countByDESC'); return false;">&darr;</a>&nbsp;
        </div>
    </div>
	
    <?php $li = '<li><a href="%1$s">%1$s</a><a href="%2$s"><span class="ilgen-edit-post"></span></a></li>';
    $rows = $this->ilgen_get_ordered_targets($_GET['order'], $_GET['filter']);
	
	if($rows['int']): foreach($rows['int'] as $k => $row):?>
        <div class="box">
            <h4 class="ilgen-toggle closed" data="box_<?= $k?>"><?= $row['target']?>
                <i class="ilgen-linked-count">[<?= intval($row['count'])?>]</i>
                <span></span>
            </h4>
            <?php if($row['keywords']):?>
                <div class="box-inner" id="box_<?= $k?>">
                    <?php foreach($row['keywords'] as $j => $kword):
                        if(!$kword->linked) continue;?>
                        <div class="box">
                            <h4 class="ilgen-toggle closed" data="box_<?= $k?>_<?= $j?>">
                                <?= stripslashes($kword->keyword)?>
                                <i class="ilgen-linked-count">[<?= intval($kword->linked)?>]</i>
                                <span></span>
                            </h4>
                            <div class="box-inner" id="box_<?= $k?>_<?= $j?>"><ul>
                                <?php foreach(array('posts', 'terms') as $type):
                                    if($posts = unserialize($kword->$type)):?>
                                        <li><b><?= ucfirst($type)?></b></li>
                                        <?php foreach($posts as $p):
                                            if(!$p) continue;
                                            if('terms' == $type){
                                                $p = explode('#', $p);
                                                if(term_exists(intval($p[0]), $p[1])){
                                                    printf($li, get_term_link(intval($p[0]), $p[1]), get_edit_term_link(intval($p[0]), $p[1]));
                                                }
                                            }else{
                                                if(get_post_status($p)){
                                                    printf($li, get_the_permalink($p), get_edit_post_link($p));
                                                }
                                            }
                                        endforeach;
                                    endif;
                                endforeach;?>
                            </ul></div>
                        </div>
                    <?php endforeach;?>
                </div>
            <?php endif;?>
        </div>
    <?php endforeach; else:?>
        <p class="ilgen-notification">
            <?php _e('Target URIs not found!', 'ilgen')?>
        </p>
    <?php endif;?>
    <script>
        jQuery(document).ready(function($){
            $('#ilgenCheckLinks').on('click', function(){
                var loader = $(this).next('.ilgen-loader');
                loader.css('display', 'inline-block');

                jQuery.ajax({
                    url : '<?= admin_url( 'admin-ajax.php' )?>',
                    type : 'post',
                    data : {
                        action   : 'check_links',
                        _wpnonce : '<?= wp_create_nonce('internal_link_generator-check_links')?>',
                    }, success : function(res){
                        $('.ilgen-ajax-res').html(res).css('display', 'block');
                    }
                }).always(function(){
                    loader.css('display', 'none');
                });
            });
        });
        function fixLinks(obj){
            jQuery(document).ready(function($){
                obj = $(obj);
                obj.text('<?php _e('Wait', 'ilgen')?>');

                jQuery.ajax({
                    url : '<?= admin_url( 'admin-ajax.php' )?>',
                    type : 'post',
                    data : {
                        action   : 'fix_links',
                        _wpnonce : '<?= wp_create_nonce('internal_link_generator-fix_links')?>',
                        postdata : {
                            'id' : obj.attr('data-id'),
                            'item' : obj.attr('data-item')
                        }
                    }, success : function(res){ console.log(res);
                        if(res.length > 0) obj.parent('td').parent('tr').remove(); 
                    }
                }).always(function(){
                    obj.text('<?php _e('Fix', 'ilgen')?>');
                });
            });
        }
    </script>
</div>