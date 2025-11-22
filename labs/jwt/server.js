import express from 'express';
import dotenv from 'dotenv';
import jwt from 'jsonwebtoken';

dotenv.config();

const app = express();
const PORT = process.env.PORT || 3000;

if (!process.env.JWT_SECRET) {
  console.warn('⚠️  Brak zmiennej JWT_SECRET. Ustaw ją w pliku .env');
}

app.use(express.json());

const users = [
  {
    id: 1,
    email: 'demo@example.com',
    password: 'secret123',
    name: 'Demo User'
  }
];

function signToken(payload) {
  if (!process.env.JWT_SECRET) {
    throw new Error('JWT secret is not configured');
  }
  return jwt.sign(payload, process.env.JWT_SECRET, { expiresIn: '1h' });
}

function requireAuth(req, res, next) {
  const authHeader = req.headers.authorization || '';
  const token = authHeader.startsWith('Bearer ')
    ? authHeader.substring('Bearer '.length)
    : null;

  if (!token) {
    return res.status(401).json({ error: 'Brak tokenu' });
  }

  try {
    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    const user = users.find((u) => u.id === decoded.sub);
    if (!user) {
      return res.status(401).json({ error: 'Niepoprawny token' });
    }
    req.authUser = user;
    req.tokenPayload = decoded;
    next();
  } catch (error) {
    console.error('JWT error:', error.message);
    return res.status(401).json({ error: 'Token nieprawidłowy lub wygasł' });
  }
}

app.post('/login', (req, res) => {
  const { email, password } = req.body || {};
  const user = users.find((u) => u.email === email);

  if (!user || user.password !== password) {
    return res.status(401).json({ error: 'Nieprawidłowy login lub hasło' });
  }

  const token = signToken({ sub: user.id, email: user.email });
  res.json({ token });
});

app.get('/profile', requireAuth, (req, res) => {
  const { id, email, name } = req.authUser;
  res.json({ id, email, name });
});

app.get('/', (req, res) => {
  res.json({ message: 'JWT Lab działa. Skorzystaj z /login i /profile.' });
});

app.listen(PORT, () => {
  console.log(`Serwer nasłuchuje na http://localhost:${PORT}`);
});
