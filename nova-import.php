<?php
/**
 * @package Nova Import
 * @version 1.0
 */
/*
Plugin Name: Nova Importer
Plugin URI: http://eliteup.net
Description: Import Dummy Data for EliteUp Theme
Author: Dzung Nova
Version: 1.0
Author URI: http://eliteup.net
*/
define( 'NOVAIMPORT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NOVAIMPORT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

if (!function_exists ('add_action')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}
function nova_import_add_js_css() {
    wp_register_style('nova-import', plugins_url('assets/css/import.css',__FILE__ ));
    wp_enqueue_style('nova-import');
}
add_action( 'admin_init','nova_import_add_js_css');

class Nova_Import {

	public $message = "";
	public $attachments = false;
	function Nova_Import() {
		add_action('admin_menu', array(&$this, 'nova_admin_import'));
		add_action('admin_init', array(&$this, 'register_nova_theme_settings'));
	}
	function register_nova_theme_settings() {
		register_setting( 'nova_options_import_page', 'nova_options_import');
	}

	public function import_content($file){
		if (!class_exists('WP_Importer')) {
			ob_start();
			$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
			require_once($class_wp_importer);
			require_once(NOVAIMPORT_PLUGIN_DIR . 'class.wordpress-importer.php');
			$nova_import = new WP_Import();
			set_time_limit(0);
			$path = NOVAIMPORT_PLUGIN_DIR . 'dummy/' . $file;

			$nova_import->fetch_attachments = $this->attachments;
			$returned_value = $nova_import->import($path);
			if(is_wp_error($returned_value)){
				$this->message = __("An Error Occurred During Import", "novaimporter");
			}
			else {
				$this->message = __("Content imported successfully", "novaimporter");
			}
			ob_get_clean();
		} else {
			$this->message = __("Error loading files", "novaimporter");
		}
	}

	public function import_widgets($file){
		// Add data to widgets
		$widgets_json = NOVAIMPORT_PLUGIN_URL . 'dummy/' . $file;
		$widgets_json = wp_remote_get($widgets_json);
		$widget_data = $widgets_json['body'];
		$import_widgets = nova_import_widget_data( $widget_data );
		$this->message = __("Widgets imported successfully", "novaimporter");
	}

	public function import_menus($file){
		if (!class_exists('WP_Importer')) {
			ob_start();
			$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
			require_once($class_wp_importer);
			require_once(NOVAIMPORT_PLUGIN_DIR . 'class.wordpress-importer.php');
			$nova_import = new WP_Import();
			set_time_limit(0);
			$path = NOVAIMPORT_PLUGIN_DIR . 'dummy/' . $file;

			$nova_import->fetch_attachments = $this->attachments;
			$returned_value = $nova_import->import($path);
			if(is_wp_error($returned_value)){
				$this->message = __("An Error Occurred During Import", "novaimporter");
			}
			else {
				$this->message = __("Content imported successfully", "novaimporter");
			}
			ob_get_clean();
		} else {
			$this->message = __("Error loading files", "novaimporter");
		}
		// Set imported menus to registered theme locations
		$locations = get_theme_mod( 'nav_menu_locations' ); // registered menu locations in theme
		$menus = wp_get_nav_menus(); // registered menus

		if($menus) {
			foreach($menus as $menu) { // assign menus to theme locations
				
				if($menu->name == 'Main Menu') {
					$locations['primary'] = $menu->term_id;
				} else if( $menu->name == 'Top Menu' ) {
					$locations['top-menu'] = $menu->term_id;
				}
			}
		}

		set_theme_mod( 'nav_menu_locations', $locations ); // set menus to locations
	}
	public function import_settings_pages($file){
		$pages = $this->file_options($file);
		foreach($pages as $nova_page_option => $nova_page_id){
			update_option( $nova_page_option, $nova_page_id);
		}
	}
	public function file_options($file){
		$file_content = "";
		$file_for_import = NOVAIMPORT_PLUGIN_DIR . 'dummy/' . $file;
		if ( file_exists($file_for_import) ) {
			$file_content = $this->nova_file_contents($file_for_import);
		} else {
			$this->message = __("File doesn't exist", "novaimporter");
		}
		if ($file_content) {

			$unserialized_content = unserialize(base64_decode($file_content));
			if ($unserialized_content) {
				return $unserialized_content;
			}
		}
		return false;
	}

	function nova_file_contents( $path ) {
		$nova_content = '';
		if ( function_exists('realpath') )
			$filepath = realpath($path);
		if ( !$filepath || !@is_file($filepath) )
			return '';

		if( ini_get('allow_url_fopen') ) {
			$nova_file_method = 'fopen';
		} else {
			$nova_file_method = 'file_get_contents';
		}
		if ( $nova_file_method == 'fopen' ) {
			$nova_handle = fopen( $filepath, 'rb' );

			if( $nova_handle !== false ) {
				while (!feof($nova_handle)) {
					$nova_content .= fread($nova_handle, 8192);
				}
				fclose( $nova_handle );
			}
			return $nova_content;
		} else {
			return file_get_contents($filepath);
		}
	}

	function nova_admin_import() {
		$this->pagehook = add_submenu_page('themes.php', 'Appearance', esc_html__('TheBear Demos', 'novaimporter'), 'manage_options', 'nova_options_import_page', array(&$this, 'nova_generate_import_page'));

	}

	function nova_generate_import_page() {

		?>
		<div id="nova-metaboxes-general" class="wrap">
			<h2><?php _e('TheBear - One-Click Import Demo Content', 'novaimporter') ?></h2>
			<form method="post" action="" id="importContentForm">
				<div id="poststuff" class="metabox-holder">
					<div id="post-body" class="has-sidebar">
						<div id="post-body-content" class="has-sidebar-content">
							<table class="form-table">
								<tbody>
								<tr valign="middle">
									<td scope="row" width="150"><?php esc_html_e('Import', 'novaimporter'); ?></td>
									<td>
										<select name="import_example" id="import_example">
											<option value="main">Main Site</option>
										</select>
										<select name="import_option" id="import_option">
											<option value="">Please Select</option>
											<option value="complete_content">All</option>
											<option value="content">Content</option>
											<option value="widgets">Widgets</option>
										</select>
										<input type="submit" value="Import" name="import" id="import_demo_data" />
									</td>
								</tr>
								<tr valign="middle">
									<td scope="row" width="150"><?php esc_html_e('Import attachments', 'novaimporter'); ?></td>
									<td>
										<input type="checkbox" value="1" name="import_attachments" id="import_attachments" />

									</td>
								</tr>
								<tr class="loading-row"><td></td><td><div class="import_load"><span><?php _e('The import process may take some time. Please be patient.', 'novaimporter') ?> </span><br />
											<div class="nova-progress-bar-wrapper html5-progress-bar">
												<div class="progress-bar-wrapper">
													<progress id="progressbar" value="0" max="100"></progress>
													<span class="progress-value">0%</span>
												</div>
												<div class="progress-bar-message">
												</div>
											</div>
										</div></td></tr>
								<tr><td colspan="2">
										<?php _e('Important notes:', 'novaimporter') ?><br />
										- <?php _e('Please note that import process will take time needed to download all attachments from demo web site.', 'novaimporter'); ?><br />
										- <?php _e('If you plan to use shop, please install WooCommerce before you run import.', 'novaimporter') ?>
									</td></tr>
								<tr><td></td><td><div class="success_msg" id="success_msg"><?php echo $this->message; ?></div></td></tr>
								</tbody>
							</table>
						</div>
					</div>
					<br class="clear"/>
				</div>
			</form>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery(document).on('click', '#import_demo_data', function(e) {
						e.preventDefault();
						if (confirm('Are you sure, you want to import Demo Data now?')) {
							jQuery('.import_load').css('display','block');
							var progressbar = jQuery('#progressbar')
							var import_opt = jQuery( "#import_option" ).val();
							var import_expl = jQuery( "#import_example" ).val();
							var p = 0;
							if(import_opt == 'content'){
								for(var i=1;i<10;i++){
									var str;
									if (i < 10) str = 'thebear_content_0'+i+'.xml';
									else str = 'thebear_content_'+i+'.xml';
									jQuery.ajax({
										type: 'POST',
										url: ajaxurl,
										data: {
											action: 'nova_dataImport',
											xml: str,
											example: import_expl,
											import_attachments: (jQuery("#import_attachments").is(':checked') ? 1 : 0)
										},
										success: function(data, textStatus, XMLHttpRequest){
											p+= 10;
											jQuery('.progress-value').html((p) + '%');
											progressbar.val(p);
											if (p == 90) {
												str = 'thebear_content_10.xml';
												jQuery.ajax({
													type: 'POST',
													url: ajaxurl,
													data: {
														action: 'nova_dataImport',
														xml: str,
														example: import_expl,
														import_attachments: (jQuery("#import_attachments").is(':checked') ? 1 : 0)
													},
													success: function(data, textStatus, XMLHttpRequest){
														p+= 10;
														jQuery('.progress-value').html((p) + '%');
														progressbar.val(p);
														jQuery('.progress-bar-message').html('<br />Import is completed.');
													},
													error: function(MLHttpRequest, textStatus, errorThrown){
													}
												});
											}
										},
										error: function(MLHttpRequest, textStatus, errorThrown){
										}
									});
								}
							} else if(import_opt == 'widgets') {
								jQuery.ajax({
									type: 'POST',
									url: ajaxurl,
									data: {
										action: 'nova_widgetsImport',
										example: import_expl
									},
									success: function(data, textStatus, XMLHttpRequest){
										jQuery('.progress-value').html((100) + '%');
										progressbar.val(100);
									},
									error: function(MLHttpRequest, textStatus, errorThrown){
									}
								});
								jQuery('.progress-bar-message').html('<br />Import is completed.');
							}else if(import_opt == 'complete_content'){
								for(var i=1;i<10;i++){
									var str;
									if (i < 10) str = 'thebear_content_0'+i+'.xml';
									else str = 'thebear_content_'+i+'.xml';
									jQuery.ajax({
										type: 'POST',
										url: ajaxurl,
										data: {
											action: 'nova_dataImport',
											xml: str,
											example: import_expl,
											import_attachments: (jQuery("#import_attachments").is(':checked') ? 1 : 0)
										},
										success: function(data, textStatus, XMLHttpRequest){
											p+= 10;
											jQuery('.progress-value').html((p) + '%');
											progressbar.val(p);
											if (p == 90) {
												str = 'thebear_content_10.xml';
												jQuery.ajax({
													type: 'POST',
													url: ajaxurl,
													data: {
														action: 'nova_dataImport',
														xml: str,
														example: import_expl,
														import_attachments: (jQuery("#import_attachments").is(':checked') ? 1 : 0)
													},
													success: function(data, textStatus, XMLHttpRequest){
														jQuery.ajax({
															type: 'POST',
															url: ajaxurl,
															data: {
																action: 'nova_otherImport',
																example: import_expl
															},
															success: function(data, textStatus, XMLHttpRequest){
																console.log(data);
																jQuery('.progress-value').html((100) + '%');
																progressbar.val(100);
																jQuery('.progress-bar-message').html('<br />Import is completed.');
															},
															error: function(MLHttpRequest, textStatus, errorThrown){
															}
														});
													
													},
													error: function(MLHttpRequest, textStatus, errorThrown){
													}
												});
											}
										},
										error: function(MLHttpRequest, textStatus, errorThrown){
										}
									});
								}
							}
						}
						return false;
					});
				});
			</script>

		</div>

	<?php	}

}
global $my_Nova_Import;
$my_Nova_Import = new Nova_Import();



