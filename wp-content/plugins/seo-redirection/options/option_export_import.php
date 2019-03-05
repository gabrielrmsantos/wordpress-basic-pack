<?php
global $wpdb, $table_prefix;
require_once PATH . "custom/lib/cf.SR_redirect_cache.class.php";
require_once PATH . "cf/lib/cf.jforms.class.php";
require_once PATH . "cf/lib/forms/cf.dropdownlist.class.php";
require_once PATH . "cf/lib/forms/cf.bcheckbox_option.class.php";

$SR_jforms = new jforms();
$SR_redirect_cache = new clogica_SR_redirect_cache();

function get_current_parameters($remove_parameter = "") {
    if ($_SERVER['QUERY_STRING'] != '') {
        $qry = '?' . urldecode($_SERVER['QUERY_STRING']);
        if (is_array($remove_parameter)) {
            for ($i = 0; $i < count($remove_parameter); $i++) {
                if (array_key_exists($remove_parameter[$i], $_GET)) {
                    $string_remove = '&' . $remove_parameter[$i] . "=" . urldecode($_GET[$remove_parameter[$i]]);
                    $qry = str_ireplace($string_remove, "", $qry);
                    $string_remove = '?' . $remove_parameter[$i] . "=" . urldecode($_GET[$remove_parameter[$i]]);
                    $qry = str_ireplace($string_remove, "", $qry);
                }
            }
        } else {
            if ($remove_parameter != '') {
                if (array_key_exists($remove_parameter, $_GET)) {
                    $string_remove = '&' . $remove_parameter . "=" . urldecode($_GET[$remove_parameter]);
                    $qry = str_ireplace($string_remove, "", $qry);
                    $string_remove = '?' . $remove_parameter . "=" . urldecode($_GET[$remove_parameter]);
                    $qry = str_ireplace($string_remove, "", $qry);
                }
            }
        }
        return $qry;
    } else {
        return "";
    }
}

function echo_message($msgtxt, $type = 'success') {
    $css = $type;
    $icon = "";
    if ($type == 'updated' || $type == 'success') {
        $css = 'success';
        $icon = "<span class=\"glyphicon glyphicon-ok\"></span>";
    } else if ($type == 'error' || $type == 'danger') {
        $css = 'danger';
        $icon = "<span class=\"glyphicon glyphicon-warning-sign\"></span>";
    }

    echo '<div class="alert alert-' . $css . '" role="alert">' . $icon . ' ' . $msgtxt . '</div>';
}

function csv_arr($file_name) {
    $arrResult = array();
    $handle = fopen($file_name, "r");
    if ($handle) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $arrResult[] = $data;
        }
        fclose($handle);
    }
    return $arrResult;
}

function add_csv_mime_upload_mimes($existing_mimes) {
    $existing_mimes['csv'] = 'application/octet-stream'; //allow CSV files
    return $existing_mimes;
}

if ( isset($_POST['btn_import']) && $_POST['btn_import'] != '') {
    add_filter('upload_mimes', 'add_csv_mime_upload_mimes');

    if (array_key_exists('import_file', $_FILES) && $_FILES['import_file']['name'] != '') {
        $filename = $_FILES['import_file']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (strtolower($ext) == 'csv') {
            if (!function_exists('wp_handle_upload')) {
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
            }

            $uploadedfile = $_FILES['import_file'];
            $upload_overrides = array('test_form' => false);
            $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
            if ($movefile && !isset($movefile['error'])) {


                echo_message(__("File is valid, and was successfully uploaded.", 'wsr'));
                $results = csv_arr($movefile['file']);

                // start add to database ----------------------------------

                $index = 0;

                //if($request->post('col_names','int')!=0) $index++;
                if ($_POST['col_names'] != 0)
                    $index++;

                $errors = 0;
                $exist = 0;
                $new = 0;
                //$grpID=$request->post('grpID','int');
                $grpID = isset($_POST['grpID'])?$_POST['grpID']:'';


                for ($i = $index; $i < count($results); $i++) {

                    $sql = "";
                    $redirect_from_type = 'Page';
                    $redirect_to_type = 'Page';
                    $redirect_from_folder_settings = '1';
                    $redirect_from_subfolders = '0';
                    $redirect_to_folder_settings = '1';
                    $redirect_type = '301';
                    $regex = '';
                    $redirect_from = '';
                    $redirect_to = '';

                    if (count($results[$i]) > 0)
                        $redirect_from = $results[$i][0];

                    if (count($results[$i]) > 1)
                        $redirect_to = $results[$i][1];

                    if (count($results[$i]) > 2)
                        $redirect_type = $results[$i][2];

                    if (count($results[$i]) > 3)
                        $redirect_from_type = $results[$i][3];

                    if (count($results[$i]) > 4)
                        $redirect_from_folder_settings = $results[$i][4];

                    if (count($results[$i]) > 5)
                        $redirect_from_subfolders = $results[$i][5];

                    if (count($results[$i]) > 6)
                        $redirect_to_type = $results[$i][6];

                    if (count($results[$i]) > 7)
                        $redirect_to_folder_settings = $results[$i][7];

                    if (count($results[$i]) > 8)
                        $regex = $results[$i][8];

                    if ($redirect_from != '' && $redirect_to != '' && intval($redirect_type) != 0) {
                        $exec = 0;
                        $seo_table = $table_prefix . "WP_SEO_Redirection";
                        if ($wpdb->get_var(" select redirect_from from $seo_table where redirect_from='$redirect_from'")) {
                            $exist++;
                            //if($request->post('rule')=='replace')
                            if ($_POST['rule'] == 'replace') {
                                $wpdb->get_var(" delete from $seo_table where redirect_from='$redirect_from'");
                                $exec = 1;
                            }
                        } else {
                            $exec = 1;
                            $new++;
                        }

                        if ($exec == 1) {

                            $wpdb->insert($seo_table, array(
                                "redirect_from" => $redirect_from,
                                "redirect_to" => $redirect_to,
                                "redirect_type" => $redirect_type,
                                "redirect_from_type" => $redirect_from_type,
                                "redirect_from_folder_settings" => $redirect_from_folder_settings,
                                "redirect_from_subfolders" => $redirect_from_subfolders,
                                "redirect_to_type" => $redirect_to_type,
                                "redirect_to_folder_settings" => $redirect_to_folder_settings,
                                "regex" => $regex,
                            ));
                        }
                    } else {
                        echo "err";
                        $errors++;
                    }
                }

                $report = intval($errors + $exist + $new) . " redirects are imported with $errors errors,$new new redirects and $exist are ";
                //if($request->post('rule')=='replace')
                if ($_POST['rule'] == 'replace') {
                    $report = $report . 'replaced!';
                } else {
                    $report = $report . 'skipped!';
                }

                echo_message($report);

                // end the entrance to database ---------------------------


                unlink($movefile['file']);
                echo_message(__("File is deleted!", 'wsr'));
                $SR_redirect_cache->free_cache();
            } else {
                echo $movefile['error'];
            }
        } else {
            echo_message(__("Please choose a CSV file", 'wsr'), 'danger');
        }
    } else {
        echo_message(__("You need to select a file to upload it!", 'wsr'), 'danger');
    }
}
?>

