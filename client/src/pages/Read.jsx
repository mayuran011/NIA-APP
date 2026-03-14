import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { blog } from '../api';

export default function Read() {
  const { id } = useParams();
  const [post, setPost] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!id) return;
    setLoading(true);
    setError(null);
    blog.post(id).then(setPost).catch((e) => setError(e.message)).finally(() => setLoading(false));
  }, [id]);

  if (loading) return <div className="container"><p>Loading…</p></div>;
  if (error || !post) return <div className="container"><p>Post not found.</p><Link to="/blog">← Back to blog</Link></div>;

  return (
    <article className="container read-page">
      <h1>{post.title}</h1>
      <p className="muted">{post.created_at ? new Date(post.created_at).toLocaleDateString() : ''}</p>
      {post.excerpt && <p className="read-excerpt">{post.excerpt}</p>}
      <div className="read-content" dangerouslySetInnerHTML={{ __html: post.content || '' }} />
      <p><Link to="/blog">← Back to blog</Link></p>
    </article>
  );
}
