/**
 * WP Easy Role Manager - Block Conditional Visibility
 *
 * Adds role/capability-based visibility conditions to all Gutenberg blocks.
 */
(function() {
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment, createElement: el } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, ToggleControl, SelectControl, RadioControl, CheckboxControl } = wp.components;
    const { addFilter } = wp.hooks;
    const { __ } = wp.i18n;

    // Get roles and capabilities from localized data
    const { roles = [], capabilities = [] } = window.wpeRmBlockConditions || {};

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

            // Handle checkbox change for multi-select
            const handleValueChange = (value, checked) => {
                let newValues = [...wpeRmConditionValues];
                if (checked) {
                    if (!newValues.includes(value)) {
                        newValues.push(value);
                    }
                } else {
                    newValues = newValues.filter(v => v !== value);
                }
                setAttributes({ wpeRmConditionValues: newValues });
            };

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
                            title: __('Visibility Conditions', 'wp-easy-role-manager'),
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
                            el(
                                'div',
                                {
                                    className: 'wpe-rm-condition-values',
                                    style: {
                                        maxHeight: '200px',
                                        overflowY: 'auto',
                                        border: '1px solid #ddd',
                                        borderRadius: '4px',
                                        padding: '8px',
                                        marginTop: '8px',
                                    }
                                },
                                el(
                                    'p',
                                    { style: { margin: '0 0 8px', fontWeight: '600', fontSize: '11px', textTransform: 'uppercase' } },
                                    wpeRmConditionType === 'roles'
                                        ? __('Select Roles:', 'wp-easy-role-manager')
                                        : __('Select Capabilities:', 'wp-easy-role-manager')
                                ),
                                options.map((option) =>
                                    el(CheckboxControl, {
                                        key: option.value,
                                        label: option.label,
                                        checked: wpeRmConditionValues.includes(option.value),
                                        onChange: (checked) => handleValueChange(option.value, checked),
                                        __nextHasNoMarginBottom: true,
                                    })
                                )
                            ),
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

            return el(
                'div',
                { className: 'wpe-rm-has-conditions', style: { position: 'relative' } },
                el('span', { className: 'wpe-rm-condition-badge' }, __('Conditional', 'wp-easy-role-manager')),
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
