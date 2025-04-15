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




//  prevent stripping of /embed/ and embed parameters on redirects
function preserve_embed_parts($redirect_url, $requested_url) {
    if ((strpos($requested_url, '/embed/') !== false || 
         isset($_GET['embed'])) && 
        $redirect_url !== $requested_url) {
        
        // If original had /embed/, add it to redirect
        if (strpos($requested_url, '/embed/') !== false && strpos($redirect_url, '/embed/') === false) {
            return rtrim($redirect_url, '/') . '/embed/';
        }
        
        // If original had ?embed or &embed, add it to redirect
        if (isset($_GET['embed']) && strpos($redirect_url, 'embed') === false) {
            $separator = (strpos($redirect_url, '?') !== false) ? '&' : '?';
            return $redirect_url . $separator . 'embed';
        }
    }
    return $redirect_url;
}
add_filter('redirect_canonical', 'preserve_embed_parts', 10, 2);

// Apply template for embed patterns


function check_embed_template($template) {
    $uri = $_SERVER['REQUEST_URI'];
    $is_embed = (
        strpos($uri, '/embed/') !== false ||
        isset($_GET['embed'])
    );
	$is_text=isset($_GET['text']);
    
    if ($is_embed) {
		/* disable Related Posts plugin if present */
add_filter( 'rp4wp_append_content', '__return_false' );
        // Buffer the output
        ob_start();
		if (!$is_text){
        ?>
        <!DOCTYPE html>
<html>
	<head><title><?php echo get_the_title(); ?></title>
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
	if (isset($_GET['title'])) {
	
echo $is_text?'':'<h2 class="embed_title">';
		echo get_the_title();
		echo $is_text?'<br>':'</h2>'; }
		if (isset($_GET['tags'])) {
	echo $is_text?'':'<style>.screen-reader-text.screen-reader-text {border: 0;clip: rect(1px,1px,1px,1px);-webkit-clip-path: inset(50%);clip-path: inset(50%);height: 1px;margin: -1px;overflow: hidden;padding: 0;position: absolute !important;width: 1px;word-wrap: normal !important;word-break: normal;
}</style>';
			echo $is_text?'':"<i>";
			echo "Posted in ";
			do_action( 'sinatra_before_post_category' );

	if ( is_singular() ) {
		sinatra_entry_meta_category( ', ', false );
		echo ', ';
		sinatra_entry_meta_genre(", ", true);
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
	
		if (isset($_GET['byline'])) {
	
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
        // Print the template contents and exit
        echo $new_template;
        exit();
    }
    
    return $template;
}

add_filter('template_include', 'check_embed_template');

add_filter('ktwp_custom_rewrite_exclusions', function($patterns) {
    $patterns[] = '/^embed\//';  // Matches URLs that start with "api/"
    return $patterns;
}); /* need to make sure /embed/?p=1234 isn't handled as a page url by the path rewriting function in my theme extensions */


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