<div class="container asearch">
    <h4><?php _e('Search Anchors across your website', 'ilgen')?></h4>
    <div class="top-panel">
        <form action="" method="post">
            <?php wp_nonce_field( 'internal_link_generator-asearch' );?>
            <input type="hidden" name="action" value="asearch">
            <p>
                <b><?php _e('Keyword:', 'ilgen')?></b><br/>
                <input type="text" name="keyword" size="44" value="<?= $_POST['keyword']?>">&nbsp; 
                <input type="submit" name="ilgen_asearch" value="<?php _e('Search', 'ilgen')?>" class="button button-primary">
            </p>
            <p>
                <b><?php _e('Additional words:', 'ilgen')?></b><br/>
                <?php _e('Before keyword', 'ilgen')?>&nbsp;<input type="text" name="before" class="ilgen-userinc" size="2" value="<?= $_POST['before']?>"> 
                <?php _e('After keyword', 'ilgen')?>&nbsp;<input type="text" name="after" class="ilgen-userinc" size="2" value="<?= $_POST['after']?>">
            </p>
        </form>
    </div>
    <?php if($rows = $this->asearch()):?>
        <form action="" method="post">
            <?php wp_nonce_field( 'internal_link_generator-bulk' );?>
            <input type="hidden" name="action" value="bulk">
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="bulk_action">
                        <option><?php _e('Bulk Actions', 'ilgen')?></option>
                        <option value="asearch_add"><?php _e('Add All', 'ilgen')?></option>
                    </select>
                    <input type="submit" class="button button-primary" name="ilgen_bulk" value="<?php _e('Apply', 'ilgen')?>">
                </div>
            </div>
            <div class="asearch-inner">
                <table>
                    <thead><tr>
                        <th><input type="checkbox" class="check_all"></th>
                        <th><?php _e('Found Words', 'ilgen')?></th>
                        <th><?php _e('Formed Keyword', 'ilgen')?></th>
                        <th><?php _e('URL', 'ilgen')?></th>
                        <th><?php _e('Action', 'ilgen')?></th>
                    <tr></thead>
                    <tbody>
                        <?php foreach($rows as $k => $row):?>
                            <tr>
                                <td><input type="checkbox" name="ids[<?= $k?>]" value="<?= $k?>"></td>
                                <td><?= $row['words']?></td>
                                <td><input type="text" name="formed[<?= $k?>]" id="formed_<?= $k?>" class="ilgen-formed"></td>
                                <td>
                                    <input type="text" name="target[<?= $k?>]" id="target_<?= $k?>" class="ilgen-target" data-id="formed_<?= $k?>">
                                    <a href="#" class="ilgen-target-set" style="display:none;"><?php _e('asign to all', 'ilgen')?></a>
                                </td>
                                <td><button class="ilgen-asearch-add button button-small" data-id="<?= $k?>"><?php _e('Add', 'ilgen')?></button></td>
                            </tr>
                        <?php endforeach;?>
                    </tbody>
                </table>
            </div>
        </form>
    <?php else:?>
        <p class="ilgen-notification"><?php _e('Please, insert keyword!', 'ilgen');?></p>
    <?php endif;?>
</div>
<script>
    jQuery(document).ready(function($){
        $("input.ilgen-userinc").userincr().data({
            'min':0,
            'max':100
        });
    });
    jQuery(document).ready(function($){
         $('.ilgen-found').click(function(e){
            e.preventDefault();
            var input_id  = '#' + $(this).attr('data-id');
            var word = new RegExp(""+$(this).text()+"\\s");
			
            if($(input_id).val().match(word)){
                if($(input_id).val($(input_id).val().replace(word, ''))){
                   $(this).removeClass('in').addClass('notin');
                }
               
            }else{
                $(input_id).val($(input_id).val() + $(this).text() + " ");
                $(this).removeClass('notin').addClass('in');
            }
            console.log($(input_id).val().indexOf($(this).text()));
        });

        $('.ilgen-target').focusout(function(e){
            e.preventDefault();
            if($(this).val().match(/(http|ftp|https):\/\/[\w-]+(\.[\w-]+)+([\w.,@?^=%&amp;:\/~+#-]*[\w@?^=%&amp;\/~+#-])?/)){
                $(this).next().css({'display':'block'});
            }
        });
        
        $('.ilgen-target-set').click(function(e){
            e.preventDefault();
            var target = $(this).prev().val();
            $('.ilgen-target').each(function(i, val) {
                $(this).val(target);
            });
        });
        
        $('.ilgen-asearch-add').click(function(e){
            e.preventDefault();
            obj = $(this);

            jQuery.ajax({
                url : '<?php menu_page_url('internal_links_generator');?>',
                type : 'post',
                data : {
                    action   : 'ajax',
                    _wpnonce : '<?php echo wp_create_nonce('internal_link_generator-ajax');?>',
                    type     : 'asearch_add',
                    keyword  : $('#formed_' + obj.attr('data-id')).val(),
                    target   : $('#target_' + obj.attr('data-id')).val()
                },
                success : function( response ) {
                    obj.attr('disabled', true);
                    obj.after('<div class="ilgen-success"><?php _e('added');?></div>');
                }
            });
        });
    });
</script>
