# KupieTools Embed Page

A WordPress plugin that enables clean, embeddable versions of your content through simple URL patterns like `/embed/` or `?embed` parameters, perfect for iframes and content sharing.

## Features

- Creates embeddable versions of any WordPress page or post
- Supports two URL patterns: `/embed/` path or `?embed` query parameter
- Preserves embed parameters during WordPress redirects
- Clean, minimalist output focused on content
- Optional parameters for customizing embedded content:
  - `?title` - Include the post title
  - `?tags` - Include post categories and tags
  - `?byline` - Include author information
  - `?text` - Output as plain text instead of HTML
- Proper canonical URL tags to maintain SEO best practices
- Copyright notice automatically added to embedded content
- Responsive design with sanitized styles
- Automatic disabling of certain plugins in embedded view

## How It Works

1. The plugin intercepts requests containing `/embed/` or `?embed`
2. It buffers the output and replaces the standard WordPress template
3. Only the essential content is rendered, with optional elements based on parameters
4. Special handling ensures proper redirects while maintaining embed status
5. Canonical links ensure proper SEO attribution
6. Optional plain text mode for non-HTML embedding scenarios

## Use Cases

- Embed WordPress content in iframes on other sites
- Create embeddable versions of articles for content syndication
- Provide clean, content-only views for specific applications
- Generate plain text versions of content for feed readers or text-based clients
- Easily share WordPress content while maintaining attribution

## URL Parameters

- `/embed/` or `?embed` - Activates embedded view
- `?title` - Includes the post title
- `?tags` - Shows post categories and taxonomies
- `?byline` - Displays author information
- `?text` - Outputs content as plain text

## Example URLs

- `https://yourdomain.com/your-post/embed/`
- `https://yourdomain.com/your-post/?embed&title&byline`
- `https://yourdomain.com/your-post/embed/?text`
- `https://yourdomain.com/embed/?p=1234&title&text`
- `https://yourdomain.com/?p=1234&embed&title&tags`

## Installation

1. Upload the plugin files to the `/wp-content/plugins/ktwp-embed-page` directory
2. Activate the plugin through the WordPress admin interface
3. Access any post with `/embed/` appended to the URL or `?embed` query parameter

## License

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.
