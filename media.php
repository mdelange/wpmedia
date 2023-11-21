<?php
/**
 * Media helpers for WordPress
 */

if (!function_exists('media_img')) {
    /**
     * Returns a responsive media `<img>`.
     *
     * `add_filter('media_img', ...)` to filter.
     *
     * Examples:
     * 	`media_img('cat.jpg')`
     *	`media_img('/wp-content/uploads/cat.jpg', 'absolute w-full h-full object-cover', 'medium')`
    * 	`media_img(734, ['alt'=>'My cute cat'])`
    *
    * @param $id_or_url      attachment ID or media URL, absolute or relative to uploads dir
    * @param $class_or_attrs optional attributes
    * @param $size           image size
    * @see   media_url(),
    *        https://developer.wordpress.org/reference/functions/wp_get_attachment_image/
    */
    function media_img(string|int $id_or_url, string|array $class_or_attrs = '', string $size = 'full'): string|null
    {
        if (is_string($class_or_attrs)) {
            $attrs = ['class' => $class_or_attrs];
        } elseif (is_array($class_or_attrs) and !empty($class_or_attrs)) {
            $attrs = [];
            foreach ($class_or_attrs as $key => $value) {
                if (is_int($key)) {
                    $key = $value;
                    $value = '';
                }
                $attrs[$key] = $value;
            }
        } else {
            throw new InvalidArgumentException('Expected string or non-empty array $class_or_attrs');
        }

        if (is_numeric($id_or_url)) {
            return apply_filters(__FUNCTION__, wp_get_attachment_image($id_or_url, $size, false, $attrs));
        }

        $abs_url = media_url($id_or_url, false);
        if (!$id = attachment_url_to_postid($abs_url)) {
            return null;
        }

        return apply_filters(__FUNCTION__, wp_get_attachment_image($id, $size, false, $attrs));
    }
}

if (!function_exists('thumbnail')) {
    /**
     * Returns a responsive `<img>` for post thumbnail.
     * Can be used with zero, 1, 2 or 3 arguments.
     *
     * # Examples for current post
     * - Default `full` size:
     *   `thumbnail()`
     * - Specified size:
     *   `thumbnail('my-size')
     * - Specified size and class:
     *   `thumbnail('full', 'w-full h-auto')
     * - Specified size and attributes:
     *   `thumbnail('my-size', ['data-scroll', 'class' => 'w-full h-auto'])
     *
     * # Examples for specific post or thumbnail.
     * Size and attribute arguments shift one position to the right.
     * - `thumbnail(get_post(123))`
     * - `thumbnail(get_post(123), 'my-size')`
     * - `thumbnail(get_post(123), 'my-size', 'w-full h-auto')`
     * - `thumbnail($thumbnail_id, 'my-size')
     * - `thumbnail($thumbnail_id, 'my-size', ['data-scroll', 'class' => 'w-full h-auto')
     *
     * @see media_img()
     */
    function thumbnail(WP_Post|int|string $post_or_thumb_id_or_size = 'full', string|array $size_or_class_or_attrs = '', string|array $class_or_attrs = ''): string|null
    {
        if (is_string($post_or_thumb_id_or_size)) {
            return media_img(get_post_thumbnail_id(), $size_or_class_or_attrs, $post_or_thumb_id_or_size);
        }

        $id = is_int($post_or_thumb_id_or_size)
            ? $post_or_thumb_id_or_size
            : get_post_thumbnail_id($post_or_thumb_id_or_size);

        if (!is_string($size_or_class_or_attrs)) {
            throw new InvalidArgumentException('Expected string thumbnail size ($size_or_class_or_attrs)');
        }

        return media_img($id, $class_or_attrs, $size_or_class_or_attrs);
    }
}

if (!function_exists('media_url')) {
    /**
     * Returns absolute media URL.
     *
     * `add_filter('media_url', ...)` to filter.
     *
     * Examples:
     * 	`media_url('cat.png')`
     * 	`media_url('custom-logo')'
     * 	`media_url(734)`
     *
     * @param $id_or_url attachment ID or media URL, absolute or relative to uploads dir
     * @see media_img
     */
    function media_url(string|int $id_or_url, bool $apply_filter = true): string|null
    {
        static $site_url, $rel_base;

        if ($id_or_url == 'custom-logo') {
            return ($custom_logo_id = get_theme_mod('custom_logo'))
                ? wp_get_attachment_image_src($custom_logo_id, 'full')[0]
                : null;
        }

        isset($site_url) or $site_url = site_url();

        if (strpos($id_or_url, $site_url) === 0) {
            return $id_or_url;
        }

        isset($rel_base) or $rel_base = wp_make_link_relative(wp_get_upload_dir()['baseurl']);

        if (strpos($id_or_url, $rel_base) !== 0) {
            $id_or_url = trailingslashit($rel_base) . $id_or_url;
        }

        $url = $site_url . $id_or_url;
        return $apply_filter ? apply_filters(__FUNCTION__, $url) : $url;
    }
}

if (!function_exists('media_path')) {
    /**
     * Returns absolute media path
     *
     * @param string
     */
    function media_path(string $rel_path): string
    {
        static $basedir;

        if (file_exists($rel_path)) {
            return $rel_path;
        }

        isset($basedir) or $basedir = wp_get_upload_dir()['basedir'];
        return $basedir . DIRECTORY_SEPARATOR . $rel_path;
    }
}

if (!function_exists('svg_media')) {
    /**
     * Returns contents for .svg media
     *
     * @param media_path() applied. `.svg` extension optional.
     * @param
     * @param `id` element of array $class_or_attrs
     */
    function svg_media(string $rel_path, string|array $class_or_attrs = '', string $id = ''): string
    {
        if (strtolower(substr($rel_path, -4)) !== '.svg') {
            $rel_path .= '.svg';
        }

        if (!$contents = file_get_contents(media_path($rel_path))) {
            return '';
        }

        if (!$class_or_attrs) {
            return $contents;
        }

        if (is_string($class_or_attrs)) {
            $attrs = ['class' => $class_or_attrs];
        } elseif (is_array($class_or_attrs) and !empty($class_or_attrs)) {
            $attrs = [];
            foreach ($class_or_attrs as $key => $value) {
                if (is_int($key)) {
                    $key = $value;
                    $value = '';
                }
                $attrs[$key] = $value;
            }
        } else {
            throw new InvalidArgumentException('Expected string or none-empty array $class_or_attrs');
        }

        !$id or $attrs['id'] = $id;

        $find = '<svg ';
        $replace = '<svg ';
        foreach ($attrs as $name => $value) {
            $replace .= sprintf('%s="%s" ', $name, $value);
        }
        return str_ireplace($find, $replace, $contents);
    }
}
