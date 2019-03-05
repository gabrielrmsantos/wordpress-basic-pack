<?php
if(!class_exists('Internal_Links_Generator_Settings')){
    
    class Internal_Links_Generator_Settings{
       
        public function __construct(){
			
            global $wpdb;
            $this->wpdb = $wpdb;
            $this->options = get_option('ilgen_options');
            $this->settings_tabs = array(
                'keywords' => __('Keywords','ilgen'),
                'links'    => __('URLs','ilgen'),
                'grab'     => __('Grab Links','ilgen'),
                'impex'    => __('Import/Export','ilgen'),
                'asearch'  => __('Search Anchors','ilgen'),
                'stat'     => __('Statistics', 'ilgen'),
                'settings' => __('Settings','ilgen')
            );
			
            if(isset($this->options['bugfixer']) && 'on' == $this->options['bugfixer']){
                $this->settings_tabs['bugfixer'] = __('Bugs Fixer', 'ilgen');
            }
            
			$this->tab = (isset( $_GET['tab'] )) ? $_GET['tab'] : key($this->settings_tabs);
            $this->urlPattern = "<a\s[^>]*href=(\"??)([^\">]*?)\\1[^>]*>(.*)<\/a>";
            $this->urlTemplate = '<a href="%s" class="ilgen">%s</a>';
            $this->termDelimiter = '#';
			
            add_action('admin_init', array(&$this, 'init'));
            add_action('admin_menu', array(&$this, 'menu'));
			add_action('admin_head', array(&$this, 'inline_script'));
            add_action('admin_enqueue_scripts', array(&$this, 'enqueue_scripts'));
            add_action('wp_ajax_bulk_actions', array(&$this, 'bulk_ajax'));
            add_action('wp_ajax_check_links', array(&$this, 'check_links_ajax'));
			add_action('wp_ajax_fix_links', array(&$this, 'fix_links_ajax'));
            
            remove_filter('pre_term_description', 'wp_filter_kses');
            remove_filter('term_description', 'wp_kses_data');
        }
        
        public function init(){
            wp_enqueue_style('ilgen-style', plugins_url( 'css/style.css', plugin_basename( __FILE__ ) ));
        }
        
        public function enqueue_scripts(){
			wp_enqueue_script( 'ilgen-scripts', plugins_url( 'js/scripts.js', plugin_basename( __FILE__ ) ), array(), '1.0' );
            /* wp_add_inline_script( 'ilgen-scripts', 
                "var ilgenBulkNonce = '" . wp_create_nonce('internal_link_generator-bulk_actions') . "';\n" .
                "var ilgenBulkUrl = '" . admin_url( 'admin-ajax.php' ) . "';\n"
            );*/
            wp_enqueue_script('ilgen-userinc', plugins_url( 'js/userincr.min.js', plugin_basename( __FILE__ ) ));
        }
		
		public function inline_script(){
			echo "<script type='text/javascript'>" .
				"var ilgenBulkNonce = '" . wp_create_nonce('internal_link_generator-bulk_actions') . "';\n" .
                "var ilgenBulkUrl = '" . admin_url( 'admin-ajax.php' ) . "';\n" .
			"</script>";
		}
                
        public function menu(){
            add_options_page(
                'Internal Links Generator Settings', 
                'Internal Links Generator', 
                'manage_options', 
                'internal_links_generator', 
                array(&$this, 'plugin_settings_page')
            );
        }
		
        public function plugin_settings_page(){
            
            if(!current_user_can('manage_options')){
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }
            
            if(isset($_REQUEST['action'])){
                $action = sanitize_title($_REQUEST['action']);
                $nonce = "internal_link_generator-$action";
                
                if(isset($_REQUEST['_wpnonce']) && !wp_verify_nonce($_REQUEST['_wpnonce'], $nonce)){
                    die( 'Security check failure!' ); 
                }else{
                    if((function_exists ('check_admin_referer')))
                        check_admin_referer($nonce);
                }
                $this->$action();
            }
            
            $template_data = array(
                'options' => get_option('ilgen_options'),
                'termDelimiter' => $this->termDelimiter
            );
            
            switch($this->tab){
                case 'keywords':
                    $template_data['order'] = @explode('__', ($_GET['order']) ? sanitize_text_field($_GET['order']) : 'keyword__ASC');
                    $template_data['filter'] = @explode('__', ($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'keyword__');

                    $query = "SELECT * FROM `{$this->wpdb->prefix}internalinks` ";

                    if($template_data['filter'][1] !== ''){
                        $query .= "WHERE `{$template_data['filter'][0]}` ";
                        
                        if(is_numeric($template_data['filter'][1])){
                            $query .= "= {$template_data['filter'][1]} ";
                        }else{ 
                            $query .= "LIKE '%{$template_data['filter'][1]}%' ";
                        }
                    }

                    $query .= "ORDER BY `{$template_data['order'][0]}` {$template_data['order'][1]}";
                    $template_data['keywords'] = $this->wpdb->get_results($query);
                break;
            }
            $this->ilgen_get_page($template_data);
        }
        
        /* action function */
        
        public function simple_import($param = 'keyword'){
			
            if(!empty($_POST['import_string'])){
                $values = array();
                $values = str_replace("\r\n", ',', $_POST['import_string']);
                $values = explode(',', $values);
                $values = array_map('trim', @array_filter($values));
                
                if(isset($_POST['param'])) 
                    $param = (string)$_POST['param'];
                
                if(isset($_POST['target'])) 
                    $target = esc_url_raw($_POST['target']);
                else $target = null;
                
                foreach($values as $value){
                    $this->{"ilgen_insert_$param"}($value, $target);
                }
                
                $this->ilgen_messages(1, 'updated');
            }
        }
        
        public function advanced_import(){

            if(!empty($_POST['import_string'])){
                $rows = @array_filter(explode(';', $_POST['import_string']));
                
                if(!empty($rows)){
                    
                    foreach($rows as $row){
                        $row = @array_map('trim', @array_filter(explode('|', $row)));
                        $this->ilgen_insert_keyword($row[0], $row[1], $row[2]);
                    }
                    $this->ilgen_messages(2, 'updated');
                }
            }
        }
        
        public function export(){
            
            $file_url = sprintf("%s/keywords.csv", dirname(__FILE__));
            $fp = fopen($file_url, 'w');
            $rows = $this->wpdb->get_results(
                "SELECT * FROM `{$this->wpdb->prefix}internalinks`"
            );
            
            if(!empty($rows)){
                fputcsv($fp, array( 
                    __('Keyword', 'ligen'),  __('Target URL', 'ligen'),
                    __('Limit', 'ligen'),  __('Found on Site', 'ligen'), 
                    __('Linked', 'ilgen')
                ));
                foreach($rows as $row){
                    fputcsv($fp, array( html_entity_decode($row->keyword), 
                        $row->target, $row->limit, $row->count, $row->linked
                    ));
                }
            }
            fclose($fp);
            
            if($this->ilgen_is_writable($file_url)) {
                $this->ilgen_messages(6, 'updated');
            }
        }
        
		public function statexport(){
            
            $file_url = sprintf("%s/statistics-export.csv", dirname(__FILE__));
            $fp = fopen($file_url, 'w');
                        
            if($rows = $this->ilgen_get_ordered_targets()){
                fputcsv($fp, array( 
                    __('Target URL', 'ligen'),  __('Keyword', 'ligen'),
                    __('Page Type', 'ligen'),  __('Link URL', 'ligen')
                ));
                foreach($rows['int'] as $row){
					if($row['keywords']){
						foreach($row['keywords'] as $j => $kword){
							if(!$kword->linked) continue;
							foreach(array('posts', 'terms') as $type){
								if($posts = unserialize($kword->$type)){
									foreach($posts as $p){
										if(!$p) continue;
										if('terms' == $type){
											$p = explode('#', $p);
											if(term_exists(intval($p[0]), $p[1])) $link = get_term_link(intval($p[0]), $p[1]);
										}else{
											if(get_post_status($p)) $link = get_the_permalink($p);
										}
										fputcsv($fp, array( $row['target'], stripslashes($kword->keyword), $type, $link ));
									}
								}
							}
						}
					}
                }
            }
            fclose($fp);
            
            if($this->ilgen_is_writable($file_url)) {
                $this->ilgen_messages(6, 'updated');
            }
        }
		
        public function update($id = 0){
			
            if($id > 0){
                if($this->ilgen_from_table('target', $id) != esc_url_raw($_POST['targets'][$id])){
                    $this->unlinking($id);
                }
                $result = $this->ilgen_insert_keyword( 'keyword', 
                    $_POST['targets'][$id], $_POST['limits'][$id], $_POST['tags'][$id], $id
                );
            }
            return $result;
        }
        
        public function remove($id = 0){
			
            if($id > 0){
                $res = $this->wpdb->delete( 
                    $this->wpdb->prefix . 'internalinks', 
                    array( 'id' => $id ) 
                );
            }
			
            return $res;
        }
        
        public function bulk(){
			
            if(!empty($_POST['ids'])):
                foreach($_POST['ids'] as $id):
                    switch($_POST['bulk_action']){
                        case 'update': $this->update($id); break;
                        case 'recount': $this->recount($id); break;
                        case 'linking': $this->linking($id); break;
                        case 'unlinking': $this->unlinking($id); break;
                        case 'delete': $this->unlinking($id); $this->remove($id); break;
                        case 'asearch_add': $this->ilgen_insert_keyword($_POST['formed'][$id], $_POST['target'][$id]); break;
                    }
                endforeach;
                $this->ilgen_messages(3, 'updated');
            else:
                $this->ilgen_messages(3, 'warning');
            endif;
        }
        
        public function bulk_ajax(){

            if(!wp_verify_nonce($_POST['_wpnonce'], 'internal_link_generator-bulk_actions')){
                wp_die(); 
            }
            
            $data = array_map('sanitize_text_field', (array)$_POST['postdata']);

            switch($_POST['subAction']){
                case 'recount': $res = $this->recount($data['id']); break;
                case 'linking': $res = $this->linking($data['id']); break;
                case 'unlinking': $res = $this->unlinking($data['id']); break;
                case 'update': 
                    if($this->ilgen_from_table('target', $data['id']) != esc_url_raw($data['target'])){
                        $this->unlinking($data['id']);
                    }
                    $res = $this->ilgen_insert_keyword( 'keyword', 
                        $data['target'], $data['limit'], $data['tag'], $data['id']
                    );
                break;
                case 'delete': 
                    $this->unlinking($data['id']); 
                    $res = $this->remove($data['id']);
                break;
                case 'grab':
					$json = stripslashes(html_entity_decode($_POST['postdata']));
					$data = json_decode($json, true);
					
					if(isset($data['keyword'])){
						$data['keyword'] = str_replace('"', "'", $data['keyword']);
                    }
					$res = $this->grab($data);
                break;
            }

            echo intval($res);
            wp_die();
        }
			
        public function linking($id){
            
            $row = $this->wpdb->get_row($this->wpdb->prepare( 
                "SELECT * FROM `{$this->wpdb->prefix}internalinks` WHERE `id` = '%d' LIMIT 1", $id
            ));
            
            $linked_posts = (array)unserialize($row->posts);
            $linked_terms = (array)unserialize($row->terms);
            
            $keyword      = stripslashes(html_entity_decode($row->keyword));
            $linked_limit = $row->limit;
            $target       = $row->target;
            $qty          = intval($row->linked);
            $tag_open     = ($row->tag) ? "<$row->tag>" : '';
            $tag_close    = ($row->tag) ? "</$row->tag>" : '';
            
            if($keyword && $target){
                $exclude_tags = implode('|', array('a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre', 'code', 'kbd'));
                $search_regex = "/(?<!\p{L})({$keyword})(?!\p{L})(?!(?:(?!<\/?(?:{$exclude_tags}).*?>).)*<\/(?:{$exclude_tags}).*?>)(?![^<>]*>)/ui";
                $url_regex = sprintf('/' . str_replace(array('"', '/'), array('\"', '\/'), $this->urlTemplate) . '/', preg_quote($target, '/'), '(.*)');
                      
                foreach(get_post_types(array('public' => true), 'names') as $post_type){
                    if(empty($this->options['allowed_pt']) || in_array($post_type, $this->options['allowed_pt'])){
                        if($posts = $this->ilgen_get_posts($post_type)){
                            foreach($posts as $p){
                                $permalink = get_the_permalink($p->ID);
                                
                                if(!in_array($p->ID, $linked_posts) 
                                  && ($qty < $linked_limit || 0 == $linked_limit) 
                                  && stristr($p->post_content, $keyword) 
                                  && $this->ilgen_numlinks($p->post_content)
                                  && $target != $permalink){
                                    
                                    if(!preg_match($url_regex, $p->post_content, $match)){
                                        $content = preg_replace($search_regex, sprintf($this->urlTemplate, $target, $tag_open.'$0'.$tag_close), $p->post_content, 1, $count);
                                        if($count && wp_update_post(array('ID' => $p->ID, 'post_content' => $content))){ 
                                            $qty += $count; $linked_posts[] = $p->ID;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                foreach(get_taxonomies(array('public' => true), 'names') as $taxonomy){
                    if(empty($this->options['allowed_tx']) || in_array($taxonomy, $this->options['allowed_tx'])){
                        $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
                        if($terms && !is_wp_error($terms)){
                            foreach($terms as $t){
                                $permalink = get_term_link($t->term_id, $t->taxonomy);
                                $itemID = $t->term_id . $this->termDelimiter . $t->taxonomy;
                                
                                if(!in_array($itemID, $linked_terms) 
                                  && ($qty < $linked_limit || 0 == $linked_limit) 
                                  && stristr($t->description, $keyword) 
                                  && $this->ilgen_numlinks($t->description)
                                  && $target != $permalink){

                                    if(!preg_match($url_regex, $t->description, $match)){
                                        $content = preg_replace($search_regex, sprintf($this->urlTemplate, $target, $tag_open.'$0'.$tag_close), $t->description, 1, $count);
                                        
                                        if($count && wp_update_term($t->term_id, $t->taxonomy, array('description' => $content))){ 
                                            $qty += $count; $linked_terms[] = $itemID;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->wpdb->query( $this->wpdb->prepare(
                "UPDATE `{$this->wpdb->prefix}internalinks` " .
                "SET `linked` = '%d', `posts` = '%s', `terms` = '%s' " .
                "WHERE `id` = %d",
                $qty, serialize($linked_posts), serialize($linked_terms), $id
            ));
            
            return intval($qty);
        }
                
        public function unlinking($id){
            
            $row = $this->wpdb->get_row( $this->wpdb->prepare( 
                "SELECT * FROM `{$this->wpdb->prefix}internalinks` WHERE `id` = '%d' LIMIT 1", $id
            ));
            $qty = intval($row->linked);
            
            foreach(array('posts', 'terms') as $type){
                
                if($linked_posts = array_filter((array)unserialize($row->{$type}))){
                    
                    foreach($linked_posts as $k => $itemID){
                        
                        if(!$itemID) continue;
                        
                        if('terms' == $type){
                            $itemIDs = explode($this->termDelimiter, $itemID);
                            $term = get_term($itemIDs[0], $itemIDs[1]);
                            $content = $term->description;
                        }else{
                            $content = get_post_field('post_content', $itemID);
                        }
                        
                        if(preg_match_all("/{$this->urlPattern}/siU", $content, $matches, PREG_SET_ORDER)){
                            
                            foreach($matches as $match){
                                
                                if(!strpos($match[0], 'class="ilgen"')) continue;
                                
                                if(esc_url_raw($match[2]) == $row->target && $this->ilgen_prepare_keyword($match[3]) == stripslashes($row->keyword)){
                                    $content = str_replace($match[0], $match[3], $content, $count);
                                    
                                    if($count){
                                        
                                        if('terms' == $type) $result = wp_update_term($term->term_id, $term->taxonomy, array('description' => $content));
                                        else $result = wp_update_post(array('ID' => $itemID, 'post_content' => $content));
                                        
                                        if($result){ $qty -= 1; unset($linked_posts[$k]); }
                                    }
                                }
                            }
                        }
                    }
                    
                    $this->wpdb->query($this->wpdb->prepare(
                        "UPDATE `{$this->wpdb->prefix}internalinks` SET `linked` = '%d', `{$type}` = '%s' WHERE `id` = '%d'",
                        $qty, serialize($linked_posts), $id
                    ));
                }
            }
			
            return intval($qty);
        }
        
        public function grab($data = array()){
     
            $check = $this->ilgen_check_exists(
                $this->ilgen_prepare_keyword($data['keyword'])
            );
            
            if(!$check){
                $this->ilgen_insert_keyword($data['keyword'], $data['target']);
                $checkID = $this->wpdb->insert_id;
            }else{ 
                $checkID = $check; 
            }
            
            $row = $this->wpdb->get_row( $this->wpdb->prepare(
                "SELECT * FROM `{$this->wpdb->prefix}internalinks` WHERE `id` = '%d' LIMIT 1", $checkID
            ));

            $linked_posts = ($lp = array_filter((array)unserialize($row->{$data['type']}))) ? $lp : array();
            $target  = ('' != $row->target) ? $row->target : $data['target'];
            
            if('terms' == $data['type']){
                $itemID = $data['id'];
                $itemIDs = explode($this->termDelimiter, $itemID);
                $term = get_term(absint($itemIDs[0]), $itemIDs[1]);
                $content = $term->description;
                $permalink = get_term_link($term->term_id, $term->taxonomy);
            }else{
                $itemIDs = explode($this->termDelimiter, $data['id']);
                $itemID = absint($itemIDs[0]);
                $content = get_post_field('post_content', $itemID);
                $permalink = get_the_permalink($itemID);
            }
            
            if(preg_match_all("/{$this->urlPattern}/siU", $content, $matches, PREG_SET_ORDER)){
                
                foreach($matches as $match){
                    $match[2] = esc_url_raw($match[2]);
                    $match[3] = sanitize_text_field($match[3]);
                    
                    if($data['target'] == $match[2] && stripslashes($data['keyword']) == $match[3]){
                        
                        if(!in_array($itemID, $linked_posts) && $target && $target != $permalink){
                            $tag_open  = ($row->tag) ? "<{$row->tag}>" : '';
                            $tag_close = ($row->tag) ? "</{$row->tag}>" : '';
                            $replacer = sprintf($this->urlTemplate, $target, $tag_open . $match[3] . $tag_close);
                            $content = preg_replace('/' . preg_quote($match[0], '/') . '/', $replacer, $content, 1, $count);
                            
                            if($count){
                                
                                if('terms' == $data['type']) $res = wp_update_term($term->term_id, $term->taxonomy, array('description' => $content));
                                else $res = wp_update_post(array('ID' => $itemID, 'post_content' => $content));

                                $linked_posts[] = $itemID;
                                $this->wpdb->query( $this->wpdb->prepare(
                                    "UPDATE `{$this->wpdb->prefix}internalinks` " .
                                    "SET `limit` = '%d', `linked` = '%d', `{$data['type']}` = '%s' WHERE `id` = %d", 
                                    absint($row->limit + 1), absint($row->linked + 1), serialize($linked_posts), $row->id
                                ));
                            }
                        }else{
                            $content = str_replace($match[0], $match[3], $content);
                            
                            if('terms' == $data['type']) $res = wp_update_term($term->term_id, $term->taxonomy, array('description' => $content));
                            else $res = wp_update_post(array('ID' => $itemID, 'post_content' => $content));
                        }
                    }
                }
            }
                       
            return $res;
        }

        public function recount($id){
            
            $qty = 0;
            
            if($keyword = $this->ilgen_from_table('keyword', $id)){
                $keyword = html_entity_decode($keyword);
                
                foreach(get_post_types(array('public' => true), 'names') as $post_type){
                    
                    if(empty($this->options['allowed_pt']) || in_array($post_type, $this->options['allowed_pt'])){
                        
                        if($posts = $this->ilgen_get_posts($post_type)){
                            
                            foreach($posts as $p){
                                if(@preg_match_all('/(?<!\p{L})'.$keyword.'(?!\p{L})(?!([^<]+)?>)/iu', $p->post_content, $matches)){
                                    $qty += count($matches[0]);
                                }
                            }
                        }
                    }
                }
                
                foreach(get_taxonomies(array('public' => true), 'names') as $taxonomy){
                    
                    if(empty($this->options['allowed_tx']) || in_array($taxonomy, $this->options['allowed_tx'])){
                        $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
                        
                        if($terms && !is_wp_error($terms)){
                            
                            foreach($terms as $t){
                                
                                if(@preg_match_all('/(?<!\p{L})'.$keyword.'(?!\p{L})(?!([^<]+)?>)/iu', $t->description, $matches)){
                                    $qty += count($matches[0]);
                                }
                            }
                        }
                    }
                }
            }
            
            if($qty > 0){
                $result = $this->wpdb->query($this->wpdb->prepare(
                    "UPDATE `{$this->wpdb->prefix}internalinks` SET `count` = '%d' WHERE `id` = '%d'", $qty, $id
                ));
            }
            
            return $qty;
        }
        
        public function asearch(){
      
            $data = array();
            
            if($keyword = sanitize_text_field($_POST['keyword'])){
                $limits = array('before' => absint($_POST['before']), 'after' => absint($_POST['after']));
                
                if($key_phrases = $this->ilgen_search_anchor($keyword, $limits)){
                    
                    foreach($key_phrases as $ind => $phrase){
                        $words = explode(" ", $phrase);
                        
                        foreach($words as $k => $word){
                            $key_class = (stristr($keyword, $word)) ? 'ilgen-keyword' : '';
                            $words[$k] = sprintf(
                                '<a href="#" class="ilgen-found notin ' . $key_class 
                                    . '" id="formed_%1$d_set_%3$d" data-id="formed_%1$d" data-num="%3$d">%2$s</a>',
                                $ind, $word, $k
                            ); 
                        }
                        $data[$ind] = array('words' => implode(" ", $words)); 
                    }
                }
            }
            return $data;
        }
        
        public function targets_edit(){

        if($_POST['target_old'] && $_POST['target_new']){

                $new = esc_url_raw($_POST['target_new']);
                if($data = $this->ilgen_get_targets(array((object)array('target' => $_POST['target_old'])))){
                    foreach($data as $dt){
                        if($dt->keywords){
                            foreach($dt->keywords as $k){
                                $this->unlinking($k->id);
                                $this->ilgen_insert_keyword($k->keyword, $new, $k->limit, $k->tag, $k->id, $k->count);
                                $this->linking($k->id);
                            }
                        }
                    }
                    $this->ilgen_messages(11, 'updated');
                }else{
                    $this->ilgen_messages(11, 'warning');
                }
            }else{
                $this->ilgen_messages(11, 'warning');
            }
        }
		
        public function settings(){

            $this->options['numlinks'] = absint($_POST['numlinks']);
            $this->options['allowed_pt'] = @array_map('sanitize_title', $_POST['allowed_pt']);
            $this->options['allowed_tx'] = @array_map('sanitize_title', $_POST['allowed_tx']);
            $this->options['bugfixer'] = $_POST['bugfixer'];
            
            if(update_option('ilgen_options', $this->options)) $this->ilgen_messages(10, 'updated');
            else $this->ilgen_messages(10, 'warning');
        }
        
        public function ajax(){
            
            switch($_POST['type']){
                case 'asearch_add':
                    $this->ilgen_insert_keyword($_POST['keyword'], $_POST['target']);
                break;
                case 'keywords_del':
                    $id = absint($_POST['id']);
                    $this->unlinking($id);
                    $this->remove($id);
                break;
                default: wp_die();
            }
        }
		
        public function check_links_ajax(){

            $res = array();

            if($rows = $this->wpdb->get_results("SELECT * FROM `{$this->wpdb->prefix}internalinks` WHERE 1")){

                foreach($rows as $row){

                    foreach(array('posts', 'terms') as $type){

                        ${$type} = array_filter((array)unserialize($row->{$type}));

                        foreach(${$type} as $itemID){
                            
                            $check = false;
                            
                            if('terms' == $type){
                                $itemIDs = explode($this->termDelimiter, $itemID);
                                $term = get_term($itemIDs[0], $itemIDs[1]);
                                $content = $term->description;
                                $permalink = get_term_link($term->term_id, $term->taxonomy);
                            }else{
                                $content = get_post_field('post_content', $itemID);
                                $permalink = get_permalink($itemID);
                            }
                                                        
                            if(preg_match_all("/{$this->urlPattern}/siU", $content, $matches, PREG_SET_ORDER)){
                                
                                foreach($matches as $match){ 
                                    if( esc_url_raw($match[2]) == $row->target 
                                        && $this->ilgen_prepare_keyword($match[3]) == stripslashes($row->keyword) 
                                        && strpos($match[0], 'class="ilgen"') ){
                                            $check = true;
                                            break;
                                    }
                                }
                            }     
                            
                            if(!$check) $res[] = array($row->id, $itemID, $row->keyword, $row->target, $permalink);
                        }
                    }
                }
            }
            
            printf(__('<p>Found %d mismatch(es)</p>%s', 'ilgen'), count($res), $this->ilgen_check_links_table($res));
            wp_die();
        }
        
        public function bugfixer(){
            
            $res = array();
            
            if($targets = array_map('trim', (array)explode("\n", $_POST['targets']))){

                foreach($targets as $target){

                    if($postID = url_to_postid( $target )){
                        $content = get_post_field('post_content', $postID);
                        
                        if(preg_match_all("/{$this->urlPattern}/siU", $content, $matches, PREG_SET_ORDER)){
                            
                            foreach($matches as $match){
                                
                                if(strpos($match[0], 'class="ilgen"')){
                                    $content = str_replace($match[0], '<a href="' . $match[2] . '">' . $match[3] . '</a>', $content);
                                    $result = wp_update_post(array('ID' => $postID, 'post_content' => $content));

                                    if( $result && $row = $this->wpdb->get_row( $this->wpdb->prepare( 
                                            "SELECT * FROM `{$this->wpdb->prefix}internalinks` WHERE `keyword` = '%s' LIMIT 1", 
                                            $this->ilgen_prepare_keyword($match[3])
                                    )) ){
                                        $linked_posts = (array)unserialize($row->posts);
                                        
                                        if(($key = array_search($postID, $linked_posts)) !== false) {
                                            unset($linked_posts[$key]);
                                            $this->wpdb->query($this->wpdb->prepare(
                                                "UPDATE `{$this->wpdb->prefix}internalinks` SET `linked` = '%d', `posts` = '%s' WHERE `id` = '%d'",
                                                absint($row->linked - 1), serialize($linked_posts), $row->id
                                            ));
                                        }
                                    }
                                    $res[] = $match[0];
                                }
                            }
                        }
                    }elseif($term = $this->ilgen_term_by_url( $target )){
                        $content = $term->description;
                        if(preg_match_all("/{$this->urlPattern}/siU", $content, $matches, PREG_SET_ORDER)){
                            foreach($matches as $match){
                                if(strpos($match[0], 'class="ilgen"')){
                                    $content = str_replace($match[0], '<a href="' . $match[2] . '">' . $match[3] . '</a>', $content);
                                    $result = wp_update_term($term->term_id, $term->taxonomy, array('description' => $content));

                                    if( $result && $row = $this->wpdb->get_row( $this->wpdb->prepare( 
                                        "SELECT * FROM `{$this->wpdb->prefix}internalinks` WHERE `keyword` = '%s' LIMIT 1", 
                                        $this->ilgen_prepare_keyword($match[3])
                                    )) ){
                                        $linked_posts = (array)unserialize($row->terms);
                                        if(($key = array_search("{$term->term_id}#{$term->taxonomy}", $linked_posts)) !== false) {
                                            unset($linked_posts[$key]);

                                            $this->wpdb->query($this->wpdb->prepare(
                                                "UPDATE `{$this->wpdb->prefix}internalinks` SET `linked` = '%d', `terms` = '%s' WHERE `id` = '%d'",
                                                absint($row->linked - 1), serialize($linked_posts), $row->id
                                            ));
                                        }
                                    }
                                    $res[] = $match[0];
                                }
                            }
                        }
                    }
                }
            }
            
            if($res)$this->ilgen_messages(3, 'updated');
            else $this->ilgen_messages(3, 'warning');
        }
        
        /* support functions */
        
        public function ilgen_get_posts($post_type){
			
            return $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT `ID`, `post_content` " .
                "FROM `{$this->wpdb->prefix}posts` " .
                "WHERE `post_type` = '%s' AND `post_status` IN ('publish', 'pending', 'draft', 'future')", 
                $post_type
            ));
        }
        
        public function ilgen_check_exists($value, $column = 'keyword'){
            
            $row = $this->wpdb->get_row( $this->wpdb->prepare(
                "SELECT `id` FROM `{$this->wpdb->prefix}internalinks` WHERE `{$column}` = '%s' LIMIT 1", $value
            ));
            
            if(is_null($row)) return false;
            else return $row->id;
        }
		
		public function ilgen_check_entry($column, $value, $type, $pid){

			if( $rows = $this->wpdb->get_results( $this->wpdb->prepare(
                "SELECT `{$type}` FROM `{$this->wpdb->prefix}internalinks` " .
				"WHERE `{$column}` = '%s'", $value
            )) ){ 
				foreach($rows as $row){
					if(in_array($pid, (array)unserialize($row->{$type}))){
						return true;
					}
				}
			}
			return false;
		}
        
        public function ilgen_insert_keyword($keyword, $target = '', $limit = 1, $tag = '', $id = null, $count = null){
            
            if(is_null($id)){
                $keyword = $this->ilgen_prepare_keyword($keyword);
                $check_id = $this->ilgen_check_exists($keyword);
                
                if(!$check_id && $keyword){
                    $query = $this->wpdb->prepare(
                        "INSERT INTO `{$this->wpdb->prefix}internalinks` (`keyword`, `target`, `limit`) " .
                        "VALUES ('%s', '%s', '%d')", $keyword, esc_url_raw($target), absint($limit)
                    );
                }
            }
            else{
                $query = $this->wpdb->prepare(
                    "UPDATE `{$this->wpdb->prefix}internalinks` " .
                    "SET `target` = '%s', `limit` = '%d', `tag` = '%s' WHERE `id` = '%d'", 
                    esc_url_raw($target), absint($limit), $tag, absint($id)
                );
            }
            $result = $this->wpdb->query($query);
            return $result;
        }
        
        public function ilgen_insert_target($target){
            
            $target = esc_url_raw($target);
            $check_id = $this->ilgen_check_exists($target, 'target');
            
            if(!$check_id){
                return $this->wpdb->query( $this->wpdb->prepare(
                    "INSERT INTO `{$this->wpdb->prefix}internalinks` (`target`) VALUES ('%s')", $target
                ));
            }
        }
        
        public function ilgen_get_targets($targets = array()){
            
            $data = array();
			
            if(empty($targets)){
                $targets = $this->wpdb->get_results(
                    "SELECT DISTINCT `target` FROM `{$this->wpdb->prefix}internalinks`"
                );
            }
            if(!empty($targets)){
                foreach($targets as $t){
                    $t->keywords = $this->wpdb->get_results( $this->wpdb->prepare(
                        "SELECT * FROM `{$this->wpdb->prefix}internalinks` " .
                        "WHERE `target` = '%s' ORDER BY `keyword` ASC", $t->target
                    ));
                    $data[] = $t;
                }
            }
            return $data;
        }
        
        public function ilgen_get_ordered_targets($order = '', $filter = ''){

            $data = array('int' => array(), 'ext' => array());
            $parentUrl = get_bloginfo('url');
            
            if($targets = $this->ilgen_get_targets()){

                foreach($targets as $k => $t){
                    if(!$t->target) continue;
                    $type = ( stristr($t->target, $parentUrl) 
                        || !preg_match('/^(http|https):\\/\\/.*/', $t->target, $match) 
                    ) ? 'int' : 'ext';
                    if($filter && !stristr($t->target, $filter)) continue;
                    
                    $data[$type][$k] = array('target' => $t->target, 'keywords' => array(), 'count' => 0);
                    if($t->keywords){
                        foreach($t->keywords as $kw){
                            /*if(!$kw->linked) continue;*/
                            $data[$type][$k]['keywords'][] = $kw;
                            $data[$type][$k]['count'] += $kw->linked;
                        }
                    }
                }
            }
            if($data && $order){
                $order = explode('By', $order);
                $data['int'] = $this->ilgen_order_by($data['int'], $order[0], (($order[1] == 'DESC') ? SORT_DESC : SORT_ASC));
                $data['ext'] = $this->ilgen_order_by($data['ext'], $order[0], (($order[1] == 'DESC') ? SORT_DESC : SORT_ASC));
            }
			
            return $data;
        }
        
        public function ilgen_get_page($template_data = array()){?>
            <div class="ilgen wrap">
                <div class="ilgen-donate">
                    <a href="https://www.paypal.me/MaxKondrachuk" target="_blank">
                        <img src="<?= plugins_url( 'images/donate.png', plugin_basename( __FILE__ ) )?>">
                    </a>
                </div>
                <h2><?php _e('Internal Links Generator', 'ilgen')?></h2>
                <h3 class="nav-tab-wrapper">
                <?php foreach ( $this->settings_tabs as $tab_key => $tab_caption ):
                    $active = ($this->tab == $tab_key) ? 'nav-tab-active' : '';?>
                    <a class="nav-tab <?= $active?>" href="options-general.php?page=internal_links_generator&tab=<?= $tab_key?>"><?= $tab_caption ?></a>
                    <?php endforeach;?>
                </h3>
                <?php @include(sprintf("%s/templates/%s.php", dirname(__FILE__), $this->tab));?>
            </div>
        <?php }
               
        public function ilgen_from_table($column, $id){
            $row = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT `{$column}` FROM `{$this->wpdb->prefix}internalinks` " .
                "WHERE `id` = '%d' LIMIT 1", $id
            ), ARRAY_N);
            
            if(is_null($row)) return false;
            else return $row[0];
        }
        
        public function ilgen_grabb_links(){
            
            $data = array('posts' => array(), 'terms' => array());
                       
            foreach(get_post_types(array('public' => true), 'names') as $post_type){
                if(empty($this->options['allowed_pt']) || in_array($post_type, $this->options['allowed_pt'])){
                    if($posts = $this->ilgen_get_posts($post_type)){
                        foreach($posts as $p){
                            if($p->post_content && preg_match_all("/{$this->urlPattern}/siU", $p->post_content, $matches, PREG_SET_ORDER)){
                                foreach($matches as $match){
                                    
									if( ( preg_match('/<\s?(img|div|span)[^>]+\>/siU', $match[3], $unmatch) ) ||
										( preg_match('/^#.*/', $match[2], $unmatch) ) ||
										( strpos($match[0], 'class="ilgen"') && $this->ilgen_check_entry('target', esc_url_raw($match[2]), 'posts', $p->ID) )
									) continue;
									
                                    $data['posts'][$p->ID . $this->termDelimiter . $post_type][] = array(esc_url_raw($match[2]), sanitize_text_field($match[3]));
                                }
                            }
                        }
                    }
                }
            }
            
            foreach(get_taxonomies(array('public' => true), 'names') as $taxonomy){
                if(empty($this->options['allowed_tx']) || in_array($taxonomy, $this->options['allowed_tx'])){
                    $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
                    if($terms && !is_wp_error($terms)){
                        foreach($terms as $t){
                            if($t->description && preg_match_all("/{$this->urlPattern}/siU", $t->description, $matches, PREG_SET_ORDER)){
                                foreach($matches as $match){
                                    
									if( ( preg_match('/<\s?(img|div|span)[^>]+\>/siU', $match[3], $unmatch) ) ||
										( preg_match('/^#.*/', $match[2], $unmatch) ) ||
										( strpos($match[0], 'class="ilgen"') && $this->ilgen_check_entry('target', esc_url_raw($match[2]), 'terms', $t->term_id . $this->termDelimiter . $t->taxonomy) )
									) continue;
									
                                    $data['terms'][$t->term_id . $this->termDelimiter . $t->taxonomy][] = array(esc_url_raw($match[2]), sanitize_text_field($match[3]));
                                }
                            }
                        }
                    }
                }
            }
            
            return $data;
        }
        
        public function ilgen_search_anchor($keyword, $limits){
            
            $data = array();
            
            for($i=0; $i<$limits['before']; $i++){
                $before .= '(?:[\w+]+)\s';
            }
            for($j=0; $j<$limits['after']; $j++){
                $after .= '\s(?:[\w-]+)';
            }
            
            if(strpos($keyword, '*')){
                $keyword = str_replace('*', '', $keyword);
                $pattern = '/%s(?<!\p{L})%s([^\W|\s]+)%s/iu';
            }else{
                $pattern = '/%s(?<!\p{L})%s(?!\p{L})%s/iu';
            }
            
            foreach(get_post_types(array('public' => true), 'names') as $post_type){
                if(empty($this->options['allowed_pt']) || in_array($post_type, $this->options['allowed_pt'])){
                    if($posts = $this->ilgen_get_posts($post_type)){
                        foreach($posts as $p){
                            if(preg_match_all( sprintf($pattern, $before, $keyword, $after), $p->post_content, $matches)){
                                $data[] = mb_convert_case(trim($matches[0][0]), MB_CASE_LOWER, "UTF-8" );
                            }
                            unset($matches);
                        }
                    }
                }
            }
            
            foreach(get_taxonomies(array('public' => true), 'names') as $taxonomy){
                if(empty($this->options['allowed_tx']) || in_array($taxonomy, $this->options['allowed_tx'])){
                    $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
                    if($terms && !is_wp_error($terms)){
                        foreach($terms as $t){
                            if(preg_match_all( sprintf($pattern, $before, $keyword, $after), $t->description, $matches)){
                                $data[] = mb_convert_case(trim($matches[0][0]), MB_CASE_LOWER, "UTF-8");
                            }
                            unset($matches);
                        }
                    }
                }
            }
                      
            return array_unique($data);
        }
        
        public function ilgen_numlinks($content = ''){
            $check = true;
            if($this->options['numlinks'] > 0){
                @preg_match_all("/class=\"ilgen\"/iu", $content, $matches);
                if(sizeof($matches, 1) - 1 >= $this->options['numlinks']) $check = false;
            }
            return $check;
        }
        
        public function ilgen_prepare_keyword($keyword){
            
            $keyword = mb_convert_case($keyword, MB_CASE_LOWER, "UTF-8");
            $keyword = sanitize_text_field($keyword);
            $keyword = htmlentities($keyword);
            
            return $keyword;
        }
        
        public function ilgen_messages($num, $type = ''){
            if('updated' === $type){
                switch($num){
                    case 1: $details = __('Keywords imported!', 'ilgen'); break;
                    case 2: $details = __('Keywords imported!', 'ilgen'); break;
                    default: $details = '';
                }
                $message = sprintf(__('Operation is successfull! %s', 'ilgen'), $details);
            }
            else{
                switch($num){
                    case 1: $details = __('Keywords not imported!', 'ilgen'); break;
                    case 2: $details = __('Keywords not imported!', 'ilgen'); break;
                    default: $details = '';
                }
                $message = sprintf(__('Operation currupted! %s', 'ilgen'), $details);
            }
            echo '<div id="message" class="' . $type . '" notice is-dismissible"><p>' . $message . '</p></div>';
        }
        
        public function ilgen_is_writable($filename) {
            if(!is_writable($filename)) {
                if(!@chmod($filename, 0666)) {
                    $pathtofilename = dirname($filename);
                    if(!is_writable($pathtofilename)) {
                        if(!@chmod($pathtoffilename, 0666)) {
                            return false;
                        }
                    }
                }
            }
            return true;
        }
        
        public function ilgen_order_by(){
            $args = func_get_args();
            $data = array_shift($args);
            foreach ($args as $n => $field) {
                if (is_string($field)) {
                    $tmp = array();
                    foreach ($data as $key => $row)
                        $tmp[$key] = $row[$field];
                    $args[$n] = $tmp;
                }
            }
            $args[] = &$data;
            call_user_func_array('array_multisort', $args);
            return array_pop($args);
        }
        
        public function ilgen_term_by_url($url = ''){
			
            foreach(get_taxonomies(array('public' => true), 'names') as $taxonomy){
                if($terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false))){
                    foreach($terms as $term){
                        if($url == get_term_link($term->term_id, $term->taxonomy)){
                            return $term;
                        }
                    }
                }
            }
        }
        
       public function ilgen_check_links_table($rows = array()){
            
            $table = '';
            
            if($rows){
                $table .= '<table class="wp-list-table widefat fixed check-links-table">';
                $table .= '<thead><tr>';
                $table .= '<th>' . __('Keyword', 'ilgen') . '</th>';
                $table .= '<th>' . __('Target', 'ilgen') . '</th>';
                $table .= '<th>' . __('Path', 'ilgen') . '</th>';
                $table .= '<th></th>';
                $table .= '</tr></thead>';
                
                foreach($rows as $r){
                    $table .= '<tr>';
                    $table .= '<td>' . stripslashes($r[2]) . '</td>';
                    $table .= '<td>' . $r[3] . '</td>';
                    $table .= '<td><a href="' . $r[4] . '" target="_blank">' . $r[4] . '</td>';
                    $table .= '<td><button class="button button-small" onclick="fixLinks(this);" data-id="' . $r[0] . '" data-item="' . $r[1] . '">' . __('Fix') . '</button></td>';
                    $table .= '</tr>';
                }
                
                $table .= '</tbody>';
                $table .= '</table>';
            }
            
            return $table;
        }
        
        public function fix_links_ajax(){
			
            if($row = $this->wpdb->get_row($this->wpdb->prepare( 
                "SELECT * FROM `{$this->wpdb->prefix}internalinks` WHERE `id` = '%d' LIMIT 1", intval($_POST['postdata']['id'])
            ))){
				
                $itemID = sanitize_text_field($_POST['postdata']['item']);
                $linked_posts = (array)unserialize($row->posts);
                $linked_terms = (array)unserialize($row->terms); 

                $exclude_tags = implode('|', array('a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre', 'code', 'kbd'));
                $search_regex = "/(?<!\p{L})({$row->keyword})(?!\p{L})(?!(?:(?!<\/?(?:{$exclude_tags}).*?>).)*<\/(?:{$exclude_tags}).*?>)(?![^<>]*>)/ui";
                $url_regex = sprintf('/' . str_replace(array('"', '/'), array('\"', '\/'), $this->urlTemplate) . '/', preg_quote($row->target, '/'), '(.*)');

                if(in_array($itemID, $linked_posts)){
                    $type = 'posts';
                    $content = get_post_field('post_content', intval($_POST['postdata']['item']));
                }elseif(in_array($itemID, $linked_terms)){
                    $type = 'terms';
                    $itemIDs = explode($this->termDelimiter, $itemID);
                    $term = get_term(intval($itemIDs[0]), $itemIDs[1]);
                    $content = $term->description;
                }

                if($content){
                    if(!preg_match($url_regex, $content, $match)){
                        $content = preg_replace($search_regex, sprintf('<a href="%s">%s</a>', $row->target, "$0"), $content, 1, $count);
                    }else{
                        if($this->ilgen_prepare_keyword($match[1]) == htmlentities($row->keyword)){
                            $content = str_replace($match[0], sprintf('<a href="%s">%s</a>', $row->target, trim($match[1])), $content);
                        }
                    }
                    if('posts' == $type) wp_update_post(array('ID' => intval($_POST['postdata']['item']), 'post_content' => $content));
                    elseif('terms' == $type) wp_update_term($term->term_id, $term->taxonomy, array('description' => $content));
                }

                if(($key = array_search($itemID, ${'linked_' . $type})) !== false){
                    unset(${'linked_' . $type}[$key]);
                    $res = $this->wpdb->query($this->wpdb->prepare(
                        "UPDATE `{$this->wpdb->prefix}internalinks` SET `linked` = '%d', `{$type}` = '%s' WHERE `id` = '%d'",
                        absint($row->linked - 1), serialize(${'linked_' . $type}), $row->id
                    ));
                }
            }
	    
            echo intval($res);
            wp_die();
        }
    }
}
