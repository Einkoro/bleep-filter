<?php
class WPAdmin extends BleepFilter
{
	public function __construct(){
		$this->admin_init();
	}
	
	public function admin_init(){
		/* Creates Post Type for filter words and exceptions*/
		add_action( 'init', array( $this, 'register_post_types' ) );
		
		/* Creats Custom Menu */
		add_action('init', array( $this, 'register_custom_menu') );
		
		/* Created Admin Menu */
		add_action('admin_menu', array( $this, 'bleep_filter_menu') );

		/* Loads Jquery into admin */
		add_action( 'admin_init', array( $this,'jquery_admin') );
		
		/* Creats admin settings page */
		add_action('admin_menu' , array( $this, 'register_bleep_filter_settings') ); 
		
		/* Creats admin import page */
		add_action('admin_menu' , array( $this, 'register_bleep_filter_import') ); 

		/* Creates settings link for plugin page */
		add_filter('plugin_action_links', array( $this, 'bleep_filter_words_settings_link' ), 2, 2);

		/* Register with hook 'wp_enqueue_scripts', which can be used for front end CSS and JavaScript */
		add_action( 'wp_enqueue_scripts', array( $this, 'bleep_filter_stylesheet' ) );
		
		/* Creates replacement word meta box */
		add_action( 'add_meta_boxes', array( $this, 'add_bleep_replace' ) );
		
		/* Save repladement words*/
		add_action( 'save_post', array( $this, 'save_replacements' ) );
		
		/* Changes default title to "Bad Word" */
		add_filter( 'enter_title_here', array( $this, 'change_default_title' ) );
		
	}
	
	/* Regsiters the post types for filtered words and exceptions */
	public function register_post_types() {
		
		register_post_type( 'bleep_filter_words',
			array(
				'labels' => array(
					'name' => __( 'Filtered Words' ),
					'singular_name' => __( 'Filtered Word' ),
					'add_new_item' => __( 'Add New Filtered Word', 'Filtered Word' ),
					'edit_item' => __( 'Edit Filtered Word' ),
					'new_item' => __( 'New Filtered Word' ),
					'view_item' => __( 'View Filtered Word' ),
					'search_items' => __( 'Search Filtered Words' ),
					'not_found' => __( 'No Words Found' ),
					'not_found_in_trash' => __( 'No Words Found' )
				),
			'public' => true,
			'show_in_menu' => 'bleep-filter-menu',
			'has_archive' => false,
			'publicly_queryable' => false,
			'supports' => array('title')
			)
		);
		
		register_post_type( 'bleep_exception',
			array(
				'labels' => array(
					'name' => __( 'Exception Words' ),
					'singular_name' => __( 'Exception Word' ),
					'add_new_item' => __( 'Add New Exception Word', 'Exception Word' ),
					'edit_item' => __( 'Edit Exception Word' ),
					'new_item' => __( 'New Exception Word' ),
					'view_item' => __( 'View Exception Word' ),
					'search_items' => __( 'Search Exception Words' ),
					'not_found' => __( 'No Exceptions Found' ),
					'not_found_in_trash' => __( 'No Exceptions Found' )
				),
			'public' => true,
			'show_in_menu' => 'bleep-filter-menu',
			'has_archive' => false,
			'publicly_queryable' => false,
			'supports' => array('title')
			)
		);
	}
	
	/* Creates Admin Menu */
	public function register_custom_menu(){
		register_nav_menu('custom_menu',__('Custom Menu'));
	}
	
	/* Creates Admin Menu Page */
	public function bleep_filter_menu() {
		add_menu_page('Bleep Filter', 'Bleep Filter', 'manage_options', 'bleep-filter-menu');
	}
	
	/* Creates Settings Page */
	public function register_bleep_filter_settings() {
		add_submenu_page('bleep-filter-menu', 'Filter Settings', 'Filter Settings', 'edit_posts', 'bleep-settings.php', array($this,'bleep_filter_settings'));
		add_action('admin_init', array($this, 'bleep_filter_settings_store' ) );
	}
	
