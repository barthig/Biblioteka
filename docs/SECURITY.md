# Security Guidelines

## 🔒 Production Deployment Checklist

### 1. JWT & API Secrets

**CRITICAL:** Change default secrets before production deployment!

```bash
# Generate strong JWT secret (64 bytes = 128 hex chars)
openssl rand -hex 64

# Generate strong API secret (32 bytes = 64 hex chars)  
openssl rand -hex 32
```

Update in `config/docker-compose.yml`:
```yaml
environment:
  JWT_SECRET: "your-generated-jwt-secret-here"
  API_SECRET: "your-generated-api-secret-here"
```

### 2. Environment Configuration

Set proper environment variables:
```yaml
APP_ENV: prod
APP_DEBUG: 0  # Disable debug mode
```

### 3. CORS Configuration

Verify CORS is properly restricted in `backend/config/packages/nelmio_cors.yaml`:
```yaml
allow_origin: ['https://yourdomain.com']  # NOT ['*']
allow_credentials: true
```

### 4. Database Security

- Use strong database passwords
- Restrict database access to backend only
- Enable SSL for database connections in production

### 5. HTTPS/TLS

- **Always use HTTPS in production**
- Configure SSL certificates in nginx
- Enforce HTTPS redirects

### 6. Rate Limiting

Rate limiting is configured per IP:
- Login: 5 attempts per 15 minutes
- API: 300 requests per minute

Consider implementing per-user rate limiting for production.

## 🔐 Authentication Flow

### JWT Token Lifecycle

1. **Login** → Returns `token` (JWT) + `refreshToken`
2. **JWT expires** after 24 hours
3. **Refresh token** valid for 30 days
4. **Auto-logout** on token expiration or 401 response

### Token Storage

- **JWT:** localStorage (auto-cleaned on expiration)
- **Refresh Token:** Database with user association

### Token Validation

Frontend validates token expiration before each request. Backend validates:
- Signature (HS256)
- Expiration time
- Issuer/Audience
- User account status (verified, not blocked)

## 🛡️ Security Features

### Implemented

✅ JWT with HS256 signing  
✅ Password hashing (bcrypt)  
✅ Role-based access control (ADMIN > LIBRARIAN > USER)  
✅ CORS protection  
✅ Rate limiting  
✅ Refresh token with IP tracking  
✅ Auto-logout on token expiration  
✅ Account status validation (verified, blocked, pending)

### Recommended for Production

- [ ] Implement JWT blacklist (Redis) for immediate logout
- [ ] Enable automatic token refresh
- [ ] Add rate limiting per user (not just per IP)
- [ ] Implement audit logging for admin actions
- [ ] Set up SSL/TLS certificates
- [ ] Configure firewall rules
- [ ] Regular security audits

## 🚨 Security Incident Response

If you suspect a security breach:

1. **Immediately** change JWT_SECRET to invalidate all tokens
2. Check logs in `backend/var/log/` for suspicious activity
3. Review database for unauthorized access
4. Notify all users to change passwords
5. Update to latest security patches

## 📞 Reporting Security Issues

Report security vulnerabilities privately to the development team.
Do not disclose publicly until patch is available.

## 🔄 Token Rotation

To rotate JWT secrets without downtime:

1. Add new secret to `JWT_SECRETS`: `old_secret,new_secret`
2. Deploy configuration
3. Update `JWT_SECRET` to `new_secret`
4. Wait 24 hours (token expiration)
5. Remove old secret from `JWT_SECRETS`

## 📚 Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [JWT Best Practices](https://tools.ietf.org/html/rfc8725)
- [Symfony Security](https://symfony.com/doc/current/security.html)