if(!function_exists('nova_dataImport'))
{
	function nova_dataImport()
	{
		global $my_Nova_Import;

		if ($_POST['import_attachments'] == 1)
			$my_Nova_Import->attachments = true;
		else
			$my_Nova_Import->attachments = false;
			
		$folder = "main/";
		if (!empty($_POST['example']))
			$folder = $_POST['example']."/";

		$my_Nova_Import->import_content($folder.$_POST['xml']);

		die();
	}

	add_action('wp_ajax_nova_dataImport', 'nova_dataImport');
}

if(!function_exists('nova_widgetsImport'))
{
	function nova_widgetsImport()
	{
		global $my_Nova_Import;
		
		$folder = "main/";
		if (!empty($_POST['example']))
			$folder = $_POST['example']."/";

		$my_Nova_Import->import_widgets($folder.'widget_data.json');

		die();
	}

	add_action('wp_ajax_nova_widgetsImport', 'nova_widgetsImport');
}

if(!function_exists('nova_otherImport'))
{
	function nova_otherImport()
	{
		global $my_Nova_Import;
		
		$folder = "main/";
		if (!empty($_POST['example']))
			$folder = $_POST['example']."/";

		$my_Nova_Import->import_widgets($folder.'widget_data.json');
		$my_Nova_Import->import_menus($folder.'menus.xml');
		$my_Nova_Import->import_settings_pages($folder.'page_settings.txt');

		die();
	}

	add_action('wp_ajax_nova_otherImport', 'nova_otherImport');
}
// Parsing Widgets Function
// Thanks to http://wordpress.org/plugins/widget-settings-importexport/
function nova_import_widget_data( $widget_data ) {
	$json_data = $widget_data;
	$json_data = json_decode( $json_data, true );
	$sidebar_data = $json_data[0];
	$widget_data = $json_data[1];

	foreach ( $widget_data as $widget_data_title => $widget_data_value ) {
		$widgets[ $widget_data_title ] = '';
		foreach( $widget_data_value as $widget_data_key => $widget_data_array ) {
			if( is_int( $widget_data_key ) ) {
				$widgets[$widget_data_title][$widget_data_key] = 'on';
			}
		}
	}
	unset($widgets[""]);

	foreach ( $sidebar_data as $title => $sidebar ) {
		$count = count( $sidebar );
		for ( $i = 0; $i < $count; $i++ ) {
			$widget = array( );
			$widget['type'] = trim( substr( $sidebar[$i], 0, strrpos( $sidebar[$i], '-' ) ) );
			$widget['type-index'] = trim( substr( $sidebar[$i], strrpos( $sidebar[$i], '-' ) + 1 ) );
			if ( !isset( $widgets[$widget['type']][$widget['type-index']] ) ) {
				unset( $sidebar_data[$title][$i] );
			}
		}
		$sidebar_data[$title] = array_values( $sidebar_data[$title] );
	}

	foreach ( $widgets as $widget_title => $widget_value ) {
		foreach ( $widget_value as $widget_key => $widget_value ) {
			$widgets[$widget_title][$widget_key] = $widget_data[$widget_title][$widget_key];
		}
	}

	$sidebar_data = array( array_filter( $sidebar_data ), $widgets );

	nova_parse_import_data( $sidebar_data );
}

