<?php
/*
Plugin Name: tweet-retweet-posts
Plugin URI: http://www.techtipsmaster.com/tweet-retweet-wordpress-posts-plugin.html
Description: Adds a button which easily lets you tweet and retweet your wordpress blog posts.
Version: 1.0
Author: Johnu George
Author URI: http://www.techtipsmaster.com
*/

function tm_options() {
	add_menu_page('Tweet-ReTweet', 'Tweet-ReTweet', 8, basename(__FILE__), 'tm_options_page');
	
}

/* Code added by Johnu George - http://techtipsmaster.com */
function tm_generate_button() {
    global $post;
    $url = '';
    if (get_post_status($post->ID) == 'publish') {
        $url = get_permalink();
    }
    $button = '<a href="http://www.techtipsmaster.com/"><img alt="Tech tips" src="http://www.linkwithin.com/pixel.png" style="border: 0"/></a>';
	$button .= '<a href="http://www.techtipsmaster.com/"><img alt="Computer Tricks" src="http://www.linkwithin.com/pixel.png" style="border: 0"/></a>';
    $button .= '<div class="tweetmeme_button" style="' . get_option('tm_style') . '">';
    $button .= '<iframe src="http://api.tweetmeme.com/button.js?url=' . urlencode($url);

    if (get_option('tm_source')) {
        $button .= '&source=' . urlencode(get_option('tm_source'));
    }

    if (get_option('tm_version') == 'compact') {
        $button .= '&style=compact';
    } else {
		$button .= '&style=normal';
	}

	if (get_option('tm_url_shortner') && get_option('tm_url_shortner') != 'default') {
    	$button .= '&service=' . urlencode(get_option('tm_url_shortner')) . '';
	}

	if (get_option('tm_api_key')) {
		$button .= '&service_api=' . urlencode(get_option('tm_api_key'));
	}

	$button .= '" ';

    if (get_option('tm_version') == 'compact') {
        $button .= 'height="20" width="90"';
    } else {
		$button .= 'height="61" width="50"';
	}

	$button .= ' frameborder="0" scrolling="no"></iframe></div>';

    return $button;
}

function tm_generate_static_button() {

	if (get_post_status($post->ID) == 'publish') {
        $url = get_permalink();
    }

	return '<div class="tweetmeme_button" style="' . get_option('tm_style') . '"><a href="http://api.tweetmeme.com/share?url=' . urlencode($url) . '"><img src="http://api.tweetmeme.com/imagebutton.gif?url=' . urlencode($url) . '" height="61" width="51" /></a></div>';
}

function tm_update($content) {

    global $post;

    if (get_option('tm_where') == 'manual') {
        return $content;
    }

    if (get_option('tm_display_page') == null && is_page()) {
        return $content;
    }

    if (get_option('tm_display_front') == null && is_home()) {
        return $content;
    }

    if (is_feed()) {
		$button = tm_generate_static_button();
		$where = 'tm_rss_where';
    } else {
		$button = tm_generate_button();
		$where = 'tm_where';
	}

	if (is_feed() && get_option('tm_display_rss') == null) {
		return $content;
	}

	if (get_option($where) == 'shortcode') {
		return str_replace('[tweetmeme]', $button, $content);
	} else {
		// if we have switched the button off
		if (get_post_meta($post->ID, 'tweetmeme') == '') {
			
			if (get_option($where) == 'beforeandafter') {
				return $button . $content . $button;
			} else if (get_option($where) == 'before') {
				return $button . $content;
			} else {
				return $content . $button;
			}
		} else {
			return $content;
		}
	}
}

// Manual output
function tweetmeme() {
    if (get_option('tm_where') == 'manual') {
        return tm_generate_button();
    } else {
        return false;
    }
}

// Remove the filter excerpts

function tm_remove_filter($content) {
	if (!is_feed()) {
    	remove_action('the_content', 'tm_update');
	}
    return $content;
}

