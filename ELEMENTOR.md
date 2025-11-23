# Elementor Integration Documentation

## Overview

This plugin integrates with both Elementor's Classic Editor (V3) and the new Editor V4 (Alpha) to provide capability-based visibility conditions for all elements.

---

## V3 (Classic) Editor Architecture

### Control Registration

V3 uses traditional PHP-based control registration via the `Controls_Manager` class:

```php
// Hook into element sections
add_action('elementor/element/after_section_end', 'add_controls', 10, 3);

// Add controls to an element
$element->start_controls_section(
    'my_section',
    [
        'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
        'label' => __('My Section', 'textdomain'),
    ]
);

$element->add_control(
    'my_control',
    [
        'label'   => __('My Control', 'textdomain'),
        'type'    => \Elementor\Controls_Manager::SWITCHER,
        'default' => '',
    ]
);

$element->end_controls_section();
```

### Available Hooks

| Hook | Description |
|------|-------------|
| `elementor/element/after_section_end` | Fires after every section ends (generic) |
| `elementor/element/{widget_id}/{section_id}/before_section_end` | Target specific widget/section |
| `elementor/element/common/_section_style/after_section_end` | All widgets (style section) |
| `elementor/element/section/section_advanced/after_section_end` | Sections |
| `elementor/element/container/section_layout/after_section_end` | Containers |
| `elementor/element/column/section_advanced/after_section_end` | Columns |

### Control Types (V3)

- `SWITCHER` - Toggle/switch
- `SELECT` - Dropdown
- `SELECT2` - Multi-select dropdown
- `TEXT` - Text input
- `TEXTAREA` - Multi-line text
- `RAW_HTML` - Custom HTML content

---

## V4 (Atomic) Editor Architecture

### Key Differences from V3

| Aspect | V3 | V4 |
|--------|----|----|
| **Control Registration** | `add_control()` method | `define_atomic_controls()` returns array |
| **Control Base Class** | `Control_Base` | `Atomic_Control_Base` |
| **Data Model** | Implicit via control defaults | Explicit via `define_props_schema()` |
| **Validation** | Runtime only | Compile-time + runtime |
| **Panel Structure** | Tab-based (Content/Style/Advanced) | Section-based (flat, unified Style Tab) |
| **Configuration Format** | PHP arrays | JSON-serializable objects |
| **Type Safety** | Loose | Strict (Prop_Type validation) |
| **API Style** | Array configuration | Fluent builder pattern |

### V4 Concepts

#### 1. Props Schema (Data Model)

Every control must be bound to a property in the props schema:

```php
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;

protected static function define_props_schema(): array {
    return [
        'my_enabled' => Boolean_Prop_Type::make()->default(false),
        'my_option' => String_Prop_Type::make()
            ->enum(['option1', 'option2'])
            ->default('option1'),
    ];
}
```

#### 2. Atomic Controls (UI Definition)

Controls are bound to props via `bind_to()`:

```php
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;

protected function define_atomic_controls(): array {
    return [
        Section::make()
            ->set_label(__('My Section', 'textdomain'))
            ->set_id('my_section')
            ->set_items([
                Switch_Control::bind_to('my_enabled')
                    ->set_label(__('Enable', 'textdomain')),
                Select_Control::bind_to('my_option')
                    ->set_label(__('Option', 'textdomain'))
                    ->set_options([
                        ['value' => 'option1', 'label' => 'Option 1'],
                        ['value' => 'option2', 'label' => 'Option 2'],
                    ]),
            ]),
    ];
}
```

### V4 Hooks for Third-Party Integration

#### `elementor/atomic-widgets/props-schema`

Add custom props to atomic widgets:

```php
add_filter('elementor/atomic-widgets/props-schema', function($schema, $element) {
    $schema['my_prop'] = String_Prop_Type::make()->default('');
    return $schema;
}, 10, 2);
```

#### `elementor/atomic-widgets/controls`

Add custom controls to atomic widgets:

```php
add_filter('elementor/atomic-widgets/controls', function($controls, $element) {
    $my_section = Section::make()
        ->set_label(__('My Section', 'textdomain'))
        ->set_id('my_section')
        ->set_items([
            Switch_Control::bind_to('my_prop')
                ->set_label(__('My Control', 'textdomain')),
        ]);

    $controls[] = $my_section;
    return $controls;
}, 10, 2);
```

#### Other V4 Hooks

| Hook | Description |
|------|-------------|
| `elementor/atomic-widgets/styles/schema` | Modify global style schema |
| `elementor/editor/v2/packages` | Register custom JS packages |
| `elementor/editor/localize_settings` | Add custom data to editor JS |
| `elementor/atomic-widgets/settings/transformers/register` | Register data transformers |

