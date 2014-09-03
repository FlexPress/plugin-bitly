<?php

namespace FlexPress\Plugins\Bitly\Hooks;

use FlexPress\Components\Hooks\HookableTrait;

class Bitly
{

    use HookableTrait;

    // ==========
    // ! FILTERS
    // ==========

    /**
     * Generate shortlink using bitly.
     *
     * @param $url
     * @param $id
     * @param $context
     *
     * @return string
     * @author Adam Bulmer
     * @type filter
     * @params 3
     */

    public function pre_get_shortlink($url, $id, $context)
    {

        if ($this->is_bitly_enabled()) {

            return $this->_generate_bitly_shortlink(get_permalink($id), $id, $context);

        }

    }

    /**
     * generate bitly link function
     *
     * @param $url
     * @param $id
     * @param $context
     *
     * @return string
     * @author Adam Bulmer
     */

    private function _generate_bitly_shortlink($url, $id, $context)
    {

        if (FCMSUtils::acf_available()) {

            $options = get_field('fpt_bitly_show_on', 'options');

            if ($options
                && is_array($options)
                && in_array($context, $options)
            ) {

                $shortlink = $this->get_bitly_shortlink();

                if (empty($shortlink)) {

                    $shortlink = $this->shorten_link($url);
                    $this->update_bitly_shortlink($shortlink);

                }

                return $shortlink;

            }

        }

    }

    // ==========
    // ! ACTIONS
    // ==========

    /**
     *
     * @type action
     *
     * @author Tim Perry
     *
     */
    public function init()
    {

        $this->_setup_cpt_acf();

    }

    private function _setup_cpt_acf()
    {

        if (function_exists('register_field')) {
            register_field('PostTypeSelector', FORECMS_DIR . '/libs/cpt_field.php');
        }

    }

    /**
     *
     * @type action
     * @hook_name acf/save_post
     *
     * @param $post_id
     *
     * @author Tim Perry
     *
     */
    public function acf_save_post($post_id)
    {

        if ($post_id == 'options' && isset($_POST['fields']['field_50b606309cc5d']) && $_POST['fields']['field_50b606309cc5d']) {

            $_POST['fields']['field_50b606309cc5d'] = 0;
            $this->generate_past_post_links();

        }

    }

    /**
     * Add meta box action function.
     *
     * @return void
     * @author Adam Bulmer
     * @type action
     */

    public function add_meta_boxes()
    {

        $this->_add_bitly_meta_box();

    }

    /**
     * add bitly meta box if bitly is enabled.
     *
     * @author Adam Bulmer
     */

    private function _add_bitly_meta_box()
    {

        if ($this->is_bitly_enabled()) {

            $options = get_field('fpt_bitly_show_on', 'options');

            if ($options
                && is_array($options)
            ) {

                foreach ($options as $post_type) {

                    add_meta_box(
                        'fpt-bitly-meta-box',
                        'Bitly Short Link',
                        array($this, 'meta_box_output'),
                        $post_type,
                        'side'
                    );

                }

            }

        }

    }

    /**
     *
     * @type action
     *
     * @author Tim Perry
     *
     */
    public function wp_head()
    {

        $post_id = $GLOBALS['post']->ID;

        $this->_output_twitter_card($post_id);
        $this->_output_facebook_open_graph($post_id);

    }