function tm_ping($post_id) {
 
    if ((get_option('tm_ping') != 'off') && function_exists('curl_init')) {
        $url = get_permalink($post_id);
        // create a new cURL resource
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, 'http://api.tweetmeme.com/ping.php?url=' . urlencode($url));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // grab URL and pass it to the browser
        curl_exec($ch);

        // close cURL resource, and free up system resources
        curl_close($ch);
    }
}

function tm_tweets($url) {
	if (function_exists('curl_init')) {
		$ch = curl_init();

		// set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, 'http://api.tweetmeme.com/analytics/free.json?url=' . urlencode($url));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // grab URL and pass it to the browser
        $data = curl_exec($ch);
        // close cURL resource, and free up system resources
        curl_close($ch);

        $data = json_decode($data, true);

        if ($data['status'] != 'success') {
        	return array('success'=>'false',
        					'reason'=>$data['comment']);
		}

        return array('success'=>'true',
        				'data'=>$data);
	}
	return false;
}

function tm_get_analytics($domain = null)
{
	$appid = get_option('tm_api_app_id');
	$apikey = get_option('tm_api_app_key');

	if (!$appid && !$apikey) {
		return array('success'=>'false',
					'reason'=>'App ID and App Key not set');
	}

	if (!is_null($domain)) {
		$domainstring = '&domain=' . urlencode($domain);
	}

	if (function_exists('curl_init')) {
		$ch = curl_init();

		// set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, 'http://api.tweetmeme.com/analytics/built.json?appid=' . $appid . '&apikey=' . $apikey . $domainstring);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 3);
        // grab URL and pass it to the browser
        $data = curl_exec($ch);
        // close cURL resource, and free up system resources
        curl_close($ch);

        $data = json_decode($data, true);

        if ($data['status'] != 'success') {
        	return array('success'=>'false',
        					'reason'=>$data['comment']);
		}

        return array('success'=>'true',
        				'data'=>$data);
	}
	return array('success'=>'false',
					'reason'=>'cURL is not installed on this server');
}

function tm_queue_analytics($url)
{
	$appid = get_option('tm_api_app_id');
	$apikey = get_option('tm_api_app_key');

	if (!$appid && !$apikey) {
		return array('success'=>'false',
					'reason'=>'App ID and App Key not set');
	}

	if (function_exists('curl_init')) {
		$ch = curl_init();

		// set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, 'http://api.tweetmeme.com/analytics/build.json?appid=' . $appid . '&apikey=' . $apikey . '&url=' . urlencode($url));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // grab URL and pass it to the browser
        $data = curl_exec($ch);
        // close cURL resource, and free up system resources
        curl_close($ch);

        $data = json_decode($data, true);

        if ($data['status'] != 'success') {
        	return array('success'=>'false',
        					'reason'=>$data['comment']);
		}

        return array('success'=>'true');
	}
	return array('success'=>'false',
					'reason'=>'cURL is not installed on this server');
}

function tm_head() {
	if (is_single()) {
		global $post;
		$title = get_the_title($post->ID);
		echo '<meta name="tweetmeme-title" content="' . $title . '" />';
	}
}



function tm_js_admin_header() {
	// use JavaScript SACK library for Ajax
	wp_print_scripts(array('sack'));
	?>
	<script type="text/javascript">
		//<![CDATA[
		function loadAnalytics(url, row) {
    		var mysack = new sack("<?php bloginfo('wpurl'); ?>/wp-admin/admin-ajax.php");
			mysack.execute = 1;
			mysack.method = 'POST';
			mysack.setVar("action", "tm_ajax_elev_lookup");
			mysack.setVar("url", url);
			mysack.setVar("id", 't' + row);
			mysack.onError = function() {
				alert('Ajax error in looking up url');
			};
			mysack.runAJAX();
  			return true;
		}
		//]]>
	</script>
<?php
}

