<?php
/**
 * 
 * Admin demo - main page
 * 
 * _ad_ - admin demo
 * _mp_ - main page
 * _os_ - other settings
 * 
 * @since 3.30
 * 
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'HT_CTC_Admin_Demo' ) ) :

class HT_CTC_Admin_Demo {

    // _get
    public $get_page = '';
    public $load_demo = 'no';

    public function __construct() {
        $this->hooks();
    }

    // is_live_preview. callback function for filter hook: ht_ctc_fh_is_live_preview
    public function is_live_preview() {

        if ( isset($this->load_demo) && 'yes' == $this->load_demo ) {
            return 'yes';
        }

        return 'no';
    }


    public function hooks() {

        if ( isset($_GET) && isset($_GET['page']) ) {
            $this->get_page = $_GET['page'];
        } else {
            return;
        }



        /**
         * admin demo: active, activating, deactivating 
         * 
         * check if admin demo is active
         * retun if not active
         * 
         * As the new feature, admin demo can active from user side.
         * 
         * active from user side?
         *  -> if _GET have &demo=active 
         *      then activate demo only for that page load and adds input filed - ht_ctc_chat_options[admin_demo]
         *      and is user saved settings. then later no need to add &demo=active
         * or
         *  -> ht_ctc_chat_options[admin_demo] is set AND ht_ctc_admin_demo_active is set to yes
         * 
         * Disable admin demo?
         *  if _GET have &demo=deactive
         *      it will set to ht_ctc_admin_demo_active to no
         *      and if user save change then ht_ctc_chat_options[admin_demo] will be removed.
         */
        if ( 'click-to-chat' == $this->get_page  || 'click-to-chat-other-settings' == $this->get_page || 'click-to-chat-customize-styles' == $this->get_page) {
            
            // check if admin demo is active.. (added inside to run only in ctc admin pages..)
            $demo_active = get_option( 'ht_ctc_admin_demo_active', 'no' );

            if ( 'yes' == $demo_active ) {
                $options = get_option('ht_ctc_chat_options');
                if (is_array($options)  && isset($options['admin_demo'])) {
                    $this->load_demo = 'yes';
                }
            }

            // check if demo is activating or deactivating..
            if ( isset($_GET['demo']) && 'active' == $_GET['demo'] ) {

                /** 
                 * set to load admin demo for this page. as 'active' == $_GET['demo']
                 * 
                 * now if user save settings, then next time load with out _GET demo. 
                 * i.e. if there is no issue and user saved settings, then ht_ctc_chat_options[admin_demo] will save to db.
                 */

                // add option to db
                update_option( 'ht_ctc_admin_demo_active', 'yes' );
                $this->load_demo = 'yes';
            } else if ( isset($_GET['demo']) && 'deactive' == $_GET['demo'] ) {
                $this->load_demo = 'no';
                // add option to db
                update_option( 'ht_ctc_admin_demo_active', 'no' );
            }

            // filter hook.
            if ( method_exists($this, 'is_live_preview') ) {
                add_filter( 'ht_ctc_fh_is_live_preview', [$this, 'is_live_preview'] );
            }
            
            
        }

        // return if load_demo is no
        if ( 'no' == $this->load_demo ) {
            return;
        }






        // below this only run if admin demo is active.. (i.e. user activated demo from user side and only in click to chat admin pages..)


        if ( 'click-to-chat' == $this->get_page || 'click-to-chat-other-settings' == $this->get_page || 'click-to-chat-customize-styles' == $this->get_page ) {
            // load styles
            add_action('admin_footer', [$this, 'load_styles']);

            // enqueue scripts
            add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);

            // other settings page
            if ( 'click-to-chat-other-settings' == $this->get_page ) {
                // load animations
                add_action('admin_footer', [$this, 'load_animations']);
            }

            // customize styles page
            if ( 'click-to-chat-customize-styles' == $this->get_page ) {
                
            }

            add_action('admin_footer', [$this, 'demo_messages']);

            // no live demo note
            add_action('admin_footer', [$this, 'no_live_demo']);
        }

    }

    public function enqueue_scripts() {

        $os = get_option('ht_ctc_othersettings');

        $js = 'admin-demo.js';
        $css = 'admin-demo.css';
        
        if ( isset($os['debug_mode']) || (isset($_GET) && isset($_GET['debug'])) ) {
            $js = 'dev/admin-demo.dev.js';
            $css = 'dev/admin-demo.dev.css';
        }

        wp_enqueue_style( 'ht-ctc-admin-demo-css', plugins_url( "new/admin/admin_demo/$css", HT_CTC_PLUGIN_FILE ), '', HT_CTC_VERSION );
        wp_enqueue_script( 'ht-ctc-admin-demo-js', plugins_url( "new/admin/admin_demo/$js", HT_CTC_PLUGIN_FILE ), ['jquery'], HT_CTC_VERSION, true );
        
        $this->admin_demo_var();
    }

    function admin_demo_var() {

        $options = get_option( 'ht_ctc_chat_options' );

        $number = isset($options['number']) ? esc_attr($options['number']) : '';

        if ( class_exists( 'HT_CTC_Formatting' ) && method_exists( 'HT_CTC_Formatting', 'wa_number' ) ) {
            $number = HT_CTC_Formatting::wa_number( $number );
        }
        
        $pre_filled = isset($options['pre_filled']) ? esc_attr($options['pre_filled']) : '';

        $url_target_d = isset($options['url_target_d']) ? esc_attr($options['url_target_d']) : '_blank';

        $url_structure_m = isset($options['url_structure_m']) ? esc_attr($options['url_structure_m']) : '';
        $url_structure_d = isset($options['url_structure_d']) ? esc_attr($options['url_structure_d']) : '';
        
        $site = HT_CTC_BLOG_NAME;

        $m1 = __( 'No Demo for click: WhatsApp Number is empty', 'click-to-chat-for-whatsapp');
        $m2 = __( 'No Demo for click: URL Target: same tab', 'click-to-chat-for-whatsapp');


        $demo_var = [
            'number' => $number,
            'pre_filled' => $pre_filled,
            'url_target_d' => $url_target_d,
            'url_structure_m' => $url_structure_m,
            'url_structure_d' => $url_structure_d,
            'site' => $site,
            'm1' => $m1,
            'm2' => $m2,
        ];

        wp_localize_script( 'ht-ctc-admin-demo-js', 'ht_ctc_admin_demo_var', $demo_var );
    }


    /**
     * load styles..
     * 
     */
    public function load_styles() {

        $options = get_option( 'ht_ctc_chat_options' );
        $othersettings = get_option( 'ht_ctc_othersettings' );

        $styles = array(
            '1', '2', '3', '3_1', '4', '5', '6', '7', '7_1', '8', '99'
        );

        // ctc, ctc customize styles - load all styles. And in ctc other settings load only desktop selected style.
        if ( 'click-to-chat-other-settings' == $this->get_page ) {
            $style_desktop = ( isset( $options['style_desktop']) ) ? esc_attr( $options['style_desktop'] ) : '4';
            $styles = array(
                $style_desktop
            );
        }

        // in styles
        $call_to_action = (isset($options['call_to_action'])) ? __(esc_attr($options['call_to_action']) , 'click-to-chat-for-whatsapp' ) : '';
        if ( '' == $call_to_action ) {
            $call_to_action = "WhatsApp us";
        }
        
        $type = 'chat';
        $is_mobile = '';
        $side_2 = 'right';

        /**
         * .ctc_demo_load_styles parent.. 
         *      greetings.. 
         *      styles.. 
         */
        ?>
        <div class="ctc_demo_load" style="position:fixed; bottom:50px; right:50px; z-index:99999999;">
        <?php
        // // greetings (to load all greetings)
        // include_once HT_CTC_PLUGIN_DIR .'new/tools/demo/demo-greetings.php';

        $notification_count = (isset($othersettings['notification_count'])) ? esc_attr($othersettings['notification_count']) : '1';
        $cs_link = admin_url( 'admin.php?page=click-to-chat-customize-styles' );
        $os_link = admin_url( 'admin.php?page=click-to-chat-other-settings' );

        // load all styles
        foreach ($styles as $style) {
            $class = "ctc_demo_style ctc_demo_style_$style ht_ctc_animation ht_ctc_entry_animation";
            ?>
            <div class="<?= $class ?>" style="display: none; cursor: pointer;">
            <?php
            if ( 'click-to-chat-other-settings' == $this->get_page ) {
                ?>
                <span class="ctc_ad_notification" style="display:none; padding:0px; margin:0px; position:relative; float:right; z-index:9999999;">
                    <span class="ctc_ad_badge" style="position: absolute; top: -11px; right: -11px; font-size:12px; font-weight:600; height:22px; width:22px; box-sizing:border-box; border-radius:50%;border:2px solid #ffffff; background:#ff4c4c; color:#ffffff; display:flex; justify-content:center; align-items:center;"><?= $notification_count ?></span>
                </span>
                <?php
            }
            $path = plugin_dir_path( HT_CTC_PLUGIN_FILE ) . 'new/inc/styles/style-' . $style. '.php';
            include $path;
            ?>
            </div>
            <?php
        }
        ?>
        </div>

        <div class="ctc_menu_at_demo ctc_init_display_none" style="position:fixed; bottom:4px; right:4px; z-index:99999999;">
            <p class="description ctc_ad_links"><a target="_blank" href="<?= $cs_link ?>" class="ctc_cs_link">Customize Styles</a> | <a target="_blank" href="<?= $os_link ?>">Animations, Notification badge</a></p>
        </div>
        <?php

    }

    public function load_animations() {

        include_once HT_CTC_PLUGIN_DIR .'new/inc/commons/class-ht-ctc-animations.php';
        $animations = new HT_CTC_Animations();


        /**
         * 'From Corner' - handle from js..
         * 'From Center' - center
         */
        $entry_an_list = [
            'center',
            // 'bounceIn',
            // 'bounceInDown',
            // 'bounceInUP',
            // 'bounceInLeft',
            // 'bounceInRight',
        ];

        $an_duration = '1s';
        $an_delay = "0s";
        $an_itr = '1';

        foreach ($entry_an_list as $entry) {
            $animations->entry( $entry, $an_duration, $an_delay, $an_itr );
        }



        $an_list = [
            'bounce',
            'flash',
            'pulse',
            'heartBeat',
            'flip',
        ];

        $an_duration = '1s';
        $an_delay = '';
        $an_delay = '';
        $an_itr = '1';

        foreach ($an_list as $an_type) {
            $animations->animations( $an_type, $an_duration, $an_delay, $an_itr );
        }



    }

    /**
     * no live demo notice
     * 
     * todo: at docs add content.. to reset the settings, we can check delete settings and deactivate, uninstall and install, activate again..
     */
    public function no_live_demo() {
        ?>
        <a href="https://holithemes.com/plugins/click-to-chat/admin-live-preview-messages/#no-live-preview/" target="_blank" class="description ctc_no_demo_notice" style="display:none; position:fixed; bottom:5px; right:5px;z-index:9;">No live demo for this feature</a>
        <?php
    }

    // demo_messages
    // todo.. finish how the demo messages will work..
    public function demo_messages() {
        ?>
        <a href="https://holithemes.com/plugins/click-to-chat/admin-live-preview-messages/" target="_blank" class="description ctc_demo_messages" style="display:none; position:fixed; bottom:5px; right:5px;z-index:9;"></a>
        <?php
    }
    


}

new HT_CTC_Admin_Demo();

endif; // END class_exists check