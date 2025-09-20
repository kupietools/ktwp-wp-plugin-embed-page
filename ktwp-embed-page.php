<?php
/*
 * Plugin Name: KupieTools Embed Page
 * Plugin URI:        https://michaelkupietz.com/
 * Description:       Adding embeddable pages with /embed/ or ?embed URLs.
 * Version:           1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Michael Kupietz
 * Author URI:        https://michaelkupietz.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://michaelkupietz.com/my-plugin/kupietools-embed-page/
 * Text Domain:       mk-plugin
 * Domain Path:       /languages
 */


/**
 * Your code goes below.
 */

// Configuration: 404 page slug (change this to match your 404 page)
$kupietools_404_page_slug = '404-wrong-river';

add_action('template_redirect', 'kupietools_octolite_template_redirect');

function kupietools_octolite_template_redirect() {
    global $wp_query;

    if (strpos($_SERVER['REQUEST_URI'], '/octolite/') !== false || strpos($_SERVER['REQUEST_URI'], '/embed/') !== false) {
		/* REALLY ONLY NEED THESE OCTOLITE FUNCTIONS FOR OCTOLITE BUT ADDING A CHECK FOR EMBED BECAUSE THAT SOMEHOW ALLOWS 404 PAGE TO HAVE TITLE */
        // Extract the path without /octolite/
        $uri = $_SERVER['REQUEST_URI'];
        $path = str_replace('/octolite/', '/', $uri);
         $path = str_replace('/embed/', '/', $path);
        $path = strtok($path, '?'); // Remove query string for path lookup
        $path = trim($path, '/');

        // Try to find the post/page by path
        $post = null;
        if (!empty($path)) {
            $post = get_page_by_path($path, OBJECT, 'post');
            if (!$post) {
                $post = get_page_by_path($path, OBJECT, 'page');
            }
        }

        // If no specific post found, don't override the query
        if ($post) {
            $wp_query->is_page = ($post->post_type === 'page');
            $wp_query->is_single = ($post->post_type === 'post');
            $wp_query->is_singular = true;
            $wp_query->is_404 = false;
            $wp_query->posts = [$post];
            $wp_query->post_count = 1;
            $wp_query->queried_object = $post;
            $wp_query->queried_object_id = $post->ID;
        }

        // Set URL parameters for octolite (equivalent to ?tags&byline&title)
        $_GET['tags'] = '';
        $_GET['byline'] = '';
        $_GET['title'] = '';

        add_filter('template_include', 'kupietools_octolite_template');
    }
}

function kupietools_octolite_template($template) {
    // Check if the octolite query variable is set.
    // In this case, we're not setting it, so we'll check the URI again.
    if (strpos($_SERVER['REQUEST_URI'], '/octolite/') !== false) {
        return plugin_dir_path(__FILE__) . 'your-template.php';
    }
    return $template;
}


/*  prevent stripping of /embed/ and embed parameters on redirects - this was updated 2025apr29 because /embed/?p=1234&embedParam1&embedParam2 was broken. I have the previous version archived locally. */
function preserve_embed_parts($redirect_url) {
    $requested_url = $_SERVER['REQUEST_URI'];
    
    if ((strpos($requested_url, '/embed/') !== false || isset($_GET['embed'])) && 
        $redirect_url !== $requested_url) {
        
        $separator = (strpos($redirect_url, '?') !== false) ? '&' : '?';
        return $redirect_url . $separator . 'embed';
    }
   if ((strpos($requested_url, '/octolite/') !== false || isset($_GET['octolite'])) && 
        $redirect_url !== $requested_url) {
        
        $separator = (strpos($redirect_url, '?') !== false) ? '&' : '?';
        return $redirect_url . $separator . 'octolite';
    }
    return $redirect_url;
}

add_filter('redirect_canonical', 'preserve_embed_parts', 10, 2);

/*NO! This somehow made all posts show
//make sure all parameters are allowed by wordpress
add_filter('query_vars', 'parameter_queryvars' );
function parameter_queryvars( $qvars )
{
$qvars = array('embed','tags','author','text','title','byline','octoth'); /~ octo says show a greeting to octothorpe visitors; should be included in submissions to octothorpe servers ~/
return $qvars;
}
*/

// Apply template for embed patterns


