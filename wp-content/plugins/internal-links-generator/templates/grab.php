<div class="container grabb">
    <h4><?php _e('Grab & Import existing links', 'ilgen')?></h4>
    <p><i><?php _e('Each time you open this tab, plugin will scan your website for internal links you created manually across your website.', 'ilgen')?></i></p>
    <form action="" method="post">
        <?php wp_nonce_field( 'internal_link_generator-grabb' );?>
        <input type="hidden" name="action" value="grabb">
        <?php $rows = $this->ilgen_grabb_links();
        if(!$rows['posts'] && !$rows['terms']):?>
            <p class="ilgen-notification"><?php _e('Links not found!', 'ilgen');?></p>
        <?php else:?>
            <div class="grabb-inner">
                <table>
                    <thead><tr>
                        <th><input type="checkbox" class="check_all"></th>
                        <th><?php _e('Anchor Text', 'ilgen')?></th>
                        <th><?php _e('Target URL', 'ilgen')?></th>
                        <th><?php _e('Found In', 'ilgen')?></th>
                    <tr></thead>
                    <tbody>
                        <?php foreach(array('posts', 'terms') as $type):
                            if($rows[$type]):?>
                                <tr><td class="heading" colspan="4"><?= ucfirst($type)?></td></tr>
                                <?php foreach($rows[$type] as $k => $rs):
                                    foreach($rs as $r):?>
                                    <tr>
                                        <td><input type='checkbox' name='ids[]' data='<?= json_encode(array('id'=>$k, 'type'=>$type, 'target'=>$r[0], 'keyword'=>$r[1]), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)?>'></td>
                                        <td><?= $r[1]?></td>
                                        <td><?= $r[0]?></td>
                                        <td>
                                            <?php $id = explode($this->termDelimiter, $k);
                                            if('terms' == $type):
                                                $term = get_term(intval($id[0]), $id[1]);?>
                                                <a href="<?= get_term_link($term->term_id, $term->taxonomy)?>" target="_blank"><?= $term->name?></a>
                                            <?php else:?>
                                                <a href="<?= get_the_permalink(intval($id[0]))?>" target="_blank"><?= get_the_title(intval($id[0]))?></a>
                                            <?php endif;?>
                                        </td>
                                    </tr>
                                    <?php endforeach;
                                endforeach;
                            endif;
                        endforeach;?>
                    </tbody>
                </table>
            </div>
            <p>
                <input type="button" name="ilgen_grabb" value="<?php _e('Import', 'ilgen')?>" class="button button-primary">
                <img src="<?= plugins_url( '../images/loader.gif', plugin_basename( __FILE__ ) )?>" class="ilgen-loader">
            </p>
        <?php endif;?>
    </form>
</div>