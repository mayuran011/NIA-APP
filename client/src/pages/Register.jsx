import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { auth } from '../api';

export default function Register() {
  const [username, setUsername] = useState('');
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    auth.register({ username, name, email, password })
      .then(() => navigate('/', { replace: true }))
      .catch((err) => setError(err.message || 'Registration failed'))
      .finally(() => setLoading(false));
  };

  return (
    <div className="container auth-page">
      <h1>Sign up</h1>
      <form onSubmit={handleSubmit} className="auth-form">
        {error && <p className="auth-error">{error}</p>}
        <label>
          Username
          <input type="text" value={username} onChange={(e) => setUsername(e.target.value)} required minLength={2} autoComplete="username" />
        </label>
        <label>
          Display name
          <input type="text" value={name} onChange={(e) => setName(e.target.value)} required autoComplete="name" />
        </label>
        <label>
          Email
          <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required autoComplete="email" />
        </label>
        <label>
          Password (min 6)
          <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} required minLength={6} autoComplete="new-password" />
        </label>
        <button type="submit" className="btn" disabled={loading}>{loading ? 'Creating…' : 'Create account'}</button>
      </form>
      <p><Link to="/login">Already have an account? Log in</Link></p>
    </div>
  );
}