    /**
     * Open Twitter card meta tag data.
     *
     * @param $post_id
     *
     * @return mixed
     * @author Adam Bulmer
     * @since 3.2
     */
    private function _output_twitter_card($post_id)
    {

        if (is_singular(array('post'))) {

            $social_links = FCMSUtils::get_social_links();

            if ($social_links['twitter']) {

                $post_content = $GLOBALS['post']->post_content;

                ?>
                <meta name="twitter:card" content="summary"/>
                <meta name="twitter:title" content="<?php echo esc_attr(get_the_title($post_id)); ?>"/>
                <meta name="twitter:description" content="<?php echo strip_tags(
                    strip_shortcodes(FCMSUtils::generate_excerpt($post_content, 140))
                ); ?>"/>
                <meta name="twitter:site" content="@<?php echo $social_links['twitter']; ?>"/>
                <meta name="twitter:creator" content="@<?php echo $social_links['twitter']; ?>"/>

                <?php if (has_post_thumbnail($post_id)) { ?>

                    <?php $thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'large'); ?>
                    <meta name="twitter:image:src" content="<?php echo $thumbnail[0]; ?>"/>

                <?php } ?>

            <?php

            }

        }

    }

    /**
     * Open Graph card meta tag data.
     *
     * @param $post_id
     *
     * @return mixed
     * @author Adam Bulmer
     * @since 3.2
     */
    private function _output_facebook_open_graph($post_id)
    {

        if (is_singular(array('post'))) {

            $post_content = $GLOBALS['post']->post_content;

            ?>

            <meta property="og:site_name" content="<?php bloginfo(); ?>"/>
            <meta property="og:type" content="article"/>
            <meta property="og:title" content="<?php echo esc_attr(get_the_title($post_id)); ?>"/>
            <meta property="og:url" content="<?php echo get_permalink($post_id); ?>"/>
            <meta property="og:description" content="<?php echo strip_shortcodes(
                strip_tags(FCMSUtils::generate_excerpt($post_content, 140))
            ); ?>"/>

            <?php if (has_post_thumbnail($post_id)) { ?>

                <?php $thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id($post_id)); ?>

                <meta property="og:image" content="<?php echo $thumbnail[0]; ?>"/>

            <?php } ?>

        <?php

        }

    }

    // ==================
    // ! METHODS
    // ==================

    /**
     * call bitly using oauth to get a shortened link
     *
     * @param $url
     *
     * @return string
     * @author Adam Bulmer
     */

    public function shorten_link($url)
    {

        $token = $this->get_bitly_client_access_token();

        $connectURL = "https://api-ssl.bitly.com/v3/shorten?access_token={$token}&longUrl={$url}&format=txt";

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $connectURL);

        $data = curl_exec($curl);
        curl_close($curl);

        return $data;

    }

    /**
     * call bitly using oauth to get the stats for a link
     *
     * @param $link
     *
     * @return string
     * @author Adam Bulmer
     */

    public function get_link_clicks($link)
    {

        $token = $this->get_bitly_client_access_token();

        $connectURL = "https://api-ssl.bitly.com/v3/link/clicks?access_token={$token}&link={$link}";

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $connectURL);

        $data = curl_exec($curl);
        curl_close($curl);

        if ($data) {

            $data = json_decode($data);
            if ($data->data) {
                return $data->data->link_clicks;
            }

        }

    }

    /**
     * is bitly enabled.
     *
     * @return bool
     * @author Adam Bulmer
     */

    public function is_bitly_enabled()
    {

        if (FCMSUtils::acf_available()) {
            return get_field('fpt_bitly_enable', 'options');
        }

    }

    /**
     * bitly metabox output.
     *
     * @return string
     * @author Adam Bulmer
     */

    public function meta_box_output()
    {

        $link = $this->get_bitly_shortlink();
        $total_clicks = $this->get_link_clicks($link);

        echo '<p><strong class="short">Bit.ly Link: </strong>';
        echo '<input type="text" value="' . $link . '"></p>';
        echo '<p><strong>Total Clicks: ' . $total_clicks . '</strong></p>';

    }

    /**
     * Get the bitly access token from the options table.
     *
     * @return string
     * @author Adam Bulmer
     */

    private function get_bitly_client_access_token()
    {

        if (FCMSUtils::acf_available()) {

            return get_field('fpt_bitly_access_token', 'options');

        }

    }

    /**
     * update the posts bitly shortlink.
     *
     * @param $shortlink
     * @param null $post_id
     *
     * @return string
     * @author Adam Bulmer
     */

    public function update_bitly_shortlink($shortlink, $post_id = null)
    {

        if (!$post_id) {
            $post_id = $GLOBALS['post']->ID;
        }

        return update_post_meta($post_id, 'fpt_bitlylink', $shortlink);

    }

    /**
     * return the bitly shortlink
     *
     * @param null $post_id
     *
     * @return string
     * @author Adam Bulmer
     */

    public function get_bitly_shortlink($post_id = null)
    {

        if (!$post_id) {
            $post_id = $GLOBALS['post']->ID;
        }

        return get_post_meta($post_id, 'fpt_bitlylink', true);

    }

    /**
     * echo the bitly shortlink
     *
     * @author Adam Bulmer
     */

    public function the_bitly_shortlink($post_id = null)
    {

        if (!$post_id) {
            $post_id = $GLOBALS['post']->ID;
        }

        $shortlink = get_post_meta($post_id, 'fpt_bitlylink', true);
        echo '<a href="' . $shortlink . '">THE SHORTLINK</a>';

    }

    /**
     * Used to generate links for old posts
     *
     * @author Adam Bulmer
     */
    public function generate_past_post_links()
    {

        // get all the posts, as wordpress does not currently support the use is null on the meta query
        $options = get_field('fpt_bitly_show_on', 'options');

        $args = array(

            'post_type' => $options,
            'meta_key' => 'fpt_bitlylink',
            'order_by' => 'meta_value',
            'order' => 'DESC',
            'numberposts' => -1,
            'meta_key' => '',

        );

        $past_posts = get_posts($args);

        foreach ($past_posts as $p) {

            if (!get_post_meta($p->ID, 'fpt_bitlylink', true)) {

                $url = get_permalink($p->ID);
                $shortlink = $this->shorten_link($url);
                $this->update_bitly_shortlink($shortlink, $p->ID);
                continue;

            }

        }

    }

}