import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { channels, videos } from '../api';
import { VideoCard } from '../components/MediaCard';

export default function Category() {
  const { id } = useParams();
  const [channel, setChannel] = useState(null);
  const [list, setList] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!id) return;
    setLoading(true);
    setError(null);
    Promise.all([
      channels.get(id).then(setChannel).catch(() => setChannel(null)),
      videos.list({ category_id: id, limit: 48 }).then(setList).catch(() => setList([])),
    ]).finally(() => setLoading(false));
  }, [id]);

  if (loading) return <div className="container"><p>Loading…</p></div>;
  if (error || !channel) return <div className="container"><p>Channel not found.</p><Link to="/show">Search</Link></div>;

  return (
    <div className="container">
      <h1>{channel.name}</h1>
      {channel.description && <p className="muted">{channel.description}</p>}
      <div className="grid">
        {list.map((v) => (
          <VideoCard key={v.id} {...v} />
        ))}
      </div>
      {list.length === 0 && <p className="muted">No videos in this channel.</p>}
    </div>
  );
}
