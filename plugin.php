<?php
/*
Plugin Name: Google Analytics
Plugin URI: http://katz.co/yourls-analytics/
Description: Easily add Google Analytics tracking tags to your generated links.
Version: 1.1
Author: Katz Web Services, Inc.
Author URI: http://www.katzwebservices.com
Settings: <a href="?page=google_analytics">Configure settings</a>
 */

if (!function_exists('yourls_add_filter') || !defined('YOURLS_SITE')) {return;}

// Register our plugin admin page
yourls_add_action('plugins_loaded', 'kws_yourls_add_analytics_add_page');
function kws_yourls_add_analytics_add_page() {
	yourls_register_plugin_page('google_analytics', 'Google Analytics', 'kws_yourls_add_analytics_do_page');
	// parameters: page slug, page title, and function that will display the page itself
}

function kws_yourls_analytics_defaults() {
	// Define defaults
	$utm_campaign = '';
	$utm_medium = 'urlshortener';
	$utm_term = $utm_content = '';
	$utm_source = preg_replace('/(?:.*?)\:\/\/(.+)/ism', '$1', YOURLS_SITE); // http://exam.pl becomes exam.pl

	$query = array(
		'utm_source' => $utm_source,
		'utm_medium' => $utm_medium,
		'utm_campaign' => $utm_campaign,
		'utm_term' => $utm_term,
		'utm_content' => $utm_content,
	);

	$defaults = yourls_get_option('analytics_defaults');

	if (!is_array($defaults)) {return $query;}

	$settings = array_merge($query, $defaults);

	// These two are required.
	if (empty($settings['utm_source'])) {
		$settings['utm_source'] = $utm_source;
	}
	if (empty($settings['utm_medium'])) {
		$settings['utm_medium'] = $utm_medium;
	}

	return $settings;
}

// Display admin page
function kws_yourls_add_analytics_do_page() {

	// Check if a form was submitted
	if (isset($_POST['analytics_override'])) {
		kws_yourls_add_analytics_update_option();
	}

	// Get value from database
	$analytics_override = yourls_get_option('analytics_override');
	$add_to_form = yourls_get_option('add_to_form');
	if ($add_to_form === false) {$add_to_form = 'yes';} // Defaults to yes

	$analytics_defaults = (array) kws_yourls_analytics_defaults();

	?>
	<style type="text/css">
		 .description { color:#555; font-style:italic; }
		 #ga_settings { font-size: 120%; }
		 #ga_settings h4 {
			margin-bottom: .25em;
		 }
		 .submit {
			margin-top: .5em;
			padding-top: .9em;
			border-top: 1px solid #ccc;
			padding-bottom: 1em;
		 }
		 .submit input {
			font-size: 14px!important;
		 }
		 #more_tracking_code_info {
			display: none;
			padding:5px 15px 5px 15px;
			background: #fcfcfc;
			border-top: 2px solid #ccc;
		 }
		 label.borderbottom {
			border-bottom: 1px dotted;
			cursor: pointer;
		 }
		 .req { color: red; }
	</style>
	<h2>Google Analytics Settings</h2>

	<form method="post" id="ga_settings">
		<h4>Tracking Tags</h4>
		<p class="description">
			The settings defined here will be the default Google Analytics tracking script settings. With the current configuration, the following string will be added to shortened URLs:<br/>
			<code>?<?php