	public function jquery_admin() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script('jquery-ui-slider');
		wp_enqueue_style('jquery-ui-slider');
	}
	
	/* Enqueue plugin style-file */
	public function bleep_filter_stylesheet() {
		// Respects SSL, Style.css is relative to the current file
		wp_register_style( 'filter-style', plugins_url('css/bleep_style.css', __FILE__) );			
		wp_enqueue_style( 'filter-style' );
	}
	
	/* Creates settings link for plugin page */
	public function bleep_filter_words_settings_link($actions, $file) {
		if(false !== strpos($file, 'filter')){
			$actions['settings'] = '<a href="admin.php?page=bleep-settings.php">Settings</a>';
			return $actions; 
		}
	}
	
	/* Saved Settings Variables */
	public function bleep_filter_settings_store() {
		register_setting('bleep_filter_settings', 'bleep_filter_content');
		register_setting('bleep_filter_settings', 'bleep_filter_content_rss');
		register_setting('bleep_filter_settings', 'bleep_filter_comment');
		register_setting('bleep_filter_settings', 'bleep_filter_comment_rss');
		register_setting('bleep_filter_settings', 'bleep_filter_format');
	}
	
	/* Change post title to say add bad word or exception */
	public function change_default_title( $title ){
		$screen = get_current_screen();
	 
		if('bleep_filter_words' == $screen->post_type) {
			$title = 'Enter Banned Word Here';
		}
		 
		if('bleep_exception' == $screen->post_type ) {
			$title = 'Enter Exception Word Here';
		}
	 
		return $title;
	}
	
	/* Add replacement word meta box */
	public function add_bleep_replace() {
		add_meta_box("bleep_replace",__( 'Replacement Word  - (Not Required)', 'replaceword' ), array( $this,"bleep_replacement_words"), "bleep_filter_words", "advanced", "high");
		
	}
	
	/* Replacement word meta box */
	public function bleep_replacement_words ( $post, $metabox ) {
		
		wp_nonce_field( 'bleep_replace_custom_box', 'bleep_replace_custom_box_nonce' );

		$value = get_post_meta( $post->ID, 'bleep_replacement', true );

		echo '<label for="bleep_replace_field">';
		_e( 'Replace banned word with this word: ', 'replaceword' );
		echo '</label> ';
		echo '<input type="text" id="bleep_replace_field" name="bleep_replace_field"';
        echo ' value="' . esc_attr( $value ) . '" size="100%" />';
	}
	
	
	/* Save replacement word */
	public function save_replacements( $post_id ) {

		if( ! isset( $_POST['bleep_replace_custom_box_nonce'] ) ) {
			return $post_id;
		}

		$nonce = $_POST['bleep_replace_custom_box_nonce'];

		if( ! wp_verify_nonce( $nonce, 'bleep_replace_custom_box' ) ) {
			return $post_id;
		}

		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if( 'page' == $_POST['post_type'] ) {
			if( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		}
		else {
			if( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}

		$mydata = sanitize_text_field( $_POST['bleep_replace_field'] );
		update_post_meta( $post_id, 'bleep_replacement', $mydata );
	}
	

	/* Creates Import Page */
	public function register_bleep_filter_import() {
		add_submenu_page('bleep-filter-menu', 'Import', 'Import', 'edit_posts', 'bleep-import.php', array($this,'bleep_filter_import'));
	}
	
	public function import_bleeps($files){
		if ( is_admin() ) {
			if($files){
				ini_set('auto_detect_line_endings',TRUE);
				$csv = '';
				$type = '';
				if($files['bleep_words']){
					$csv = $files['bleep_words']['tmp_name'];
					$type = "bleep_filter_words";
				}
				elseif($files['bleep_exceptions']){
					$csv = $files['bleep_exceptions']['tmp_name'];
					$type = "bleep_exception";
				}
			
				if (($handle = fopen($csv, "r")) !== FALSE) {
					$word_count = 0;
					while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
						$num = count($data);
						for ($c=0; $c < $num; $c++) {
							if(!get_page_by_title($data[$c], 'OBJECT', "$type")){
								$word_count++;
								$post = array(
									  'comment_status' =>  'closed',
									  'post_author' => 1,
									  'post_date' => date('Y-m-d H:i:s'),
									  'post_status' => 'publish', 
									  'post_title' => $data[$c], 
									  'post_type' => "$type" // custom type
									);  
								wp_insert_post($post); 
							}
			
						}
					}
					return "<h2>=== Import Complete ===</h2><h3><em>$word_count words added</em></h3>";
					fclose($handle);
				}
			}
				
			ini_set('auto_detect_line_endings',FALSE);
		}	
	}
		
		
	
	public function bleep_filter_import(){
		?>
		<div class="wrap">
        	<h2>Bleep Filter Import</h2>
            	<p>Here you can import bad words and exceptions using a <strong>CSV file</strong> or <strong>comma separated text file.</strong></p>
                <?php
					if(isset($_POST['import'])){
						echo $this->import_bleeps($_FILES);
					}
				?>
            <h3>Import Filtered Words</h3>
			<form action="<?php echo "admin.php?page=bleep-import.php"; ?>" method="post" enctype="multipart/form-data" >
            	<input type="file" name="bleep_words" id="bleep_words" /><input class="button-primary"  type="submit" value="import" name="import" />
            </form><br /><br />
            <h3>Import Exception Words</h3>
			<form action="<?php echo "admin.php?page=bleep-import.php"; ?>" method="post" enctype="multipart/form-data" >
             	<input type="file" name="bleep_exceptions" id="bleep_exceptions" /><input class="button-primary"  type="submit" value="import" name="import" />
             </form>	
        </div>
        <?php
	}
	
	
	/* Settings Link Page */
	public function bleep_filter_settings() { 
		$style = $this->bleep_filter_stylesheet();
		?>
		<div class="wrap">
			<h2>Bleep Filter Plugin Settings</h2>
	
			<form method="post" action="options.php">
				<?php settings_fields('bleep_filter_settings');  ?>            
				<h3 class='bleep_filter_mtop_sm'>What do you want filtered?</h3>
				<?php 
					$bleep_filter_content = get_option('bleep_filter_content'); 
					$bleep_filter_content_rss = get_option('bleep_filter_content_rss'); 
					$bleep_filter_comment = get_option('bleep_filter_comment'); 
					$bleep_filter_comment_rss = get_option('bleep_filter_comment_rss'); 
					$bleep_filter_format = get_option('bleep_filter_format');
				?>
				<p><input name='bleep_filter_content'  type="checkbox" <?php if($bleep_filter_content=='on'){echo "checked";} ?> /> <label for="bleep_filter_content">Pages & Posts</label></p>
				<p><input name='bleep_filter_content_rss'   type="checkbox" <?php if($bleep_filter_content_rss=='on'){echo "checked";} ?> /> <label for="bleep_filter_content_rss">RSS Pages & RSS Posts</label></p>
				<p><input name='bleep_filter_comment'   type="checkbox" <?php if($bleep_filter_comment=='on'){echo "checked";} ?> /> <label for="bleep_filter_comment">Comments</label></p>
				<p><input name='bleep_filter_comment_rss'   type="checkbox" <?php if($bleep_filter_comment_rss=='on'){echo "checked";} ?> /> <label for="bleep_filter_comment_rss">Comments RSS</label></p>
					
				<div class='bleep_filter_mtop'>
					<h3 class='bleep_filter_mtop_sm'>How do you want it styled?</h3>
					<p><em>examples use the word <strong>shazbot</strong> to show styling</em></p>
					<p><input type="radio" name="bleep_filter_format" value="erase" <?php if($bleep_filter_format=='erase'){echo "checked";} ?> > <label for="bleep_filter_format">Erase Word</label> (<em>Example:</em> What the !)</p>
					<p><input type="radio" name="bleep_filter_format" value="blackout" <?php if($bleep_filter_format=='blackout'){echo "checked";} ?> > <label for="bleep_filter_format">Blackout Spoiler Word</label> (<em>Example:</em> What the <span class='blackout bleep_filter_blackout'>shazbot</span>!)</p>
					<p><input type="radio" name="bleep_filter_format" value="blackout_erase" <?php if($bleep_filter_format=='blackout_erase'){echo "checked";} ?> > <label for="bleep_filter_format">Blackout & Erase Word</label> (<em>Example:</em> What the <span class='blackout bleep_filter_blackout_black'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>!)</p>
					<p><input type="radio" name="bleep_filter_format" value="strikeout" <?php if($bleep_filter_format=='strikeout'){echo "checked";} ?> > <label for="bleep_filter_format">Strikeout Word</label> (<em>Example:</em> What the <span class='blackout'><strike>shazbot</strike></span>!)</p>
					<p><input type="radio" name="bleep_filter_format" value="bleep" <?php if($bleep_filter_format=='bleep'){echo "checked";} ?> > <label for="bleep_filter_format">Bleep Word</label> (<em>Example:</em> What the <span class='blackout'>!$@*%^& </span>!)</p>
					<p><input type="radio" name="bleep_filter_format" value="asterisk" <?php if($bleep_filter_format=='asterisk'){echo "checked";} ?> > <label for="bleep_filter_format">Asterisk Entire Word</label> (<em>Example:</em> What the <span class='blackout'>*******</span>!)</p>
				</div>
				<p class="submit">
					<input type="submit" class="button-primary" value="Save Changes" />
				</p>
			</form>
		</div>
	
	<?php }
}
?>