function nova_parse_import_data( $import_array ) {
	global $wp_registered_sidebars;
	$sidebars_data = $import_array[0];
	$widget_data = $import_array[1];
	$current_sidebars = get_option( 'sidebars_widgets' );
	$new_widgets = array( );

	foreach ( $sidebars_data as $import_sidebar => $import_widgets ) :

		foreach ( $import_widgets as $import_widget ) :
			//if the sidebar exists
			if ( isset( $wp_registered_sidebars[$import_sidebar] ) ) :
				$title = trim( substr( $import_widget, 0, strrpos( $import_widget, '-' ) ) );
				$index = trim( substr( $import_widget, strrpos( $import_widget, '-' ) + 1 ) );
				$current_widget_data = get_option( 'widget_' . $title );
				$new_widget_name = nova_get_new_widget_name( $title, $index );
				$new_index = trim( substr( $new_widget_name, strrpos( $new_widget_name, '-' ) + 1 ) );

				if ( !empty( $new_widgets[ $title ] ) && is_array( $new_widgets[$title] ) ) {
					while ( array_key_exists( $new_index, $new_widgets[$title] ) ) {
						$new_index++;
					}
				}
				$current_sidebars[$import_sidebar][] = $title . '-' . $new_index;
				if ( array_key_exists( $title, $new_widgets ) ) {
					$new_widgets[$title][$new_index] = $widget_data[$title][$index];
					$multiwidget = $new_widgets[$title]['_multiwidget'];
					unset( $new_widgets[$title]['_multiwidget'] );
					$new_widgets[$title]['_multiwidget'] = $multiwidget;
				} else {
					$current_widget_data[$new_index] = $widget_data[$title][$index];
					$current_multiwidget = $current_widget_data['_multiwidget'];
					$new_multiwidget = isset($widget_data[$title]['_multiwidget']) ? $widget_data[$title]['_multiwidget'] : false;
					$multiwidget = ($current_multiwidget != $new_multiwidget) ? $current_multiwidget : 1;
					unset( $current_widget_data['_multiwidget'] );
					$current_widget_data['_multiwidget'] = $multiwidget;
					$new_widgets[$title] = $current_widget_data;
				}

			endif;
		endforeach;
	endforeach;

	if ( isset( $new_widgets ) && isset( $current_sidebars ) ) {
		update_option( 'sidebars_widgets', $current_sidebars );

		foreach ( $new_widgets as $title => $content )
			update_option( 'widget_' . $title, $content );

		return true;
	}

	return false;
}

function nova_get_new_widget_name( $widget_name, $widget_index ) {
	$current_sidebars = get_option( 'sidebars_widgets' );
	$all_widget_array = array( );
	foreach ( $current_sidebars as $sidebar => $widgets ) {
		if ( !empty( $widgets ) && is_array( $widgets ) && $sidebar != 'wp_inactive_widgets' ) {
			foreach ( $widgets as $widget ) {
				$all_widget_array[] = $widget;
			}
		}
	}
	while ( in_array( $widget_name . '-' . $widget_index, $all_widget_array ) ) {
		$widget_index++;
	}
	$new_widget_name = $widget_name . '-' . $widget_index;
	return $new_widget_name;
}
