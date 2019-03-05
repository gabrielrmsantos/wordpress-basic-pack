<?php
define('option_group_name','clogica_option_group');
if(!class_exists('clogica_SR_redirect_cache')){
    class clogica_SR_redirect_cache {
        
        public static $show_notifications=true;
        private $option_group_name='clogica_option_group';
        private $cf;
        private $request;
 
        
        /* Set the object's parent cf to access all objects ------------------  */        
        public function set_cf($cf)
        {
            $this->cf=$cf;
            $this->request= call_user_func(array($cf, 'get_request'));
            //$this->request = $cf::get_request();
        }
        
        /* initialization --------------------------------------------------  */
        public function init($option_group_name)
        {
            $this->option_group_name=$option_group_name;
        }
        
        /* set_option_group --------------------------------------------------  */
        public static function set_option_group($option_group_name)
        {
            $this->option_group_name=$option_group_name;
        }

        /* get_option_group --------------------------------------------------  */
        public static function get_option_group()
        {
            return option_group_name;
        }

        /* update_my_options -------------------------------------------------  */
        public static function update_my_options($options,$blog=0)
        {
            if(intval($blog)<=0)
            {
                update_site_option(self::get_option_group(),$options);
            }else
            {
                update_blog_option($blog, self::get_option_group(), $options);
            }

        }

        /* get_my_options ----------------------------------------------------  */
        public static function get_my_options($blog=0)
        {
            if(intval($blog)<=0)
            {
                $options=get_site_option(self::get_option_group());
                if(!is_array($options))
                {
                    $options= array();
                    add_site_option(self::get_option_group(),$options);
                }
                return $options;
            }else
            {
                $options=get_blog_option($blog, self::get_option_group());
                if(!is_array($options))
                {
                    $options= array();
                    add_blog_option($blog, self::get_option_group(),$options);
                }
                return $options;
            }
        }

        /* read_option_value -------------------------------------------------  */
        public static function read_option_value($key,$default='',$blog=0)
        {
            $options=self::get_my_options($blog);
            if(array_key_exists($key,$options))
            {
                return $options[$key];
            }else
            {
                self::save_option_value($key,$default,$blog);
                return $default;
            }
        }

        /* save_option_value -------------------------------------------------  */
        public static function save_option_value($key,$value,$blog=0)
        {
            $options=self::get_my_options($blog);
            $options[$key]=$value;
            self::update_my_options($options,$blog);
        }

        /* save_post_option_value -------------------------------------------------  */
        public function save_post_option_value($key,$type='text',$blog=0)
        {
            $options=self::get_my_options($blog);
            $options[$key]=$this->request->post($key,$type);
            $this->update_my_options($options,$blog);
        }

        /* save_get_option_value --------------------------------------------------  */
        public function save_get_option_value($key,$type='text',$blog=0)
        {
            $options=self::get_my_options($blog);
            $options[$key]=$this->request->get($key,$type);
            self::update_my_options($options,$blog);
        }

        /* delete_my_options -----------------------------------------------------  */
        public function delete_my_options($blog)
        {
            if(intval($blog)<=0)
            {
                delete_site_option(self::get_option_group());
            }else
            {
                delete_blog_option($blog,self::get_option_group());
            }
        }

        /*- Add Redirect ----------------------------------------*/
        public function add_redirect($post_id,$is_redirected,$redirect_from,$redirect_to,$redirect_type=301)
        {
           if(self::cache_enabled()){
            global $wpdb,$table_prefix;
            $table_name = $table_prefix."WP_SEO_Cache";
            $wpdb->query(" insert IGNORE into $table_name(ID,is_redirected,redirect_from,redirect_to,redirect_type) values('$post_id','$is_redirected','$redirect_from','$redirect_to','$redirect_type'); ");
           }
        }

        /*- Fetch Redirect ----------------------------------------*/
        public function fetch_redirect($post_id)
        {
            global $wpdb,$table_prefix;
            $table_name = $table_prefix."WP_SEO_Cache";
            return $wpdb->get_row("select *  from  $table_name where ID='$post_id'; ");
        }

        /*- Redirect Cache ----------------------------------------*/
        public function redirect_cached($post_id)
        {
            $redirect = self::fetch_redirect($post_id);
            if($redirect != null && $redirect->redirect_from==self::get_current_relative_url() && self::cache_enabled())
            {                
                
                if($redirect->is_redirected==1)
                {
                    if($redirect->redirect_type==301)
                    {
                        header ('HTTP/1.1 301 Moved Permanently');
                        header ("Location: " . $redirect->redirect_to);
                        exit();
                    }
                    else if($redirect->redirect_type==307)
                    {
                        header ('HTTP/1.0 307 Temporary Redirect');
                        header ("Location: " . $redirect->redirect_to);
                        exit();
                    }
                    else if($redirect->redirect_type==302)
                    {
                        header ("Location: " . $redirect->redirect_to);
                        exit();
                    }
                }
                return 'not_redirected';
            }
            return 'not_found';
        }

        /*- Delete Redirect ----------------------------------------*/
        public function del_redirect($post_id)
        {
            global $wpdb,$table_prefix;
            $table_name = $table_prefix."WP_SEO_Cache";
            return $wpdb->get_var("delete from  $table_name where ID='$post_id'; ");
        }

        /*- Free Cache ----------------------------------------*/
        public static function free_cache($force=0)
        {
            if(self::cache_enabled() || $force==1){
            global $wpdb,$table_prefix;
            $table_name = $table_prefix."WP_SEO_Cache";
            $wpdb->query("delete * from $table_name");
            if(self::show_notifications() && $force!=2)
            echo "<b>".__("All cached redirects are deleted!",'wsr')."</b>";
            }
        }
        
        /*- Free Cache ----------------------------------------*/
        public static function free_cache_without_notification(){
            self::free_cache(2);  
        }

        /*- Cache Count ----------------------------------------*/
        public function count_cache()
        {
          global $wpdb,$table_prefix;
            $table_name = $table_prefix."WP_SEO_Cache";
            return $wpdb->get_var("select count(*) as cnt from  $table_name ");
        }
        
        
        /* ----------------------------------------------- */
         public static function show_notifications()
         {
             return self::$show_notifications;
         }
         
         /* ----------------------------------------------- */
         public static function cache_enabled()
         {
             return (self::read_option_value("cache_enable")!=0);
         }
         
         

    }}