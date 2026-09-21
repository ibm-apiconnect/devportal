# connect_theme

API Connect theme for Drupal 8

## Dark Mode Architecture

### Dark Mode Detection

Dark mode is controlled via a data attribute on the `<body>` element:

- **Attribute**: `data-dmt-mode="dark"`
- **Selector**: All dark mode styles use `body[data-dmt-mode="dark"]` as the base selector

### Naming Conventions

**Dark Mode Tokens**

- Prefix: `dark-`
- Example: `$dark-btn-primary-bg`

**Dark Mode Assets**

- Suffix: `_dark`
- Example: `signup_dark.svg`

---

## File Structure and Responsibilities

### `scss/carbon/vendor/@carbon/elements/scss/colors/mixins.scss`

**Purpose**: Defines the Carbon colour palette

**Modification Policy**: ⚠️ **Do not modify**

- Only update when syncing with official Carbon palette changes
- Contains base colour definitions like `$blue-60: #0f62fe;`

---

### `scss/_connect-theme.scss`

**Purpose**: Defines Carbon semantic colour tokens

**Reference**: Based on [Carbon Design System color tokens](https://carbondesignsystem.com/elements/color/tokens/)

**Modification Policy**: ⚠️ **Do not modify**

- Only update when syncing with official Carbon token changes
- Contains semantic mappings like:
  - `$button-primary: $blue-60;`
  - `$dark-button-primary: $blue-60;`

---

### `scss/_connect-variables.scss`

**Purpose**: Defines custom theme tokens for project-specific styling

**Token Sources**: Values may reference:

- Carbon palette colours (`mixins.scss`)
- Carbon semantic tokens (`_connect-theme.scss`)
- Portal @carbon/themes (`scss/carbon`)
- Bootstrap (`bootstrap/assets`)

**Best Practices**:

- ✅ Add new custom theme tokens here when needed
- ✅ Check for existing reusable tokens before creating new ones
- ✅ Avoid hard-coded colours; use Carbon tokens instead

**Example**:

```scss
$btn-primary-bg: $button-primary;
$dark-btn-primary-bg: $dark-button-primary;
```

---

### `scss/_css-custom-properties.scss`

**Purpose**: Maps SCSS tokens to CSS custom properties

#### Core Mapping Section (Top)

**Characteristics**:

- 1:1 mapping from `_connect-theme.scss`
- Must stay fully aligned with Carbon semantic tokens
- ⚠️ **Do not modify**

#### Connect Theme Override Section (Bottom)

**Characteristics**:

- Maps tokens from `_connect-variables.scss` to CSS custom properties
- ✅ New custom theme tokens must be mapped here

**Naming Rule**: CSS custom property names must match the mapped SCSS token names exactly to maintain strict 1:1 mapping for traceability.

**Correct Usage**:

```scss
:root {
  --btn-primary-bg: #{$btn-primary-bg};
}
body[data-dmt-mode="dark"] {
  --btn-primary-bg: #{$dark-btn-primary-bg};
}
```

**Incorrect Usage** ❌:

```scss
// Do not bypass the mapping layer
--btn-primary-bg: #{$blue-60};
```

---

### `scss/_connect-dark-theme.scss`

**Purpose**: Centralized location for dark mode overrides that cannot be handled through CSS custom properties

**Usage Guidelines**:

- Use only when an override is strictly necessary
- Typical use cases:
  - Third-party component styling
  - Image / SVG / icon adjustments
  - Styles that cannot be handled cleanly through existing CSS custom properties

---

## Token Flow Architecture

The theme implements a layered token system that enables customization while maintaining consistency:

```
Carbon Colour Palette (mixins.scss)
    ↓
    e.g., $blue-60: #0f62fe;
    ↓
Carbon Semantic Tokens (_connect-theme.scss)
    ↓
    e.g., $button-primary: $blue-60;
          $dark-button-primary: $blue-60;
    ↓
Custom Theme Tokens (_connect-variables.scss) ← Customization point
    ↓
    e.g., $btn-primary-bg: $button-primary;
          $dark-btn-primary-bg: $dark-button-primary;
    ↓
CSS Custom Properties (_css-custom-properties.scss)
    ↓
    e.g., :root { --btn-primary-bg: #{$btn-primary-bg}; }
          body[data-dmt-mode="dark"] { --btn-primary-bg: #{$dark-btn-primary-bg}; }
    ↓
Component Styles (use var(--...))
    ↓
    e.g., .button { background-color: var(--btn-primary-bg); }
```

### Anti-Patterns

**Avoid Direct Token Usage** ❌:

```scss
.button {
  background-color: $btn-primary-bg; // Wrong
}
```

**Avoid Hardcoded Colours** ❌:

```scss
.button {
  background-color: #0f62fe; // Wrong
}
```

---

## Working with Colours

### Case 1: Replacing Hardcoded Colours (Non-Customizable)

**When to Use**: Styles that should not be customizable

**Process**:

1. Check if a suitable Carbon token already exists
2. If yes, use that token directly
3. Carbon tokens should not be overridden

---

### Case 2: Replacing Custom Theme Colour Tokens (Customizable)

**When to Use**: Component already uses a custom theme token from `_connect-variables.scss`

**Process**:

1. Add the corresponding dark token in `_connect-variables.scss`
2. Map both light and dark values in `_css-custom-properties.scss`
3. Update the component to use `var(--...)` instead of SCSS tokens

**Benefits**:

- Automatic switching between light and dark mode
- Colour remains overridable via project tokens

---

### Case 3: Replacing Non-Customizable Colour Tokens

**Applicable Tokens**:

- Carbon palette colours (`mixins.scss`)
- Carbon semantic tokens (`_connect-theme.scss`)
- Portal @carbon/themes
- Bootstrap

**Options**:

#### Option A: Map Directly to CSS Custom Properties (Not Customizable)

1. Add the corresponding dark token in `_connect-variables.scss`
2. Map both light and dark values in `_css-custom-properties.scss`
3. Update the component to use `var(--...)`

#### Option B: Use Existing Customizable Project Token (Customizable)

**Requirements**:

- Light mode colour matches intended usage
- Corresponding dark token exists

#### Option C: Create New Custom Theme Token (Customizable)

1. Define it in `_connect-variables.scss`
2. Add a corresponding dark token
3. Map it in `_css-custom-properties.scss`

---

## Generated Theme Template Development

### Supported Template Types

- **SCSS**: Primary development format
- **CSS**: Derived from SCSS

⚠️ **Important**: Always implement and validate changes in SCSS first, as CSS is derived from SCSS.

---

### SCSS Theme Development Workflow

#### Step 1: Define Colour Palette in overrides.scss

- Copy relevant palette values from Carbon (e.g., reds, greys) into `overrides.scss`
- Custom colours can also be defined here
- Demonstrates how users can customize colours in their theme

#### Step 2: Create Dark Mode Assets

- Create dark mode versions of SVG assets in the theme folder
- Adjust colours to ensure visibility in dark mode

#### Step 3: Clean Up Existing Overrides

- Remove existing colour overrides based on CSS selectors or direct token usage

**Note**: At this point, all customizable elements should use CSS custom properties mapped from custom theme tokens. Selector-based overrides are no longer needed. If any styles are not using tokens, update them in the corresponding component or styling files, not in the theme overrides.

#### Step 4: Define and Override Tokens

Add or update light and dark tokens in `overrides.scss`

**Includes**:

- Existing tokens that require dark mode values
- Newly introduced project tokens (if needed)

**Note**: Not all tokens need to be overridden; identify gaps based on actual UI usage.

⚠️ **If a style is using**:

- A non-customizable token (e.g., Carbon token), or
- A hardcoded value

→ Replace it with a custom theme token in `_connect-variables.scss`

#### Step 5: Handle Required Overrides

Add overrides for cases that cannot be handled via tokens:

- SVG / image replacements
- Other edge cases requiring direct CSS overrides

---

### CSS Theme Development Workflow

#### Step 1: Complete SCSS First

- Ensure SCSS is fully implemented and tested
- Do not start CSS updates before this

#### Step 2: Clean CSS File

- Remove existing content from the CSS file
- Previous selector-based overrides are no longer needed

#### Step 3: Map from SCSS to CSS

Recreate all SCSS logic in CSS using CSS custom properties

**Includes**:

**Colour Definitions**

- Map all defined colours to CSS custom properties

**Token Overrides**

- Map all overridden tokens to CSS custom properties
- Ensure both light mode and dark mode values are defined separately

**SVG and Image Overrides**

- Convert SCSS overrides into CSS format

⚠️ **Critical**: SCSS and CSS must stay fully in sync.
