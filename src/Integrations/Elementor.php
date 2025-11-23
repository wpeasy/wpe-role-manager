<?php
/**
 * Elementor Integration
 *
 * Adds capability-based visibility conditions to all Elementor widgets, sections, and containers.
 *
 * @package WP_Easy\RoleManager
 */

namespace WP_Easy\RoleManager\Integrations;

defined('ABSPATH') || exit;

/**
 * Elementor integration class.
 *
 * @since 0.1.7-beta
 */
final class Elementor {
    /**
     * Track which elements have had controls added to prevent duplicates.
     *
     * @var array
     */
    private static array $controls_added = [];

    /**
     * Initialize the integration.
     *
     * @return void
     */
    public static function init(): void {
        // Check if feature is enabled in settings
        $settings = get_option('wpe_rm_settings', []);
        $enabled = $settings['enable_elementor_conditions'] ?? true;

        if (!$enabled) {
            return;
        }

        // Wait for Elementor to initialize
        add_action('elementor/init', [self::class, 'register_hooks']);
    }

    /**
     * Register Elementor hooks after Elementor is initialized.
     *
     * @return void
     */
    public static function register_hooks(): void {
        // Use the generic hook that fires for ALL sections on ALL elements
        // This is more compatible with Editor V4
        add_action('elementor/element/after_section_end', [self::class, 'maybe_add_controls'], 10, 3);

        // Filter widget rendering on frontend
        add_filter('elementor/frontend/widget/should_render', [self::class, 'should_render_widget'], 10, 2);
        add_filter('elementor/frontend/section/should_render', [self::class, 'should_render_element'], 10, 2);
        add_filter('elementor/frontend/container/should_render', [self::class, 'should_render_element'], 10, 2);
        add_filter('elementor/frontend/column/should_render', [self::class, 'should_render_element'], 10, 2);

        // Add editor styles
        add_action('elementor/editor/after_enqueue_styles', [self::class, 'enqueue_editor_styles']);
    }

    /**
     * Maybe add controls to element (prevents duplicates).
     *
     * @param \Elementor\Element_Base $element    The element.
     * @param string                  $section_id The section ID.
     * @param array                   $args       Arguments.
     * @return void
     */
    public static function maybe_add_controls($element, $section_id, $args): void {
        // Get unique element identifier for this specific element instance
        $element_id = $element->get_unique_name();

        // Prevent duplicate controls for this element instance
        if (isset(self::$controls_added[$element_id])) {
            return;
        }

        // Only add after certain sections to ensure it appears at the end
        // We track all sections and add controls after the last known "style" related section
        $style_sections = [
            '_section_style',           // Widgets (classic)
            'section_advanced',         // Sections, columns
            'section_layout',           // Containers
            'section_effects',          // V4 editor - Effects section
            '_section_responsive',      // Responsive section
            'section_custom_css',       // Custom CSS section
            '_section_transform',       // Transform section
            'section_transform',        // Transform section (alt)
            'section_motion_effects',   // Motion effects
            '_section_background',      // Background section
            'section_background',       // Background section (alt)
        ];

        // Check if this is a style/advanced section
        if (!in_array($section_id, $style_sections, true)) {
            return;
        }

        self::$controls_added[$element_id] = true;

        // Add our controls section
        self::add_controls($element, $args);
    }

    /**
     * Add capability condition controls to elements.
     *
     * @param \Elementor\Element_Base $element The element.
     * @param array                   $args    Arguments.
     * @return void
     */
    public static function add_controls($element, $args): void {
        $element->start_controls_section(
            'wpe_rm_capability_conditions',
            [
                'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
                'label' => __('Capability Conditions', WPE_RM_TEXTDOMAIN),
            ]
        );

        // Enable toggle
        $element->add_control(
            'wpe_rm_conditions_enabled',
            [
                'label'        => __('Enable Conditions', WPE_RM_TEXTDOMAIN),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', WPE_RM_TEXTDOMAIN),
                'label_off'    => __('No', WPE_RM_TEXTDOMAIN),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => __('Show or hide this element based on user roles or capabilities.', WPE_RM_TEXTDOMAIN),
            ]
        );

        // Condition type
        $element->add_control(
            'wpe_rm_condition_type',
            [
                'label'     => __('Condition Type', WPE_RM_TEXTDOMAIN),
                'type'      => \Elementor\Controls_Manager::SELECT,
                'default'   => 'roles',
                'options'   => [
                    'roles'        => __('Roles', WPE_RM_TEXTDOMAIN),
                    'capabilities' => __('Capabilities', WPE_RM_TEXTDOMAIN),
                ],
                'condition' => [
                    'wpe_rm_conditions_enabled' => 'yes',
                ],
            ]
        );

        // Condition mode
        $element->add_control(
            'wpe_rm_condition_mode',
            [
                'label'     => __('Condition Mode', WPE_RM_TEXTDOMAIN),
                'type'      => \Elementor\Controls_Manager::SELECT,
                'default'   => 'has',
                'options'   => [
                    'has'     => __('User HAS (show if match)', WPE_RM_TEXTDOMAIN),
                    'has_not' => __('User HAS NOT (hide if match)', WPE_RM_TEXTDOMAIN),
                ],
                'condition' => [
                    'wpe_rm_conditions_enabled' => 'yes',
                ],
            ]
        );

        // Roles select
        $element->add_control(
            'wpe_rm_condition_roles',
            [
                'label'       => __('Select Roles', WPE_RM_TEXTDOMAIN),
                'type'        => \Elementor\Controls_Manager::SELECT2,
                'multiple'    => true,
                'options'     => self::get_roles_options(),
                'default'     => [],
                'description' => __('Select one or more roles. User must have at least one.', WPE_RM_TEXTDOMAIN),
                'condition'   => [
                    'wpe_rm_conditions_enabled' => 'yes',
                    'wpe_rm_condition_type'     => 'roles',
                ],
            ]
        );

        // Capabilities select
        $element->add_control(
            'wpe_rm_condition_capabilities',
            [
                'label'       => __('Select Capabilities', WPE_RM_TEXTDOMAIN),
                'type'        => \Elementor\Controls_Manager::SELECT2,
                'multiple'    => true,
                'options'     => self::get_capabilities_options(),
                'default'     => [],
                'description' => __('Select one or more capabilities. User must have at least one.', WPE_RM_TEXTDOMAIN),
                'condition'   => [
                    'wpe_rm_conditions_enabled' => 'yes',
                    'wpe_rm_condition_type'     => 'capabilities',
                ],
            ]
        );

        // Info notice
        $element->add_control(
            'wpe_rm_condition_info',
            [
                'type'            => \Elementor\Controls_Manager::RAW_HTML,
                'raw'             => '<div style="font-size: 11px; color: #6d7882; margin-top: 10px;">' .
                                    __('Powered by WP Easy Role Manager', WPE_RM_TEXTDOMAIN) .
                                    '</div>',
                'content_classes' => 'elementor-panel-alert',
                'condition'       => [
                    'wpe_rm_conditions_enabled' => 'yes',
                ],
            ]
        );

        $element->end_controls_section();
    }

