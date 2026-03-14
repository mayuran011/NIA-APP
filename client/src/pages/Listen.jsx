import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { music } from '../api';

export default function Listen() {
  const { id } = useParams();
  const [track, setTrack] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!id) return;
    setLoading(true);
    music.get(id).then(setTrack).catch((e) => { setError(e.message); setTrack(null); }).finally(() => setLoading(false));
  }, [id]);

  if (loading) return <div className="container"><p>Loading…</p></div>;
  if (error || !track) return <div className="container"><p>Track not found.</p><Link to="/music">Back to music</Link></div>;

  const isEmbed = track.embed_code || (track.source === 'remote' && track.remote_url);
  const streamSrc = track.source === 'local' && track.file_path ? `/api/stream/${track.id}` : null;

  return (
    <div className="container listen-page">
      <div className="listen-player-wrap">
        {isEmbed && track.embed_code && (
          <div className="embed-wrap" dangerouslySetInnerHTML={{ __html: track.embed_code }} />
        )}
        {isEmbed && !track.embed_code && track.remote_url && (
          <iframe title={track.title} src={track.remote_url} className="embed-iframe" />
        )}
        {streamSrc && (
          <audio controls className="listen-audio" src={streamSrc}>
            Your browser does not support the audio tag.
          </audio>
        )}
        {!isEmbed && !streamSrc && (
          <p className="muted">Playback not available.</p>
        )}
      </div>
      <div className="watch-info">
        <h1>{track.title}</h1>
        <p className="watch-meta">{track.views ?? 0} plays · {track.likes ?? 0} likes</p>
        {track.description && <p className="watch-desc">{track.description}</p>}
      </div>
      <p><Link to="/music">← Back to music</Link></p>
    </div>
  );
}
