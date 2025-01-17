<?php
/*
Plugin Name: Google Analytics for WordPress
Plugin URI: http://yoast.com/wordpress/analytics/
Description: This plugin makes it simple to add Google Analytics with extra search engines and automatic clickout and download tracking to your WordPress blog. 
Author: Joost de Valk
Version: 2.9
Author URI: http://yoast.com/
License: GPL

Originally based on Rich Boakes' Analytics plugin: http://boakes.org/analytics

*/

// Pre-2.6 compatibility
if ( !defined('WP_CONTENT_URL') )
    define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if ( !defined('WP_CONTENT_DIR') )
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
 
// Guess the location
$gapppluginpath = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__)).'/';

$uastring = "UA-00000-0";

/*
 * Admin User Interface
 */

if ( ! class_exists( 'GA_Admin' ) ) {

	class GA_Admin {

		function add_config_page() {
			global $wpdb;
			if ( function_exists('add_submenu_page') ) {
				$plugin_page = add_submenu_page('plugins.php', 'Google Analytics for WordPress Configuration', 'Google Analytics', 9, basename(__FILE__), array('GA_Admin','config_page'));
				add_action( 'admin_head-'. $plugin_page, array('GA_Admin','config_page_head') );
				add_filter( 'plugin_action_links', array( 'GA_Admin', 'filter_plugin_actions'), 10, 2 );
				add_filter( 'ozh_adminmenu_icon', array( 'GA_Admin', 'add_ozh_adminmenu_icon' ) );				
			}
		} // end add_GA_config_page()

		function add_ozh_adminmenu_icon( $hook ) {
			static $gawpicon;
			if (!$gawpicon) {
				$gawpicon = WP_CONTENT_URL . '/plugins/' . plugin_basename(dirname(__FILE__)). '/chart_curve.png';
			}
			if ($hook == 'googleanalytics.php') return $gawpicon;
			return $hook;
		}

		function filter_plugin_actions( $links, $file ){
			//Static so we don't call plugin_basename on every plugin row.
			static $this_plugin;
			if ( ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);

			if ( $file == $this_plugin ){
				$settings_link = '<a href="plugins.php?page=googleanalytics.php">' . __('Settings') . '</a>';
				array_unshift( $links, $settings_link ); // before other links
			}
			return $links;
		}
		
		function config_page_head() {
				wp_enqueue_script('jquery');
			?>
				 <script type="text/javascript" charset="utf-8">
				 	jQuery(document).ready(function(){
						jQuery('#explanation td').css("display","none");
						jQuery('#advancedsettings').change(function(){
							if ((jQuery('#advancedsettings').attr('checked')) == true)  {
								jQuery('#advancedsettingstr').css("border-bottom","1px solid #333");
								jQuery('.advanced th, .advanced td').css("display","table-cell");
							} else {
								jQuery('#advancedsettingstr').css("border-bottom","none");
								jQuery('.advanced th, .advanced td').css("display","none");
							}
						}).change();
						jQuery('#explain').click(function(){
							if ((jQuery('#explanation td').css("display")) == "table-cell")  {
								jQuery('#explanation td').css("display","none");
							} else {
								jQuery('#explanation td').css("display","table-cell");
							}
						});
					});
				 </script>
			<?php
		}
		
		function config_page() {
			global $dlextensions, $gapppluginpath;
			if ( isset($_POST['reset']) && $_POST['reset'] == "true") {
				$options['dlextensions'] = 'doc,exe,.js,pdf,ppt,tgz,zip,xls';
				$options['dlprefix'] = '/downloads';
				$options['artprefix'] = '/outbound/article';
				$options['comprefix'] = '/outbound/comment';
				$options['comautprefix'] = '/outbound/commentauthor';
				$options['blogrollprefix'] = '/outbound/blogroll';
				$options['domainorurl'] = 'domain';
				$options['userv2'] = false;
				$options['extrase'] = false;
				$options['imagese'] = false;
				$options['admintracking'] = true;
				$options['trackoutbound'] = true;
				$options['advancedsettings'] = false;
				$options['allowanchor'] = false;
				update_option('GoogleAnalyticsPP',$options);
				echo "<div class=\"updated\"><p>Google Analytics settings reset to default.</p></div>\n";
			}
			if ( isset($_POST['submit']) ) {
				if (!current_user_can('manage_options')) die(__('You cannot edit the Google Analytics for WordPress options.'));
				check_admin_referer('analyticspp-config');
				$options['uastring'] = $_POST['uastring'];
				
				foreach (array('dlextensions', 'dlprefix', 'artprefix', 'comprefix', 'comautprefix', 'blogrollprefix', 'domainorurl','position','domain') as $option_name) {
					if (isset($_POST[$option_name])) {
						$options[$option_name] = strtolower($_POST[$option_name]);
					}
				}
				
				foreach (array('extrase', 'imagese', 'trackoutbound', 'admintracking', 'trackadsense', 'userv2', 'allowanchor') as $option_name) {
					if (isset($_POST[$option_name])) {
						$options[$option_name] = true;
					} else {
						$options[$option_name] = false;
					}
				}

				if ($options['imagese']) {
					$options['extrase'] = true;
				} 

				update_option('GoogleAnalyticsPP', $options);
				echo "<div id=\"message\" class=\"updated\"><p>Google Analytics settings updated.</p></div>\n";
			}

			$options  = get_option('GoogleAnalyticsPP');
			?>
			<div class="wrap">
				<h2>Google Analytics for WordPress Configuration</h2>
				<iframe style="float: right; width: 200px; height: 250px" src="http://yoast.com/wp/google-analytics.php"></iframe>
				
				<form action="" method="post" id="analytics-conf">
					<table class="form-table" style="clear:none;">
					<?php
					if ( function_exists('wp_nonce_field') )
						wp_nonce_field('analyticspp-config');
					?>
					<tr>
						<th scope="row" style="width:250px;" valign="top">
							<label for="uastring">Analytics Account ID</label> &nbsp; &nbsp; &nbsp; <small><a href="#" id="explain">What's this?</a></small>
						</th>
						<td>
							<input id="uastring" name="uastring" type="text" size="20" maxlength="40" value="<?php echo $options['uastring']; ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.5em;" /><br/>
						</td>
					</tr>
					<tr id="explanation">
						<td colspan="2">
							<div style="background: #fff; border: 1px solid #ccc; width: 60%; padding: 5px;">
								<strong>Explanation</strong><br/>
								Find the Account ID, starting with UA- in your account overview, as marked below:<br/>
								<br/>
								<img src="<?php echo $gapppluginpath ?>/account-id.png" alt="Account ID"/><br/>
								<br/>
								Once you have entered your Account ID in the box above your pages will be trackable by Google Analytics.
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top">
							<label for="position">Where should the tracking script be placed?</label>
						</th>
						<td>
							<select name="position" id="position" style="width:200px;">
								<option value="footer"<?php if ($options['position'] == 'footer' || $options['position'] == "") { echo ' selected="selected"';} ?>>In the footer (default)</option>
								<option value="header"<?php if ($options['position'] == 'header') { echo ' selected="selected"';} ?>>In the header</option>
							</select>
						</td>
					</tr>						
					<tr>
						<th scope="row" valign="top">
							<label for="trackoutbound">Track outbound clicks<br/>
							&amp; downloads</label>
						</th>
						<td>
							<input type="checkbox" id="trackoutbound" name="trackoutbound" <?php if ($options['trackoutbound']) echo ' checked="checked" '; ?>/> 
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top">
							<label for="advancedsettings">Show advanced settings</label><br/>
							<small>Only adviced for advanced users who know their way around Google Analytics</small>
						</th>
						<td>
							<input type="checkbox" id="advancedsettings" name="advancedsettings" <?php if ($options['advancedsettings']) echo ' checked="checked" '; ?>/> 
						</td>
					</tr>
					<tr class="advanced">
						<th scope="row" valign="top">
							<label for="admintracking">Track the administrator too</label><br/>
							<small>(default is true)</small>
						</th>
						<td>
							<input type="checkbox" id="admintracking" name="admintracking" <?php if ($options['admintracking']) echo ' checked="checked" '; ?>/> 
						</td>
					</tr>
					<tr class="advanced">
						<th scope="row" valign="top">
							<label for="dlextensions">Extensions of files to track as downloads</label><br/>
							<small>(If the extension is only two chars, prefix it with a dot, like '.js')</small>
						</th>
						<td>
							<input type="text" name="dlextensions" size="30" value="<?php echo $options['dlextensions']; ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.5em;"/>
						</td>	
					</tr>
					<tr class="advanced">
						<th scope="row" valign="top">
							<label for="dlprefix">Prefix for tracked downloads</label>
						</th>
						<td>
							<input type="text" id="dlprefix" name="dlprefix" size="30" value="<?php echo $options['dlprefix']; ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.5em;"/>
						</td>
					</tr>
					<tr class="advanced">
						<th scope="row" valign="top">
							<label for="artprefix">Prefix for outbound clicks from articles</label>
						</th>
						<td>
							<input type="text" id="artprefix" name="artprefix" size="30" value="<?php echo $options['artprefix']; ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.5em;"/>
						</td>
					</tr>
					<tr class="advanced">
						<th scope="row" valign="top">
							<label for="comprefix">Prefix for outbound clicks from within comments</label>
						</th>
						<td>
							<input type="text" id="comprefix" name="comprefix" size="30" value="<?php echo $options['comprefix']; ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.5em;"/>
						</td>
					</tr>
					<tr class="advanced">
						<th scope="row" valign="top">
							<label for="comautprefix">Prefix for outbound clicks from comment author links</label>
						</th>
						<td>
							<input type="text" id="comautprefix" name="comautprefix" size="30" value="<?php echo $options['comautprefix']; ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.5em;"/>
						</td>
					</tr>
					<tr class="advanced">
						<th scope="row" valign="top">
							<label for="blogrollprefix">Prefix for outbound clicks from blogroll links</label>
						</th>
						<td>
							<input type="text" id="blogrollprefix" name="blogrollprefix" size="30" value="<?php echo $options['blogrollprefix']; ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.5em;"/>
						</td>
					</tr>
					<tr class="advanced">
						<th scope="row" valign="top">
							<label for="domainorurl">Track full URL of outbound clicks or just the domain?</label>
						</th>
						<td>
							<select name="domainorurl" id="domainorurl" style="width:200px;">
								<option value="domain"<?php if ($options['domainorurl'] == 'domain') { echo ' selected="selected"';} ?>>Just the domain</option>
								<option value="url"<?php if ($options['domainorurl'] == 'url') { echo ' selected="selected"';} ?>>Track the complete URL</option>
							</select>
						</td>
					</tr>
					<tr class="advanced">
						<th scope="row" valign="top">
							<label for="domain">Domain Tracking</label><br/>
							<small>This allows you to set the domain that's set by <a href="http://code.google.com/apis/analytics/docs/gaJSApiDomainDirectory.html#_gat.GA_Tracker_._setDomainName"><code>setDomainName</code></a> for tracking subdomains, if empty this will not be set.</small>
						</th>
						<td>
							<input type="text" id="domain" name="domain" size="30" value="<?php echo $options['domain']; ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.5em;"/>
						</td>
					</tr>
					<tr class="advanced">
						<th scope="row" valign="top">
							<label for="trackadsense">Track AdSense</label><br/>
							<small>This requires integration of your Analytics and AdSense account, for help, <a href="https://www.google.com/adsense/support/bin/topic.py?topic=15007">look here</a>.</small>
						</th>
						<td>
							<input type="checkbox" id="trackadsense" name="trackadsense" <?php if ($options['trackadsense']) echo ' checked="checked" '; ?>/>
						</td>
					</tr>
					<tr class="advanced">
						<th scope="row" valign="top">
							<label for="extrase">Track extra Search Engines</label>
						</th>
						<td>
							<input type="checkbox" id="extrase" name="extrase" <?php if ($options['extrase']) echo ' checked="checked" '; ?>/>
						</td>
					</tr>
					<tr class="advanced">
						<th scope="row" valign="top">
							<label for="userv2">I use Urchin too.</label>
						</th>
						<td>
							<input type="checkbox" id="userv2" name="userv2" <?php if ($options['userv2']) echo ' checked="checked" '; ?>/>
						</td>
					</tr>
					<tr class="advanced">
						<th scope="row" valign="top">
							<label for="allowanchor">Use # instead of ? for Campaign tracking?</label><br/>
							<small>This adds a <a href="http://code.google.com/apis/analytics/docs/gaJSApiCampaignTracking.html#_gat.GA_Tracker_._setAllowAnchor">setAllowAnchor</a> call to your tracking script.</small>
						</th>
						<td>
							<input type="checkbox" id="allowanchor" name="allowanchor" <?php if ($options['allowanchor']) echo ' checked="checked" '; ?>/>
						</td>
					</tr>					
					</table>
					<p style="border:0;" class="submit"><input type="submit" name="submit" value="Update Settings &raquo;" /></p>
				</form>
				<form action="" method="post">
					<input type="hidden" name="reset" value="true"/>
					<p style="border:0;" class="submit"><input type="submit" value="Reset Settings &raquo;" /></p>
				</form>
			</div>
			<?php
			if (isset($options['uastring'])) {
				if ($options['uastring'] == "") {
					add_action('admin_footer', array('GA_Admin','warning'));
				} else {
					if (isset($_POST['submit'])) {
						if ($_POST['uastring'] != $options['uastring'] ) {
							add_action('admin_footer', array('GA_Admin','success'));
						}
					}
				}
			} else {
				add_action('admin_footer', array('GA_Admin','warning'));
			}

		} // end config_page()

		function restore_defaults() {
			$options['dlextensions'] = 'doc,exe,.js,pdf,ppt,tgz,zip,xls';
			$options['dlprefix'] = '/downloads';
			$options['artprefix'] = '/outbound/article';
			$options['comprefix'] = '/outbound/comment';
			$options['comautprefix'] = '/outbound/commentauthor';
			$options['blogrollprefix'] = '/outbound/blogroll';
			$options['domainorurl'] = 'domain';
			$options['userv2'] = false;
			$options['extrase'] = false;
			$options['imagese'] = false;
			$options['trackoutbound'] = true;
			update_option('GoogleAnalyticsPP',$options);
		}
		
		function success() {
			echo "
			<div id='analytics-warning' class='updated'><p><strong>Congratulations! You have just activated Google Analytics.</p></div>
			<style type='text/css'>
			#adminmenu { margin-bottom: 7em; }
			#analytics-warning { position: absolute; top: 7em; }
			</style>";
		} // end success()

		function warning() {
			echo "
			<div id='analytics-warning' class='updated-ff0000'><p><strong>Google Analytics is not active.</strong> You must <a href='plugins.php?page=googleanalytics.php'>enter your UA String</a> for it to work.</p></div>
			<style type='text/css'>
			#adminmenu { margin-bottom: 6em; }
			#analytics-warning { position: absolute; top: 7em; }
			</style>";
		} // end warning()

	} // end class GA_Admin

} //endif


