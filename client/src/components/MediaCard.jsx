import { Link } from 'react-router-dom';

function thumbUrl(thumb, type) {
  if (thumb && (thumb.startsWith('http') || thumb.startsWith('//'))) return thumb;
  if (thumb && thumb.startsWith('/')) return thumb;
  return null;
}

export function VideoCard({ id, title, thumb, duration, views, likes }) {
  const thumbSrc = thumbUrl(thumb);
  const to = `/watch/${id}`;
  return (
    <Link to={to} className="card">
      <div className="card-thumb-wrap">
        {thumbSrc ? <img src={thumbSrc} alt="" className="card-thumb" /> : <div className="card-thumb card-thumb-placeholder" />}
        {duration != null && duration > 0 && (
          <span className="card-duration">{formatDuration(duration)}</span>
        )}
      </div>
      <div className="card-body">
        <h3 className="card-title">{title || 'Untitled'}</h3>
        <div className="card-meta">{formatViews(views)} views · {likes ?? 0} likes</div>
      </div>
    </Link>
  );
}

export function MusicCard({ id, title, thumb, duration, views, likes }) {
  const thumbSrc = thumbUrl(thumb);
  const to = `/listen/${id}`;
  return (
    <Link to={to} className="card">
      <div className="card-thumb-wrap">
        {thumbSrc ? <img src={thumbSrc} alt="" className="card-thumb" /> : <div className="card-thumb card-thumb-placeholder" />}
        {duration != null && duration > 0 && (
          <span className="card-duration">{formatDuration(duration)}</span>
        )}
      </div>
      <div className="card-body">
        <h3 className="card-title">{title || 'Untitled'}</h3>
        <div className="card-meta">{formatViews(views)} plays · {likes ?? 0} likes</div>
      </div>
    </Link>
  );
}

function formatDuration(sec) {
  const m = Math.floor(sec / 60);
  const s = sec % 60;
  return `${m}:${String(s).padStart(2, '0')}`;
}

function formatViews(n) {
  if (n >= 1e6) return (n / 1e6).toFixed(1) + 'M';
  if (n >= 1e3) return (n / 1e3).toFixed(1) + 'K';
  return String(n ?? 0);
}
