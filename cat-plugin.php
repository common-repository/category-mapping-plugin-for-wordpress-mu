<?php
/*
Plugin Name: WordPress MU Category Mapping Plugin
Plugin URI: http://webdevstudios.com/support/wordpress-plugins/
Description: A category mapping plugin allowing an easy way to create global categories so your top-level blog will act as a portal for your global community of blogs.
Version: 1.0
Author: WebDevStudios.com
Author URI: http://webdevstudios.com

Funding for plugin provided by Margaret Roach http://margaretroach.com
*/

// Hook for adding admin menus
add_action('admin_menu', 'mt_add_pages');

function mt_add_pages() {

   	// Add a new top-level menu (ill-advised):
    add_menu_page('Category Mapping', 'Category Mapping', 8, __FILE__, 'mt_toplevel_page');

}

function mt_toplevel_page() {
	
	global $wpdb;
	$prefix = substr($wpdb->prefix, 0, stripos($wpdb->prefix, "_")+1);
	$table_name = $wpdb->prefix . "cat_mapping";
	$sql = "SELECT blog_id FROM $wpdb->blogs";
	$the_blog_ids = $wpdb->get_results($sql);
	if ($the_blog_ids) {
		$blog_id = array();
		foreach ($the_blog_ids as $the_blog_id) {	
			//build array of all WordPress MU blog IDs
			If ($the_blog_id->blog_id != 1) {
				$blog_id[] = $the_blog_id->blog_id;	
			}
		}
	}

	//loop through blog ID array to pull out categories for each blog
	$blog_cats = array();
	$blog_cat_ids = array();
	$blog_name = array();
	$blog_main_id = array();
	
	foreach($blog_id as $key  => $value) {
		//retrieve all categories for each individual blog
		$sql = "SELECT name, t.term_id, o.option_value FROM ". $prefix . $value ."_term_taxonomy tt
		INNER JOIN ". $prefix . $value ."_terms t ON tt.term_id = t.term_id
		INNER JOIN " . $prefix . $value ."_options o ON o.option_name = 'blogname'
		WHERE tt.taxonomy = 'category' 
		ORDER BY name "; 
		$the_cats = $wpdb->get_results($sql);
		if ($the_cats) {
			$x = 0;
			foreach ($the_cats as $the_cat) {
				//assign categories, category IDs, and blog names to individual arrays
				$blog_cats[] = $the_cat->name;
				$blog_cat_ids[] = $the_cat->term_id;
				$blog_name[] = $the_cat->option_value;
				$blog_main_id[] = $value;
				$x++;
			}
		}
	}
	?>
        <div class="wrap">
        	<H2>WordPress MU Category Mapping Plugin</H2>
            <H4>Below is a list of all blogs in your WPMU network and their categories.  Select a top level category (created on your main blog) from the drop-down list and add a mapping.  Each blog post from that blog's category will now aggregate to the top level category you just assigned.  Multiple mappings can be created for each category.</H4>
			   <table width="100%" align="left">
               		<tr>
                    	<td width="20%"><strong>Category</strong></td>
                        <td width="20%"><strong>Top Level Category</strong></td>
                        <td width="20%"><strong>Add Mapping</strong></td>
                        <td><strong>Current Mappings</strong></td>
                    </tr>
					<?php
					foreach($blog_cats as $key  => $value) {
						//check if category is being deleted
						If ($_GET['action'] == "del" && $_POST["add_cat"] == "" && $isdeleted != true) { 
							$sql = "DELETE FROM ".$wpdb->prefix."cat_mapping
								WHERE id = ".$_GET['id'];
							$wpdb->get_results($sql);
							$isdeleted = true;
							echo "<div id=message class=updated fade>Mapping deleted successfully.</div>";
						//else check if form has been submitted
						}ElseIf ($_POST["add_cat"] != "") {
							//add category mapping to the table
							$select_var = "cat_".$blog_main_id[$key]."_".$blog_cat_ids[$key];
							$select_var = $_POST[$select_var];

							If ($select_var != "--select--") {
								$sql = "SELECT top_cat_id FROM ".$wpdb->prefix."cat_mapping 
									WHERE blog_id = ".$blog_main_id[$key]."
									AND sub_cat_id = ".$blog_cat_ids[$key]."
									AND top_cat_id = ".$select_var."";
								$chk_mapping = $wpdb->get_results($sql);
								if ($chk_mapping) {
									//record exists
									echo "<div id=message class=updated fade>Mapping already exists.</div>";
								}Elseif ($select_var != ""){
									//record does NOT exist so insert it
									$sql = "INSERT INTO ".$wpdb->prefix."cat_mapping (blog_id, top_cat_id, sub_cat_id)
										VALUES (".$blog_main_id[$key].",".$select_var.",".$blog_cat_ids[$key].")";
									$wpdb->get_results($sql);
									echo "<div id=message class=updated fade>Mapping added successfully.</div>";
								}
							}
						}
						
						If ($blog_name[$key] != $old_blog_name) {
						?>
                        	<tr>
                            	<td colspan="4"><HR></td>
                            </tr>
                        	<tr>
                            	<td colspan="4"><H3>Blog: <?php echo $blog_name[$key];?></H3></td>
                            </tr>
                        <?php
						}
                        ?>
                        <form method="post">
                           <tr>
                                <td><?php echo $value ?></td>
                                <?php
                                $sql = "SELECT name, t.term_id FROM " . $prefix . "1_term_taxonomy tt
                                    INNER JOIN " . $prefix . "1_terms t ON tt.term_id = t.term_id
                                    WHERE tt.taxonomy = 'category' AND parent = 0 AND t.term_id <> 0
                                    ORDER BY name ";
                                $cat_results = $wpdb->get_results($sql);
                                if ($cat_results) {
                                    $select_box = "<SELECT name=cat_".$blog_main_id[$key]."_".$blog_cat_ids[$key]."><OPTION>--select--</OPTION>";
                                    foreach ($cat_results as $cat_result) {	
                                        //build category select box from top level blog categories
                                        $select_box .= "<OPTION value=". $cat_result->term_id .">". $cat_result->name ."</OPTION>";	
										
											$sql = "SELECT name, t.term_id FROM " . $prefix . "1_term_taxonomy tt
												INNER JOIN " . $prefix . "1_terms t ON tt.term_id = t.term_id
												WHERE tt.taxonomy = 'category' AND parent = ".$cat_result->term_id."
												ORDER BY name ";
											$subcat_results = $wpdb->get_results($sql);
											if ($subcat_results) {
												foreach ($subcat_results as $subcat_result) {	
													$select_box .= "<OPTION value=". $subcat_result->term_id .">--". $subcat_result->name ."</OPTION>";	
												}
												
											}
                                    }
                                    $select_box .= "</SELECT>";
                                }
                                ?>
                                <td><?php echo $select_box; ?></td>
                                <td>
                                    <div class="submit" style="border-top:0px;margin:0px;padding:0px;">
                                    	<input type="submit" name="add_cat" value="add" />
                                    </div>
                                </td>
                                <td>
                                	<?php
									$sql = "SELECT m.ID, t.name, t.term_id FROM ". $prefix . "1_term_taxonomy tt
											INNER JOIN ". $prefix . "1_terms t ON tt.term_id = t.term_id
											INNER JOIN ".$wpdb->prefix."cat_mapping m ON t.term_id = m.top_cat_id 
											WHERE tt.taxonomy = 'category' 
											AND m.sub_cat_id=".$blog_cat_ids[$key]." AND m.blog_id=".$blog_main_id[$key]."
											ORDER BY name";
									$current_maps = $wpdb->get_results($sql);
									if ($current_maps) {
										echo "<div id=tagchecklist>";
										foreach ($current_maps as $current_map) {
											echo "<span style=font-size:14px;><a href=/wp-admin/admin.php?page=cat-plugin.php&action=del&id=".$current_map->ID." id=tag-check-0 class=ntdelbutton>X</a>&nbsp;".$current_map->name."</span>";
										}
										echo "</div>";
									}Else{
										echo "<i>no current mappings</i>";
									}
									?>
                                </td>
                           </tr>
                        </form>
                        <?php
						$old_blog_name = $blog_name[$key];
                    }
                    ?>
                    <tr>
                        <td colspan="4"><HR></td>
                    </tr>
			   </table>
               <p>For support please visit our <a href="http://webdevstudios.com/support/wordpress-plugins/" target="_blank">WordPress Plugins Support page</a> | Version 1.0 by <a href="http://webdevstudios.com/" title="WordPress Development and Design" target="_blank">WebDevStudios.com</a></p>
        </div>
<?php 
}
function get_data() {
	global $wpdb, $wpmuBaseTablePrefix;
	$map = create_map();
	if (!is_array($map)) return false;
	foreach($map as $item) {
			$sql = "SELECT * FROM `".$wpmuBaseTablePrefix.intval($item[0])."_posts` WHERE `ID` = '".intval($item[1])."'";
			$row = $wpdb->get_row($sql);
			if ($row->ID) {
				if (!$row->post_title) $row->post_title = $this->untitled;
				$row->blogid = intval($item[0]);
				$rows[] = $row;
			}
	}
	if ($rows) return $rows;
}

