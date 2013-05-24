<?php
/*
Plugin Name: Bleep Filter
Plugin URI: http://www.filterplugin.com
Description: A better word filter that passively removes unwanted words from your wordpress site by easily capturing common misspellings and deliberate obfuscation
Version: 0.4
Author: Nathan Lampe
Author URI: http://www.nathanlampe.com
License: GPL2
*/


/*  Copyright 2013  Nathan Lampe  (email : nathan@nathanlampe.com)

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

class BleepFilterPlugin
{
		
		public function __construct(){
			/* Creates Post Type for filter words and exceptions*/
			add_action( 'init', array( $this, 'create_post_type' ) );
			/* Creats admin menu */
			add_action('init', array( $this, 'register_custom_menu') );
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
			
			$bleep_filter_content = get_option('bleep_filter_content'); 
			$bleep_filter_content_rss = get_option('bleep_filter_content_rss'); 
			$bleep_filter_comment = get_option('bleep_filter_comment'); 
			$bleep_filter_comment_rss = get_option('bleep_filter_comment_rss');
			
			if ( ! is_admin() ) {
				/* Check which settings are toggled on */
				if($bleep_filter_content == 'on'){
					add_filter( 'the_content' , array( $this, 'word_filter' ) , 50 );
					add_filter( 'the_excerpt' , array( $this, 'word_filter' ), 50 );
					add_filter( 'the_title' , array( $this, 'word_filter' ), 50 );
				}
				if($bleep_filter_content_rss == 'on'){
					add_filter( 'the_content_rss' , array( $this, 'word_filter' ) , 50 );
					add_filter( 'the_excerpt_rss' , array( $this, 'word_filter' ) , 50 );
					add_filter( 'the_title_rss' , array( $this, 'word_filter' ) , 50 );
				}
				
				if($bleep_filter_comment == 'on'){
					add_filter( 'comment_text' , array( $this, 'word_filter' ), 50);
					add_filter( 'comment_excerpt' , array( $this, 'word_filter' ), 50);
				}
				
				if($bleep_filter_comment_rss == 'on'){
					add_filter( 'comment_text_rss' , array( $this, 'word_filter' ), 50 );
					add_filter( 'comment_excerpt_rss' , array( $this, 'word_filter' ), 50);
				}
			}

		}

		
		/* Regsiters the post types for filtered words and exceptions */
		public function create_post_type() {
			
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
		
		
		/* Enqueue plugin style-file */
		public function bleep_filter_stylesheet() {
			// Respects SSL, Style.css is relative to the current file
			wp_register_style( 'filter-style', plugins_url('css/bleep_style.css', __FILE__) );			
			wp_enqueue_style( 'filter-style' );
			wp_register_style( 'jquery-ui-slider', plugins_url('css/jquery-ui-1.10.2.custom.min.css', __FILE__) );
			wp_enqueue_style( 'jquery-ui-slider' );
		}
		
		
		/* Creates Admin Menu Page */
		function bleep_filter_menu() {
			add_menu_page('Bleep Filter', 'Bleep Filter', 'manage_options', 'bleep-filter-menu');
		}
		
		/* Creates Admin Menu */
		function register_custom_menu(){
			register_nav_menu('custom_menu',__('Custom Menu'));
		}
		
		/* Creates Settings Page */
		function register_bleep_filter_settings() {
			add_submenu_page('bleep-filter-menu', 'Filter Settings', 'Filter Settings', 'edit_posts', 'bleep-settings.php', array($this,'bleep_filter_settings'));
			add_action('admin_init', array($this, 'bleep_filter_settings_store' ) );
		}
		
		/* Creates Import Page */
		function register_bleep_filter_import() {
			add_submenu_page('bleep-filter-menu', 'Import', 'Import', 'edit_posts', 'bleep-import.php', array($this,'bleep_filter_import'));
		}
		
		/* Saved Settings Variables */
		function bleep_filter_settings_store() {
			register_setting('bleep_filter_settings', 'bleep_filter_word_intensity');
			register_setting('bleep_filter_settings', 'bleep_filter_content');
			register_setting('bleep_filter_settings', 'bleep_filter_content_rss');
			register_setting('bleep_filter_settings', 'bleep_filter_comment');
			register_setting('bleep_filter_settings', 'bleep_filter_comment_rss');
			register_setting('bleep_filter_settings', 'bleep_filter_format');
		}
		

		function jquery_admin() {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script('jquery-ui-slider');
			wp_enqueue_style('jquery-ui-slider');
		}

		/* Creates settings link for plugin page */
		function bleep_filter_words_settings_link($actions, $file) {
			if(false !== strpos($file, 'filter')){
				$actions['settings'] = '<a href="admin.php?page=bleep-settings.php">Settings</a>';
				return $actions; 
			}
		}
		
		
		function import_bleeps($_FILES){
			if ( is_admin() ) {
				if($_FILES){
					ini_set('auto_detect_line_endings',TRUE);
					$csv = '';
					$type = '';
					if($_FILES['bleep_words']){
						$csv = $_FILES['bleep_words']['tmp_name'];
						$type = "bleep_filter_words";
					}
					elseif($_FILES['bleep_exceptions']){
						$csv = $_FILES['bleep_exceptions']['tmp_name'];
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
		
		
		
		function bleep_filter_import(){
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
		function bleep_filter_settings() { 
			$intensity = get_option('bleep_filter_word_intensity');
			if(get_option('bleep_filter_word_intensity')){
				$intensity =  get_option('bleep_filter_word_intensity');
			}
			else{
				$intensity = 45;	
			}
			$style = $this->bleep_filter_stylesheet();
		?>
			<script>
			jQuery(document).ready(function(){
			 jQuery( "#bleep_filter_slider" ).slider({
			  value: <?php if($intensity >= 0){echo $intensity;}else{echo 45; } ?>,
			  min: 0,
			  max: 100,
			  step: 5,
			  slide: function( event, ui ) {
				 jQuery( "#bleep_filter_amount" ).val(  ui.value  );
			  }
			});
			
			jQuery( "#bleep_filter_amount" ).val( $( "#bleep_filter_slider" ).slider( "value" ));
			
			
		
		});</script>
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
					
					
					  <p class='bleep_filter_mtop'>
					   <h3>How exact should the word have to be to get filtered?</h3>
					  <label for="amount">Filter Word Match Intensity (decreasing too low may cause unintented words to be filtered):</label><br />
					  <input type="text" id="bleep_filter_amount" size='1' name='bleep_filter_word_intensity' class='bleep_filter_word_intensity' value="<?php if($intensity >= 0){echo $intensity;}else{echo 45; } ?>" /> <span class='bleep_filter_word_percent'>% Word Match Intensity</span>
					  <div id="bleep_filter_slider" class='bleep_filter_word_intensity_slider'></div>
					</p>
						
					<div class='bleep_filter_mtop'>
						<h3 class='bleep_filter_mtop_sm'>How do you want it styled?</h3>
						<p><em>examples use the word <strong>shazbot</strong> to show styling</em></p>
						<p><input type="radio" name="bleep_filter_format" value="erase" <?php if($bleep_filter_format=='erase'){echo "checked";} ?> > <label for="bleep_filter_format">Erase Word</label> (<em>Example:</em> What the !)</p>
						<p><input type="radio" name="bleep_filter_format" value="blackout" <?php if($bleep_filter_format=='blackout'){echo "checked";} ?> > <label for="bleep_filter_format">Blackout Spoiler Word</label> (<em>Example:</em> What the <span class='blackout bleep_filter_blackout'>shazbot</span>!)</p>
						<p><input type="radio" name="bleep_filter_format" value="blackout_erase" <?php if($bleep_filter_format=='blackout_erase'){echo "checked";} ?> > <label for="bleep_filter_format">Blackout & Erase Word</label> (<em>Example:</em> What the <span class='blackout bleep_filter_blackout_black'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>!)</p>
						<p><input type="radio" name="bleep_filter_format" value="strikeout" <?php if($bleep_filter_format=='strikeout'){echo "checked";} ?> > <label for="bleep_filter_format">Strikeout Word</label> (<em>Example:</em> What the <span class='blackout'><strike>shazbot</strike></span>!)</p>
						<p><input type="radio" name="bleep_filter_format" value="bleep" <?php if($bleep_filter_format=='bleep'){echo "checked";} ?> > <label for="bleep_filter_format">Bleep Word</label> (<em>Example:</em> What the <span class='blackout'>!$@*%^& </span>!)</p>
						<p><input type="radio" name="bleep_filter_format" value="asterisk" <?php if($bleep_filter_format=='asterisk'){echo "checked";} ?> > <label for="bleep_filter_format">Asterisk Word</label> (<em>Example:</em> What the <span class='blackout'>*******</span>!)</p>
					</div>
					<p class="submit">
						<input type="submit" class="button-primary" value="Save Changes" />
					</p>
				</form>
			</div>
		
		<?php }

				
		/* Grabs filtered words as array */
		function get_words(){
			
			$alert_words = array();
				$the_posts = get_posts(array('post_type' => 'bleep_filter_words','numberposts'     => -1));
				foreach($the_posts as $post){
					array_push($alert_words,$post->post_title);
				}
			return $alert_words;
			
		}
		
		/* Grabs word exceptions as array */
		function get_exceptions(){
			
			$exception_words = array();
				$the_posts = get_posts(array('post_type' => 'bleep_exception','numberposts'     => -1));
				foreach($the_posts as $post){
					array_push($exception_words,$post->post_name);
				}
			return $exception_words;
			
		}
		
		/* Gets before and after text formatting 
		   $formatting[0] is used for before text formatting
		   $formatting[1] is used for adter text formatting
		   $formatting[2] is used as replacement string for string_replace
		*/
		public function filterFormatting($bleep_filter_format){
			$formatting = array();
		
			switch($bleep_filter_format){
				case 'erase':
					$formatting[0] = '';
					$formatting[1] = '';
					break;
				case 'blackout':
					$formatting[0] = "<span class='bleep_filter_blackout'>";
					$formatting[1] = '</span>';
					break;
				case 'blackout_erase':
					$formatting[0] = "<span class='bleep_filter_blackout_erase'>";
					$formatting[1] = '</span>';
					break;
				case 'strikeout':
					$formatting[0] = "<span class='bleep_filter_strikeout'>";
					$formatting[1] = '</span>';
					break;
				case 'bleep':
					$formatting[0] = '';
					$formatting[1] = '';
					break;
				case 'asterisk':
					$formatting[0] = '';
					$formatting[1] = '';
					$formatting[2] = '*';
					break;
				default:
					$formatting[0] = '';
					$formatting[1] = '';
					break;
			}
			
			return $formatting;
		}
		
		/* Filters filtered words */
		function word_filter($comment){
			
			/* Intensity refers to the similarity match in the metaphone. 
			A higher intensity would mean a more exact match is required. 
			A lower intensity would catch the most words but setting too 
			low may cause unwanted words to be filtered out */
			$intensity =  get_option('bleep_filter_word_intensity');
			$bleep_filter_format =  get_option('bleep_filter_format');
			$words_formatting = $this->filterFormatting($bleep_filter_format);
			$word_before = $words_formatting[0];
			$word_after = $words_formatting[1];
			$words_format = $words_formatting[2];
			
			/* The post / comment text */
			$words = $comment;
			$words = preg_replace('/\s+/', ' ',$words);
			$word_count = str_word_count($words,0,'0123456789');
			$words_explode = explode(' ',$words);
			
			$alert_words = $this->get_words();
			$exceptions = $this->get_exceptions();
				
			$words_metaphone = '';
			$alert_words_metaphone = array();
			
			/* Loop through all words to filter our and create a metaphone array */
			foreach($alert_words as $alert_phrase){
				$alert_phrase_count = str_word_count($alert_phrase,1);
				$alert_phrase_metaphone = '';
				$new_alert_phrase = '';
				if($alert_phrase_count >= 1){
					foreach($alert_phrase_count as $alert_phrase_value){
						$new_alert_phrase .= metaphone($alert_phrase_value).' ';
					}
					array_push($alert_words_metaphone,rtrim($new_alert_phrase));
				}
				else{
					$alert_phrase_explode = explode(' ',$alert_phrase);
					foreach($alert_phrase_explode as $alert_word){
						$new_alert_phrase .= ' '.metaphone($alert_word);
					}
					array_push($alert_words_metaphone,ltrim($new_alert_phrase));
				}
			}
			
			/* Create metaphone for each word in the post */
			foreach($words_explode as $word){
				$metaphone_word = metaphone($word);
				
				if(trim($metaphone_word) == ''){
					$metaphone_word = $word;
				}
				
				$words_metaphone .= ' '.$metaphone_word;
			}
				
			$words_metaphone = ltrim($words_metaphone);
			
			/* Loop through metaphone words to filter */
			foreach($alert_words_metaphone as $alert_word_metaphone){
				
				$pattern = '/'.$alert_word_metaphone.'/';
				
				/* Find all metaphone matchs containg the metaphone of the word to filter */
				preg_match_all($pattern, $words_metaphone, $matches, PREG_OFFSET_CAPTURE);
				foreach($matches as $match=>$m){
					
					$string = '';
					$char_start = '';
					$string_len = '';
					
					if($m){
						foreach($m as $ma){
							
							$string = $ma[0];
							$string_len = strlen($string);
							$char_start = $ma[1]+1;
							
							if($string_len != ''){
								$meta_substr = substr($words_metaphone,0,$char_start);
								
								/* finds all words in the found matches array */
								if(str_word_count($meta_substr) >= 0){
									/* finds out the starting word placement in the metaphone array */
									$words_start = str_word_count($meta_substr,0,'0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ');
									
									/* finds out the end word placement in the metaphone array */
									$words_end = $words_start + (str_word_count($alert_word_metaphone,0,'0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ')-1);
									
									$words_start--;
									$words_end--;
									
									/* compares metaphone word against the metaphone filter word and returns a percent match */
									similar_text(metaphone($words_explode[$words_start]),$alert_word_metaphone,$p);
									
									/* check if match is greater than the set intensity setting */
									if($p >= $intensity){
										
										$exception_found = false;
										
										/* check to see if the match is an exception word */
										foreach($exceptions as $exception){
											if(strip_tags(trim($words_explode[$words_start])) == $exception){
												$exception_found = true;	
											}
										}
										
										/* filter the word if the exception is not found */
										if($exception_found === false){
											$replace = null;
											
											for($i = $words_start; $i <= $words_end; $i++){
												$words_explode[$i] = strip_tags($words_explode[$i]);
											}
												
											if($bleep_filter_format == 'erase'){
												$words_explode[$words_start] = '';	
											}
											
											
											if($bleep_filter_format == 'blackout_erase'){
												$replace = '&nbsp;';	
											}
											
											if($words_format){
												if($bleep_filter_format == 'asterisk'){
													$replace = '*';
												}
											}
											
											
											if($bleep_filter_format == 'bleep'){
												$bleeps = array('!','@','#','$','%','&','*','?');
												$bleeps_count = count($bleeps);
												for($i = $words_start; $i <= $words_end; $i++){
													$word_len = strlen($words_explode[$i]);
													for($a = 0; $a < $word_len; $a++){
														$bleep = $bleeps[rand(0,$bleeps_count)];
														$words_explode[$i] = substr_replace($words_explode[$i],$bleep,$a,1);
													}
												}
											}
											
											if($replace != null){
												for($i = $words_start; $i <= $words_end; $i++){
													$words_explode[$i] = preg_replace('/[\S]/', "$replace", $words_explode[$i]);
												}
											}
											
											$words_explode[$words_start] = $word_before.$words_explode[$words_start];
											$words_explode[$words_end] = $words_explode[$words_end].$word_after;
										}
									}
								}
							}
						}
					}
				}
			}
			$show_words = '';
	
			/* loop through the post words and return the filtered text */
			foreach($words_explode as $show_word){
				$show_words .= $show_word . ' ';	
			}
			
			return rtrim($show_words);	
		
		}
		
	}
	
	$bfp = new BleepFilterPlugin();
?>