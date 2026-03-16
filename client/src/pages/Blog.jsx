import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { blog } from '../api';

export default function Blog() {
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    blog.posts({ limit: 24 }).then(setPosts).catch(() => setPosts([])).finally(() => setLoading(false));
  }, []);

  return (
    <div className="container">
      <h1>Blog</h1>
      {loading ? <p>Loading…</p> : (
        <ul className="blog-list">
          {posts.map((p) => (
            <li key={p.id}>
              <Link to={`/read/${p.slug}/${p.id}`}>{p.title}</Link>
              <span className="muted"> — {p.created_at ? new Date(p.created_at).toLocaleDateString() : ''}</span>
            </li>
          ))}
        </ul>
      )}
      {!loading && posts.length === 0 && <p className="muted">No posts yet.</p>}
    </div>
  );
}