$analytics_defaultsexample = $analytics_defaults;
	$urlstring = array();
	foreach ($analytics_defaultsexample as $key => $value) {
		if (empty($value)) {
			unset($analytics_defaultsexample[$key]);
			continue;
		}
		$urlstring[] = $key . '=' . $value;
	}
	echo implode('&', $urlstring);?>
			</code><br/>
		</p>
		<p class="description"><strong>Note:</strong> These default settings can be overridden by shortening an URL with the tracking tags in place already. If you want to override (or not override), configure the "Override existing tracking tags?" setting below.</p>
		<p style="clear:both;">
			<label for="utm_source" style="padding-left:.75em;">Source<span class="req" title="Required">*</span> <input id="utm_source" type="text" size="15" name="analytics_defaults[utm_source]" value="<?php echo $analytics_defaults['utm_source'];?>"></label>
			<label for="utm_medium" style="padding-left:.75em;">Medium<span class="req" title="Required">*</span> <input id="utm_medium" type="text" size="15" name="analytics_defaults[utm_medium]" value="<?php echo $analytics_defaults['utm_medium'];?>"></label>
			<label for="utm_campaign" style="padding-left:.75em;">Campaign Name <input id="utm_campaign" type="text" size="15" name="analytics_defaults[utm_campaign]" value="<?php echo $analytics_defaults['utm_campaign'];?>"></label>
			<label for="utm_term" style="padding-left:.75em;">Term <input id="utm_term" type="text" size="15" name="analytics_defaults[utm_term]" value="<?php echo $analytics_defaults['utm_term'];?>"></label>
			<label for="utm_content" style="padding-left:.75em;">Content <input id="utm_content" type="text" size="15" name="analytics_defaults[utm_content]" value="<?php echo $analytics_defaults['utm_content'];?>"></label>
		</p>
		<p class="description" style="text-align:center;"><span class="req">*</span> = required fields</p>
		<?php kws_yourls_show_analytics_help();?>

		<h4>Override existing tracking tags?</h4>
		<p class="description">If an URL already has Google Analytics tracking tags, do you want to use those instead of your default tracking tags?</p>
		<p><label for="analytics_override_yes">Override with Defaults <input type="radio" id="analytics_override_yes" name="analytics_override" value="1"<?php if ($analytics_override) {echo ' checked="checked"';}?> /></label>
		<label for="analytics_override_no" style="padding-left:.5em; border-left:1px solid #ccc; margin-left:.5em;">Use Existing (default)<input id="analytics_override_no" type="radio" name="analytics_override" value="0"<?php if (!$analytics_override) {echo ' checked="checked"';}?> /></label></p>


		<h4>Add GA tracking options to Add URL form?</h4>
		 <p><input type="hidden" name="add_to_form" value="no" /><label for="add_to_form_yes">Add tracking code options to Add URL form <input type="checkbox" id="add_to_form_yes" name="add_to_form" value="yes"<?php if ($add_to_form != 'no') {echo ' checked="checked"';}?> /></label></p>

		<div class="submit">
			<input style="display:block;" type="submit" value="Update Settings">
		</div>

	</form>
<?php
}

function kws_yourls_show_analytics_help() {
	?>
	<p style="text-align:center;">Not sure what these settings are? <a class="toggle" href="#more_tracking_code_info">Show descriptions of these terms</a></p>
		<div id="more_tracking_code_info" class="toggle">
			<table class="outline2" border="0" cellpadding="0" cellspacing="5">
				<tbody>
					<tr>
						<td width="201">
							<p><label for="utm_source" class="borderbottom">Source (utm_source)</label></p>
						</td>

						<td width="666"><span class="req">Required.</span> Use <strong>utm_source</strong> to identify a search engine, newsletter name, or other source.<br>
						<em>Example</em>: <tt>utm_source=google</tt></td>
					</tr>

					<tr>
						<td>
							<p><label for="utm_medium" class="borderbottom">Medium (utm_medium)</label></p>
						</td>

						<td><span class="req">Required.</span> Use <strong>utm_medium</strong> to identify a medium such as email or cost-per-click.<br>
						<em>Example</em>: <tt>utm_medium=cpc</tt></td>
					</tr>

					<tr>
						<td>
							<p><label for="utm_campaign" class="borderbottom">Campaign Name (utm_campaign)</label></p>
						</td>

						<td>Used for keyword analysis. Use <strong>utm_campaign</strong> to identify a specific product promotion or strategic campaign.<br>
						<em>Example</em>: <tt>utm_campaign=spring_sale</tt></td>
					</tr>

					<tr>
						<td>
							<p><label for="utm_term" class="borderbottom">Term (utm_term)</label></p>
						</td>

						<td>Used for paid search. Use <strong>utm_term</strong> to note the keywords for this ad.<br>
						<em>Example</em>: <tt>utm_term=running+shoes</tt></td>
					</tr>

					<tr>
						<td>
							<p><label for="utm_content" class="borderbottom">Content (utm_content)</label></p>
						</td>

						<td>Used for A/B testing and content-targeted ads. Use <strong>utm_content</strong> to differentiate ads or links that point to the same URL.<br>
						<em>Examples</em>: <tt>utm_content=logolink</tt> <em>or</em> <tt>utm_content=textlink</tt></td>
					</tr>
				</tbody>
			</table>
			<p style="text-align:center; font-weight:bold;"><a href="http://www.google.com/support/googleanalytics/bin/answer.py?answer=55578" target="_blank">Learn more about Google Analytics link tagging</a></p>
		</div>
	<?php
}

