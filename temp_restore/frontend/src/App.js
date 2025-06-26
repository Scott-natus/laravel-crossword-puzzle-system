import React from 'react';
import { BrowserRouter as Router, Routes, Route, Link } from 'react-router-dom';
import { AuthProvider, useAuth } from './contexts/AuthContext';
import Login from './components/Login';
import Register from './components/Register';
import BoardList from './components/BoardList';
import BoardForm from './components/BoardForm';
import BoardDetail from './components/BoardDetail';
import ProtectedRoute from './components/ProtectedRoute';
import CrosswordPuzzle from './components/puzzle/CrosswordPuzzle';
import PuzzleGenerator from './components/puzzle/PuzzleGenerator';
import './App.css';

const Navigation = () => {
  const { user, logout } = useAuth();

  return (
    <nav className="navbar navbar-expand-lg navbar-light bg-light">
      <div className="container">
        <Link className="navbar-brand" to="/">홈</Link>
        <div className="navbar-nav">
          {user ? (
            <>
              <span className="nav-link">안녕하세요, {user.user.name}님!</span>
              <Link className="nav-link" to="/board/bbs1">주저리</Link>
              <Link className="nav-link" to="/board/talk">자유게시판</Link>
              <Link className="nav-link" to="/board/lab">자료정리</Link>
              <button onClick={logout} className="nav-link btn btn-link">로그아웃</button>
            </>
          ) : (
            <>
              <Link className="nav-link" to="/login">로그인</Link>
              <Link className="nav-link" to="/register">회원가입</Link>
            </>
          )}
        </div>
      </div>
    </nav>
  );
};

function App() {
  return (
    <AuthProvider>
      <Router>
        <div className="App">
          <Navigation />
          <div className="container mt-3">
            <nav style={{ margin: '1rem' }}>
              <Link to="/puzzle">
                <button style={{ padding: '0.5rem 1rem', fontSize: '1rem' }}>십자낱말 퍼즐</button>
              </Link>
            </nav>
            <Routes>
              <Route path="/login" element={<Login />} />
              <Route path="/register" element={<Register />} />
              <Route path="/board/:boardType" element={
                <ProtectedRoute>
                  <BoardList />
                </ProtectedRoute>
              } />
              <Route path="/board/:boardType/create" element={
                <ProtectedRoute>
                  <BoardForm />
                </ProtectedRoute>
              } />
              <Route path="/board/:boardType/:id" element={
                <ProtectedRoute>
                  <BoardDetail />
                </ProtectedRoute>
              } />
              <Route path="/board/:boardType/:id/edit" element={
                <ProtectedRoute>
                  <BoardForm />
                </ProtectedRoute>
              } />
              <Route path="/" element={
                <ProtectedRoute>
                  <div className="text-center">
                    <h1>Welcome to React Board</h1>
                    <p>게시판을 선택해주세요.</p>
                    <div className="mt-4">
                      <Link to="/board/bbs1" className="btn btn-primary me-2">주저리</Link>
                      <Link to="/board/talk" className="btn btn-primary me-2">자유게시판</Link>
                      <Link to="/board/lab" className="btn btn-primary">자료정리</Link>
                    </div>
                  </div>
                </ProtectedRoute>
              } />
              <Route path="/puzzle" element={<CrosswordPuzzle levelId={1} />} />
              <Route path="/puzzle-generator" element={<PuzzleGenerator />} />
            </Routes>
          </div>
        </div>
      </Router>
    </AuthProvider>
  );
}

export default App;
