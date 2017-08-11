<?php

/**
 * The admin-specific functionality of the plugin.
 * 
 * @link http://felixwelberg.de
 * @since 1.0.0
 * @package Sis_Handball
 * @subpackage Sis_Handball/admin
 * @author Felix Welberg <felix@welberg.de>
 */
class Sis_Handball_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string $sis_handball
     */
    private $sis_handball;

    /**
     * The version of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string version
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param string $sis_handball
     * @param string $version
     */
    public function __construct($sis_handball, $version)
    {

        $this->sis_handball = $sis_handball;
        $this->version = $version;
    }

    public function get_version()
    {
        return $this->version;
    }

    /**
     * Register admin menu pages
     * 
     * @since 1.0.0
     */
    public function admin_menu()
    {
        add_menu_page(__('SIS Handball - Settings', 'sis-handball'), __('SIS Handball', 'sis-handball'), 'manage_options', 'sis-handball-options', 'Sis_Handball_Admin::page_options', 'dashicons-awards');
        add_submenu_page('sis-handball-options', __('SIS Handball - Shortcode generator', 'sis-handball'), __('Shortcode generator', 'sis-handball'), 'manage_options', 'sis-handball-shortcode-generator', 'Sis_Handball_Admin::page_shortcode_generator');
        add_submenu_page('sis-handball-options', __('SIS Handball - Snapshots', 'sis-handball'), __('Snapshots', 'sis-handball'), 'manage_options', 'sis-handball-snapshots', 'Sis_Handball_Admin::page_snapshots');
        add_submenu_page('sis-handball-options', __('SIS Handball - Concatenations', 'sis-handball'), __('Concatenations', 'sis-handball'), 'manage_options', 'sis-handball-concatenations', 'Sis_Handball_Admin::page_concatenations');

        add_screen_option('per_page', array('label' => _x('Comments', 'comments per page (screen options)')));
    }

    /**
     * Register the admin javascript
     *
     * @since 1.0.0
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script($this->sis_handball, plugin_dir_url(__FILE__) . 'js/sis-handball-admin.js', array(), $this->version, 'all');
    }

    /**
     * Empty the cache table
     * 
     * @since 1.0.0
     * @global type $wpdb
     * @return boolean
     */
    private function cache_empty()
    {
        global $wpdb;
        return $wpdb->query('TRUNCATE ' . $wpdb->prefix . 'sis_cache');
    }

    /**
     * Show options page in BE
     * 
     * @since 1.0.0
     */
    public function page_options()
    {
        global $wpdb;

        if (isset($_POST['save_options'])) {
            update_option('sis-handball-default-styles', $_POST['setting']['sis-handball-default-styles']);
            update_option('sis-handball-cache', $_POST['setting']['sis-handball-cache']);
            update_option('sis-handball-show-cache-update', $_POST['setting']['sis-handball-show-cache-update']);
            update_option('sis-handball-cache-time', $_POST['setting']['sis-handball-cache-time']);
            update_option('sis-handball-lazyload-limit', $_POST['setting']['sis-handball-lazyload-limit']);
            update_option('sis-handball-hide-errors', $_POST['setting']['sis-handball-hide-errors']);
            echo Sis_Handball_Admin::flash_message(__('Settings updated', 'sis-handball'));
        }

        if (isset($_POST['empty_cache'])) {
            if (Sis_Handball_Admin::cache_empty()) {
                echo Sis_Handball_Admin::flash_message(__('Cache cleared', 'sis-handball'));
            } else {
                echo Sis_Handball_Admin::flash_message(__('Cache could not be cleared', 'sis-handball'), 'error');
            }
        }

        if (isset($_POST['save_translations'])) {
            foreach ($_POST['translation'] AS $key => $value) {
                update_option($key, $value);
            }
            echo Sis_Handball_Admin::flash_message(__('Translations updated', 'sis-handball'));
        }

        echo Sis_Handball_Admin_Viewhelpers::page_options();
    }

    /**
     * Show shortcode generator page in BE
     * 
     * @since 1.0.16
     */
    public function page_shortcode_generator()
    {
        echo Sis_Handball_Admin_Viewhelpers::page_shortcode_generator();
    }

    /**
     * Shows snapshots page in BE
     * 
     * @since 1.0.5
     * @global type $wpdb
     */
    public function page_snapshots()
    {
        if (isset($_POST['save_snapshot'])) {
            if (Sis_Handball_Admin_Snapshot::prepare($_POST)) {
                echo Sis_Handball_Admin::flash_message(__('Snapshot created', 'sis-handball'));
            } else {
                echo Sis_Handball_Admin::flash_message(__('Snapshot could not be created', 'sis-handball'), 'error');
            }
        }

        if (isset($_POST['edit_snapshot'])) {
            $edit_snapshot = Sis_Handball_Admin_Snapshot::edit($_POST);
            if ($edit_snapshot == 0) {
                echo Sis_Handball_Admin::flash_message(__('Nothing changed', 'sis-handball'), 'info');
            } else if ($edit_snapshot >= 1) {
                echo Sis_Handball_Admin::flash_message(__('Snapshot saved', 'sis-handball'));
            } else {
                echo Sis_Handball_Admin::flash_message(__('Snapshot could not be saved', 'sis-handball'), 'error');
            }
        }

        if ($_GET['action'] == 'delete' && isset($_GET['id'])) {
            if (Sis_Handball_Admin_Snapshot::delete($_GET['id'])) {
                echo Sis_Handball_Admin::flash_message(__('Snapshot deleted', 'sis-handball'));
            } else {
                echo Sis_Handball_Admin::flash_message(__('Snapshot could not be deleted', 'sis-handball'), 'error');
            }
        }

        if ($_GET['action'] == 'new') {
            echo Sis_Handball_Admin_Viewhelpers::snapshots_new();
        } else if ($_GET['action'] == 'edit' && isset($_GET['id'])) {
            echo Sis_Handball_Admin_Viewhelpers::snapshots_edit();
        } else {
            echo Sis_Handball_Admin_Viewhelpers::snapshots_overview();
        }
    }

    /**
     * Shows concatenation page in BE
     * 
     * @since 1.0.16
     * @global type $wpdb
     */
    public function page_concatenations()
    {
        if ($_GET['action'] == 'delete') {
            if (Sis_Handball_Admin_Concatenations::delete($_GET['id'])) {
                echo Sis_Handball_Admin::flash_message(__('Concatenation deleted', 'sis-handball'));
            } else {
                echo Sis_Handball_Admin::flash_message(__('Concatenation could not be deleted', 'sis-handball'), 'error');
            }
        }

        if (isset($_POST['create'])) {
            echo Sis_Handball_Admin::flash_message(__('Successfully created new concatenation, you can now add conditions to your new concatenation.', 'sis-handball'));
            echo Sis_Handball_Admin_Viewhelpers::concatenations_create($_POST);
        } else if ($_GET['action'] == 'edit' && isset($_GET['id'])) {
            if (isset($_POST['build_concatenation'])) {
                if (Sis_Handball_Admin_Concatenations::create_condition($_POST)) {
                    echo Sis_Handball_Admin::flash_message(__('Concatenation condition created', 'sis-handball'));
                } else {
                    echo Sis_Handball_Admin::flash_message(__('Concatenation condition could not be created', 'sis-handball'), 'error');
                }
            }

            if (isset($_POST['delete_concatenation_condition'])) {
                if (Sis_Handball_Admin_Concatenations::delete_condition($_POST)) {
                    echo Sis_Handball_Admin::flash_message(__('Concatenation condition deleted', 'sis-handball'));
                } else {
                    echo Sis_Handball_Admin::flash_message(__('Concatenation condition could not be deleted', 'sis-handball'), 'error');
                }
            }

            $conditions = Sis_Handball_Admin_Concatenations::get_conditions($_GET['id']);
            $condition_returner = '
                <h2 class="title">' . __('Saved conditions in this concatenation', 'sis-handball') . '</h2>
                <table class="form-table">
                    <tbody>
            ';
            foreach ($conditions AS $condition) {
                $data = unserialize($condition->data);
                $condition_returner .= '
                    <tr class="concatenation-form">
                        <th><label for="sis_handball_shortcode_generator_league_id">' . __('League ID', 'sis-handball') . '<br />' . date('d.m.Y H:i', $condition->condition_time) . '</label></th>
                        <td><a href="http://sis-handball.de/default.aspx?view=Mannschaft&Liga=' . $data['league'] . '" target="_blank">' . $data['league'] . '</a></td>
                        <td>' . $condition->comment . '</td>
                        <td>
                            <form action="" method="post">
                                <input type="hidden" name="cid" value="' . $_GET['id'] . '" />
                                <input type="hidden" name="condition_id" value="' . $condition->id . '" />
                                <input type="submit" class="button" name="delete_concatenation_condition" value="' . __('Delete', 'sis-handball') . '" />
                            </form>
                        </td>
                    </tr>
                ';
            }
            $condition_returner .= '
                    </tbody>
                </table>
            ';

            echo '
                <div class="wrap">
                    <h1>' . __('SIS Handball', 'sis-handball') . ' › ' . __('Concatenations', 'sis-handball') . '</h1>
                    <form action="" method="post">
                        <h2 class="title">' . __('Edit concatenation', 'sis-handball') . '</h2>
                        <table class="form-table">
                            <tbody>
                                <tr class="concatenation-form">
                                    <th><label for="sis_handball_shortcode_generator_league_id">' . __('League ID', 'sis-handball') . '</label></th>
                                    <td>
                                        <fieldset>
                                            <legend class="screen-reader-text"><span>' . __('League ID', 'sis-handball') . '</span></legend>
                                            <input id="sis_handball_concatenation_league_id" name="sis_handball_concatenation_league_id" class="regular-text code" placeholder="' . __('e.g.', 'sis-handball') . ' 001517000000000000000000000000000001000" />
                                            <p class="description" id="sis_handball_concatenation_league_id_description">
                                                ' . __('The last part of your selected url: ', 'sis-handball') . '<br />
                                                ' . __('e.g.', 'sis-handball') . ' <span id="sis_handball_concatenation_league_id_description_link"></span>
                                            </p>
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr class="concatenation-form">
                                    <th><label for="sis_handball_shortcode_generator_comment">' . __('Comment', 'sis-handball') . '</label></th>
                                    <td>
                                        <fieldset>
                                            <legend class="screen-reader-text"><span>' . __('Comment', 'sis-handball') . '</span></legend>
                                            <input id="sis_handball_concatenation_comment" name="sis_handball_concatenation_comment" class="regular-text" />
                                            <p class="description" id="sis_handball_concatenation_comment_description">' . __('Optional', 'sis-handball') . '</p>
                                        </fieldset>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <input type="hidden" name="cid" value="' . $_GET['id'] . '" />
                        ' . get_submit_button(__('Update concatenation', 'sis-handball'), 'primary', 'build_concatenation') . '
                    </form>
                    ' . $condition_returner . '
                </div>
            ';
        } else if ($_GET['action'] == 'new') {
            echo Sis_Handball_Admin_Viewhelpers::concatenations_new();
        } else {
            echo Sis_Handball_Admin_Viewhelpers::concatenations_overview();
        }
    }

    /**
     * Returns ajax result for shortcode generator
     * 
     * @since 1.0.18
     * @param type $post
     * @return boolean
     */
    public function shortcode_generator_ajax($post = array())
    {
        $url = '';
        if (isset($post['type'])) {
            if (isset($post['league_id']) && $post['type'] != 'next') {
                $atts = array('league' => $post['league_id'], 'type' => $post['type']);
                $result = Sis_Handball_Public::shortcode_sis_handball($atts, '', 'sishandball', TRUE);
                if ($result) {
                    return $result;
                } else {
                    return FALSE;
                }
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    /**
     * Provides ajax team data for shortcode generator
     * 
     * @since 1.0.19
     */
    public function team_fetch()
    {
        $received_data = Sis_Handball_Admin::shortcode_generator_ajax($_POST);
        $team_array = array();
        if ($received_data != FALSE) {
            foreach ($received_data AS $team) {
                if ($_POST['type'] == 'team' || $_POST['type'] == 'games') {
                    $team_array[] = $team[3];
                } else if ($_POST['type'] == 'standings' || $_POST['type'] == 'chart' || $_POST['type'] == 'stats') {
                    $team_array[] = $team[2];
                }
            }
            header('Content-Type: application/json');
            echo json_encode(array_unique($team_array));
        }
        die;
    }

    /**
     * Extracts attributes from shortcode and returns them as array
     * 
     * @since 1.0.5
     * @param string $shortcode
     * @param string $tag
     * @return array
     */
    public function extract_shortcode_atts($shortcode = '', $tag = '')
    {
        $regex = '/([^ "]+)="([^"]*)"/';
        $shortcode = str_replace(array($tag, '[', ']', '\\'), '', $shortcode);
        preg_match_all($regex, $shortcode, $match);
        $clean_atts_array = array();
        foreach ($match[1] AS $key => $clean_key) {
            $clean_atts_array[$clean_key] = $match[2][$key];
        }
        return $clean_atts_array;
    }

    /**
     * Display backend messages
     * 
     * @since 1.0.22
     * @param type $type
     * @param type $content
     * @return string
     */
    public function flash_message($content = '', $type = 'success')
    {
        $returner = '';
        $returner .= '
            <div class="notice notice-' . $type . ' is-dismissible">
                <p>' . $content . '</p>
            </div>
        ';
        return $returner;
    }

    /**
     * Returns current cache count
     * 
     * @since 1.0.22
     * @global type $wpdb
     * @return type
     */
    public function cache_count()
    {
        global $wpdb;
        return $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'sis_cache');
    }

    /**
     * Returns last cache update
     * 
     * @since 1.0.22
     * @global type $wpdb
     * @return type
     */
    public function cache_last_update()
    {
        global $wpdb;
        return $wpdb->get_var('SELECT cache_time FROM ' . $wpdb->prefix . 'sis_cache ORDER BY id DESC LIMIT 1');
    }

    /**
     * Defines help tab on shortcode generator page
     * 
     * @since 1.0.22
     */
    public function shortcode_generator_add_help_tab()
    {
        $screen = get_current_screen();
        if ($screen->base == 'sis-handball_page_sis-handball-shortcode-generator') {
            $screen->add_help_tab(array(
                'id' => 'generator-help-10',
                'title' => __('Games of single team', 'sis-handball'),
                'content' => '
                    <p>' . __('Displays the last games of the selected team (date, time, opponent, result and points) and marks the selected team if it has won a game.', 'sis-handball') . '</p>
                    <p><strong>' . __('Example', 'sis-handball') . '</strong></p>
                    <img src="' . plugins_url('sis-handball/admin/images/help/example_1_1.png') . '" />
                ',
            ));
            $screen->add_help_tab(array(
                'id' => 'generator-help-20',
                'title' => __('All games in a league', 'sis-handball'),
                'content' => '
                    <p>' . __('Displays all last games in the selected league (date, time, opponent, result and points) and marks the selected team if it has won a game.', 'sis-handball') . '</p>
                    <p><strong>' . __('Example', 'sis-handball') . '</strong></p>
                    <img src="' . plugins_url('sis-handball/admin/images/help/example_2_1.png') . '" />
                ',
            ));
            $screen->add_help_tab(array(
                'id' => 'generator-help-30',
                'title' => __('Standings', 'sis-handball'),
                'content' => '
                    <p>' . __('Displays the current table (position, team, wins/losses, goals, points).', 'sis-handball') . '</p>
                    <p><strong>' . __('Example', 'sis-handball') . '</strong></p>
                    <img src="' . plugins_url('sis-handball/admin/images/help/example_3_1.png') . '" />
                ',
            ));
            $screen->add_help_tab(array(
                'id' => 'generator-help-40',
                'title' => __('Next games of single team', 'sis-handball'),
                'content' => '
                    <p>' . __('Displays the next games (date, time, opponent and optional link to google map) of the selected team. The table can be limited and the link to the google map can be removed.', 'sis-handball') . '</p>
                    <p><strong>' . __('Example', 'sis-handball') . '</strong></p>
                    <img src="' . plugins_url('sis-handball/admin/images/help/example_4_1.png') . '" />
                ',
            ));
            $screen->add_help_tab(array(
                'id' => 'generator-help-50',
                'title' => __('Diagram of positioning', 'sis-handball'),
                'content' => '
                    <p>' . __('Displays a diagram with a new step for each gameday. The diagram is generated via google charts (javascript).', 'sis-handball') . '</p>
                    <p><strong>' . __('Example', 'sis-handball') . '</strong></p>
                    <img src="' . plugins_url('sis-handball/admin/images/help/example_5_1.png') . '" />
                ',
            ));
            $screen->add_help_tab(array(
                'id' => 'generator-help-60',
                'title' => __('Statistic of single team', 'sis-handball'),
                'content' => '
                    <p>' . __('Displays a table with data belonging to the selected team (position, games, goals, goals against, wins/losses).', 'sis-handball') . '</p>
                    <p><strong>' . __('Example', 'sis-handball') . '</strong></p>
                    <img src="' . plugins_url('sis-handball/admin/images/help/example_6_1.png') . '" />
                ',
            ));
        }
    }
}