// Update option in database
function kws_yourls_add_analytics_update_option() {
	if (isset($_POST['analytics_override'])) {
		yourls_update_option('analytics_override', !empty($_POST['analytics_override']));
	}
	if (isset($_POST['add_to_form']) && $_POST['add_to_form'] === 'yes' || $_POST['add_to_form'] === 'no') {
		yourls_update_option('add_to_form', $_POST['add_to_form']);
	}
	if (isset($_POST['analytics_defaults'])) {
		$analytics_defaults = array();
		if (is_array($_POST['analytics_defaults'])) {
			foreach ($_POST['analytics_defaults'] as $k => $v) {
				$analytics_defaults[$k] = yourls_sanitize_title($v);
			}
			yourls_update_option('analytics_defaults', $analytics_defaults);
		}
	}
}

yourls_add_filter('custom_url', 'kws_yourls_custom_url', 999);

function kws_yourls_custom_url($url = '') {
	if (!isset($_GET['allfields']) || !isset($_GET['keyword'])) {return $url;}

	$allfields = yourls_maybe_unserialize($_GET['allfields']);

	parse_str($allfields, $fields);

	$accepted = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content');

	foreach ($fields as $key => $field) {
		if (!in_array($key, $accepted) || empty($field)) {
			unset($fields[$key]);
		}
	}

	return yourls_add_query_arg($fields, $url);
}

yourls_add_action('admin_init', 'kws_yourls_error_messages');

function kws_yourls_error_messages() {
	if (!isset($_POST['analytics_defaults'])) {return;}
	$settings = kws_yourls_analytics_defaults();
	$message = '';
	if (empty($_POST['analytics_defaults']['utm_source'])) {
		$message .= '<p><label for="utm_source" class="borderbottom">Source</label> is a required field. It has been reset to the default setting: <tt>' . $settings['utm_source'] . '</tt></p>';
	}
	if (empty($_POST['analytics_defaults']['utm_medium'])) {
		$message .= '<p><label for="utm_medium" class="borderbottom">Medium</label> is a required field. It has been reset to the default setting: <tt>' . $settings['utm_medium'] . '</tt></p>';
	}
	if (!empty($message)) {
		echo yourls_add_notice($message);
	}
}

// We need to hook into the admin init so we can modify the request before it gets
// processed by the ajax calls et al.
yourls_add_action('admin_init', 'kws_yourls_admin_init');

