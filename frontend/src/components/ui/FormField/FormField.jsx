import React, { forwardRef, useId } from 'react'
import PropTypes from 'prop-types'
import './FormField.css'

/**
 * FormField component - wrapper for form inputs with label, error, and helper text
 * 
 * @example
 * <FormField
 *   label="Email"
 *   error={errors.email}
 *   helperText="Wprowadź adres email"
 *   required
 * >
 *   <input type="email" {...register('email')} />
 * </FormField>
 */
const FormField = forwardRef(function FormField({
  label,
  error,
  helperText,
  required = false,
  disabled = false,
  size = 'md',
  variant = 'default',
  className = '',
  labelProps = {},
  children,
  ...props
}, ref) {
  const generatedId = useId()
  const fieldId = props.id || generatedId
  const errorId = `${fieldId}-error`
  const helperId = `${fieldId}-helper`

  const classes = [
    'form-field',
    `form-field--${size}`,
    `form-field--${variant}`,
    error && 'form-field--error',
    disabled && 'form-field--disabled',
    required && 'form-field--required',
    className
  ].filter(Boolean).join(' ')

  // Clone children to add aria attributes
  const enhancedChildren = React.Children.map(children, child => {
    if (!React.isValidElement(child)) return child
    
    return React.cloneElement(child, {
      id: child.props.id || fieldId,
      'aria-invalid': error ? 'true' : undefined,
      'aria-describedby': [
        error ? errorId : null,
        helperText ? helperId : null
      ].filter(Boolean).join(' ') || undefined,
      disabled: child.props.disabled ?? disabled
    })
  })

  return (
    <div ref={ref} className={classes} {...props}>
      {label && (
        <label 
          htmlFor={fieldId} 
          className="form-field__label"
          {...labelProps}
        >
          {label}
          {required && <span className="form-field__required">*</span>}
        </label>
      )}

      <div className="form-field__input-wrapper">
        {enhancedChildren}
      </div>

      {error && (
        <p id={errorId} className="form-field__error" role="alert">
          <span className="form-field__error-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="12" cy="12" r="10" />
              <path d="M12 8v4" />
              <path d="M12 16h.01" />
            </svg>
          </span>
          {error}
        </p>
      )}

      {helperText && !error && (
        <p id={helperId} className="form-field__helper">
          {helperText}
        </p>
      )}
    </div>
  )
})

FormField.propTypes = {
  label: PropTypes.node,
  error: PropTypes.string,
  helperText: PropTypes.node,
  required: PropTypes.bool,
  disabled: PropTypes.bool,
  size: PropTypes.oneOf(['sm', 'md', 'lg']),
  variant: PropTypes.oneOf(['default', 'inline', 'floating']),
  className: PropTypes.string,
  labelProps: PropTypes.object,
  children: PropTypes.node,
  id: PropTypes.string
}

export default FormField

/**
 * TextField - FormField with integrated text input
 */
export const TextField = forwardRef(function TextField({
  label,
  error,
  helperText,
  required,
  disabled,
  type = 'text',
  value,
  onChange,
  placeholder,
  size = 'md',
  ...props
}, ref) {
  return (
    <FormField
      label={label}
      error={error}
      helperText={helperText}
      required={required}
      disabled={disabled}
      size={size}
    >
      <input
        ref={ref}
        type={type}
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        className="form-field__text-input"
        {...props}
      />
    </FormField>
  )
})

/**
 * TextArea - FormField with integrated textarea
 */
export const TextArea = forwardRef(function TextArea({
  label,
  error,
  helperText,
  required,
  disabled,
  value,
  onChange,
  placeholder,
  rows = 4,
  size = 'md',
  ...props
}, ref) {
  return (
    <FormField
      label={label}
      error={error}
      helperText={helperText}
      required={required}
      disabled={disabled}
      size={size}
    >
      <textarea
        ref={ref}
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        rows={rows}
        className="form-field__textarea"
        {...props}
      />
    </FormField>
  )
})

/**
 * Select - FormField with integrated select
 */
export const SelectField = forwardRef(function SelectField({
  label,
  error,
  helperText,
  required,
  disabled,
  value,
  onChange,
  options = [],
  placeholder = 'Wybierz...',
  size = 'md',
  ...props
}, ref) {
  return (
    <FormField
      label={label}
      error={error}
      helperText={helperText}
      required={required}
      disabled={disabled}
      size={size}
    >
      <select
        ref={ref}
        value={value}
        onChange={onChange}
        className="form-field__select"
        {...props}
      >
        {placeholder && (
          <option value="" disabled>
            {placeholder}
          </option>
        )}
        {options.map(option => (
          <option 
            key={option.value} 
            value={option.value}
            disabled={option.disabled}
          >
            {option.label}
          </option>
        ))}
      </select>
    </FormField>
  )
})

/**
 * Checkbox - FormField with integrated checkbox
 */
export const CheckboxField = forwardRef(function CheckboxField({
  label,
  error,
  helperText,
  checked,
  onChange,
  disabled,
  ...props
}, ref) {
  return (
    <FormField
      error={error}
      helperText={helperText}
      disabled={disabled}
      variant="inline"
    >
      <label className="form-field__checkbox-wrapper">
        <input
          ref={ref}
          type="checkbox"
          checked={checked}
          onChange={onChange}
          className="form-field__checkbox"
          {...props}
        />
        <span className="form-field__checkbox-label">{label}</span>
      </label>
    </FormField>
  )
})
