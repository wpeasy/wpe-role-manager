/**
 * WP Easy Role Manager - Block Conditional Visibility
 *
 * Adds role/capability-based visibility conditions to all Gutenberg blocks.
 */
(function() {
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment, createElement: el } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, ToggleControl, SelectControl, RadioControl, FormTokenField } = wp.components;
    const { addFilter } = wp.hooks;
    const { __ } = wp.i18n;

    // Get roles and capabilities from localized data
    const { roles = [], capabilities = [] } = window.wpeRmBlockConditions || {};

    // Inject styles for the condition badge
    const styleId = 'wpe-rm-block-conditions-styles';
    if (!document.getElementById(styleId)) {
        const style = document.createElement('style');
        style.id = styleId;
        style.textContent = `
            .wpe-rm-condition-badge {
                position: absolute;
                top: -6px;
                right: -6px;
                background: #2271b1;
                color: #fff;
                font-size: 9px;
                font-weight: 600;
                padding: 2px 6px;
                border-radius: 3px;
                z-index: 1000;
                text-transform: uppercase;
                letter-spacing: 0.3px;
                pointer-events: none;
                line-height: 1.2;
                box-shadow: 0 1px 2px rgba(0,0,0,0.2);
            }
            .wpe-rm-has-conditions {
                position: relative !important;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Add custom attributes to all blocks
     */
    function addConditionAttributes(settings) {
        if (settings.attributes) {
            settings.attributes.wpeRmConditionsEnabled = {
                type: 'boolean',
                default: false,
            };
            settings.attributes.wpeRmConditionType = {
                type: 'string',
                default: 'roles',
            };
            settings.attributes.wpeRmConditionMode = {
                type: 'string',
                default: 'has',
            };
            settings.attributes.wpeRmConditionValues = {
                type: 'array',
                default: [],
            };
        }
        return settings;
    }

    /**
     * Add inspector controls to all blocks
     */
    const withConditionControls = createHigherOrderComponent((BlockEdit) => {
        return function(props) {
            const { attributes, setAttributes, isSelected } = props;
            const {
                wpeRmConditionsEnabled = false,
                wpeRmConditionType = 'roles',
                wpeRmConditionMode = 'has',
                wpeRmConditionValues = [],
            } = attributes;

            // Get the options based on condition type
            const options = wpeRmConditionType === 'roles' ? roles : capabilities;

            return el(
                Fragment,
                null,
                el(BlockEdit, props),
                isSelected && el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        {
                            title: __('Capability Conditions', 'wp-easy-role-manager'),
                            initialOpen: wpeRmConditionsEnabled,
                            icon: 'visibility',
                        },
                        el(ToggleControl, {
                            label: __('Enable Conditions', 'wp-easy-role-manager'),
                            checked: wpeRmConditionsEnabled,
                            onChange: (value) => setAttributes({ wpeRmConditionsEnabled: value }),
                            help: wpeRmConditionsEnabled
                                ? __('This block has visibility conditions applied.', 'wp-easy-role-manager')
                                : __('Control who can see this block on the frontend.', 'wp-easy-role-manager'),
                        }),
                        wpeRmConditionsEnabled && el(
                            Fragment,
                            null,
                            el(SelectControl, {
                                label: __('Condition Type', 'wp-easy-role-manager'),
                                value: wpeRmConditionType,
                                options: [
                                    { value: 'roles', label: __('User Roles', 'wp-easy-role-manager') },
                                    { value: 'capabilities', label: __('User Capabilities', 'wp-easy-role-manager') },
                                ],
                                onChange: (value) => setAttributes({
                                    wpeRmConditionType: value,
                                    wpeRmConditionValues: [], // Reset values when type changes
                                }),
                            }),
                            el(RadioControl, {
                                label: __('Show block when user:', 'wp-easy-role-manager'),
                                selected: wpeRmConditionMode,
                                options: [
                                    { value: 'has', label: __('Has selected', 'wp-easy-role-manager') },
                                    { value: 'has_not', label: __('Does NOT have selected', 'wp-easy-role-manager') },
                                ],
                                onChange: (value) => setAttributes({ wpeRmConditionMode: value }),
                            }),
                            el(FormTokenField, {
                                label: wpeRmConditionType === 'roles'
                                    ? __('Select Roles', 'wp-easy-role-manager')
                                    : __('Select Capabilities', 'wp-easy-role-manager'),
                                value: wpeRmConditionValues.map(val => {
                                    const found = options.find(o => o.value === val);
                                    return found ? found.label : val;
                                }),
                                suggestions: options.map(o => o.label),
                                onChange: (tokens) => {
                                    // Convert labels back to values
                                    const newValues = tokens.map(token => {
                                        const found = options.find(o => o.label === token);
                                        return found ? found.value : token;
                                    });
                                    setAttributes({ wpeRmConditionValues: newValues });
                                },
                                __experimentalExpandOnFocus: true,
                                __experimentalShowHowTo: false,
                            }),
                            wpeRmConditionValues.length > 0 && el(
                                'p',
                                {
                                    style: {
                                        marginTop: '12px',
                                        padding: '8px',
                                        background: '#f0f0f1',
                                        borderRadius: '4px',
                                        fontSize: '12px',
                                    }
                                },
                                wpeRmConditionMode === 'has'
                                    ? __('Block visible to users with: ', 'wp-easy-role-manager')
                                    : __('Block hidden from users with: ', 'wp-easy-role-manager'),
                                el('strong', null, wpeRmConditionValues.join(', '))
                            )
                        )
                    )
                )
            );
        };
    }, 'withConditionControls');

    /**
     * Add wrapper with badge to blocks with conditions in editor
     */
    const withConditionBadge = createHigherOrderComponent((BlockListBlock) => {
        return function(props) {
            const { attributes } = props;
            const { wpeRmConditionsEnabled = false } = attributes;

            if (!wpeRmConditionsEnabled) {
                return el(BlockListBlock, props);
            }

            const badgeStyle = {
                position: 'absolute',
                top: '-8px',
                right: '-8px',
                background: '#2271b1',
                color: '#fff',
                fontSize: '9px',
                fontWeight: '600',
                padding: '2px 6px',
                borderRadius: '3px',
                zIndex: 1000,
                textTransform: 'uppercase',
                letterSpacing: '0.3px',
                pointerEvents: 'none',
                lineHeight: '1.2',
                boxShadow: '0 1px 2px rgba(0,0,0,0.2)',
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
            };

            const wrapperStyle = {
                position: 'relative',
                outline: '1px dashed #6c6c6c2b',
            };

            return el(
                'div',
                { style: wrapperStyle },
                el('span', { style: badgeStyle }, __('Conditional', 'wp-easy-role-manager')),
                el(BlockListBlock, props)
            );
        };
    }, 'withConditionBadge');

    // Register the filters
    addFilter(
        'blocks.registerBlockType',
        'wpe-role-manager/condition-attributes',
        addConditionAttributes
    );

    addFilter(
        'editor.BlockEdit',
        'wpe-role-manager/condition-controls',
        withConditionControls
    );

    addFilter(
        'editor.BlockListBlock',
        'wpe-role-manager/condition-badge',
        withConditionBadge
    );

})();