    /**
     * Check if a widget should render.
     *
     * @param bool                      $should_render Whether to render.
     * @param \Elementor\Widget_Base    $widget        The widget.
     * @return bool
     */
    public static function should_render_widget($should_render, $widget): bool {
        return self::check_conditions($should_render, $widget->get_settings_for_display());
    }

    /**
     * Check if an element (section/container/column) should render.
     *
     * @param bool                      $should_render Whether to render.
     * @param \Elementor\Element_Base   $element       The element.
     * @return bool
     */
    public static function should_render_element($should_render, $element): bool {
        return self::check_conditions($should_render, $element->get_settings_for_display());
    }

    /**
     * Check conditions and determine if element should render.
     *
     * @param bool  $should_render Current render state.
     * @param array $settings      Element settings.
     * @return bool
     */
    private static function check_conditions(bool $should_render, array $settings): bool {
        // If already set to not render, respect that
        if (!$should_render) {
            return false;
        }

        // Check if conditions are enabled
        if (empty($settings['wpe_rm_conditions_enabled']) || $settings['wpe_rm_conditions_enabled'] !== 'yes') {
            return true;
        }

        $condition_type = $settings['wpe_rm_condition_type'] ?? 'roles';
        $condition_mode = $settings['wpe_rm_condition_mode'] ?? 'has';

        // Get condition values based on type
        if ($condition_type === 'roles') {
            $condition_values = $settings['wpe_rm_condition_roles'] ?? [];
        } else {
            $condition_values = $settings['wpe_rm_condition_capabilities'] ?? [];
        }

        // If no values selected, show element
        if (empty($condition_values)) {
            return true;
        }

        // Check user against conditions
        $user = wp_get_current_user();
        $has_condition = false;

        if ($condition_type === 'roles') {
            if ($user->ID === 0) {
                // Guest user - check for 'guest' pseudo-role
                $has_condition = in_array('guest', $condition_values, true);
            } else {
                $has_condition = !empty(array_intersect($user->roles, $condition_values));
            }
        } else {
            // Capabilities check
            foreach ($condition_values as $cap) {
                if ($user->has_cap($cap)) {
                    $has_condition = true;
                    break;
                }
            }
        }

        // Determine if should show based on mode
        if ($condition_mode === 'has') {
            return $has_condition;
        } else {
            return !$has_condition;
        }
    }

    /**
     * Get roles options for select.
     *
     * @return array
     */
    private static function get_roles_options(): array {
        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new \WP_Roles();
        }

        $options = [
            'guest' => __('Guest (Not Logged In)', WPE_RM_TEXTDOMAIN),
        ];

        foreach ($wp_roles->roles as $role_slug => $role_info) {
            $options[$role_slug] = translate_user_role($role_info['name']);
        }

        return $options;
    }

    /**
     * Get capabilities options for select.
     *
     * @return array
     */
    private static function get_capabilities_options(): array {
        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new \WP_Roles();
        }

        $capabilities = [];

        foreach ($wp_roles->roles as $role) {
            if (isset($role['capabilities'])) {
                $capabilities = array_merge($capabilities, array_keys($role['capabilities']));
            }
        }

        $capabilities = array_unique($capabilities);
        sort($capabilities);

        $options = [];
        foreach ($capabilities as $cap) {
            $options[$cap] = $cap;
        }

        return $options;
    }

    /**
     * Enqueue editor styles.
     *
     * @return void
     */
    public static function enqueue_editor_styles(): void {
        wp_add_inline_style('elementor-editor', '
            .elementor-control-wpe_rm_conditions_enabled .elementor-switch-label {
                font-weight: 600;
            }
            .elementor-control-wpe_rm_condition_info {
                margin-top: -10px;
            }
        ');
    }
}
