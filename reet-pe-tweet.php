<?php
/**
 * Plugin Name: Reet-pe-Tweet
 * Plugin URI: http://www.maltpress.co.uk/services-2/wordpress-plugin-development/reet-pe-tweet/
 * Description: Displays latest tweets from a particular user.
 * Version: 0.2.1
 * Author: Adam Maltpress
 * Author URI: http://www.maltpress.co.uk
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/*
 * prefix for functions, variables etc: st_
 */

/**
 * Add function to widgets_init that'll load our widget.
 * @since 0.1
 */
add_action( 'widgets_init', 'st_load_widgets' );

/**
 * Register our widget.
 * 'ST_twitter_Widget' is the widget class used below.
 *
 * @since 0.1
 */
function st_load_widgets() {
	register_widget( 'ST_twitter_Widget' );
}


/**
 * Class for producing twitter feed
 */

/*
 * general XML parsing class (may be reused)
 */

class st_XmlParser {

    // set the variables
    var $parser;
    var $record;
    var $current_field = '';
    var $field_type;
    var $ends_record;
    var $records;

    /**
     * Create parser
     *
     * @param string $data The data to be parsed (could be replaced with filename)
     */

    function st_XmlParser($data) {
        // create parser
        $this->parser = xml_parser_create();

        // set up handlers
        xml_set_object($this->parser, &$this);
        xml_set_element_handler($this->parser, 'start_element', 'end_element');
        xml_set_character_data_handler($this->parser, 'cdata');

        // set field types; 1 = single field, 2 = array field, 3 = record container
        // for twtitter, all are single fields...
        $this->field_type = array(
            'created_at' => 2, // created date - in ISO8601 format
            'text' => 1, // body of the tweet
            'source' => 1, // added via...
            'in_reply_to_user_id' => 1 // is in reply to... - check this so we can skip if required
        );

        // what ends each record?
        $this->ends_record = array('status' => true);

        xml_parse($this->parser, $data);
        xml_parser_free($this->parser);

    }

    /**
     * Start element handler
     *
     * @param string $p
     * @param string $element the element being handled
     * @param string $attributes
     */

    function start_element ($p, $element, &$attributes) {
        // remove case sensitivity from element name...
        $element = strtolower($element);

        // check element has been identified for parsing
        if ($this->field_type[$element] != 0) {
            $this->current_field = $element;
        } else {
            $this->current_field = '';
        }

    }

    /**
     * End element handler
     *
     * @param string $p
     * @param string $element the element being handled
     */

    function end_element ($p, $element) {
        // remove case sensitivity from element name...
        $element = strtolower($element);

        // check element has been identified for parsing, and put the contents into the records array
        if ($this->ends_record[$element]) {
            $this->records[] = $this->record;
            $this->record = array();
        }
        $this->current_field = '';
    }

    /**
     * How to handle the actual data
     *
     * Checks field type and deals accordingly
     *
     * @param string $p
     * @param string $text  output of parser
     */

    function cdata ($p, $text) {
        if($this->field_type[$this->current_field] === 2 ) { // is this an array?
            $this->record[$this->current_field][] .= $text;
        } elseif ($this->field_type[$this->current_field] === 1) { // is this just a string?
            $this->record[$this->current_field] .= $text;
        }
    }

