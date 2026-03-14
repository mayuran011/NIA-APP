const API = '/api';

async function request(path, options = {}) {
  const url = path.startsWith('http') ? path : `${API}${path}`;
  const res = await fetch(url, { credentials: 'include', ...options });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || res.statusText);
  return data;
}

export const auth = {
  me: () => request('/auth/me'),
  login: (email, password) =>
    request('/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password }),
    }),
  register: (body) =>
    request('/auth/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    }),
  logout: () =>
    request('/auth/logout', { method: 'POST' }),
};

export const options = () => request('/options');

export const videos = {
  list: (params) => {
    const q = new URLSearchParams(params).toString();
    return request(`/videos${q ? `?${q}` : ''}`);
  },
  get: (id) => request(`/videos/${id}`),
  related: (id) => request(`/videos/${id}/related`),
};

export const music = {
  list: (params) => {
    const q = new URLSearchParams(params).toString();
    return request(`/music${q ? `?${q}` : ''}`);
  },
  get: (id) => request(`/music/${id}`),
};

export const search = (q, type = 'all') =>
  request(`/search?q=${encodeURIComponent(q)}&type=${type}`);

export const blog = {
  posts: (params) => {
    const q = new URLSearchParams(params).toString();
    return request(`/blog/posts${q ? `?${q}` : ''}`);
  },
  post: (id) => request(`/blog/posts/${id}`),
};

export const channels = {
  get: (id) => request(`/channels/${id}`),
};