/**
 * Code that actually inserts stuff into pages.
 */
if ( ! class_exists( 'GA_Filter' ) ) {
	class GA_Filter {

		/*
		 * Insert the tracking code into the page
		 */
		function spool_analytics() {
			global $gapppluginpath;
			
			$options  = get_option('GoogleAnalyticsPP');
			
			if ($options["uastring"] != "" && (!current_user_can('edit_users') || $options["admintracking"]) && !is_preview() ) { ?>
	<!-- Google Analytics for WordPress | http://yoast.com/wordpress/google-analytics/ -->
	<script type="text/javascript">
		var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
		document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
		var pageTracker = _gat._getTracker("<?php echo $options["uastring"]; ?>");
	</script>
<?php if ( $options["extrase"] == true ) {
		echo("\t<script src=\"".$gapppluginpath."custom_se.js\" type=\"text/javascript\"></script>\n"); 
} ?>
	<script type="text/javascript">
<?php if ( $options['userv2'] ) {
		echo("\t\tpageTracker._setLocalRemoteServerMode();\n");
} 
if ( $options['allowanchor'] ) {
		echo("\t\tpageTracker._setAllowAnchor(true);\n");
} 
if ( isset($options['domain']) && $options['domain'] != "" ) {
		if (substr($options['domain'],0,1) != ".") {
			$options['domain'] = ".".$options['domain'];
		}
		echo("\t\tpageTracker._setDomainName(\"".$options['domain']."\");\n");
}
if (strpos($_SERVER['HTTP_REFERER'],"images.google") && strpos($_SERVER['HTTP_REFERER'],"&prev") && $options["imagese"]) { ?>
		regex = new RegExp("images.google.([^\/]+).*&prev=([^&]+)");
		var match = regex.exec(pageTracker.qa);
		pageTracker.qa = "http://images.google." + match[1] + unescape(match[2]);
<?php } ?>		
		pageTracker._trackPageview();
	</script>
	<!-- End of Google Analytics code -->
	<?php
			}
		}

		/*
		 * Insert the AdSense parameter code into the page. This'll go into the header per Google's instructions.
		 */
		function spool_adsense() {
			$options  = get_option('GoogleAnalyticsPP');
			if ($options["uastring"] != "" && (!current_user_can('edit_users') || $options["admintracking"]) && !is_preview() ) { ?>
				
	<script type="text/javascript">
		window.google_analytics_uacct = "<?php echo $options["uastring"]; ?>";
	</script>
	<?php
			}
		}		

		/* Create an array which contians:
		 * "domain" e.g. boakes.org
		 * "host" e.g. store.boakes.org
		 */
		function ga_get_domain($uri){

			$hostPattern = "/^(http:\/\/)?([^\/]+)/i";
			$domainPattern = "/[^\.\/]+\.[^\.\/]+$/";

			preg_match($hostPattern, $uri, $matches);
			$host = $matches[2];
			preg_match($domainPattern, $host, $matches);
			if (isset($matches[0]))
				return array("domain"=>$matches[0],"host"=>$host);    
			else
				return array("domain"=>"","host"=>"");
		}

		function ga_parse_link($leaf, $matches){
			global $origin ;
			
			$options  = get_option('GoogleAnalyticsPP');
			
			$target = GA_Filter::ga_get_domain($matches[3]);
			$coolBit = "";
			$extension = substr($matches[3],-3);
			$dlextensions = split(",",$options['dlextensions']);
			if ( $target["domain"] != $origin["domain"] ){
				if ($options['domainorurl'] == "domain") {
					$coolBit .= "javascript:pageTracker._trackPageview('".$leaf."/".$target["host"]."');";
				} else if ($options['domainorurl'] == "url") {
					$coolBit .= "javascript:pageTracker._trackPageview('".$leaf."/".$matches[2]."//".$matches[3]."');";
				}
			} else if ( in_array($extension, $dlextensions) && $target["domain"] == $origin["domain"] ) {
				$file = str_replace($origin["domain"],"",$matches[3]);
				$file = str_replace('www.',"",$file);
				$coolBit .= "javascript:pageTracker._trackPageview('".$options['dlprefix'].$file."');";
			}
			if (preg_match('/onclick=[\'\"](.*?)[\'\"]/i', $matches[4]) > 0) {
				$matches[4] = preg_replace('/onclick=[\'\"](.*?)[\'\"]/i', 'onclick="' . $coolBit .' $1"', $matches[4]);
			} else {
				$matches[4] = 'onclick="' . $coolBit . '"' . $matches[4];
			}
			return '<a ' . $matches[1] . 'href="' . $matches[2] . '//' . $matches[3] . '"' . ' ' . $matches[4] . '>' . $matches[5] . '</a>';
		}

		function ga_parse_article_link($matches){
			$options  = get_option('GoogleAnalyticsPP');
			return GA_Filter::ga_parse_link($options['artprefix'],$matches);
		}

		function ga_parse_comment_link($matches){
			$options  = get_option('GoogleAnalyticsPP');
			return GA_Filter::ga_parse_link($options['comprefix'],$matches);
		}

		function the_content($text) {
			static $anchorPattern = '/<a (.*?)href=[\'\"](.*?)\/\/([^\'\"]+?)[\'\"](.*?)>(.*?)<\/a>/i';
			$text = preg_replace_callback($anchorPattern,array('GA_Filter','ga_parse_article_link'),$text);
			return $text;
		}

		function comment_text($text) {
			static $anchorPattern = '/<a (.*?)href="(.*?)\/\/(.*?)"(.*?)>(.*?)<\/a>/i';
			$text = preg_replace_callback($anchorPattern,array('GA_Filter','ga_parse_comment_link'),$text);
			return $text;
		}

		function comment_author_link($text) {
			$options  = get_option('GoogleAnalyticsPP');

	        static $anchorPattern = '/(.*\s+.*?href\s*=\s*)["\'](.*?)["\'](.*)/';
			preg_match($anchorPattern, $text, $matches);
			if ($matches[2] == "") return $text;

			$target = GA_Filter::ga_get_domain($matches[2]);
			$coolBit = "";
			$origin = GA_Filter::ga_get_domain($_SERVER["HTTP_HOST"]);
			if ( $target["domain"] != $origin["domain"]  ){
				if ($options['domainorurl'] == "domain") {
					$coolBit .= "onclick=\"javascript:pageTracker._trackPageview('".$options['comautprefix']."/".$target["host"]."');\"";
				} else if ($options['domainorurl'] == "url") {
					$coolBit .= "onclick=\"javascript:pageTracker._trackPageview('".$options['comautprefix']."/".$matches[2]."');\"";
				}
			} 
			return $matches[1] . "\"" . $matches[2] . "\" " . $coolBit ." ". $matches[3];    
		}
		
		function bookmarks($bookmarks) {
			$options  = get_option('GoogleAnalyticsPP');
			
			if (!is_admin() && (!current_user_can('edit_users') || $options['admintracking'] ) ) {
				$options  = get_option('GoogleAnalyticsPP');

				foreach ( (array) $bookmarks as $bookmark ) {
					if ($options['domainorurl'] == "domain") {
						$target = GA_Filter::ga_get_domain($bookmark->link_url);
						$bookmark->link_rel = $bookmark->link_rel."\" onclick=\"javascript:pageTracker._trackPageview('".$options['blogrollprefix']."/".$target["host"]."');";
					} else if ($options['domainorurl'] == "url") {
						$bookmark->link_rel = $bookmark->link_rel."\" onclick=\"javascript:pageTracker._trackPageview('".$options['blogrollprefix']."/".$bookmark->link_url."');";
					}
				}
			}
			return $bookmarks;
		}
	} // class GA_Filter
} // endif

