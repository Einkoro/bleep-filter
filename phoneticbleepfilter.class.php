<?php
class PhoneticBleepFilter extends BleepFilter
{
	public function __construct(){
		$bleep_filter_content = get_option('bleep_filter_content'); 
		$bleep_filter_content_rss = get_option('bleep_filter_content_rss'); 
		$bleep_filter_comment = get_option('bleep_filter_comment'); 
		$bleep_filter_comment_rss = get_option('bleep_filter_comment_rss');
		$bleep_filter_bbpress = get_option('bleep_filter_bbpress'); 
		

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
            
			/* bbPress specific filtering (only if bbPress is present) */
			if( class_exists('bbPress') and $bleep_filter_bbpress == 'on' ) {
				add_filter( 'bbp_get_topic_content', array( $this, 'word_filter' ), 50 );
				add_filter( 'bbp_get_topic_title',   array( $this, 'word_filter' ), 50 );
				add_filter( 'bbp_get_reply_content', array( $this, 'word_filter' ), 50 );
				add_filter( 'bbp_get_reply_title',   array( $this, 'word_filter' ), 50 );
			}
			
			/* Load Filtered Words and Exceptions */
			$this->alert_words = $this->get_words();
			$this->exception_words = $this->get_exceptions();

		}
		
	}
	
	/* Grabs filtered words as array */
	public function get_words(){
		
		$alert_words = array();
		$replace_words = array();
		
		$the_posts = get_posts(array('post_type' => 'bleep_filter_words','numberposts'     => -1));
		foreach($the_posts as $post){
			$value = get_post_meta( $post->ID, 'bleep_replacement', true );
			array_push($alert_words, $post->post_title);
			$replace_words[$post->post_title] = esc_attr( $value );
		}
		$this->replace_words = $replace_words;
        
		return $alert_words;
	}
	
	/* Grabs word exceptions as array */
	function get_exceptions(){
		
		$exception_words = array();
		$the_posts = get_posts(array('post_type' => 'bleep_exception','numberposts'     => -1));
		foreach($the_posts as $post){
			array_push($exception_words, $post->post_name);
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
			case 'asterisk_first':
				$formatting[0] = '';
				$formatting[1] = '';
				$formatting[2] = 'z';
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
		$intensity = 5;
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
		$words_metaphone = '';
		$alert_words_metaphone = array();
		
		/* Loop through all words to filter out and create a metaphone array */
		foreach($this->alert_words as $alert_phrase){
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
			
			if(trim($metaphone_word) === ''){
				$metaphone_word = $word;
			}
			
			$words_metaphone .= ' '.$metaphone_word;
		}
			
		$words_metaphone = ltrim($words_metaphone);
		
		/* Loop through metaphone words to filter */
		foreach($alert_words_metaphone as $alert_word_index => $alert_word_metaphone){
		
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
								
								$phrase_word_count = $words_end - $words_start + 1;
								$phrase_words = '';
								
								$start_word_test = strtolower(trim(strip_tags($words_explode[$words_start])));
								$start_word_test = preg_replace('/[^A-Za-z0-9\-]/', '', $start_word_test);
								$start_word_metaphone_test = metaphone($start_word_test);
								$alert_word_test = strtolower(trim($this->alert_words[$alert_word_index]));
								$alert_word_metaphone_test = metaphone($alert_word_test);
								
								$found = 0;
								
								if($phrase_word_count > 1){
									for($i=0;$i<$phrase_word_count;$i++){
										$phrase_words .= metaphone(strtolower(trim(strip_tags($words_explode[$words_start+$i])))). ' ';
									}
									$phrase_words = rtrim($phrase_words);
									if($phrase_words == $ma[0]){
										$found = 1;	
									}
								}
								
								/* compares metaphone word against the metaphone filter word and returns a percent match */
								similar_text($start_word_metaphone_test,$alert_word_metaphone_test,$p);
								
								if($found !== 1){
									$subject = preg_replace('/(.)\1{2,}/', '$1',$start_word_test);
									$pattern = '/'.$alert_word_test.'/';
									preg_match($pattern, $subject, $matches);
									$found = count($matches[0]);
								}
								
								if(($p == 100) || ($found == 1)){
									$exception_found = false;
									if($found == 0){
										if(strlen($alert_word_metaphone_test) <= 3){
											similar_text($start_word_test,$alert_word_test,$p2);
											if( (($p+$p2)/2) < 85){
												$exception_found = true;	
											}
										}
									}
								}
								else{
									$exception_found = true;	
								}
								
								if($exception_found !== true){
									/* check to see if the match is an exception word */
									foreach($this->exception_words as $exception){
										if($start_word_test === $exception){
											$exception_found = true;	
										}
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
									
									//$alert_word_test = trim($alert_word_test);
									if($this->replace_words[$alert_word_test] != ''){
										$replace = $this->replace_words[$alert_word_test];
										$words_explode[$words_start] = str_replace($words_explode[$words_start], $replace, 0);
									}
									
									
									if($replace != null){
										for($i = $words_start; $i <= $words_end; $i++){
											$words_explode[$i] = preg_replace('/[\S]/', "$replace", $words_explode[$i]);
										}
									}
									
									if($this->replace_words[$alert_word_test] === ''){
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
?>