    function displayParseResults ($showReplies, $tweetQuant, $show_via) {
        // this is the main bit which will need amending for a widget

        foreach ($this->records as $tweet) {

            $content = $tweet['text'];
            $timeCreated = strtotime($tweet['created_at'][0]);
            // early versions of PHP user mktime()
            if (phpversion() == "4") {
                $timeCreated = mktime($timeCreated);
            }
            $source = $tweet['source'];
            $in_reply = $tweet['in_reply_to_user_id'];


            // let's do some magic with the content to get links working...
            // find www links and add http:// on the front

                $content =
                      preg_replace("/ www.(([[:graph:]]*))/"," http://www.\\1",$content);

           // find http: links and convert to html links

                $content =
                        preg_replace("/(((mailto|http|ftp|nntp|news):.+?)(&gt;|\\s|\\)|\\.\\s|$))/i","<a href=\"\\1\" target=\"_blank\">\\1</a>",$content);

            // find @replies in the tweet, convert to links

                $content =
                        preg_replace("/(\s|^)@((\w*))/","<a href=\"http://www.twitter.com/\\2\" target=\"_blank\">\\1@\\2</a>",$content);

            // find #tags in the tweet, link to twitter search
               $content =
                        preg_replace("/(\s|^)#((\w*))/","<a href=\"http://search.twitter.com/search?q=%23\\2\" target=\"_blank\">\\1#\\2</a>",$content);


                $content = preg_replace("/[.,]?-=-\"/", '"', $content);

            

            // skip if we're not allowing replies and this one is a reply...
            if ($showReplies == 'false') {
                if ($in_reply == '') {
                $intoArray = '';
                $intoArray .= '<li class="tweet-single">';
                $intoArray .= $content;
                $intoArray .= '<br /><span class="tweet-date">';
                $intoArray .= date('l d F', $timeCreated);
                $intoArray .= '</span>';
                if ($show_via == 'true') {
                    $intoArray .= '<br /><span class="tweet-source">Via ';
                    $intoArray .= $source;
                    $intoArray .= '</span>';
                }
                $intoArray .= '</li>';
                $outputArray[] = $intoArray;
                } else {
                    $intoArray = '';
                }
            } else {
                $intoArray = '';
                $intoArray .= '<li class="tweet-single">';
                $intoArray .= $content;
                $intoArray .= '<br /><span class="tweet-date">';
                $intoArray .= date('l d F', $timeCreated);
                $intoArray .= '</span>';
                if ($show_via == 'true') {
                    $intoArray .= '<br /><span class="tweet-source">Via ';
                    $intoArray .= $source;
                    $intoArray .= '</span>';
                }
                $intoArray .= '</li>';
                $outputArray[] = $intoArray;
            }

        }

        // now produce the output
        $incrementTweet = 0;
        foreach ($outputArray as $finalTweet) {
            if ($incrementTweet <= $tweetQuant-1) { // this can be set to change number of tweets shown
                echo $finalTweet;
                $incrementTweet++;
            }
        }
    }

// end class
}

/**
 * Twitter reading and display function
 *
 * Gets twitter timeline for a particular user
 *
 * @param string $twitterUser Twitter user name to get tweets for
 * @param int $cacheTime Time (seconds) to cache tweets for
 * @param bool $showReplies Show or hide replies from timeline
 * @param int $tweetQuant Number of tweets to show
 */

function st_twitterShow($twitterUser, $cacheTime, $showReplies, $tweetQuant, $show_via) {

    // some variables
    $cache_filename = 'twitterapi.xml';
    $cache_path = wp_upload_dir();
    $cache_final_path = $cache_path['basedir'] . '/' . $cache_filename;

    $cache_url = $cache_path['baseurl'] . '/' . $cache_filename;

    // do we have the cache file? if not, create it:

    if (!file_exists($cache_final_path)) {
        // get the data using API call

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://api.twitter.com/1/statuses/user_timeline.xml?screen_name=' . $twitterUser);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $twit_output = curl_exec($ch);
        // now write the data to the file

        $filewrite = fopen($cache_final_path, 'w') or die("can't open file");
        curl_setopt($ch, CURLOPT_FILE, $filewrite);
        curl_exec($ch);
        curl_close($ch);
        fclose($filewrite);
    } else {

        // check the time of the file

        if (filemtime($cache_final_path) + $cacheTime >= time())
        {
            // file is fine, so use it as the output
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $cache_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $twit_output = curl_exec($ch);
            curl_close($ch);
        }

        else {
            // too old
            // get the data using API call

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://api.twitter.com/1/statuses/user_timeline.xml?screen_name=' . $twitterUser);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

             //check connection to Twitter
            if(curl_exec($ch) === false)
                {

                    curl_close($ch);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $cache_url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $twit_output = curl_exec($ch);
                    curl_close($ch);
                }
                else
                {
                    $twit_output = curl_exec($ch);
                    // now write the data to the file
                    $filewrite = fopen($cache_final_path, 'w') or die("can't open file");
                    curl_setopt($ch, CURLOPT_FILE, $filewrite);
                    $data = curl_exec($ch);
                    // close everything
                    curl_close($ch);
                    fclose($filewrite);
                }
            
        }
    }
    // now we have the data in $twit_output we can do stuff with it
    // echo $twit_output;
    $tweet_results = new st_XmlParser($twit_output);
    
    return $tweet_results->displayParseResults($showReplies, $tweetQuant, $show_via);
}




/**
 * Example Widget class.
 * This class handles settings, form, display, and update.
 *
 * @since 0.1
 */
class ST_twitter_Widget extends WP_Widget {