function tm_ajax_elev_lookup() {
	// read submitted information
	$url = $_POST['url'];
	$id = $_POST['id'];
	// fetch the data from tweetmeme
	$json = tm_tweets($url);
	// get the data
	$data = $json['data'];
	// build up the output
	ob_start();
	?>
	<table cellpadding="0" cellspacing="0" style="width: 100%;">
		<tr>
			<th>Tweets in 1 Hour</th>
			<th>Tweets in 24 Hours</th>
			<th>Top Sources in 1 Hour</th>
		</tr>
		<tr>
			<td>
				<img src="<?php echo $data['hourChart']; ?>" alt="*" />
			</td>
			<td>
				<img src="<?php echo $data['dayChart']; ?>" alt="*" />
			</td>
			<td>
				<img src="<?php echo $data['sources']; ?>" alt="*" />
			</td>
		</tr>
	</table>
	<?php if (count($data['users']) > 0) { ?>
		<table cellpadding="0" cellspacing="0" style="width: 100%;">
			<tr>
				<th>Tweeter</th>
				<th>Retweet Of</th>
				<th></th>
			</tr>
			<?php foreach ($data['users'] as $user) { ?>
				<tr>
					<td><a href="http://tweetmeme.com/user/<?php echo $user['tweeter'] ?>" target="_blank"><?php echo $user['tweeter'] ?></a></td>
					<td><?php if ($user['isRT']) { ?>
						<a href="http://tweetmeme.com/user/<?php echo $user['RTUser'] ?>" target="_blank"><?php echo $user['RTUser'] ?></a>
					<?php } ?></td>
					<td><a href="http://twitter.com/<?php echo $user['tweeter'] ?>/status/<?php echo $user['tweetid'] ?>" target="_blank">View</a></td>
				</tr>
			<?php } ?>
		</table>
	<?php }

	$output = '<td colspan="3">' . ob_get_clean() . '</td>';
	$output = str_replace(array("\r","\n","\r\n","\n\r"),'', $output);
  	// Compose JavaScript for return
  	die("document.getElementById('$id').innerHTML = '" . $output  . "';");
}