function check_embed_template($template) {
    $uri = $_SERVER['REQUEST_URI'];
    $is_embed = (
        strpos($uri, '/embed/') !== false ||
        isset($_GET['embed'])
    );
    $is_octo = (
        strpos($uri, '/octolite/') !== false ||
        isset($_GET['octolite'])
    );

	$is_text=isset($_GET['text']);

    if ($is_embed || $is_octo) {
        global $wp_query;

        // Track if this is a 404 situation before we modify the query
        $is_404_request = (!have_posts() || $wp_query->post_count > 1);

        // If we have no posts or too many posts (indicating default query ran),
        // we're in a 404 situation - load configured 404 page
        if ($is_404_request) {
            global $kupietools_404_page_slug;

            $not_found_post = get_page_by_path($kupietools_404_page_slug);

            if (!$not_found_post) {
                // Fallback: create a simple 404 page if the configured slug doesn't exist
                $post_data = array(
                    'ID' => -1, // Use negative ID to avoid conflicts
                    'post_title' => '404 - You Done Took The Wrong River',
                    'post_content' => '<p>This site has no dang page at this URL.</p>',
                    'post_type' => 'page',
                    'post_status' => 'publish',
                    'post_author' => 1,
                    'post_date' => current_time('mysql'),
                    'post_date_gmt' => current_time('mysql', 1),
                    'post_modified' => current_time('mysql'),
                    'post_modified_gmt' => current_time('mysql', 1),
                    'post_name' => '404-not-found',
                    'post_parent' => 0,
                    'menu_order' => 0,
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                    'comment_count' => 0,
                    'filter' => 'raw' // Important for WP_Post
                );
                $not_found_post = new WP_Post((object)$post_data);
            }

            $wp_query->posts = [$not_found_post];
            $wp_query->post_count = 1;
            $wp_query->current_post = -1; // Reset current post counter
            $wp_query->is_singular = true;
            $wp_query->is_page = ($not_found_post->post_type === 'page');
            $wp_query->is_404 = false; // We're showing content, not a true 404
            $wp_query->queried_object = $not_found_post;
            $wp_query->queried_object_id = $not_found_post->ID;

            // Store the 404 flag for later use
            $wp_query->kupietools_was_404 = true;

            // Ensure the post is set as the current global post for the loop
            global $post;
            $post = $not_found_post;
            setup_postdata($post);
        }

		/* disable Related Posts plugin if present */
add_filter( 'rp4wp_append_content', '__return_false' );
        // Buffer the output
        ob_start();
		if (!$is_text){
        ?>
        <!DOCTYPE html>
<html>
	<head><title><?php echo get_the_title(); ?></title>
		<!-- NOTE 2025sep18 ok, attempted to set this up for octothorpes, since they can't handle my full pages, but A.) They strip URL paramaters, and B.) I couldn't really get the harmonizer working without setting up a separate file for it, and C.) Even with a harmonizer file, they strip URL parameters, so can't see the tags, and, can't display "Hello, Octothorpes User" message. -->
		 <!-- DISABLED 2025sep20 link
    rel="preload"
    as="fetch"
    href="http://octothorp.es/?uri=<?php echo urlencode(get_the_permalink().'octolite/')/*.'&as=data:application/json;charset=utf-8,'.urlencode('{"selector": "[rel=\'tag\']","attribute": "textContent"}');*/?>" -->
		<?php /* ADD LINK TAGS FOR OCTOTHORPES - DISABLED
			// Get the post ID. You can use get_the_ID() within The Loop, or pass a specific ID.
$post_id = get_the_ID();

// Get the post's tags.
$post_tags = wp_get_post_terms($post_id, 'post_tag', array('fields' => 'names'));

// Get the post's custom taxonomy terms for 'genre'.
$post_genres = wp_get_post_terms($post_id, 'genre', array('fields' => 'names'));

// Combine the two arrays of terms.
$all_terms = array_merge($post_tags, $post_genres);

// Loop through each term and output the desired link format.
foreach ($all_terms as $term) {
 
    echo '<link rel="octo:octothorpes" href="' . $term . '">';
} */?>
		  <!-- meta name="octo:harmonizer" content="*[rel='tag']" -->
<?php if (get_the_ID()==10627) {
		  echo '<link rel="canonical" href="https://michaelkupietz.com/?p=1385" /><!-- add canonical to iframed kwits in right column. -->' . "\n";
	}
	
	else 
	{  echo '<link rel="canonical" href="'.get_the_permalink().'" /><!-- add canonical. -->' . "\n";}
	?> <link rel="stylesheet" href="/whostyles/sanitize.min.css">
          <link id="whostyle-link" rel="stylesheet">
          <style id="whostyle-css" type="text/css"></style></head><body class="whostyle"><?php
		}
			while ( have_posts() ) : the_post();
		if ( $is_octo) { 
echo '<div style="border:2px solid purple;background:#FFEEFF;padding:12px;"><span style="font-size:2em;font-weight:900;"> # Ghost Page </span><!-- Greeting, Octothorpes user! You are looking at a low-bandwidth version of this content, since at present the octothorpe server seems to have a problem parsing the full unmitigated grandeur of my regular site&apos;s pages. -->Hello, Octothorpes Protocol user. Not sure how you got to this page, but unfortunately, I am no longer attempting to participate in the Octothorpes project due to complete lack of interest from everyone else I have contacted about it, and other than this message, have turned off all integrations with it. <p>I do have an improved version of the official Octothorpes WordPress plugin, but unfortunately the original authors have refused to open-source it, and without an open source license attached to the original files I built on, I can&apos;t share it.<details style="border:1px solid gray;padding:6px;"><summary>Frustrated rant about it here, click to read...</summary><p>&lt;rant&gt;I have to express some frustration. I&apos;m extremely disappointed that Octothorpes do not seem to work at all. The whole project is a brilliant idea—I went for it hook, line, and sinker. In fact, I liked it so much that I immediately started working on improving things, such as their extremely lacking WordPress integration plugin... so eagerly, in fact, that I put in a lot of work <em>before</em> testing whether Octothorpes actually work at all.<p>And it appears that they don&apos;t.<p>At least, <em>I</em> couldn&apos;t ever get them to, despite sinking several long nights into it. Disappointingly, I had to add a lot of unneeded extra structure to my site code to even try and get Octothorpes working—contrary to what the documentation suggested—and even after doing everything right, as confirmed by their debugging tool, my Octothorpes never appeared on their server.<p>Given that I never got it to work and couldn&apos;t find anyone who was able to assist me, all the complexity I had to add to my site in the effort—such as serving Octothorpe-specific page versions, like the one you are looking at right now—became needless cruft. So after a few very long nights of fruitlessly spinning my wheels, I&apos;ve given up, and removed it all again. <p>I have to add that I&apos;m disappointed. I&apos;m not used to showing up enthusiastic to contribute to an open-source project and so quickly winding up walking away with absolutely nothing, no involvement, no ability to use or run it, no interest in my desire to contribute (even code I&apos;ve already finished!), nothing. Maybe I&apos;ve been lucky before now.<p>Octothorpes are a brilliant idea, in concept. I hope they work someday, and become a project that might see wide adoption and contribution by enthusiastic developers. (And that the main devs see the value in WordPress plugin developers expressing an interest in authoring OP integrations with WordPress... 43% of the world&apos;s websites can&apos;t be wrong...)<p>Sorry to rant, but, my site, my several nights of work lost, my prerogative to express it.&lt;/rant&gt;</details> <hr>Seeing as how this Octothorpe-compliant low-bandwidth page version serves no purpose anymore (and doesn&apos;t contain any code related to the Octothorpe service anymore, except for noticing requests, in order to show this message), you probably want to view the original page in its complete technicolor glory at ';
			if (isset($_GET['text'])) {echo get_the_permalink(); } else {echo '<a href="'.get_the_permalink().'" target="_blank">'.get_the_permalink().'</a>';}
			echo ".</div>";
		}
	if (isset($_GET['title']) || $is_octo) {
	
echo $is_text?'':'<h2 class="embed_title">';
		echo get_the_title();
		echo $is_text?'<br>':'</h2>'; }
		if (isset($_GET['tags']) || $is_octo) {
	echo $is_text?'':'<style>.screen-reader-text.screen-reader-text {border: 0;clip: rect(1px,1px,1px,1px);-webkit-clip-path: inset(50%);clip-path: inset(50%);height: 1px;margin: -1px;overflow: hidden;padding: 0;position: absolute !important;width: 1px;word-wrap: normal !important;word-break: normal;
}</style>';
			echo $is_text?'':"<i>";
			
			do_action( 'sinatra_before_post_category' );

	if ( is_singular() ) {
		echo "Posted in ";
		sinatra_entry_meta_category( ', ', false,false,get_the_ID(),false,false,false,"a" );
/* $sep = ", ",
    $show_icon = true,
    $return = false,
    $thisID = 0, 
	$iconOnly = false,
	$nolink = false,
	$useCat = false,
$theTag="span" */

		echo ', ';
		sinatra_entry_meta_genre(", ", true,false,0,"a");

/* $sep = ", ",
    $show_icon = true,
    $return = false,
    $thisID = 0,
    $theTag = 'span' */
	} else {
		if ( 'blog-horizontal' === sinatra_get_article_feed_layout() ) {
			sinatra_entry_meta_category( ', ', false );
				echo ', ';sinatra_entry_meta_genre(", ", false);

		} else {
			sinatra_entry_meta_category( ', ', false );
					echo ', ';sinatra_entry_meta_genre(", ", false);
		}
	}
	
	do_action( 'sinatra_after_post_category' );
			echo $is_text?'':"</i>";
		}
	
		if (isset($_GET['byline']) || $is_octo) {
	
echo '<p class="embed_author">'; /* leave paragraphs, code will convert to line breaks */
			echo $is_text?'':'<i>';
			echo 'by ';echo get_the_author();
			echo $is_text?'':'</i>';
			echo'</p>'; 
		}
			 the_content(); 

	$pl=get_the_permalink();
