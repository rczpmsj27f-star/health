<?php
/**
 * Dropdown Helper Functions
 * Centralized functions for fetching dropdown options from database
 */

/**
 * Get active dropdown options for a category
 * 
 * @param PDO $pdo Database connection
 * @param string $category_key The category key (e.g., 'special_instructions')
 * @param bool $include_inactive Include inactive options (default: false)
 * @return array Array of options with id, value, icon_emoji
 */
function getDropdownOptions($pdo, $category_key, $include_inactive = false) {
    $sql = "
        SELECT 
            o.id,
            o.option_value,
            o.icon_emoji,
            o.display_order,
            o.is_active
        FROM dropdown_options o
        INNER JOIN dropdown_categories c ON o.category_id = c.id
        WHERE c.category_key = ?
    ";
    
    if (!$include_inactive) {
        $sql .= " AND o.is_active = 1";
    }
    
    $sql .= " ORDER BY o.display_order ASC, o.option_value ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$category_key]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Render a dropdown select element
 * 
 * @param PDO $pdo Database connection
 * @param string $category_key The category key
 * @param string $name The name attribute for the select element
 * @param string $selected_value Currently selected value
 * @param array $attributes Additional HTML attributes
 * @return string HTML for select element
 */
function renderDropdown($pdo, $category_key, $name, $selected_value = '', $attributes = []) {
    $options = getDropdownOptions($pdo, $category_key);
    
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        $attr_string .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }
    
    $html = '<select name="' . htmlspecialchars($name) . '"' . $attr_string . '>';
    
    // Add empty option if not required
    if (!isset($attributes['required'])) {
        $html .= '<option value="">Select...</option>';
    }
    
    foreach ($options as $option) {
        $selected = ($option['option_value'] == $selected_value) ? ' selected' : '';
        $icon = $option['icon_emoji'] ? $option['icon_emoji'] . ' ' : '';
        $html .= '<option value="' . htmlspecialchars($option['option_value']) . '"' . $selected . '>';
        $html .= $icon . htmlspecialchars($option['option_value']);
        $html .= '</option>';
    }
    
    $html .= '</select>';
    return $html;
}

/**
 * Render checkboxes for multi-select dropdown
 * 
 * @param PDO $pdo Database connection
 * @param string $category_key The category key
 * @param string $name The name attribute (will have [] appended)
 * @param array $selected_values Array of selected values
 * @return string HTML for checkbox group
 */
function renderCheckboxGroup($pdo, $category_key, $name, $selected_values = []) {
    $options = getDropdownOptions($pdo, $category_key);
    
    $html = '<div class="checkbox-group">';
    
    foreach ($options as $option) {
        $checked = in_array($option['option_value'], $selected_values) ? ' checked' : '';
        $icon = $option['icon_emoji'] ? $option['icon_emoji'] . ' ' : '';
        
        $html .= '<label>';
        $html .= '<input type="checkbox" name="' . htmlspecialchars($name) . '[]" ';
        $html .= 'value="' . htmlspecialchars($option['option_value']) . '"' . $checked . '>';
        $html .= $icon . htmlspecialchars($option['option_value']);
        $html .= '</label>';
    }
    
    $html .= '</div>';
    return $html;
}
