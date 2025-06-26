import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import boardService from '../services/board';
import CommentForm from './CommentForm';
import { commentService } from '../services/comment';

const BoardDetail = () => {
  const { boardType, id } = useParams();
  const [board, setBoard] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [deletePassword, setDeletePassword] = useState('');
  const [deleteError, setDeleteError] = useState('');
  const [comments, setComments] = useState([]);
  const [showReplyForm, setShowReplyForm] = useState(null);
  const [showPostReplyForm, setShowPostReplyForm] = useState(false);
  const [replyFormData, setReplyFormData] = useState({
    title: '',
    content: '',
    password: ''
  });
  const navigate = useNavigate();
  const { user } = useAuth();
  const [relatedThreads, setRelatedThreads] = useState([]);

  const fetchBoard = async () => {
    try {
      const data = await boardService.getBoard(boardType, id);
      setBoard(data);
      setLoading(false);
    } catch (error) {
      setError(error.message);
      setLoading(false);
    }
  };

  const fetchComments = async () => {
    try {
      const data = await commentService.getComments(boardType, id);
      setComments(data);
    } catch (error) {
      console.error('Error fetching comments:', error);
    }
  };

  const fetchRelatedThreads = async () => {
    try {
      const data = await boardService.getRelatedThreads(boardType, id);
      setRelatedThreads(data);
    } catch (error) {
      console.error('Error fetching related threads:', error);
    }
  };

  useEffect(() => {
    fetchBoard();
    fetchComments();
    fetchRelatedThreads();
  }, [boardType, id]);

  const handleDelete = async () => {
    try {
      await boardService.deleteBoard(boardType, id, deletePassword);
      navigate(`/board/${boardType}`);
    } catch (error) {
      setDeleteError(error.message);
    }
  };

  const handleDeleteComment = async (commentId, password) => {
    try {
      await commentService.deleteComment(boardType, id, commentId, password);
      fetchComments();
    } catch (error) {
      alert(error.message || '댓글 삭제 중 오류가 발생했습니다.');
    }
  };

  const handleReplySubmit = async (e) => {
    e.preventDefault();
    try {
      const formData = new FormData();
      formData.append('title', replyFormData.title);
      formData.append('content', replyFormData.content);
      formData.append('password', replyFormData.password);
      formData.append('parent_id', board.id);

      await boardService.createBoard(boardType, formData);
      setShowPostReplyForm(false);
      setReplyFormData({ title: '', content: '', password: '' });
      navigate(`/board/${boardType}`);
    } catch (error) {
      setError(error.message || '답글 작성 중 오류가 발생했습니다.');
    }
  };

  const handleReplyChange = (e) => {
    const { name, value } = e.target;
    setReplyFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const renderComments = (parentId = null, depth = 0) => {
    return comments
      .filter(comment => comment.parent_id === parentId)
      .map(comment => (
        <div key={comment.id} className="comment mb-3" style={{ marginLeft: `${depth * 20}px` }}>
          <div className="card">
            <div className="card-body">
              <div className="d-flex justify-content-between">
                <h6 className="card-subtitle mb-2 text-muted">{comment.writer}</h6>
                <small className="text-muted">{new Date(comment.created_at).toLocaleString()}</small>
              </div>
              <p className="card-text">{comment.content}</p>
              <div className="btn-group">
                <button 
                  className="btn btn-sm btn-outline-primary"
                  onClick={() => setShowReplyForm(comment.id)}
                >
                  답글
                </button>
                <button 
                  className="btn btn-sm btn-outline-danger"
                  onClick={() => {
                    const password = prompt('비밀번호를 입력하세요:');
                    if (password) {
                      handleDeleteComment(comment.id, password);
                    }
                  }}
                >
                  삭제
                </button>
              </div>
            </div>
          </div>
          {showReplyForm === comment.id && (
            <div className="mt-2">
              <CommentForm 
                onCommentAdded={() => {
                  setShowReplyForm(null);
                  fetchComments();
                }}
                parentId={comment.id}
              />
            </div>
          )}
          {renderComments(comment.id, depth + 1)}
        </div>
      ));
  };

  const renderThreadTree = (thread, depth = 0) => {
    if (depth > 20) return null;

    return (
      <div key={thread.id} className="thread-item" style={{ marginLeft: `${depth * 24}px` }}>
        <div className={`card mb-2 ${thread.id === parseInt(id) ? 'border-primary' : ''}`}>
          <div className="card-body py-2">
            <div className="d-flex justify-content-between align-items-center">
              <h6 className="card-title mb-0">
                {depth > 0 && (
                  <span style={{ 
                    marginRight: 8, 
                    color: '#666',
                    fontSize: '1.2em',
                    fontWeight: 'bold'
                  }}>↳</span>
                )}
                <Link 
                  to={`/board/${boardType}/${thread.id}`}
                  className={thread.id === parseInt(id) ? 'text-primary' : 'text-dark'}
                  style={{ textDecoration: 'none' }}
                >
                  {thread.title}
                </Link>
              </h6>
              <small className="text-muted">
                {new Date(thread.created_at).toLocaleString()}
              </small>
            </div>
            <div className="card-text mt-1">
              <small className="text-muted">작성자: {thread.user?.name}</small>
            </div>
          </div>
        </div>
        {thread.children && thread.children.map(child => renderThreadTree(child, depth + 1))}
      </div>
    );
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;
  if (!board) return <div>Board not found</div>;

  const isAuthor = user && board.writer === user.name;

  return (
    <div className="container mt-4">
      <div className="board-detail">
        <div className="card">
          <div className="card-body">
            <h2 className="card-title">{board.title}</h2>
            <div className="d-flex justify-content-between align-items-center mb-3">
              <div>
                <span className="text-muted">작성자: {board.writer}</span>
                <span className="text-muted ms-3">작성일: {new Date(board.created_at).toLocaleString()}</span>
              </div>
              <div>
                <span className="text-muted">조회수: {board.views}</span>
              </div>
            </div>
            <div className="card-text mb-4" style={{ whiteSpace: 'pre-wrap' }}>
              {board.content}
            </div>
            {board.files && board.files.length > 0 && (
              <div className="mb-4">
                <h5>첨부파일</h5>
                <ul className="list-unstyled">
                  {board.files.map((file, index) => (
                    <li key={index}>
                      <a href={`/storage/${file.path}`} download={file.original_name}>
                        {file.original_name}
                      </a>
                    </li>
                  ))}
                </ul>
              </div>
            )}
            <div className="d-flex justify-content-between">
              <Link to={`/board/${boardType}`} className="btn btn-secondary">
                목록으로
              </Link>
              <div>
                <button 
                  className="btn btn-primary me-2"
                  onClick={() => setShowPostReplyForm(true)}
                >
                  답글
                </button>
                {isAuthor && (
                  <>
                    <Link to={`/board/${boardType}/${id}/edit`} className="btn btn-primary me-2">
                      수정
                    </Link>
                    <button 
                      className="btn btn-danger"
                      onClick={() => setShowDeleteModal(true)}
                    >
                      삭제
                    </button>
                  </>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>

      {showPostReplyForm && (
        <div className="card mt-4">
          <div className="card-body">
            <h4>답글 작성</h4>
            <form onSubmit={handleReplySubmit}>
              <div className="mb-3">
                <label htmlFor="replyTitle" className="form-label">제목</label>
                <input
                  type="text"
                  className="form-control"
                  id="replyTitle"
                  name="title"
                  value={replyFormData.title}
                  onChange={handleReplyChange}
                  required
                />
              </div>
              <div className="mb-3">
                <label htmlFor="replyContent" className="form-label">내용</label>
                <textarea
                  className="form-control"
                  id="replyContent"
                  name="content"
                  value={replyFormData.content}
                  onChange={handleReplyChange}
                  rows="5"
                  required
                />
              </div>
              <div className="mb-3">
                <label htmlFor="replyPassword" className="form-label">비밀번호</label>
                <input
                  type="password"
                  className="form-control"
                  id="replyPassword"
                  name="password"
                  value={replyFormData.password}
                  onChange={handleReplyChange}
                  required
                />
              </div>
              <div className="d-flex justify-content-end">
                <button 
                  type="button" 
                  className="btn btn-secondary me-2"
                  onClick={() => setShowPostReplyForm(false)}
                >
                  취소
                </button>
                <button type="submit" className="btn btn-primary">
                  답글 등록
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      <div className="mt-5">
        <h4 className="mb-3">연관 글</h4>
        <div className="thread-list">
          {relatedThreads.map(thread => renderThreadTree(thread))}
        </div>
      </div>

      <div className="mt-5">
        <h4>댓글</h4>
        <CommentForm onCommentAdded={fetchComments} />
        <div className="comments">
          {renderComments()}
        </div>
      </div>

      {showDeleteModal && (
        <div className="modal show d-block" tabIndex="-1">
          <div className="modal-dialog">
            <div className="modal-content">
              <div className="modal-header">
                <h5 className="modal-title">게시글 삭제</h5>
                <button 
                  type="button" 
                  className="btn-close"
                  onClick={() => setShowDeleteModal(false)}
                ></button>
              </div>
              <div className="modal-body">
                <p>비밀번호를 입력하세요:</p>
                <input
                  type="password"
                  className="form-control"
                  value={deletePassword}
                  onChange={(e) => setDeletePassword(e.target.value)}
                />
                {deleteError && (
                  <div className="text-danger mt-2">{deleteError}</div>
                )}
              </div>
              <div className="modal-footer">
                <button 
                  type="button" 
                  className="btn btn-secondary"
                  onClick={() => setShowDeleteModal(false)}
                >
                  취소
                </button>
                <button 
                  type="button" 
                  className="btn btn-danger"
                  onClick={handleDelete}
                >
                  삭제
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default BoardDetail; 