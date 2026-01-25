import React, { useState } from 'react'
import PropTypes from 'prop-types'
import './Avatar.css'

/**
 * Avatar component - displays user avatar with fallback to initials
 * 
 * @example
 * <Avatar 
 *   src="/avatars/john.jpg" 
 *   name="Jan Kowalski" 
 *   size="lg"
 * />
 */
export default function Avatar({
  src,
  name = '',
  size = 'md',
  variant = 'circle',
  status,
  className = '',
  onClick,
  ...props
}) {
  const [imageError, setImageError] = useState(false)

  // Generate initials from name
  const getInitials = (fullName) => {
    if (!fullName) return '?'
    
    const names = fullName.trim().split(/\s+/)
    if (names.length === 1) {
      return names[0].charAt(0).toUpperCase()
    }
    return (names[0].charAt(0) + names[names.length - 1].charAt(0)).toUpperCase()
  }

  // Generate consistent color based on name
  const getColorFromName = (name) => {
    if (!name) return 'hsl(0, 0%, 60%)'
    
    let hash = 0
    for (let i = 0; i < name.length; i++) {
      hash = name.charCodeAt(i) + ((hash << 5) - hash)
    }
    
    const hue = Math.abs(hash % 360)
    return `hsl(${hue}, 65%, 55%)`
  }

  const initials = getInitials(name)
  const bgColor = getColorFromName(name)
  const showImage = src && !imageError

  const classes = [
    'avatar',
    `avatar--${size}`,
    `avatar--${variant}`,
    onClick && 'avatar--clickable',
    className
  ].filter(Boolean).join(' ')

  const statusClasses = [
    'avatar__status',
    `avatar__status--${status}`
  ].filter(Boolean).join(' ')

  return (
    <div 
      className={classes} 
      onClick={onClick}
      style={!showImage ? { backgroundColor: bgColor } : undefined}
      title={name}
      role={onClick ? 'button' : undefined}
      tabIndex={onClick ? 0 : undefined}
      {...props}
    >
      {showImage ? (
        <img
          src={src}
          alt={name || 'Avatar'}
          className="avatar__image"
          onError={() => setImageError(true)}
        />
      ) : (
        <span className="avatar__initials">{initials}</span>
      )}

      {status && <span className={statusClasses} />}
    </div>
  )
}

Avatar.propTypes = {
  src: PropTypes.string,
  name: PropTypes.string,
  size: PropTypes.oneOf(['xs', 'sm', 'md', 'lg', 'xl', '2xl']),
  variant: PropTypes.oneOf(['circle', 'rounded', 'square']),
  status: PropTypes.oneOf(['online', 'offline', 'away', 'busy']),
  className: PropTypes.string,
  onClick: PropTypes.func
}

/**
 * AvatarGroup - displays multiple avatars with overlap
 */
export function AvatarGroup({
  avatars = [],
  max = 4,
  size = 'md',
  className = '',
  ...props
}) {
  const displayAvatars = avatars.slice(0, max)
  const remainingCount = avatars.length - max

  return (
    <div className={`avatar-group avatar-group--${size} ${className}`} {...props}>
      {displayAvatars.map((avatar, index) => (
        <Avatar
          key={avatar.id || index}
          src={avatar.src}
          name={avatar.name}
          size={size}
          className="avatar-group__item"
        />
      ))}
      
      {remainingCount > 0 && (
        <div className={`avatar avatar--${size} avatar-group__item avatar-group__remaining`}>
          <span className="avatar__initials">+{remainingCount}</span>
        </div>
      )}
    </div>
  )
}

AvatarGroup.propTypes = {
  avatars: PropTypes.arrayOf(PropTypes.shape({
    id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    src: PropTypes.string,
    name: PropTypes.string
  })),
  max: PropTypes.number,
  size: PropTypes.oneOf(['xs', 'sm', 'md', 'lg', 'xl']),
  className: PropTypes.string
}