$version = "0.61";
$uakey = "analytics";

$mulch = ($uastring=""?"##-#####-#":$uastring);
$gaf = new GA_Filter();
$origin = $gaf->ga_get_domain($_SERVER["HTTP_HOST"]);

$options  = get_option('GoogleAnalyticsPP',"");

if ($options == "") {
	$options['dlextensions'] = 'doc,exe,.js,pdf,ppt,tgz,zip,xls';
	$options['dlprefix'] = '/downloads';
	$options['artprefix'] = '/outbound/article';
	$options['comprefix'] = '/outbound/comment';
	$options['comautprefix'] = '/outbound/commentauthor';
	$options['blogrollprefix'] = '/outbound/blogroll';
	$options['domainorurl'] = 'domain';
	$options['position'] = 'header';
	$options['userv2'] = false;
	$options['extrase'] = false;
	$options['imagese'] = false;
	$options['trackoutbound'] = true;
	update_option('GoogleAnalyticsPP',$options);
} 

// adds the menu item to the admin interface
add_action('admin_menu', array('GA_Admin','add_config_page'));
add_action('admin_menu', array('GA_Admin','add_config_page'));

if ($options['trackoutbound']) {
	// filters alter the existing content
	add_filter('the_content', array('GA_Filter','the_content'), 99);
	add_filter('the_excerpt', array('GA_Filter','the_content'), 99);
	add_filter('comment_text', array('GA_Filter','comment_text'), 99);
	add_filter('get_bookmarks', array('GA_Filter','bookmarks'), 99);
	add_filter('get_comment_author_link', array('GA_Filter','comment_author_link'), 99);
}

if ($options['trackadsense']) {
	add_action('wp_head', array('GA_Filter','spool_adsense'),10);	
}

if ($options['position'] == 'footer' || $options['position'] == "") {
	add_action('wp_footer', array('GA_Filter','spool_analytics'));	
} else {
	add_action('wp_head', array('GA_Filter','spool_analytics'),20);	
}

?>