### Available V4 Control Types

Located in: `modules/atomic-widgets/controls/types/`

- `Switch_Control` - Toggle boolean
- `Select_Control` - Dropdown with options
- `Text_Control` - Single-line text
- `Textarea_Control` - Multi-line text
- `Number_Control` - Numeric input
- `Link_Control` - URL picker
- `Image_Control` - Image selector
- `Size_Control` - Dimension with units
- `Html_Tag_Control` - HTML tag selector
- `Toggle_Control` - Alternative toggle
- `Tabs_Control` - Tabbed content
- `Repeatable_Control` - Repeating fields

### Available V4 Prop Types

Located in: `modules/atomic-widgets/prop-types/primitives/`

- `String_Prop_Type` - String values (supports `.enum()`)
- `Boolean_Prop_Type` - Boolean values
- `Number_Prop_Type` - Numeric values

---

## Frontend Rendering Filters

These work for both V3 and V4:

```php
// Filter widget rendering
add_filter('elementor/frontend/widget/should_render', function($should_render, $widget) {
    $settings = $widget->get_settings_for_display();
    // Check conditions and return true/false
    return $should_render;
}, 10, 2);

// Filter section rendering
add_filter('elementor/frontend/section/should_render', 'callback', 10, 2);

// Filter container rendering
add_filter('elementor/frontend/container/should_render', 'callback', 10, 2);

// Filter column rendering
add_filter('elementor/frontend/column/should_render', 'callback', 10, 2);
```

---

## File Locations (Elementor Core)

### V4 Atomic Widgets Module

```
modules/atomic-widgets/
├── base/
│   ├── atomic-control-base.php      # Base class for controls
│   └── element-control-base.php     # Element control base
├── controls/
│   ├── section.php                  # Section container
│   └── types/                       # Control type classes
│       ├── switch-control.php
│       ├── select-control.php
│       ├── text-control.php
│       └── ...
├── elements/
│   ├── atomic-element-base.php      # Base for atomic elements
│   ├── atomic-widget-base.php       # Base for atomic widgets
│   ├── atomic-heading/              # Example: Heading widget
│   └── atomic-button/               # Example: Button widget
├── prop-types/
│   └── primitives/
│       ├── string-prop-type.php
│       ├── boolean-prop-type.php
│       └── number-prop-type.php
└── module.php                       # Main V4 module registration
```

### V3 Classic Controls

```
includes/
├── controls/
│   ├── base.php                     # Control base class
│   └── groups/                      # Control groups
└── widgets/
    └── base.php                     # Widget base class
```

---

## Implementation in This Plugin

### How We Support Both V3 and V4

```php
public static function register_hooks(): void {
    // V3 (Classic) - for legacy widgets
    add_action('elementor/element/after_section_end', [self::class, 'maybe_add_controls'], 10, 3);

    // V4 (Atomic) - for atomic widgets
    if (class_exists('\Elementor\Modules\AtomicWidgets\Module')) {
        add_filter('elementor/atomic-widgets/props-schema', [self::class, 'add_v4_props_schema'], 10, 2);
        add_filter('elementor/atomic-widgets/controls', [self::class, 'add_v4_controls'], 10, 2);
    }

    // Frontend filters work for both
    add_filter('elementor/frontend/widget/should_render', [self::class, 'should_render_widget'], 10, 2);
}
```

### Props We Add (V4)

- `wpe_rm_conditions_enabled` (Boolean) - Enable/disable conditions
- `wpe_rm_condition_type` (String, enum) - 'roles' or 'capabilities'
- `wpe_rm_condition_mode` (String, enum) - 'has' or 'has_not'
- `wpe_rm_condition_roles` (String) - Selected roles
- `wpe_rm_condition_capabilities` (String) - Selected capabilities

---

## Notes

### V4 Is Still in Alpha

- Editor V4 was introduced in Elementor 3.29 (May 2025)
- Full release expected Q3 2025
- API may change before stable release
- Developer documentation is still being developed

### Enabling/Disabling V4

- Go to: Elementor → Settings → Editor V4 tab
- Click "Activate" or "Deactivate"
- V4 should only be used on test/staging sites during Alpha

### Resources

- [Elementor Developer Docs](https://developers.elementor.com/)
- [Injecting Controls](https://developers.elementor.com/docs/hooks/injecting-controls/)
- [Editor V4 Announcement](https://elementor.com/blog/editor-v4-1st-alpha/)
- [GitHub Discussions - Editor V4](https://github.com/orgs/elementor/discussions/categories/editor-v4)