	/**
	 * Widget setup.
	 */
	function ST_twitter_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'reet-pe-tweet', 'description' => 'Displays latest tweets from a particular user.' );

		/* Widget control settings. */
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'reet-pe-tweet' );

		/* Create the widget. */
		$this->WP_Widget( 'reet-pe-tweet', __('Reet-pe-Tweet', 'reet-pe-tweet'), $widget_ops, $control_ops );
	}

	/**
	 * How to display the widget on the screen.
	 */
	function widget( $args, $instance ) {
		extract( $args );

		/* Our variables from the widget settings. */
		$title = apply_filters('widget_title', $instance['title'] );
		$twitterUser = $instance['twitterUser'];
		$cacheTime = $instance['cacheTime'];
		$showReplies = $instance['showReplies'];
                $tweetQuant = $instance['tweetQuant'];
                $show_via = $instance['show_via'];

		/* Before widget (defined by themes). */
		echo $before_widget;

		/* Display the widget title if one was input (before and after defined by themes). */
		if ( $title )
			echo $before_title . $title . $after_title;

                /* display the widget output */
                echo "<ul class='reet-pe-tweet-ul'>";
                st_twitterShow($twitterUser, $cacheTime, $showReplies, $tweetQuant, $show_via);
                echo "</ul>";

		/* After widget (defined by themes). */
		echo $after_widget;
	}

	/**
	 * Update the widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );
                $instance['twitterUser'] = $new_instance['twitterUser'];
                $instance['cacheTime'] = $new_instance['cacheTime'];
                $instance['showReplies'] = $new_instance['showReplies'];
                $instance['tweetQuant'] = $new_instance['tweetQuant'];
                $instance['show_via'] = $new_instance['show_via'];

		return $instance;
	}

	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 
                    'title' => 'Latest tweets',
                    'twitterUser' => '',
                    'cacheTime' => 60,
                    'showReplies' => 'true',
                    'tweetQuant' => 5,
                    'show_via' => 'true'
                );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>

		<!-- Twitter user name: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'twitterUser' ); ?>">Twitter user name:</label>
			<input id="<?php echo $this->get_field_id( 'twitterUser' ); ?>" name="<?php echo $this->get_field_name( 'twitterUser' ); ?>" value="<?php echo $instance['twitterUser']; ?>" style="width:100%;" />
		</p>

                <!-- Cache time: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'cacheTime' ); ?>">Cache time (in seconds; you shouldn't need to change this unless you get rate limit reached errors):</label>
			<input id="<?php echo $this->get_field_id( 'cacheTime' ); ?>" name="<?php echo $this->get_field_name( 'cacheTime' ); ?>" value="<?php echo $instance['cacheTime']; ?>" style="width:100%;" />
		</p>



		<!-- show replies?: Select Box -->
		<p>
			<label for="<?php echo $this->get_field_id( 'showReplies' ); ?>">Show replies? See note below:</label>
			<select id="<?php echo $this->get_field_id( 'showReplies' ); ?>" name="<?php echo $this->get_field_name( 'showReplies' ); ?>" class="widefat" style="width:100%;">
				<option <?php if ( $instance['showReplies'] == 'true' ) echo 'selected="selected"'; ?> value="true">True</option>
				<option <?php if ( $instance['showReplies'] == 'false' ) echo 'selected="selected"'; ?> value="false">False</option>
			</select>
		</p>

                <!-- show via?: Select Box -->
		<p>
			<label for="<?php echo $this->get_field_id( 'show_via' ); ?>">Show "via" link?:</label>
			<select id="<?php echo $this->get_field_id( 'show_via' ); ?>" name="<?php echo $this->get_field_name( 'show_via' ); ?>" class="widefat" style="width:100%;">
				<option <?php if ( $instance['show_via'] == 'true' ) echo 'selected="selected"'; ?> value="true">True</option>
				<option <?php if ( $instance['show_via'] == 'false' ) echo 'selected="selected"'; ?> value="false">False</option>
			</select>
		</p>

		<!-- Number of tweets: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'tweetQuant' ); ?>">Tweets to show (if you fill your stream with replies and then turn them off, you might not see as many as you expect...):</label>
			<input id="<?php echo $this->get_field_id( 'tweetQuant' ); ?>" name="<?php echo $this->get_field_name( 'tweetQuant' ); ?>" value="<?php echo $instance['tweetQuant']; ?>" style="width:100%;" />
		</p>

	<?php
	}
}

?>