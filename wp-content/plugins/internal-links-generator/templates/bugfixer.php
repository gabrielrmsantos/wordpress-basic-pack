<div class="container">
    <form method="post" action="">
        <?php wp_nonce_field( 'internal_link_generator-bugfixer' );?>
        <input type="hidden" name="action" value="bugfixer">
        <div class="ilgen-container">
            <p class="ilgen-notification"><?php _e('Put each page url on a separate line.', 'ilgen')?></p>
            <textarea rows="10" name="targets"></textarea>
            <p><input type="submit" name="ilgen_bugfix" value="<?php _e('Submit', 'ilgen')?>" class="button button-primary"></p>
        </div>
    </form>
</div>