<h3><?php _e("Export Redirects", 'wsr') ?></h3><hr/>


<form id="export" target="_blank" action="<?php echo URL ?>custom/export/csv.php" method="post" class="form-horizontal" role="form" data-toggle="validator">

    <table cellpadding="10">
        <tr>
            <td>
                <label class="control-label col-sm-2" for="export_file_type"><?php _e("Output Type:", 'wsr') ?></label>
            </td>
            <td>
                <?php
                $drop = new dropdown_list('export_file_type');
                $drop->add('CSV', 'csv');
                $drop->run($SR_jforms);
                ?>

            </td>
        </tr>
    </table>
    <br>
    <input type="hidden" name="blog" value="<?php echo get_current_blog_id() ?>"/>
    <button type="submit" class="button-secondary" name="btn_export" value="btn_export"><span class="dashicons dashicons-admin-links" style="padding-top: 3px;"></span>&nbsp;<?php _e("Export", 'wsr'); ?>&nbsp; </button>

</form>
<br>
<h3><?php _e("Import Redirects", 'wsr'); ?></h3><hr/>

<form id="import" name="import" enctype='multipart/form-data' action="<?php echo get_current_parameters(array("add", "edit", "del")); ?>" method="post" class="form-horizontal" role="form" data-toggle="validator">

    <table cellpadding="10">
        <tr>
            <td><label class="control-label col-sm-2" for="import_file_type"><?php _e("File Type:", 'wsr') ?></label></td>
            <td>
                <?php
                $drop = new dropdown_list('import_file_type');
                $drop->add(__('CSV', 'wsr'), 'csv');
                $drop->run($SR_jforms);
                ?>
            </td>
        </tr>
        <tr>
            <td><label class="control-label col-sm-2" for="file"><?php _e("Choose File:", 'wsr'); ?></label></td>
            <td>
                <input class="btn btn-default btn-sm" type="file" accept="text/csv" id="import_file" name="import_file" required/>
            </td>
        </tr>
        <tr>
            <td><label class="control-label col-sm-2" for="Rule"><?php _e("Column Titles:", 'wsr') ?> </label></td>
            <td>
                <?php
                $check = new bcheckbox_option();
                $check->create_single_option('col_names', 1);
                echo __(" Skip the first row of the file (if there is a table header)", 'wsr');
                ?>
            </td>
        </tr>
        <tr>
            <td>  <label class="control-label col-sm-2" for="Rule"><?php _e("Import Rule:", 'wsr'); ?></label></td>
            <td>
                <?php
                $drop = new dropdown_list('rule');
                $drop->add(__('Skip the existing redirects with the same source', 'wsr'), 'skip');
                $drop->add(__('Replace the existing redirects with the same source', 'wsr'), 'replace');
                $drop->run($SR_jforms);
                ?>
            </td>
        </tr>
    </table>

    <input type="hidden" name="MAX_FILE_SIZE" value="999000000" />
    <br>
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-12">
            <button class="button" type="submit" name="btn_import" value="btn_import"><span  style="padding-top: 3px;" class="dashicons dashicons-migrate"></span>&nbsp;<?php _e("Import", 'wsr') ?></button>
        </div>
    </div>
    <br/>
    <h3><a target="_blank" href="<?php echo URL . 'custom/export/sample.csv' ?>">Sample Csv File</a></h3>
    <div style="text-align: right"><?php _e("* Need Help?", 'wsr'); ?> <a target="_blank" href="http://www.clogica.com/kb/topics/seo-redirection-premium/export-import"><?php _e("click here to see info about import and export", "wsr"); ?></a></div>
    <br/>
</form>

<?php

