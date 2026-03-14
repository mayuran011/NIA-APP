import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { auth } from '../api';

export default function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    auth.login(email, password)
      .then(() => navigate('/', { replace: true }))
      .catch((err) => setError(err.message || 'Login failed'))
      .finally(() => setLoading(false));
  };

  return (
    <div className="container auth-page">
      <h1>Log in</h1>
      <form onSubmit={handleSubmit} className="auth-form">
        {error && <p className="auth-error">{error}</p>}
        <label>
          Email
          <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required autoComplete="email" />
        </label>
        <label>
          Password
          <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} required autoComplete="current-password" />
        </label>
        <button type="submit" className="btn" disabled={loading}>{loading ? 'Signing in…' : 'Sign in'}</button>
      </form>
      <p><Link to="/register">Create an account</Link></p>
    </div>
  );
}
