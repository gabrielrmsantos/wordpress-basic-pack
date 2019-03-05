<div class="container">
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
    <hr>
    <form method="post" action="">
        <?php wp_nonce_field( 'internal_link_generator-advanced_import' );?>
        <input type="hidden" name="action" value="advanced_import">
        <h4><?php _e('Advanced keywords import', 'ilgen')?></h4>
        <p class="ilgen-notification">
            <?php _e('Insert template: keyword1 | target_url1 | limit1; keyword2 | target_url2 | limit2; ...', 'ligen')?>
        </p>
        <textarea rows="5" name="import_string" id="ilgen_keywords_area"></textarea>
        <p>
            <input type="submit" name="ilgen_advanced_import" value="<?php _e('Import', 'ligen')?>" class="button button-primary">
        </p>
    </form>
    <hr>  
    <form method="post" action="">
        <?php wp_nonce_field( 'internal_link_generator-export' );?>
        <input type="hidden" name="action" value="export">
        <h4><?php _e('Keyword export', 'ilgen')?></h4>
        <p>
            <input type="submit" id="ilgen_export" name="ilgen_export" value="<?php _e('Export', 'ligen')?>" class="button button-primary">
            <?php if(isset($_POST['ilgen_export'])):?>
                <a href="<?= plugins_url('../keywords.csv', __FILE__)?>" target="_blank"><?php _e('Download File', 'ilgen')?></a>
            <?php endif;?>
        </p>
    </form>
</div>
