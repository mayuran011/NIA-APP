import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { videos } from '../api';

function streamUrl(id) {
  return `/api/stream/${id}`;
}

export default function Watch() {
  const { id } = useParams();
  const [video, setVideo] = useState(null);
  const [related, setRelated] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!id) return;
    setLoading(true);
    setError(null);
    Promise.all([
      videos.get(id).then(setVideo).catch((e) => { setError(e.message); setVideo(null); }),
      videos.related(id).then(setRelated).catch(() => []),
    ]).finally(() => setLoading(false));
  }, [id]);

  if (loading) return <div className="container"><p>Loading…</p></div>;
  if (error || !video) return <div className="container"><p>Video not found.</p><Link to="/videos">Back to videos</Link></div>;

  const isEmbed = video.embed_code || (video.source === 'remote' && video.remote_url);
  const isLocal = video.source === 'local' && video.file_path;

  return (
    <div className="container watch-page">
      <div className="watch-player-wrap">
        {isEmbed && video.embed_code && (
          <div className="embed-wrap" dangerouslySetInnerHTML={{ __html: video.embed_code }} />
        )}
        {isEmbed && !video.embed_code && video.remote_url && (
          <iframe title={video.title} src={video.remote_url} allowFullScreen className="embed-iframe" />
        )}
        {isLocal && (
          <video controls className="watch-video" poster={video.thumb || undefined} src={streamUrl(video.id)}>
            Your browser does not support the video tag.
          </video>
        )}
        {!isEmbed && !isLocal && (
          <p className="muted">Playback not available for this source.</p>
        )}
      </div>
      <div className="watch-info">
        <h1>{video.title}</h1>
        <p className="watch-meta">{video.views ?? 0} views · {video.likes ?? 0} likes</p>
        {video.description && <p className="watch-desc">{video.description}</p>}
      </div>
      {related.length > 0 && (
        <section className="watch-related">
          <h2>Related</h2>
          <div className="grid">
            {related.slice(0, 12).map((v) => (
              <Link key={v.id} to={`/watch/${v.id}`} className="card">
                <div className="card-thumb-wrap">
                  {v.thumb && (v.thumb.startsWith('http') || v.thumb.startsWith('/')) ? (
                    <img src={v.thumb} alt="" className="card-thumb" />
                  ) : <div className="card-thumb card-thumb-placeholder" />}
                </div>
                <div className="card-body">
                  <h3 className="card-title">{v.title || 'Untitled'}</h3>
                </div>
              </Link>
            ))}
          </div>
        </section>
      )}
    </div>
  );
}
