import { Outlet, Link, useNavigate } from 'react-router-dom';
import { useState, useEffect } from 'react';
import { auth } from '../api';
import './Layout.css';

export default function Layout() {
  const [user, setUser] = useState(null);
  const navigate = useNavigate();

  useEffect(() => {
    auth.me().then(setUser).catch(() => setUser(null));
  }, []);

  const handleLogout = () => {
    auth.logout().then(() => {
      setUser(null);
      navigate('/');
    }).catch(() => setUser(null));
  };

  return (
    <div className="layout">
      <header className="header">
        <div className="container header-inner">
          <Link to="/" className="logo">Nia App</Link>
          <nav className="nav">
            <Link to="/videos">Videos</Link>
            <Link to="/music">Music</Link>
            <Link to="/blog">Blog</Link>
            <Link to="/show">Search</Link>
            {user ? (
              <>
                <span className="user-name">{user.name}</span>
                <button type="button" className="btn btn-ghost" onClick={handleLogout}>Log out</button>
              </>
            ) : (
              <>
                <Link to="/login" className="btn btn-ghost">Log in</Link>
                <Link to="/register" className="btn">Sign up</Link>
              </>
            )}
          </nav>
        </div>
      </header>
      <main className="main">
        <Outlet />
      </main>
      <footer className="footer">
        <div className="container">
          <p>Nia App – Video &amp; Music. Domain: <a href="https://msdeploy.com/" target="_blank" rel="noopener noreferrer">msdeploy.com</a></p>
        </div>
      </footer>
    </div>
  );
}