function create_map() {
	global $wpdb, $wpmuBaseTablePrefix;
	$current_url = rtrim($_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"], "/"); 
	$arr_current_url = split("/", $current_url);
	$thecategory = get_category_by_slug(end($arr_current_url));
	$posts_found = False;
		$sql = "SELECT DISTINCT `ID`,`post_date_gmt` , post_title 
			FROM wp_1_posts p
			INNER JOIN wp_1_term_relationships r ON p.id = r.object_id
			INNER JOIN wp_1_term_taxonomy tt ON r.term_taxonomy_id = tt.term_taxonomy_id
			INNER JOIN wp_1_terms t ON tt.term_id = t.term_id
			INNER JOIN wp_1_options o ON o.option_name = 'blogname'
			WHERE `post_status` = 'publish' AND (`post_type` = 'post' OR `post_type` = '') AND `post_date_gmt` < '".gmdate("Y-m-d H:i:s")."'
			AND tt.taxonomy = 'category' AND t.term_id = ".$thecategory->term_id."
			ORDER BY `post_date_gmt`, post_title DESC ";
			$results = $wpdb->get_results($sql);
			if ($results) {
				$title_used_id[] = "";
				foreach($results as $result) {
					If (!in_array($result->ID, $title_used_id)) {
						$map[] = array(1,$result->ID,$result->post_date_gmt);
						$ID[] = $result->ID;
						$date_gmt[] = $result->post_date_gmt;
						$title_used_id[] = $result->ID;
						$posts_found = True;
					}
				}
			}
			
			//now look for sub-blog posts based on mappings
			$sql = "SELECT blog_id, sub_cat_id FROM ".$wpdb->prefix."cat_mapping  WHERE top_cat_id = ".$thecategory->term_id."";
			$blogs = $wpdb->get_results($sql);
			if ($blogs) {
			

				foreach($blogs as $blogid) {
					$sql = "SELECT DISTINCT `ID`,`post_date_gmt` , post_title 
						FROM ".$wpmuBaseTablePrefix.$blogid->blog_id."_posts p
						INNER JOIN ".$wpmuBaseTablePrefix.$blogid->blog_id."_term_relationships r ON p.id = r.object_id
						INNER JOIN ".$wpmuBaseTablePrefix.$blogid->blog_id."_term_taxonomy tt ON r.term_taxonomy_id = tt.term_taxonomy_id
						INNER JOIN ".$wpmuBaseTablePrefix.$blogid->blog_id."_terms t ON tt.term_id = t.term_id
						INNER JOIN ".$wpmuBaseTablePrefix.$blogid->blog_id."_options o ON o.option_name = 'blogname'
						WHERE `post_status` = 'publish' AND (`post_type` = 'post' OR `post_type` = '') AND `post_date_gmt` < '".gmdate("Y-m-d H:i:s")."'
						AND tt.taxonomy = 'category' AND t.term_id = '".$blogid->sub_cat_id."'
						ORDER BY `post_date_gmt`, post_title DESC ";
							$results = $wpdb->get_results($sql);
							if ($results) {
								$title_used_id[] = "";
								foreach($results as $result) {
									//If (!in_array($result->ID, $title_used_id)) {
										$map[] = array($blogid->blog_id,$result->ID,$result->post_date_gmt);
										$ID[] = $result->ID;
										$date_gmt[] = $result->post_date_gmt;
										$title_used_id[] = $result->ID;
										//echo $result->ID. " added";
									//}
								}
							}
				}
			}Else{
				//no posts found so say something clever
				If ($posts_found == False) {
					//echo "No posts found.  Please try a different category";
				}
			}
			if (is_array($map)) {
				array_multisort($date_gmt, SORT_DESC, $ID, SORT_ASC, $map);
				return array_slice($map,0);
			}
		//}
	
}
function display_cats() {
  global $posts;
  $posts = get_data();
  get_header();?>
  <div id="content" class="narrowcolumn">
  <?php if ($posts) {
            foreach ($posts as $post) {
              switch_to_blog($post->blogid);
              start_wp();?>
              <div <?php post_class() ?> id="post-<?php the_ID(); ?>">
                <h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
                <small><?php the_time('F jS, Y') ?>  by <?php the_author() ?></small>
                <div class="entry">
                  <?php the_content('Read the rest of this entry &raquo;'); ?>
                </div>
                <p class="postmetadata"><?php the_tags('Tags: ', ', ', '<br />'); ?> Posted in <?php the_category(', ') ?> | <?php edit_post_link('Edit', '', ' | '); ?>  <?php comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;'); ?></p>
              </div>
              <?php
              restore_current_blog();
          }
      }else{?>
          <h2 class="center">Not Found</h2>
          <p class="center">Sorry, but you are looking for something that isn't here.</p>
          <?php get_search_form();
      } ?>
  </div> <!-- End content -->
<?php
  get_sidebar();
  get_footer();
  exit;
}

function list_all_categories_init() {
	
		if ( !function_exists('register_sidebar_widget') )
			return;
	
	
		function list_all_categories($args) {
			extract($args);
		?>
		<li class="widget widget_categories">
			<h3>Categories</h3>
				<ul>
				<?php echo wp_list_categories('title_li=&hide_empty=0'); ?>
				</ul>
		</li>
		<?php
		}
		
	
		if ( function_exists('wp_register_sidebar_widget') ) // fix for wordpress 2.2.1
		  wp_register_sidebar_widget(sanitize_title('Mapping Widget: All Categories' ), 'Mapping Widget: All Categories', 'list_all_categories', array(), 1);
		else
		  register_sidebar_widget('Mapping Widget: All Categories', 'list_all_categories', 1);

}

// Install/Uninstall Plugin
register_activation_hook(__FILE__,'jal_install');
register_deactivation_hook(__FILE__,'jal_uninstall');

$jal_db_version = "1.0";

function jal_install () {
   global $wpdb;
   global $jal_db_version;

   $table_name = $wpdb->prefix . "cat_mapping";
   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      
      $sql = "CREATE TABLE " . $table_name . " (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  blog_id INT NOT NULL,
	  top_cat_id INT NOT NULL,
	  sub_cat_id INT NOT NULL,
	  UNIQUE KEY id (id)
	);";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
 
      add_option("jal_db_version", $jal_db_version);

   }
}

function jal_uninstall () {
	//global $wpdb;

	//$table_name = $wpdb->prefix . "cat_mapping";
      
	//$sql = "DROP TABLE " . $table_name . ";";
	//$wpdb->query( $sql );
	
	//require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	//dbDelta($sql);
}

//Call function to display categories based on mappings
add_filter('category_template', 'display_cats');

//Call function for category widget
add_action('plugins_loaded', 'list_all_categories_init');
?>