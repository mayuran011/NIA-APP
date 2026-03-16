import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { videos, music, options } from '../api';
import { VideoCard } from '../components/MediaCard';
import { MusicCard } from '../components/MediaCard';

export default function Home() {
  const [siteName, setSiteName] = useState('Nia App');
  const [featuredVideos, setFeaturedVideos] = useState([]);
  const [featuredMusic, setFeaturedMusic] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([
      options().then((opts) => setSiteName(opts.sitename || 'Nia App')),
      videos.list({ section: 'featured', limit: 8 }).then(setFeaturedVideos).catch(() => []),
      music.list({ section: 'featured', limit: 8 }).then(setFeaturedMusic).catch(() => []),
    ]).finally(() => setLoading(false));
  }, []);

  if (loading) {
    return (
      <div className="container">
        <p>Loading…</p>
      </div>
    );
  }

  return (
    <div className="container">
      <section className="home-hero">
        <h1>{siteName}</h1>
        <p className="muted">Video &amp; music sharing</p>
      </section>

      {featuredVideos.length > 0 && (
        <section className="home-section">
          <h2>
            <Link to="/videos/featured">Featured Videos</Link>
          </h2>
          <div className="grid">
            {featuredVideos.map((v) => (
              <VideoCard key={v.id} {...v} />
            ))}
          </div>
          <p><Link to="/videos">Browse all videos →</Link></p>
        </section>
      )}

      {featuredMusic.length > 0 && (
        <section className="home-section">
          <h2>
            <Link to="/music/featured">Featured Music</Link>
          </h2>
          <div className="grid">
            {featuredMusic.map((m) => (
              <MusicCard key={m.id} {...m} />
            ))}
          </div>
          <p><Link to="/music">Browse all music →</Link></p>
        </section>
      )}

      {featuredVideos.length === 0 && featuredMusic.length === 0 && (
        <section className="home-section">
          <p className="muted">No featured content yet. <Link to="/videos">Browse videos</Link> or <Link to="/music">music</Link>.</p>
        </section>
      )}
    </div>
  );
}
