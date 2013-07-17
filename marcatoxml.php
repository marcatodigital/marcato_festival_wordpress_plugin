<?php
/*
 * Plugin Name: Marcato XML Importer
 * Description: Imports artists, venues, shows, and workshops from Marcato Festival XML Feeds.
 * Author: Marcato Digital Solutions
 * Author URI: http://marcatofestival.com
 * Plugin URI: http://github.com/morgancurrie/marcato_festival_wordpress_plugin
 * Version: 1.3.2
 * License: GPL2
 * =======================================================================
	Copyright 2012  Marcato Digital Solutions  (email : support@marcatodigital.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//Initialize Updater
include_once('updater.php');
if (is_admin()){
	$config = array(
		'slug' => plugin_basename(__FILE__),
		'transient_slug' => "marcato",
		'proper_folder_name' => plugin_dir_path(__FILE__),
		'api_url' => 'https://api.github.com/repos/morgancurrie/marcato_festival_wordpress_plugin',
		'raw_url' => 'https://raw.github.com/morgancurrie/marcato_festival_wordpress_plugin/master',
		'github_url' => 'https://github.com/morgancurrie/marcato_festival_wordpress_plugin',
		'zip_url' => 'https://github.com/morgancurrie/marcato_festival_wordpress_plugin/zipball/master',
		'sslverify' => true,
		'requires' => '3.0',
		'tested' => '3.5.1',
		'readme' => 'README.md'
	);
	new WPGitHubUpdater($config);
}
//Initialize the plugin
$marcatoxml = new marcatoxml_plugin();
class marcatoxml_plugin {
	
	public $importer;

	function marcatoxml_plugin(){
		$this->importer = new marcatoxml_importer();
		add_action('admin_menu',array($this, 'build_menus'));
		add_filter('posts_where', array($this, 'posts_where'));
		add_action('init',array($this, 'register_custom_post_types'));
		add_action('init',array($this, 'enqueue_styles'));
		register_activation_hook(__FILE__, array($this,'flush_rewrites'));
		register_deactivation_hook(__FILE__, array($this, 'flush_rewrites'));
		register_activation_hook(__FILE__, array($this, 'schedule_updates'));
		register_deactivation_hook(__FILE__, array($this, 'unschedule_updates'));
		add_action('marcato_update', array($this,'cron_job'));
		add_filter('pre_get_posts', array($this,'query_post_type'));
		add_filter('mce_css', array($this,'add_mce_css'));
		add_shortcode('marcato-link',array($this,'marcato_link'));
		add_shortcode('marcato-field', array($this,'marcato_field'));
		add_shortcode('marcato-thumbnail', array($this,'marcato_thumbnail'));
		wp_oembed_add_provider('#http://(www\.)?soundcloud.com/.*#i', 'http://www.soundcloud.com/oembed/', true);
	}

	function marcato_thumbnail($str){
		$args = wp_parse_args($str);
		$result = "";
		if(!$atts['size']){
			$atts['size'] = 'full';
		}
		echo "<span class='post_thumbnail " . $atts['class'] . "'>" . get_the_post_thumbnail(null,$atts['size']) . "</span>";
	}

 	function marcato_field($atts){
		global $wpdb;
		global $post;
		extract( shortcode_atts( array(
			'field' => '',
			'label' => '',
		), $atts) );
		if (empty($field)){
			return "";
		}else {
			if ($field=="website" && !empty($label)){
				$sql = "SELECT m.meta_key FROM $wpdb->postmeta m WHERE m.post_id = $post->ID AND m.meta_value = '$label'";
				$rows = $wpdb->get_results($sql);
				if (!empty($rows)){
					$name = preg_replace("/name/","url", $rows[0]->meta_key);
					$sql = "SELECT m.meta_value FROM $wpdb->postmeta m WHERE m.post_id = $post->ID AND m.meta_key = '$name'";
					$rows = $wpdb->get_results($sql);
					if (!empty($rows)){
						return $rows[0]->meta_value;
					}
				}
			}else {
				$sql = "SELECT m.meta_value FROM $wpdb->postmeta m WHERE m.post_id = $post->ID AND m.meta_key = '$field'";
				$rows = $wpdb->get_results($sql);
				if(!empty($rows)){
					return $rows[0]->meta_value;
				}
			}
		}
		return "";
	}
	function marcato_link($atts){
		global $wpdb;
		global $post;
		extract( shortcode_atts( array(
			'type' => '',
			'marcato_id' => '',
			'marcato_field' => ''
		), $atts) );
		if (empty($type) || (empty($marcato_id) && empty($marcato_field))) {
			return "";
		} else {
			if (!empty($marcato_field)){
				$sql = "SELECT p.ID FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta m ON (p.ID = m.post_id) WHERE p.post_type = '$type' AND m.meta_key = '".$type."_id' AND m.meta_value = (SELECT x.meta_value FROM $wpdb->postmeta x WHERE x.post_id = $post->ID AND x.meta_key = '$marcato_field' LIMIT 1)";
			}else{
				$sql = "SELECT p.ID FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta m ON (p.ID = m.post_id) WHERE p.post_type= '$type' AND m.meta_key = '".$type."_id' AND m.meta_value = '$marcato_id' LIMIT 1";
			}
			$rows = $wpdb->get_results($sql);
			if(!empty($rows)){
				$permalink = get_permalink($rows[0]->ID);
				return $permalink;
			}else{
				return "";
			}
		}
	}
	
	function query_post_type($query) {
	  if(is_category() || is_tag()) {
	    $post_type = get_query_var('post_type');
		if($post_type){
		    $post_type = $post_type;
		}else{
		  $post_type = array('nav_menu_item','post','marcato_artist','marcato_show','marcato_workshop','marcato_venue');
	    $query->set('post_type',$post_type);
		}
		return $query;
	    }
	}		
	public function flush_rewrites(){
		flush_rewrite_rules();
	}
	public function enqueue_styles(){
		wp_enqueue_style("marcato",plugins_url("",__FILE__)."/css/marcato.css");
	}
	public function add_mce_css($mce_css){
		if(!empty($mce_css)){
			$mce_css .= ',';
		}
		$mce_css .= plugins_url("", __FILE__)."/css/marcato.css";
		return $mce_css;
	}

	public function register_custom_post_types(){
		$supports = array("title","editor","thumbnail");
		if ($this->importer->options["include_meta_data"]=="1"){
			$supports[] = "custom-fields";
		}
		if($this->importer->options["include_excerpts"]=="1"){
			$supports[] = "excerpt";
		}
		register_post_type("marcato_artist", array(
			"label"=>"Artists", "has_archive"=>"artists", 
			"labels"=>array("name"=>"Artists","singular_name"=>"Artist"), 
			"public"=>true, 
			"has_archive"=>true,
			"rewrite"=>array("slug"=>"artists", "with_front"=>false),
			"supports"=>$supports,
			"menu_icon"=>plugin_dir_url(__FILE__)."/images/wp_marcato_logo.png",
			"taxonomies"=>array("category","post_tag")
			)
		);
		register_post_type("marcato_venue", array(
			"label"=>"Venues", "has_archive"=>"venues",
			"labels"=>array("name"=>"Venues","singular_name"=>"Venue"),
			"public"=>true,
			"has_archive"=>true,
			"rewrite"=>array("slug"=>"venues", "with_front"=>false),
			"supports"=>$supports,
			"menu_icon"=>plugin_dir_url(__FILE__)."/images/wp_marcato_logo.png",
			"taxonomies"=>array("category","post_tag")
			)
		);
		register_post_type("marcato_show", array(
			"label"=>"Shows", "has_archive"=>"shows",
			"labels"=>array("name"=>"Shows","singular_name"=>"Show"),
			"public"=>true,
			"has_archive"=>true,
			"rewrite"=>array("slug"=>"shows", "with_front"=>false),
			"supports"=>$supports,
			"menu_icon"=>plugin_dir_url(__FILE__)."/images/wp_marcato_logo.png",
			"taxonomies"=>array("category","post_tag")
			)
		);
		register_post_type("marcato_workshop", array(
			"label"=>"Workshops", "has_archive"=>"workshops",
			"labels"=>array("name"=>"Workshops","singular_name"=>"Workshop"),
			"public"=>true,
			"has_archive"=>true,
			"rewrite"=>array("slug"=>"workshops", "with_front"=>false),
			"supports"=>$supports,
			"menu_icon"=>plugin_dir_url(__FILE__)."/images/wp_marcato_logo.png",
			"taxonomies"=>array("category","post_tag")
			)
		);
		$labels = array(
		  'name' => _x( 'Genres', 'taxonomy general name' ),
		  'singular_name' => _x( 'Genre', 'taxonomy singular name' ),
		  'search_items' =>  __( 'Search Genres' ),
		  'all_items' => __( 'All Genres' ),
		  'parent_item' => __( 'Parent Genre' ),
		  'parent_item_colon' => __( 'Parent Genre:' ),
		  'edit_item' => __( 'Edit Genre' ), 
		  'update_item' => __( 'Update Genre' ),
		  'add_new_item' => __( 'Add New Genre' ),
		  'new_item_name' => __( 'New Genre Name' ),
		  'menu_name' => __( 'Genres' ),
		); 	
		register_taxonomy( 'marcato_genre', array('marcato_artist'), array(
		  'hierarchical' => true,
		  'labels' => $labels,
		  'show_ui' => true,
		  'has_archive' => true, 
		  'query_var' => true,
		  'rewrite' => array( 'slug' => 'genre' ),
		) );
	}
		
	public function import($field){
		return $this->importer->import($field);
	}
	
	public function cron_job(){
		if ($this->importer->options["auto_update"]=="1"){
			$this->import_all();
		}	
	}
	
	public function import_all(){
		return $this->importer->import_all();
	}
	
	public function schedule_updates(){
		if (!wp_next_scheduled('marcato_update')){
			wp_schedule_event( time(), 'hourly', 'marcato_update' );
		}
	}
	public function unschedule_updates(){
		wp_clear_scheduled_hook('marcato_update');
	}
	
	public function posts_where($where){
		global $wpdb;
		if (isset($_GET['artist_name']) && !empty($_GET['artist_name'])){
			$where = " AND {$wpdb->posts}.post_type = 'marcato_artist' AND {$wpdb->posts}.post_status = 'publish' AND LOWER({$wpdb->posts}.post_title) = LOWER('{$_GET['artist_name']}')";
		}
		else if (isset($_GET['artist_id']) && !empty($_GET['artist_id'])){
			$where = " AND {$wpdb->posts}.post_type = 'marcato_artist' AND {$wpdb->posts}.post_status = 'publish' AND EXISTS (SELECT * FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.meta_key = 'marcato_artist_id' AND {$wpdb->postmeta}.meta_value = '{$_GET['artist_id']}' AND {$wpdb->postmeta}.post_id = {$wpdb->posts}.id)";
		}
		else if (isset($_GET['venue_name']) && !empty($_GET['venue_name'])){
			$where = " AND {$wpdb->posts}.post_type = 'marcato_venue' AND {$wpdb->posts}.post_status = 'publish' AND LOWER({$wpdb->posts}.post_title) = LOWER('{$_GET['venue_name']}')";
		}
		else if (isset($_GET['venue_id']) && !empty($_GET['venue_id'])){
			$where = " AND {$wpdb->posts}.post_type = 'marcato_venue' AND {$wpdb->posts}.post_status = 'publish' AND EXISTS (SELECT * FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.meta_key = 'marcato_venue_id' AND {$wpdb->postmeta}.meta_value = '{$_GET['venue_id']}' AND {$wpdb->postmeta}.post_id = {$wpdb->posts}.id)";
		}
		else if (isset($_GET['show_id']) && !empty($_GET['show_id'])){
			$where = " AND {$wpdb->posts}.post_type = 'marcato_show' AND {$wpdb->posts}.post_status = 'publish' AND EXISTS (SELECT * FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.meta_key = 'marcato_show_id' AND {$wpdb->postmeta}.meta_value = '{$_GET['show_id']}' AND {$wpdb->postmeta}.post_id = {$wpdb->posts}.id)";
		}
		else if (isset($_GET['workshop_id']) && !empty($_GET['workshop_id'])){
			$where = " AND {$wpdb->posts}.post_type = 'marcato_workshop' AND {$wpdb->posts}.post_status = 'publish' AND EXISTS (SELECT * FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.meta_key = 'marcato_workshop_id' AND {$wpdb->postmeta}.meta_value = '{$_GET['workshop_id']}' AND {$wpdb->postmeta}.post_id = {$wpdb->posts}.id)";
		}
		else if (isset($_GET['show_name']) && !empty($_GET['show_name'])){
			$where = " AND {$wpdb->posts}.post_type = 'marcato_show' AND {$wpdb->posts}.post_status = 'publish' AND LOWER({$wpdb->posts}.post_title) = LOWER('{$_GET['show_name']}')";
		}
		else if (isset($_GET['workshop_name']) && !empty($_GET['workshop_name'])){
			$where = " AND {$wpdb->posts}.post_type = 'marcato_workshop' AND {$wpdb->posts}.post_status = 'publish' AND LOWER({$wpdb->posts}.post_title) = LOWER('{$_GET['workshop_name']}')";
		}
		
		return $where;
	}
	public function build_menus(){
		// add_object_page("Marcato XML Importer","Marcato","import","marcatoxmlsettings",array($this,'admin_page'),plugin_dir_url(__FILE__)."/images/wp_marcato_logo.png");
		add_options_page('Marcato XML Options','Marcato','manage_options','marcatoxml-options',array($this,'admin_page'),plugins_url().'/marcato/images/wp_marcato_logo.png');
	}
	public function admin_page(){
		if (!current_user_can('manage_options')){
			wp_die( __('You do not have sufficient permissions to access this page.'));
		}
		if( isset($_POST['marcato_submit_hidden']) && $_POST['marcato_submit_hidden'] == 'Y'){
			foreach($this->importer->options as $option=>$value){
				$field_value = $_POST[$option];
				update_option($option, $field_value);
				$this->importer->options[$option] = $field_value;
			}
			if (isset($_POST['Submit'])){
				?>
				<div class="updated"><p><strong><?php _e('settings saved.','marcatoxml-options'); ?></strong></p></div>
				<?php
			}
			if (isset($_POST['marcato_import'])){
				echo "IMPORTING...<br>NOTE: If you are importing featured images this could take a while. If you get a maximum execution time exceeded message, please try the import again.";
				$results = $this->import_all();
				$errors = array();
				foreach($results as $result){
					if(is_string($result))
					$errors[] = $result;
				}
				?>
				<div class="updated"><p><strong>
					<?php if (!empty($errors)) {
							foreach($errors as $error){
								echo $error."<br/>";
							}
						}else{
							_e('Marcato XML Feed Imported','marcatoxml-options'); 
						}
					?>
				</strong></p></div>
				<?php
			}	
		}
		echo '<div class="wrap">';
		echo "<h2>" . __('Marcato XML Plugin Settings', 'marcatoxml-options') . "</h2>";
		?>
		
		<form name="marcatoxmlsettings" method="post" action="">
			<input type="hidden" name="marcato_submit_hidden" value="Y">
			<p>
				Marcato Organization ID
				<input type="text" name="marcato_organization_id" value="<?php echo $this->importer->options["marcato_organization_id"] ?>">
			</p>
			<p>
				Include photos as featured images on posts?
				<input type="hidden" name="attach_photos" value="0">
				<input type="checkbox" name="attach_photos" value="1" <?php echo $this->importer->options["attach_photos"]=="1" ? "checked='checked'" : "" ?>><br />
				<cite><small>Enable this to include photos from Marcato as the featured image of a post instead of embedding the image directly in the post body.</small></cite>
			</p>
			<p>
				Include photos in post body?
				<input type="hidden" name="include_photos_in_posts" value="0">
				<input type="checkbox" name="include_photos_in_posts" value="1" <?php echo $this->importer->options["include_photos_in_posts"]=="1" ? "checked='checked'" : "" ?>><br />
				<label for="post_photo_size">Size:</label>
				<select name="post_photo_size">
					<?php 
						$options = array("thumbnail","medium","large","full");
						foreach($options as $option){
							echo "<option value='".$option."' ".($this->importer->options["post_photo_size"]==$option ? "selected='selected'" : "").">".$option."</option>";
						}
					?>
				</select><br />
				<cite><small>Enable this to have photos from Marcato included in the post body.</small></cite>
			</p>
			<p>
				Embed links?
				<input type="hidden" name="embed_video_links" value="0">
				<input type="checkbox" name="embed_video_links" value="1" <?php echo $this->importer->options["embed_video_links"]=="1" ? "checked='checked'" : "" ?>>
				<br />
				<cite><small>Enable this to automatically embed any YouTube, Vimeo, or Soundcloud links that have been entered into the Marcato website fields on artists.</small></cite>
			</p>
			<p>
				Include Short Bio/Descriptions as excerpts?
				<input type="hidden" name="include_excerpts" value="0">
				<input type="checkbox" name="include_excerpts" value="1" <?php echo $this->importer->options["include_excerpts"]=="1" ? "checked='checked'" : "" ?>>
				<br />
				<cite><small>Enable this to include the shorter versions of things like Artist Bios and Show Descriptions from Marcato as post excerpts</small></cite>
			<p>
				Include XML fields as post Meta-data?
				<input type="hidden" name="include_meta_data" value="0">
				<input type="checkbox" name="include_meta_data" value="1" <?php echo $this->importer->options["include_meta_data"]=="1" ? "checked='checked'" : "" ?>><br />
				<cite><small>Enable this to include all xml fields as custom fields on posts. This is useful if you use other plugins that make use of post meta data.</small></cite>
			</p>
			<p>
			  Include the shows and workshops the artist is performing/presenting at in their post.
			  <input type="hidden" name="include_artist_lineup" value="0">
			  <input type="checkbox" name="include_artist_lineup" value="1" <?php echo $this->importer->options["include_artist_lineup"]=="1" ? "checked='checked'" : "" ?>><br />
			  <cite><small>Enable this to include a table at the bottom of artists posts that displays all of the shows and workshops they are involved in</small></cite>
			  <br />
				Set the show and workshop times listed in the artist post to their performance times instead, if available.
				<input type="hidden" name="artist_lineup_set_times" value="0">
				<input type="checkbox" name="artist_lineup_set_times" value="1" <?php echo $this->importer->options["artist_lineup_set_times"]=="1" ? "checked='checked'" : "" ?>>
			</p>
			<p>
				Auto Update data every hour?
				<input type="hidden" name="auto_update" value="0">
				<input type="checkbox" name="auto_update" value="1" <?php echo $this->importer->options["auto_update"]=="1" ? "checked='checked'" : "" ?>><br />
				<cite><small>Enable this is have a WP cron job run hourly that automatically updates all your marcato data.</small></cite>
			</p>
			<hr />
			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
				<input type="submit" name="marcato_import" class="button-secondary" value="<?php esc_attr_e('Import Now') ?>" />
			</p>
		</form>
	<?php
	}
}
class marcatoxml_importer {		

	public $options = array('marcato_organization_id'=>"0", 'attach_photos'=>"0",'include_photos_in_posts'=>'0', 'embed_video_links'=>"0", 'include_meta_data'=>"0",'include_excerpts'=>"0","auto_update"=>"1","include_artist_lineup"=>"0","artist_lineup_set_times"=>"0","post_photo_size"=>"full");
	public $fields = array("artists","venues","shows","workshops");
	public $marcato_xml_url = "http://marcatoweb.com/xml";
		
	function marcatoxml_importer(){
		foreach($this->options as $option=>$value){
			$set_value = get_option($option);
			if ($set_value=="1" || $set_value=="0" || $option=="marcato_organization_id" || $option=="post_photo_size"){
				$this->options[$option] = get_option($option);
			}
		}
	}	
	
	public function import_all(){
		$org_id = $this->options['marcato_organization_id'];
		if (empty($org_id)){
			return array("Organization ID is not set.");
		}
		$results = array();
		foreach($this->fields as $field){
			$results[] = $this->import($field);
		}
		if($this->generate_schedule_page()){
			$results[]="Schedule page generated successfully.";
		};
		return $results;
	}
	
	public function import($field) {
		$org_id = $this->options['marcato_organization_id'];
		if (empty($org_id)){
			return "Error importing {$field}: Organization ID is not set";
		}
		$result;
		$errors = array();
		if ($posts = $this->get_posts($field)){
			foreach ($posts as $key=>$post){
				if(!$this->import_post($post)){
					$errors[0] = $post['post_title'];					
				}
			}
		}else{
			return "Error importing {$field}: Error loading xml file. This plugin requires simpleXML, and curl to be enabled in order to retreive and process the xml feeds from Marcato. Check the output of phpinfo(); to determine if cURL and simpleXML are disabled. If you don't know how to do this, or how to enable them, contact your server administrator.";
		}
		return "{$field} Imported.\n" . implode("\n", $errors);
	}
	
	private function get_xml_location($field){
		return $this->marcato_xml_url . '/' . $field . '_' . $this->options['marcato_organization_id'] . '.xml';
	}
	private function get_posts($field) {
	  $xml = $this->load_XML($field);
		if ($xml){
			if($field == 'artists'){
				return $this->parse_artists($xml);
			}if($field == 'venues'){
				return $this->parse_venues($xml);
			}else if ($field == "shows"){
				return $this->parse_shows($xml);
			}else if ($field == "workshops"){
				return $this->parse_workshops($xml);
			}else{
				return array();
			}
			wp_import_cleanup($xml['id']);
		}else{
			return false;
		}		
	}
	private function load_performances(){
		$map = array();
		$xml = $this->load_XML('performances');
		foreach($xml->performance as $performance){
			if(!isset($map[(string)$performance->performer_id])){
				$map[(string)$performance->performer_id] = array();
			}
			$map[(string)$performance->performer_id][] = $performance;
			$performance->type = 'show';
			$performance->link_id = $performance->show_id;
			$performance->name = $performance->show_name;
			$performance->formatted_dtstart = date_i18n(get_option('date_format'),(integer)$performance->set_time) . " " . date_i18n(get_option('time_format'),(integer)$performance->set_time);
		}
		return $map;
	}
	private function load_presentations(){
		$map = array();
		$xml = $this->load_XML('presentations');
		foreach($xml->presentation as $presentation){
			if($presentation->presenter_type == 'Artist'){
				if(!isset($map[(string)$presentation->presenter_id])){
					$map[(string)$presentation->presenter_id] = array();
				}
				$map[(string)$presentation->presenter_id][] = $presentation;
				$presentation->type = 'workshop';
				$presentation->link_id = $presentation->workshop_id;
				$presentation->name = $presentation->workshop_name;
				$presentation->formatted_dtstart = date_i18n(get_option('date_format'),(integer)$presentation->set_time) . " " . date_i18n(get_option('time_format'),(integer)$presentation->set_time);
			}
		}
		return $map;
	}
	private function load_XML($field){
    if(ini_get('allow_url_fopen')==true){
       return simplexml_load_file($this->get_xml_location($field));
    }else if (function_exists('curl_init')){
         $curl = curl_init($this->get_xml_location($field));
         curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
         $result = curl_exec($curl);
         curl_close($curl);
         return simplexml_load_string($result);
    }else{
      return false;
    }    
	}
	private function remove_posts_missing_from_xml_feed($xml_ids, $post_type){
		if(!empty($xml_ids)){
			global $wpdb;
			$id_list = implode(",",$xml_ids);
			$sql = "SELECT p.id FROM $wpdb->posts p WHERE p.post_type = '$post_type' AND EXISTS(SELECT * FROM $wpdb->postmeta m WHERE m.post_id = p.id AND m.meta_key = '{$post_type}_id') AND NOT EXISTS(SELECT * FROM $wpdb->postmeta m WHERE m.post_id = p.id AND m.meta_key = '{$post_type}_id' AND m.meta_value IN ($id_list))";
			$rows = $wpdb->get_results($sql);
			foreach($rows as $row){
				wp_delete_post($row->id, true);
			}
		}
	}
	private function import_post($post){
	 	global $wpdb;
		extract($post);
		$meta = $wpdb->get_row("SELECT * FROM $wpdb->postmeta WHERE meta_key = '{$post_type}_id' AND meta_value = '$post_marcato_id'");
		if ($meta) {
			$existing_post_id = $meta->post_id;
			//If exists, update;
			$post['ID'] = $existing_post_id;
			if ($updated_post_id = wp_update_post($post)){
				if(isset($post['post_attachment'])){
					$this->set_featured_image($existing_post_id, $post['post_attachment']);
				}
				if(isset($post['post_meta'])){
					$this->set_post_meta($existing_post_id, $post['post_meta']);
				}
				if(isset($post['post_taxonomy'])){
					$this->set_post_taxonomy($existing_post_id, $post['post_taxonomy']);
				}
				return $existing_post_id;
			}else{
				return "Error updating {$post_title}.";
			}
		} else {
 			//If it doesn't exist create;
			$post['post_status'] = "pending";
			$post['comment_status'] = 'closed';
			if($post_id = wp_insert_post($post)){
				add_post_meta($post_id, "{$post_type}_id", $post_marcato_id, true);
				if(isset($post['post_attachment'])){
					$this->set_featured_image($post_id, $post['post_attachment']);
				}
				if(isset($post['post_meta'])){
					$this->set_post_meta($post_id, $post['post_meta']);
				}
				if(isset($post['post_taxonomy'])){
					$this->set_post_taxonomy($post_id, $post['post_taxonomy']);
				}
				return $post_id;
			}else{
				return "Error creating {$post_title}.";
			}
		}
	}	
	private function parse_artists($xml){
		global $wpdb;
   	$index = 0;
		$posts = array();
		$ids = array();
		if($this->options['artist_lineup_set_times']=="1"){
			$performance_map = $this->load_performances();
			$presentation_map = $this->load_presentations();
		}
		foreach ($xml->artist as $artist) {
			$ids[] = (string)$artist->id;
			$post_attachment = array();
			$embed_codes = array();
			$link_content = "";
			$post_title = (string)$artist->name;
			$post_content = "";
			$post_content .= "<div class='artist_homebase'>" . $artist->homebase . "</div>";
			if(!empty($artist->genre)){
				$post_content .= "<div class='artist_genre'>" . $artist->genre . "</div>";
			}
			if (!empty($artist->web_photo_url)){
				if ($this->options['attach_photos']=="1" || $this->options['include_photos_in_post']=="1"){
					$post_attachment = array('url'=>(string)$artist->web_photo_url_root . "large.jpg", 'name'=>(string)$artist->name, 'fingerprint'=>(string)$artist->web_photo_fingerprint, 'field'=>'web_photo');
				}
				if($this->options['include_photos_in_posts']=="1"){
					$post_content .= "[marcato-thumbnail size='".$this->options['post_photo_size']."']";
					// <img src='".$artist->web_photo_url_root."web.jpg' class='artist_photo'>
				}
			}else if(!empty($artist->photo_url)){
				if ($this->options['attach_photos']=="1" || $this->options['include_photos_in_posts']=="1"){
					$post_attachment = array('url'=>(string)$artist->photo_url_root."large.jpg", 'name'=>(string)$artist->name, 'fingerprint'=>(string)$artist->photo_fingerprint, 'field'=>'photo');
				}
				if($this->options['include_photos_in_posts']=="1"){
					$post_content .= "[marcato-thumbnail size='".$this->options['post_photo_size']."']";
					// $post_content .= "<img src='".$artist->photo_url_root."web_compressed.jpg' class='artist_photo'>";
				}
			}
			$post_content .= "<div class='artist_bio'>" . nl2br((string)$artist->bio_public) . "</div>";
			if(!empty($artist->websites)){
				foreach($artist->websites->website as $website){
					if(strpos($website->url,'http://')===false && strpos($website->url,'https://')===false){
						$url = 'http://'.$website->url;
					}else{
						$url = $website->url;
					}
					$embed_code = $this->get_video_embed_code($url);
					if ($this->options["embed_video_links"]=="1" && !empty($embed_code)){
						$embed_codes[] = $embed_code;
					}else{
						$link_content .= "<a class='artist_website ".strtolower(ereg_replace("[^A-Za-z0-9_]","",str_replace(" ","_", $website->name)))."' href='".$url."' target='_blank'>".$website->name."</a><br>";
					}
				}
			}
			if (!empty($embed_codes)){
				foreach($embed_codes as $embed_code){
					$post_content .= "<div class='artist_embedded_video'>".$embed_code."</div>";
				}
			}
			$post_content .= "<div class='artist_websites'>" . $link_content . "</div>";
			
			if($this->options["include_artist_lineup"]){
				$events = array();
				if($this->options['artist_lineup_set_times']=="0"){
	  			if(!empty($artist->shows)){
	  				foreach($artist->shows->show as $show){
	  					if((string)$show->show_on_website=="false"){continue;}
	  					$show->type = 'show';
	  					$show->link_id = $show->id;
	  					$show->formatted_dtstart = date_i18n(get_option('date_format'), strtotime($show->date . ' ' . $show->formatted_start_time)) . " " . date_i18n(get_option('time_format'), strtotime($show->date . ' ' . $show->formatted_start_time));
	  				  $events[] = $show;
	  				}
	  			}
	  			if(!empty($artist->workshops)){
	  				foreach($artist->workshops->workshop as $workshop){
	            if((string)$workshop->show_on_website=="false"){continue;}
	  					$workshop->type = 'workshop';
	  					$workshop->link_id = $workshop->id;
	  					$workshop->formatted_dtstart = date_i18n(get_option('date_format'), strtotime($workshop->date . ' ' . $workshop->formatted_start_time)) . " " . date_i18n(get_option('time_format'), strtotime($workshop->date . ' ' . $workshop->formatted_start_time));
	  					$events[] = $workshop;
	  				}
	  			}
	  			usort($events, array($this, 'sort_by_unix_time'));
	  		}else{
	  			$array1 = $performance_map[(string)$artist->id];
	  			$array2 = $presentation_map[(string)$artist->id];
	  			if(!isset($array1)){$array1 = array();}
	  			if(!isset($array2)){$array2 = array();}
	    		$events = array_merge($array1, $array2);
	    		usort($events, array($this, 'sort_timeslots_by_set_time'));
	  		}
  			if(!empty($events)){
    			$post_content .= "<table class='artist_lineup'>";
    			foreach($events as $event){
    			  $post_content .= "<tr><td class='time'>".$event->formatted_dtstart."</td><td class='event'><a href='[marcato-link type='marcato_".$event->type."' marcato_id='".$event->link_id."']'>".$event->name."</a></td></tr>";
    			}
	  			$post_content .= "</table>";
	    	}
  		}
			
			$post_type = "marcato_artist";
			$post_marcato_id = intval($artist->id);
			if($this->options["include_excerpts"]=="1"){
				$post_excerpt = (string)$artist->bio_limited;
				if (empty($post_excerpt)){
					$post_excerpt = (string)$artist->bio_public;
				}
			}else{
				$post_excerpt = "";
			}
			if(!empty($artist->genre)){
				$post_taxonomy['marcato_genre'] = (string)$artist->genre;
			}
			$post_meta = array();
			if ($this->options["include_meta_data"]=="1"){
				foreach(array('name','bio_public','bio_limited','homebase','web_photo_url','web_photo_url_root','photo_url','photo_url_root','updated_at') as $field){
					$post_meta["marcato_artist_".$field] = nl2br((string)$artist->$field);
				}
				if(!empty($artist->shows)){
					$i = 0;
					foreach($artist->shows->show as $show){
						if((string)$show->show_on_website=="false"){continue;}
						foreach(array('id','name','show_on_website','date','formatted_date','venue_name') as $field){
							$post_meta["marcato_artist_show_".$i."_".$field] = $show->$field;
						}
						$i++;
					}
				}
				if(!empty($artist->workshops)){
					$i = 0;
					foreach($artist->workshops->workshop as $workshop){
						if((string)$workshop->show_on_website=="false"){continue;}
						foreach(array('id','name','show_on_website','date','formatted_date','venue_name') as $field){
							$post_meta["marcato_artist_workshop_".$i."_".$field] = $workshop->$field;
						}
						$i++;
					}
				}
				if(!empty($artist->websites)){
					$i = 0;
					foreach($artist->websites->website as $website){
						$post_meta["marcato_artist_website_".$i."_name"] = $website->name;
						$post_meta["marcato_artist_website_".$i."_url"] = $website->url;
						$i++;
					}
				}
			}
			$posts[$index] = compact('post_content', 'post_title','post_type', 'post_taxonomy', 'post_marcato_id','post_status','post_attachment','post_meta','post_excerpt');
			$index++;
		}
		$this->remove_posts_missing_from_xml_feed($ids, $post_type);
		return $posts;
	}
	private function parse_venues($xml){
		global $wpdb;
   	$index = 0;
   	$ids = array();
		$posts = array();
		foreach ($xml->venue as $venue) {
			$ids[] = (string)$venue->id;
			$post_attachment = array();
			$post_title = (string)$venue->name;
			$post_content = "";
			$post_content .= "<div class='venue_community'>" . $venue->community . "</div>";
			if (!empty($venue->photo_url)){
				if ($this->options['attach_photos']=="1" || $this->options['include_photos_in_posts']=="1"){
					$post_attachment = array('url'=>(string)$venue->photo_url_root . "web.png", 'name'=>(string)$venue->name, 'fingerprint'=>(string)$venue->photo_fingerprint, 'field'=>'photo');
				}
				if($this->options['include_photos_in_posts']=="1"){
					$post_content .= "[marcato-thumbnail size='".$this->options['post_photo_size']."']";
					// $post_content .= "<img src='".(string)$venue->photo_url_root . "web.png' class='venue_photo'>";
				}
			}
			$post_content .= "<div class='venue_address'>";
			$post_content .= "<span class='street'>" . $venue->street . "</span>";
			$post_content .= "<span class='city'>" . $venue->city . "</span>";
			$post_content .= "<span class='province_state'>" . $venue->province_state . "</span>";
			$post_content .= "<span class='postal_code'>" . $venue->postal_code . "</span>";
			$post_content .= "<span class='country'>" . $venue->country . "</span>";
			$post_content .= "</div>";
			$post_content .= "<div class='venue_phone'>" . $venue->primary_phone_number . "</div>";
			$post_content .= "<div class='venue_description'>" . $venue->description . "</div>";
			$post_type = "marcato_venue";
			if($this->options["include_excerpts"]=="1"){
				$post_excerpt = (string)$venue->description;
			}else{
				$post_excerpt = "";
			}
			$post_marcato_id = intval($venue->id);
			$post_meta = array();
			if ($this->options["include_meta_data"]=="1"){
				$i = 0;
				foreach($venue->shows->show as $show){
					if((string)$show->show_on_website=="false"){continue;}
					foreach(array('id','name','show_on_website','date','formatted_date','formatted_start_time','formatted_end_time') as $field){
						$post_meta["marcato_venue_show_".$i."_".$field] = $show->$field;
					}
					$i++;
				}
				$i = 0;
				foreach($venue->workshops->workshop as $workshop){
					if((string)$workshop->show_on_website=="false"){continue;}
					foreach(array('id','name','show_on_website','date','formatted_date','formatted_start_time','formatted_end_time') as $field){
						$post_meta["marcato_venue_workshop_".$i."_".$field] = $workshop->$field;
					}
					$i++;
				}
				foreach(array('name','street','city','province_state','country','postal_code','community','directions','longitude','latitude','primary_phone_number','photo_url','photo_url_root','updated_at') as $field){
					$post_meta["marcato_venue_".$field] = $venue->$field;
				}
			}			
			$posts[$index] = compact('post_content', 'post_title', 'post_type', 'post_marcato_id', 'post_status', 'post_meta','post_attachment', 'post_excerpt');
			$index++;
		}
		$this->remove_posts_missing_from_xml_feed($ids, $post_type);
		return $posts;
	}
	
	private function parse_shows($xml){
		global $wpdb;
   	$index = 0;
   	$ids = array();
		$posts = array();
		foreach ($xml->show as $show) {
			$ids[] = (string)$show->id;
			$post_attachment = array();
			$post_title = (string)$show->name;			
			$post_content = "";
			$post_content .= "<div class='show_time'>";
			$post_content .= "<span class='date'>" .date_i18n(get_option('date_format'), strtotime($show->date)) . "</span>";
			$post_content .= "<span class='show_time'><span class='start_time'>".date_i18n(get_option('time_format'), strtotime($show->date . ' ' . $show->formatted_start_time))."</span>";
			if (!empty($show->formatted_end_time)){
				$post_content .= "<span class='time_divider'>-</span><span class='end_time'>".date_i18n(get_option('time_format'), strtotime($show->date . ' ' . $show->formatted_end_time))."</span>";
			}
			$post_content .= "</div>";
			$venue_name = (string)$show->venue_name;
			$post_content .= "<div class='show_venue'><a class='show_venue_link' href=\"[marcato-link type='marcato_venue' marcato_id='".$show->venue->id."']\">" . $show->venue_name . "</a></div>";
			if (!empty($show->poster_url)){
				if ($this->options['attach_photos']=="1" || $this->options['include_photos_in_posts']=="1"){
					$post_attachment = array('url'=>(string)$show->poster_url_root . "web.png", 'name'=>(string)$show->name, 'fingerprint'=>(string)$show->poster_fingerprint, 'field'=>'poster');
				}
				if($this->options['include_photos_in_posts']=="1"){
					$post_content .= "[marcato-thumbnail size='".$this->options["post_photo_size"]."']";
					// $post_content .= "<img src='".(string)$show->poster_url_root."web.png' class='show_photo'>";
				}
			}
			$post_content .= "<div class='show_ticket_info'>";
			$post_content .= "<span class='price'>" . $show->price . "</span>";
			$post_content .= "<span class='ticket_info'>" . $show->ticket_info . "</span>";
			$post_content .= "<a class='ticket_link' href='" . $show->ticket_link . "' target='_blank'>".$show->ticket_link."</a>";
			$post_content .= "</div>";
			$post_content .= "<div class='show_description'>" . nl2br((string)$show->description_web) . "</div>";
			$post_content .= "<table class='show_lineup'>";
			if(!empty($show->performances)){
				foreach ($show->performances->performance as $performance){
					$post_content .= "<tr class='performance'>";
					$artist_name = (string)$performance->artist;
					$post_content .= "<td class='performance_time'><span class='performance_start'>".date_i18n(get_option('time_format'), strtotime($show->date . ' ' . $performance->start))."</span>";
					if (!empty($performance->end)){
						$post_content .= "<span class='time_divider'>-</span><span class='performance_end'>".date_i18n(get_option('time_format'), strtotime($show->date . ' ' . $performance->end))."</span>";
					}
					$post_content .= "</td>";
					$post_content .= "<td class='artist'><a class='performance_artist_link' href=\"[marcato-link type='marcato_artist' marcato_id='" . $performance->artist_id . "']\">" .$performance->artist . "</a></td>";
					$post_content .= "</tr>";
				}
			}
			$post_content .= "</table>";
			$post_type = "marcato_show";
			$post_marcato_id = intval($show->id);
			if($this->options["include_excerpts"]=="1"){
				$post_excerpt = (string)$show->description_web;
			}else{
				$post_excerpt = "";
			}
			$post_meta = array();
			if ($this->options["include_meta_data"]=="1"){
				foreach(array('name','date','formatted_date','venue_name','formatted_start_time','start_time_unix','formatted_end_time','facebook_link','description_public','description_web','ticket_info','ticket_link','price','poster_url','poster_url_root','updated_at','seating') as $field){
					$post_meta["marcato_show_".$field] = nl2br((string)$show->$field);
				}
				foreach($show->venue as $venue){
					foreach(array('name','street','city','province_state','community','longitute','latitude','id') as $field){
						$post_meta["marcato_show_venue_".$field] = $venue->$field;
					}
				}
				if(!empty($show->performances)){
					$i = 0;
					foreach($show->performances->performance as $performance){
						foreach(array('id','artist','artist_id','start','end','rank') as $field){
							$post_meta["marcato_show_performance_".$i."_".$field] = $performance->$field;
						}
						$i++;
					}
				}
			}			
			$posts[$index] = compact('post_content', 'post_title', 'post_type', 'post_marcato_id','post_attachment','post_meta','post_excerpt');
			$index++;
		}
		$this->remove_posts_missing_from_xml_feed($ids, $post_type);
		return $posts;
	}
	
	private function parse_workshops($xml){
		global $wpdb;
   	$index = 0;
   	$ids = array();
		$posts = array();
		foreach ($xml->workshop as $workshop) {
			$ids[] = (string)$workshop->id;
			$post_attachment = array();
			$post_title = (string)$workshop->name;
			$post_content = "";
			$post_content .= "<div class='workshop_time'>";
			$post_content .= "<span class='date'>" .date_i18n(get_option('date_format'), strtotime($workshop->date)). "</span>";
			$post_content .= "<span class='start_time'>".date_i18n(get_option('time_format'), strtotime($workshop->date . ' ' . $workshop->formatted_start_time)) . "</span>";
			if (!empty($workshop->formatted_end_time)){
				$post_content .= "<span class='time_divider'>-</span><span class='end_time'>".date_i18n(get_option('time_format'), strtotime($workshop->date . ' ' . $workshop->formatted_end_time))."</span>";
			}
			$post_content .= "</div>";
			$venue_name = (string)$workshop->venue_name;
			$post_content .= "<div class='workshop_venue'><a class='workshop_venue_link' href=\"[marcato-link type='marcato_venue' marcato_id='".$workshop->venue->id."']\">" . $workshop->venue_name . "</a></div>";
			if (!empty($workshop->poster_url)){
				if ($this->options['attach_photos']=="1" || $this->options["include_photos_in_posts"]){
					$post_attachment = array('url'=>(string)$workshop->poster_url_root . "web.png", 'name'=>(string)$workshop->name, 'fingerprint'=>(string)$workshop->poster_fingerprint, 'field'=>'poster');
				}
				if($this->options['include_photos_in_posts']=="1"){
					$post_content .= "[marcato-thumbnail size='".$this->options["post_photo_size"]."']";
					// $post_content .= "<img src='".(string)$workshop->poster_url_root."web.png' class='workshop_photo'>";
				}
			}
			$post_content .= "<div class='workshop_ticket_info'>";
			$post_content .= "<span class='price'>" . $workshop->price . "</span>";
			$post_content .= "<span class='ticket_info'>" . $workshop->ticket_info . "</span>";
			$post_content .= "<a class='ticket_link' href='" . $workshop->ticket_link . "' target='_blank'>".$workshop->ticket_link."</a>";
			$post_content .= "</div>";
			$description = (string)$workshop->description_web;
			if(empty($description)){
				$description = (string)$workshop->description_public;
			}
			$post_content .= "<div class='workshop_description'>" . nl2br($description) . "</div>";
			
			$post_content .= "<div class='workshop_types'>";
			if(!empty($workshop->workshop_types)){
				foreach ($workshop->workshop_types->workshop_type as $type){
					$post_content .= "<span class='workshop_type'>".$type->name."</span>";
				}
			}
			$post_content .= "</div>";

			$post_content .= "<table class='workshop_lineup'>";
			if (!empty($workshop->presentations)){
				foreach ($workshop->presentations->presentation as $presentation){
					$post_content .= "<tr class='presentation'>";
					$post_content .= "<td class='presentation_time'><span class='presentation_start'>".date_i18n(get_option('time_format'), strtotime($workshop->date . ' ' . $presentation->start))."</span>";
					if(!empty($presentation->end)){
						$post_content .= "<span class='time_divider'>-</span><span class='presentation_end'>".date_i18n(get_option('time_format'), strtotime($workshop->date . ' ' . $presentation->end))."</span>";
					}
					$post_content .= "</td>";
					if ($presentation->presenter_type == "artist"){
						$artist_name = (string)$presentation->presenter;
						$post_content .= "<td class='presenter'><a class='presentation_presenter_link' href=\"[marcato-link type='marcato_artist' marcato_id='".$presentation->presenter_id."']\">".$presentation->presenter."</a></td>";
					}else{
						$post_content .= "<td class='presenter'>".$presentation->presenter."</td>";
					}
					$post_content .= "</tr>";
				}
			}
			$post_content .= "</table>";
			$post_type = "marcato_workshop";
			$post_marcato_id = intval($workshop->id);
			if($this->options["include_excerpts"]=="1"){
				$post_excerpt = (string)$workshop->description_web;
				if (empty($post_excerpt)){
					$post_excerpt = (string)$workshop->description_limited;
				}
				if(empty($post_excerpt)){
					$post_excerpt = (string)$workshop->description_public;
				}
			}else{
				$post_excerpt = "";
			}
			$post_meta = array();
			if ($this->options["include_meta_data"]=="1"){
				foreach(array('name','date','formatted_date','venue_name','formatted_start_time','start_time_unix','end_time','formatted_end_time','facebook_link','description_public','description_web','ticket_info','ticket_link','price','poster_url','poster_url_root','event_contact_summary','event_contact_name','event_contact_phone','event_contact_email','hosting_organization_title','updated_at','seating') as $field){
					$post_meta["marcato_workshop_".$field] = nl2br((string)$workshop->$field);
				}
				if(!empty($workshop->workshop_types)){
					$i = 0;
					foreach($workshop->workshop_types->workshop_type as $workshop_type){
						$post_meta["marcato_workshop_type_".$i."_name"] = $workshop_type->name;
						$post_meta["marcato_workshop_type_".$i."_id"] = $workshop_type->id;
						$i++;
					}
				}
				foreach($workshop->venue as $venue){
					foreach(array('name','street','city','province_state','community','longitute','latitude','id') as $field){
						$post_meta["marcato_workshop_venue_".$field] = $venue->$field;
					}
				}
				if(!empty($workshop->presentations)){
					$i = 0;
					foreach($workshop->presentations->presentation as $presentation){
						foreach(array('id','presenter','presenter_id','start','end','rank','presenter_type') as $field){
							$post_meta["marcato_workshop_presentation_".$i."_".$field] = $presentation->$field;
						}
						$i++;
					}
				}
			}			
			$posts[$index] = compact('post_content', 'post_title', 'post_type', 'post_marcato_id','post_attachment','post_meta','post_excerpt');
			$index++;
		}
		$this->remove_posts_missing_from_xml_feed($ids, $post_type);
		return $posts;
	}
	
	public function generate_schedule_page(){
		$workshop_xml = $this->load_XML('workshops');
		$show_xml = $this->load_XML('shows');
		if(!$workshop_xml && !$show_xml){return false;}
		if(!$workshop_xml){ $workshop_xml = array(); }
		if(!$show_xml) { $show_xml = array(); }
	
		$post_title = "Schedule";
		$post_content = "";
		$events = array();
		foreach($workshop_xml->workshop as $workshop){
			$workshop->type = "workshop";
			$events[] = $workshop; 
		}
		foreach($show_xml->show as $show){
			$show->type = "show";
			$events[] = $show; 
		}
		function sort_by_datetime($a, $b){
			$a_date = strtotime($a->date . ' ' . $a->formatted_start_time);
			$b_date = strtotime($b->date . ' ' . $b->formatted_start_time);
			if ($a_date == $b_date){return 0;}
			return ($a_date < $b_date) ? -1 : 1;
		}
		usort($events, 'sort_by_datetime');
		foreach($events as $event){
			if ($event->type=="show"){
				$types = 'performances';
				$type = 'performance';
				$person = "artist";
				$archive_link_type = "marcato_show";
				$link_query = "show_id";
			}else if ($event->type=="workshop"){
				$types = 'presentations';
				$type = 'presentation';
				$person = "presenter";
				$archive_link_type = "marcato_workshop";
				$link_query = "workshop_id";
			}
			$post_content .= "<div class='schedule_event'>";
			$post_content .= "<div class='schedule_event_title'><a href=\"[marcato-link type='marcato_".$event->type."' marcato_id='".$event->id."']\">".$event->name."</a></div>";
			$post_content .= "<div class='schedule_time'>";
			$post_content .= "<span class='date'>".date_i18n(get_option('date_format'), strtotime($event->date))."</span>";
			$post_content .= "<span class='start_time'>".date_i18n(get_option('time_format'), strtotime($event->date . ' ' . $event->formatted_start_time))."</span>";
			if (!empty($event->formatted_end_time)){
				$post_content .= "<span class='time_divider'>-</span><span class='end_time'>".date_i18n(get_option('time_format'), strtotime($event->date . ' ' . $event->formatted_end_time))."</span>";
			}
			$post_content .= "</div>";
			$venue_name = (string)$event->venue_name;
			$post_content .= "<div class='schedule_venue'><a class='schedule_venue_link' href=\"[marcato-link type='marcato_venue' marcato_id='".$event->venue->id."']\">".$venue_name."</a></div>";
			$post_content .= "<table class='schedule_timeslots'>";
			foreach($event->$types as $slots){
				foreach($slots->$type as $timeslot){
					if($person == "artist" || ($person=="presenter" && (string)$timeslot->presenter_type=="artist")){
						$post_content .= "<tr><td class='time'>".date_i18n(get_option('time_format'), strtotime($event->date . ' ' . $timeslot->start))."</td><td class='artist'><a href=\"[marcato-link type='marcato_artist' marcato_id='".($person=="presenter" ? $timeslot->presenter_id : $timeslot->artist_id)."']\">".$timeslot->$person."</a></td></tr>";
					}else{
						$post_content .= "<tr><td class='time'>".date_i18n(get_option('time_format'), strtotime($event->date . ' ' . $timeslot->start))."</td><td class='artist'>".$timeslot->$person."</td></tr>";
					}
				}
			}
			$post_content .= "</table>";
			$post_content .= "</div>";
		}
		$post_type = 'page';
		$post_name = 'schedule';
		$page = compact('post_content', 'post_title', 'post_type','post_name');
		$post_marcato_id = 'marcato_schedule';
		global $wpdb;
		$meta = $wpdb->get_row("SELECT * FROM $wpdb->postmeta WHERE meta_key = '{$post_type}_id' AND meta_value = '$post_marcato_id'");
		if ($meta){
			$existing_page_id = $meta->post_id;
			$page['ID'] = $existing_page_id;
			wp_update_post($page);
		}else{
			$page['post_status'] = 'pending';
			$page_id = wp_insert_post($page, true);
			add_post_meta($page_id, "{$post_type}_id", $post_marcato_id, true);
		}
		return true;
	}
	private function set_featured_image($post_id, $post_attachment){
		#determine if the fingerprint has changed
		if(!isset($post_attachment['fingerprint']) || !isset($post_attachment['field'])){
			$fingerprint = NULL;
		}else{
			$fingerprint = get_post_meta($post_id, $post_attachment['field'].'_fingerprint', true);
		}
		$thumbnail_id = get_post_thumbnail_id($post_id);
		if(empty($fingerprint) || empty($thumbnail_id) || $fingerprint!==$post_attachment['fingerprint']){
			#delete the current featured image if there is one
			if (!empty($thumbnail_id)){
				wp_delete_attachment($thumbnail_id, true);
			}
			#Save the image from marcato and set it as the post's featured image and save the fingerprint for future reference
			if (!empty($post_attachment)){
				$filename = $this->save_image_locally($post_attachment['url'],$post_attachment['name']);
				if ($filename != null){
					$this->save_attachment($filename,$post_id);
					update_post_meta($post_id, $post_attachment['field'].'_fingerprint', $post_attachment['fingerprint']);
				}
			}
		}
	}
	private function save_attachment($filename, $post_id){
		$wp_filetype = wp_check_filetype(basename($filename));
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => preg_replace("/\.[^.]+$/", '', basename($filename)),
			'post_content' => '',
			'post_status' => 'inherit'
		);
		$attachment_id = wp_insert_attachment($attachment, $filename, $post_id);
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$attach_data = wp_generate_attachment_metadata($attachment_id, $filename);
		wp_update_attachment_metadata($attachment_id, $attach_data);
		set_post_thumbnail($post_id, $attachment_id);
	}
	private function save_image_locally($image_url, $object_name){
		#Use curl to download the image from marcato and save it to the filesystem
		$upload_dir = wp_upload_dir();
		if(array_key_exists('basedir', $upload_dir)){
			if (!file_exists($upload_dir['basedir']."/marcato")){
				mkdir($upload_dir['basedir']."/marcato");
			}
			$sanitized = preg_replace('/[^a-zA-Z0-9-_\.]/', '', $object_name);
			$filename = $upload_dir['basedir']."/marcato/".$sanitized.".jpg";
			$ch = curl_init($image_url);
			$fp = fopen($filename, 'wb');
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_exec($ch);
			curl_close($ch);
			fclose($fp);
			return $filename;
		}else{
			echo "<br />Could not save image. Upload folder does not exist or is unwritable.";
			return null;
		}
	}
	# Taken from user ridgerunner's response to http://stackoverflow.com/questions/5830387/php-regex-find-all-youtube-video-ids-in-string
	private function is_youtube_link($text) {
    if(preg_match('~
	    # Match non-linked youtube URL in the wild. (Rev:20111012)
	    https?://         # Required scheme. Either http or https.
	    (?:[0-9A-Z-]+\.)? # Optional subdomain.
	    (?:               # Group host alternatives.
	      youtu\.be/      # Either youtu.be,
	    | youtube\.com    # or youtube.com followed by
	      \S*             # Allow anything up to VIDEO_ID,
	      [^\w\-\s]       # but char before ID is non-ID char.
	    )                 # End host alternatives.
	    ([\w\-]{11})      # $1: VIDEO_ID is exactly 11 chars.
	    (?=[^\w\-]|$)     # Assert next char is non-ID or EOS.
	    [?=&+%\w\-]*        # Consume any URL (query) remainder.
	    ~ix', $text) > 0){
			return true;
		}else{
			return false;
		}
	}
	private function is_vimeo_link($text){
		if(preg_match("~vimeo\.com/(\d+)~ix",$text,$matches) > 0){
			return true;
		}else{
			return false;
		}
	}
	private function is_soundcloud_link($text){
		if (preg_match("~http://(?:www.)?soundcloud.com/(([^/]+)/([0-9a-z-/]+))~",$text,$matches) > 0){
			return true;
		}else{
			return false;
		}
	}
	private function get_video_embed_code($link){
		if($this->is_youtube_link($link)){
			return "<div class='artist_youtube_video'>[embed width='350']".$link."[/embed]</div>";
		}elseif ($this->is_vimeo_link($link)){
			return "<div class='artist_vimeo_video'>[embed width='350']".$link."[/embed]</div>";
		}elseif ($this->is_soundcloud_link($link)){
			return "<div class='artist_soundcloud_embed'>[embed width='350' height='166']".add_query_arg('show_artwork','false',$link)."[/embed]</div>";
		}else{
			return "";
		}
	}
	private function set_post_meta($post_id, $meta_data){
		if($this->options["include_meta_data"]=="1" && !empty($meta_data)){
			foreach($meta_data as $key=>$value){
				update_post_meta($post_id, (string)$key, (string)$value);
			}
		}
	}
	private function set_post_taxonomy($post_id, $taxonomy_data){
		foreach($taxonomy_data as $tax => $name){
			wp_set_object_terms($post_id, $name, $tax);
		}
	}
	private function sort_by_unix_time($a, $b){
		return intval($a->start_time_unix) - intval($b->start_time_unix);
	}
	private function sort_timeslots_by_set_time($a,$b){
		return intval($a->set_time) - intval($b->set_time);
	}
}
?>