function tm_options_page() {
?>
    <div class="wrap">
    <div class="icon32" id="icon-options-general"><br/></div><h2>Settings for tweet-retweet Integration</h2>
    <p>This plugin will install the tweet-retweet widget for each of your blog posts in both the content of your posts and the RSS feed.
    It can be easily styles in your blog posts and is referenced by the id <code>tweet-retweet_button</code>.
    </p>
    <form method="post" action="options.php">
    <?php
        // New way of setting the fields, for WP 2.7 and newer
        if(function_exists('settings_fields')){
            settings_fields('tm-options');
        } else {
            wp_nonce_field('update-options');
            ?>
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="tm_ping,tm_where,tm_style,tm_version,tm_display_page,tm_display_front,tm_display_rss,tm_display_feed,tm_source,tm_url_shortner,tm_api_key" />
            <?php
        }
    ?>
        <table class="form-table">
            <tr>
	            <tr>
	                <th scope="row">
	                    Display
	                </th>
	                <td>
	                    <p>
	                        <input type="checkbox" value="1" <?php if (get_option('tm_display_page') == '1') echo 'checked="checked"'; ?> name="tm_display_page" id="tm_display_page" group="tm_display"/>
	                        <label for="tm_display_page">Display the button on pages</label>
	                    </p>
	                    <p>
	                        <input type="checkbox" value="1" <?php if (get_option('tm_display_front') == '1') echo 'checked="checked"'; ?> name="tm_display_front" id="tm_display_front" group="tm_display"/>
	                        <label for="tm_display_front">Display the button on the front page (home)</label>
	                    </p>
	                    <p>
	                        <input type="checkbox" value="1" <?php if (get_option('tm_display_rss') == '1') echo 'checked="checked"'; ?> name="tm_display_rss" id="tm_display_rss" group="tm_display"/>
	                        <label for="tm_display_rss">Display the image button in your feed, only available as <strong>the normal size</strong> widget.</label>
	                    </p>
	                </td>
	            </tr>
                <th scope="row">
                    Position
                </th>
                <td>
                	<p>
                		<select name="tm_where">
                			<option <?php if (get_option('tm_where') == 'before') echo 'selected="selected"'; ?> value="before">Before</option>
                			<option <?php if (get_option('tm_where') == 'after') echo 'selected="selected"'; ?> value="after">After</option>
                			<option <?php if (get_option('tm_where') == 'beforeandafter') echo 'selected="selected"'; ?> value="beforeandafter">Before and After</option>
                			<option <?php if (get_option('tm_where') == 'shortcode') echo 'selected="selected"'; ?> value="shortcode">Shortcode [tweetmeme]</option>
                			<option <?php if (get_option('tm_where') == 'manual') echo 'selected="selected"'; ?> value="manual">Manual</option>
                		</select>
                	</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    RSS Position
                </th>
                <td>
                	<p>
                		<select name="tm_rss_where">
                			<option <?php if (get_option('tm_rss_where') == 'before') echo 'selected="selected"'; ?> value="before">Before</option>
                			<option <?php if (get_option('tm_rss_where') == 'after') echo 'selected="selected"'; ?> value="after">After</option>
                			<option <?php if (get_option('tm_rss_where') == 'beforeandafter') echo 'selected="selected"'; ?> value="beforeandafter">Before and After</option>
                			<option <?php if (get_option('tm_where') == 'shortcode') echo 'selected="selected"'; ?> value="shortcode">Shortcode [tweetmeme]</option>
                		</select>
                		<span class="setting-description">The position of the button in your RSS feed.</code></span>
                	</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="tm_style">Styling</label></th>
                <td>
                    <input type="text" value="<?php echo htmlspecialchars(get_option('tm_style')); ?>" name="tm_style" id="tm_style" />
                    <span class="setting-description">Add style to the div that surrounds the button E.g. <code>float: left; margin-right: 10px;</code></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    Type
                </th>
                <td>
                    <p>
                        <input type="radio" value="large" <?php if (get_option('tm_version') == 'large') echo 'checked="checked"'; ?> name="tm_version" id="tm_version_large" group="tm_version"/>
                        <label for="tm_version_large">The normal size widget</label>
                    </p>
                    <p>
                        <input type="radio" value="compact" <?php if (get_option('tm_version') == 'compact') echo 'checked="checked"'; ?> name="tm_version" id="tm_version_compact" group="tm_version" />
                        <label for="tm_version_compact">The compact widget</label>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    Source
                </th>
                <td>
                    <p>
                        RT @<input type="text" value="<?php echo get_option('tm_source'); ?>" name="tm_source" id="tm_source" />
                        <label for="tm_source">Change the RT source of the button from RT @tweetmeme to RT @yourname</label>    <br/>
                        <span class="setting-description">Please use the format of 'yourname', not 'RT @yourname'. For more information please see the <a href="http://help.tweetmeme.com">help</a>.</span>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    URL Shortner
                </th>
                <td>
                    <p>
                        <select name="tm_url_shortner">
                        	<option <?php if (get_option('tm_url_shortner') == 'default') echo 'selected="selected"'; ?> value="default">Default</option>
                        	<option <?php if (get_option('tm_url_shortner') == 'bit.ly') echo 'selected="selected"'; ?> value="bit.ly">bit.ly</option>
                        	<option <?php if (get_option('tm_url_shortner') == 'awe.sm') echo 'selected="selected"'; ?> value="awe.sm">awe.sm</option>
                        	<option <?php if (get_option('tm_url_shortner') == 'cli.gs') echo 'selected="selected"'; ?> value="cli.gs">cligs</option>
                        	<option <?php if (get_option('tm_url_shortner') == 'digg.com') echo 'selected="selected"'; ?> value="digg.com">digg</option>
                        	<option <?php if (get_option('tm_url_shortner') == 'is.gd') echo 'selected="selected"'; ?> value="is.gd">is.gd</option>
                        	<option <?php if (get_option('tm_url_shortner') == 'TinyURL.com') echo 'selected="selected"'; ?> value="TinyURL.com">TinyURL</option>
                        	<option <?php if (get_option('tm_url_shortner') == 'ow.ly') echo 'selected="selected"'; ?> value="ow.ly">Ow.ly</option>
                        	<option <?php if (get_option('tm_url_shortner') == 'retwt.me') echo 'selected="selected"'; ?> value="retwt.me">retwt.me</option>
                        </select><br/>
                        <span class="setting-description">If you use <strong>awe.sm</strong> an API key is required.</span>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    URL Shortener API Key
                </th>
                <td>
                    <p>
                        <input type="text" value="<?php echo get_option('tm_api_key'); ?>" name="tm_api_key" id="tm_api_key" />
                        <label for="tm_api_key">API Key for use with <strong>awe.sm</strong>, <strong>cligs</strong> and <strong>digg</strong>.</label>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tm_ping">Ping Tweetmeme</label>
                </th>
                <td>
                    <p>
                        <input type="radio" value="on" <?php if (get_option('tm_ping') == 'on') echo 'checked="checked"'; ?> name="tm_ping" id="tm_ping_on" group="tm_ping"/>
                        <label for="tm_ping_on">Yes</label>
                    </p>
                    <p>
                        <input type="radio" value="off" <?php if (get_option('tm_ping') == 'off') echo 'checked="checked"'; ?> name="tm_ping" id="tm_ping_off" group="tm_ping" />
                        <label for="tm_ping_off">No</label>
                    </p>
                    <span class="setting-description">Alert TweetMeme whenever a new post is published, so it can update the details, such as the post title.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tm_api_app_id">TweetMeme API App ID</label>
                </th>
                <td>
                    <p>
                        <input type="text" value="<?php echo get_option('tm_api_app_id'); ?>" name="tm_api_app_id" id="tm_api_app_id" />
                    </p>
                    <span class="setting-description">Your TweetMeme API Application ID, available from <a href="http://my.tweetmeme.com/profile" target="_blank">My TweetMeme</a></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tm_api_app_id">TweetMeme API App Key</label>
                </th>
                <td>
                    <p>
                        <input type="text" value="<?php echo get_option('tm_api_app_key'); ?>" name="tm_api_app_key" id="tm_api_app_key" />
                    </p>
                    <span class="setting-description">Your TweetMeme API Application Key, available from <a href="http://my.tweetmeme.com/profile" target="_blank">My TweetMeme</a></span>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
        </p>
    </form>
    </div>
<?php
}