echo '<p style="font-size:.75em";>'; /* leave paragraphs even if is_text, code will convert to line breaks */
	if (get_the_ID()!=10627) {echo 'Content originally from <a href="'.$pl.'" target="_blank">'.$pl.'</a>. ';}
	echo '&copy; copyright '.$modified_date = esc_html( get_the_modified_date( 'Y' ) ).' Michael E. Kupietz';
		echo '</p>';

			
			endwhile; // End of the loop.
	if(!$is_text) { ?>	
	</body>
            </html>
        <?php 
	}
        $new_template = ob_get_clean();
        if ( $is_text) {

						$new_template = preg_replace (array('/(<a[^>]* href=")([^"]*)("[^>]*>)/i',"/<p [^>]*>/i","/<p>/i","</p>","/<br +[^>]*>/i","/<br>/i"),array("$1$2$3[$2]","\n\n","\n\n",'',"\n","\n"),$new_template);
			$new_template = strip_tags(html_entity_decode($new_template));

			header("Content-Type: text/plain");

        }

        // Send proper HTTP status header and debugging info
        if (isset($wp_query->kupietools_was_404) && $wp_query->kupietools_was_404) {
            // This was originally a 404 situation
            http_response_code(404);
            header('X-Debug-Status: 404-embed');
        } else {
            // Normal content
            http_response_code(200);
            header('X-Debug-Status: 200-embed');
        }

        // Add content type if not already set
        if (!$is_text) {
            header('Content-Type: text/html; charset=utf-8');
        }

        // Print the template contents and exit
        echo $new_template;
        exit();
    }
    
    return $template;
}

add_filter('template_include', 'check_embed_template');

add_filter('ktwp_custom_rewrite_exclusions', function($patterns) {
//    $patterns[] = '/^embed\//';  // Matches URLs that start with "api/"
   $patterns[] = '/\/embed\/(\?.*)?$/'; 
	 $patterns[] = '/\/octolite\/(\?.*)?$/'; 
	return $patterns;
}); /* need to make sure /embed/?p=1234 isn't handled as a page url by the path rewriting function in my theme extensions */
do_action('ktwp_embed_footer'); /* <--- I know this isn't used, can't remember where it's added to... search for it and remove. */

/* old way with externalk template 
 * function xcheck_embed_template($template) {
    $uri = $_SERVER['REQUEST_URI'];
    $is_embed = (
        strpos($uri, '/embed/') !== false ||
        isset($_GET['embed'])
    );
    
    if ($is_embed) {
        $new_template = locate_template(array('template-content-only.php'));
        if ($new_template) {
            return $new_template;
        }
    }
    return $template;
} */
?>
