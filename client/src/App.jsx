import { Routes, Route, Navigate } from 'react-router-dom';
import Layout from './components/Layout';
import Home from './pages/Home';
import Watch from './pages/Watch';
import Listen from './pages/Listen';
import Videos from './pages/Videos';
import Music from './pages/Music';
import Login from './pages/Login';
import Register from './pages/Register';
import Search from './pages/Search';
import Blog from './pages/Blog';
import Read from './pages/Read';
import Category from './pages/Category';

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<Layout />}>
        <Route index element={<Home />} />
        <Route path="watch/:id" element={<Watch />} />
        <Route path="listen/:id" element={<Listen />} />
        <Route path="videos" element={<Videos />} />
        <Route path="videos/:section" element={<Videos />} />
        <Route path="music" element={<Music />} />
        <Route path="music/:section" element={<Music />} />
        <Route path="show" element={<Search />} />
        <Route path="login" element={<Login />} />
        <Route path="register" element={<Register />} />
        <Route path="blog" element={<Blog />} />
        <Route path="read/:name/:id" element={<Read />} />
        <Route path="category/:id" element={<Category />} />
        <Route path="*" element={<Navigate to="/" replace />} />
      </Route>
    </Routes>
  );
}