// On access of the admin page, register these variables (required for WP 2.7 & newer)
function tm_init(){
    if(function_exists('register_setting')){
        register_setting('tm-options', 'tm_display_page');
        register_setting('tm-options', 'tm_display_front');
        register_setting('tm-options', 'tm_display_rss');
        register_setting('tm-options', 'tm_source', 'tm_sanitize_username');
        register_setting('tm-options', 'tm_style');
        register_setting('tm-options', 'tm_version');
        register_setting('tm-options', 'tm_where');
        register_setting('tm-options', 'tm_rss_where');
        register_setting('tm-options', 'tm_ping');
        register_setting('tm-options', 'tm_url_shortner');
        register_setting('tm-options', 'tm_api_key');
        register_setting('tm-options', 'tm_api_app_id');
        register_setting('tm-options', 'tm_api_app_key');
    }
}

function tm_sanitize_username($username){
    return preg_replace('/[^A-Za-z0-9_]/','',$username);
}


// Only all the admin options if the user is an admin
if(is_admin()){
    add_action('admin_menu', 'tm_options');
    add_action('admin_init', 'tm_init');
}

// Set the default options when the plugin is activated
function tm_activate(){
    add_option('tm_where', 'before');
    add_option('tm_rss_where', 'before');
    add_option('tm_source');
    add_option('tm_style', 'float: right; margin-left: 10px;');
    add_option('tm_version', 'large');
    add_option('tm_display_page', '1');
    add_option('tm_display_front', '1');
    add_option('tm_display_rss', '1');
    add_option('tm_ping', 'on');
}

add_filter('the_content', 'tm_update');
add_filter('get_the_excerpt', 'tm_remove_filter', 9);

add_action('publish_post', 'tm_ping', 9);

add_action('wp_head', 'tm_head');

add_action('admin_print_scripts', 'tm_js_admin_header');
add_action('wp_ajax_tm_ajax_elev_lookup', 'tm_ajax_elev_lookup');

register_activation_hook( __FILE__, 'tm_activate');