function kws_yourls_admin_init() {
	if ((empty($_REQUEST['action']) || empty($_REQUEST['url'])) && !isset($_REQUEST['u'])) {return;}

	// Bookmark
	if (isset($_REQUEST['u'])) {

		$query = kws_yourls_analytics_defaults();

		if (isset($_GET)) {
			unset($_GET['u'], $_GET['t'], $_GET['k'], $_GET['s'], $_GET['signature']);
			$queryGET = kws_yourls_process_array($_GET);
			foreach ($queryGET as $key => $qg) {if (empty($qg)) {unset($queryGET[$key]);}}
			$query = array_merge($query, $queryGET);
			foreach ($query as $k => $q) {
				if (empty($q)) {unset($query[$k]);}
			}
		}

		$_GET['u'] = $_REQUEST['u'] = yourls_add_query_arg($query, $_REQUEST['u']);

		return;
	}

	// In the admin. We only want to process on adds and edits.
	switch ($_REQUEST['action']) {
		case 'add':
		case 'edit_save':
			$_REQUEST['url'] = yourls_apply_filter('custom_url', $_REQUEST['url']);
			break;
	}

	return;
}

yourls_add_action('html_head', 'kws_yourls_addnew_js');

function kws_yourls_addnew_js() {
	$add_to_form = yourls_get_option('add_to_form');
	if (!yourls_is_admin() || $add_to_form == 'no') {return;}
	?>
<script type="text/javascript">

// Overrride the existing function to include the new fields
function add_link() {
	if( $('#add-button').hasClass('disabled') ) {
		return false;
	}
	var newurl = $("#add-url").val();
	var nonce = $("#nonce-add").val();
	if ( !newurl || newurl === 'http://' || newurl === 'https://' ) {
		$("#add-url").css({'border-color':'red', 'color':'red'}).focus();
		return;
	}
	$("#add-url").css({'border-color':'#88C0EB', 'color': '#595441'});
	var keyword = $("#add-keyword").val();
	var allfields = $('#new_url_form').serialize(); // Added by KWS
	add_loading("#add-button");
	$.getJSON(
		ajaxurl,
		{action:'add', url: newurl, keyword: keyword, nonce: nonce, allfields: allfields},
		function(data){
			if(data.status === 'success') {
				$('#main_table tbody').prepend( data.html ).trigger("update");
				$('#nourl_found').css('display', 'none');
				zebra_table();
				increment_counter();
				toggle_share_fill_boxes( data.url.url, data.shorturl, data.url.title );


			}

			//reset_url();


			add_link_reset();
			end_loading("#add-button");
			end_disable("#add-button");

			feedback(data.message, data.status);
					}
	);
}

	jQuery(document).ready(function($){
		$('#new_url_form').append('<div>GA Tracking Options</div><div class="toggle" id="ga_tracking_options"><?php

	$sources = kws_yourls_analytics_defaults();

	foreach ($sources as $source => $value) {
		$sourceNice = preg_replace('/(?:.*?)\_(.+)/ism', '$1', $source);
		echo '<label for="' . $source . '" style="padding-left:.75em;">' . $sourceNice . ' <input id="' . $source . '" type="text" size="12" name="' . $source . '" value="' . $value . '" /></label>';
	}

	?></div><div style="clear:both;"></div>');
	});

</script>
<?php
}

yourls_add_filter('get_keyword_info', 'kws_yourls_add_analytics_tracking_code', 999, 4);

function kws_yourls_add_analytics_tracking_code($return, $keyword, $field, $notfound = false) {

	// If we're not working with a long URL, this filter is unnecessary.
	if ($field !== 'url' || yourls_is_admin() || defined('YOURLS_INFOS') || defined('YOURLS_PREVIEW')) {return $return;}

	// If we are working with a long URL, then let's get to it!
	$url = $return;

	// Don't create a non-empty URL from an empty URL (i.e. one that was not in the database) since YOURLS depends on emptiness in yourls-go.php
	if (empty($url)) {
		return $return;
	}

	$parsed = parse_url($url);
	$parsed['scheme'] = isset($parsed['scheme']) ? $parsed['scheme'] : 'http';
	$parsed['host'] = isset($parsed['host']) ? $parsed['host'] : '';
	$parsed['path'] = isset($parsed['path']) ? $parsed['path'] : '';

	$urlStripped = $parsed['scheme'] . '://' . $parsed['host'] . $parsed['path'];
	$urlQueryString = array();
	$query = kws_yourls_analytics_defaults();

	$urlParsed = parse_url($url);

	// Are there query args in the long URL? We'll want an array of those, thanks.
	if (isset($urlParsed['query'])) {
		parse_str($urlParsed['query'], $urlQueryString);
	}

	// Override trumps embedded long URL query args
	if (yourls_get_option('analytics_override') == 1) {
		echo '<h2>After override</h2>';
		$query = array_merge(kws_yourls_process_array($urlQueryString), kws_yourls_process_array($query));
	} else {
		$query = array_merge(kws_yourls_process_array($query), kws_yourls_process_array($urlQueryString));
	}

	// $_GET query strings trumps all (If there are query args added to the shortlink)
	if (isset($_GET)) {
		$_GET = kws_yourls_process_array($_GET);
		if (isset($_GET['u'])) {
			unset($_GET['u'], $_GET['t'], $_GET['k'], $_GET['signature']);
		}
		foreach ($_GET as $key => $value) {
			if (!empty($value)) {$query[$key] = $value;}
		}
	}

	// Add back in the query string
	$url = yourls_add_query_arg($query, $urlStripped);

	return $url;
}

function kws_yourls_parse_url($url) {

	$urlParsed = parse_url($url);

	if (isset($urlParsed['query'])) {
		parse_str($urlParsed['query'], $urlParsed);
	}

	if (empty($urlParsed)) {return $url;}

	foreach ($urlParsed as $key => $queryString) {
		$queryStringParsed = explode('=', $queryString);
		unset($urlParsed[$key]);
		if (isset($queryStringParsed[0]) && isset($queryStringParsed[1])) {
			$urlParsed[$queryStringParsed[0]] = $queryStringParsed[1];
		} else {
			continue;
		}
	}

	return $urlParsed;
}

function kws_yourls_process_source_abbr($value = '') {
	switch ($value) {
		case 'fb':
			$value = 'facebook';
			break;
		case 'tw':
			$value = 'twitter';
			break;
		case 'li':
			$value = 'linkedin';
			break;
		case 'em':
			$value = 'email';
			break;
	}
	return $value;
}

function kws_yourls_process_array($query) {

	if (!is_array($query)) {return $query;}

	foreach ($query as $key => $value) {
		switch ($key) {
			case 'utm_medium':
				$utm_medium = kws_yourls_process_source_abbr($value);
				break;
			case 'med':
				unset($query[$key]);
				$utm_medium = kws_yourls_process_source_abbr($value);
				break;
			case 'medium':
				unset($query[$key]);
				$utm_medium = kws_yourls_process_source_abbr($value);
				break;
			case 'utm_source':
				$utm_source = kws_yourls_process_source_abbr($value);
				break;
			case 'source':
				unset($query[$key]);
				$utm_source = kws_yourls_process_source_abbr($value);
				break;
			case 'src':
				unset($query[$key]);
				$utm_source = kws_yourls_process_source_abbr($value);
				break;
			case 'utm_campaign':
				$utm_campaign = $value;
				break;
			case 'campaign':
				unset($query[$key]);
				$utm_campaign = $value;
				break;
			case 'cam':
				unset($query[$key]);
				$utm_campaign = $value;
				break;
			case 'utm_content':
				$utm_content = $value;
				break;
			case 'content':
				unset($query[$key]);
				$utm_content = $value;
				break;
			case 'con':
				unset($query[$key]);
				$utm_content = $value;
				break;
			case 'utm_term':
				$utm_term = $value;
				break;
			case 'term':
				unset($query[$key]);
				$utm_term = $value;
				break;
		}
	}

	unset($query['utm_source'], $query['utm_medium'], $query['utm_campaign'], $query['utm_term'], $query['utm_content']);

	$query['utm_source'] = $utm_source;
	$query['utm_medium'] = $utm_medium;
	$query['utm_campaign'] = $utm_campaign;
	$query['utm_term'] = $utm_term;
	$query['utm_content'] = $utm_content;

	return $query;

}
