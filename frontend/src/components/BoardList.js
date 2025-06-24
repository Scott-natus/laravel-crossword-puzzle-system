import React, { useState, useEffect } from 'react';
import { Link, useParams, useNavigate, useLocation } from 'react-router-dom';
import boardService from '../services/board';

const BoardList = () => {
  const [boardData, setBoardData] = useState({
    data: [],
    current_page: 1,
    last_page: 1,
    total: 0
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [searchParams, setSearchParams] = useState({
    keyword: '',
    searchType: 'title' // 'title', 'writer', 'content'
  });
  const { boardType } = useParams();
  const location = useLocation();
  const navigate = useNavigate();

  // URL에서 현재 페이지 번호와 검색 파라미터 가져오기
  const queryParams = new URLSearchParams(location.search);
  const currentPage = parseInt(queryParams.get('page')) || 1;
  const keyword = queryParams.get('keyword') || '';
  const searchType = queryParams.get('searchType') || 'title';

  useEffect(() => {
    setSearchParams({
      keyword,
      searchType
    });
  }, [keyword, searchType]);

  useEffect(() => {
    const fetchBoards = async () => {
      try {
        const data = await boardService.getBoards(boardType, {
          page: currentPage,
          keyword: searchParams.keyword,
          searchType: searchParams.searchType
        });
        setBoardData(data);
        setLoading(false);
      } catch (err) {
        setError(err.message);
        setLoading(false);
      }
    };

    fetchBoards();
  }, [boardType, currentPage, searchParams]);

  const handlePageChange = (page) => {
    const params = new URLSearchParams(location.search);
    params.set('page', page);
    navigate(`/board/${boardType}?${params.toString()}`);
  };

  const handleSearch = (e) => {
    e.preventDefault();
    const params = new URLSearchParams(location.search);
    params.set('page', 1); // 검색 시 첫 페이지로 이동
    params.set('keyword', searchParams.keyword);
    params.set('searchType', searchParams.searchType);
    navigate(`/board/${boardType}?${params.toString()}`);
  };

  const handleSearchChange = (e) => {
    const { name, value } = e.target;
    setSearchParams(prev => ({
      ...prev,
      [name]: value
    }));
  };

  // 계층형(트리) 렌더링 함수
  const renderThread = (board, depth = 0) => {
    if (depth > 20) return []; // 최대 20뎁스 제한
    
    const elements = [
      <tr key={board.id} className={depth > 0 ? 'reply-row' : ''}>
        <td>{board.id}</td>
        <td style={{ paddingLeft: `${depth * 24}px` }}>
          {depth > 0 && (
            <span style={{ 
              marginRight: 6, 
              color: '#666',
              fontSize: '1.2em',
              fontWeight: 'bold'
            }}>↳</span>
          )}
          <Link 
            to={`/board/${boardType}/${board.id}`}
            style={{
              color: depth > 0 ? '#666' : '#000',
              textDecoration: 'none'
            }}
          >
            {board.title}
          </Link>
        </td>
        <td>{board.user?.name}</td>
        <td>{new Date(board.created_at).toLocaleDateString()}</td>
        <td>{board.views}</td>
      </tr>
    ];

    if (Array.isArray(board.children)) {
      elements.push(...board.children.map(child => renderThread(child, depth + 1)).flat());
    }

    return elements;
  };

  // 트리구조로 변환된 데이터가 아니라면, 평탄화된 배열에서 parent-child 관계를 트리로 변환
  const buildTree = (flatList) => {
    const idMap = {};
    const roots = [];
    flatList.forEach(item => {
      idMap[item.id] = { ...item, children: [] };
    });
    flatList.forEach(item => {
      if (item.parent_id && idMap[item.parent_id]) {
        idMap[item.parent_id].children.push(idMap[item.id]);
      } else {
        roots.push(idMap[item.id]);
      }
    });
    return roots;
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div className="alert alert-danger">{error}</div>;

  // 트리 데이터 생성
  const treeData = buildTree(boardData.data);

  return (
    <div className="board-list">
      <div className="d-flex justify-content-between align-items-center mb-3">
        <h2>게시글 목록</h2>
        <Link to={`/board/${boardType}/create`} className="btn btn-primary">
          글쓰기
        </Link>
      </div>

      {/* 검색 폼 */}
      <div className="card mb-4">
        <div className="card-body">
          <form onSubmit={handleSearch} className="row g-3">
            <div className="col-md-3">
              <select
                name="searchType"
                value={searchParams.searchType}
                onChange={handleSearchChange}
                className="form-select"
              >
                <option value="title">제목</option>
                <option value="writer">작성자</option>
                <option value="content">내용</option>
              </select>
            </div>
            <div className="col-md-7">
              <input
                type="text"
                name="keyword"
                value={searchParams.keyword}
                onChange={handleSearchChange}
                className="form-control"
                placeholder="검색어를 입력하세요"
              />
            </div>
            <div className="col-md-2">
              <button type="submit" className="btn btn-primary w-100">
                검색
              </button>
            </div>
          </form>
        </div>
      </div>

      <table className="table table-hover">
        <thead className="table-light">
          <tr>
            <th style={{ width: '10%' }}>번호</th>
            <th style={{ width: '50%' }}>제목</th>
            <th style={{ width: '15%' }}>작성자</th>
            <th style={{ width: '15%' }}>작성일</th>
            <th style={{ width: '10%' }}>조회수</th>
          </tr>
        </thead>
        <tbody>
          {treeData.map(board => renderThread(board))}
        </tbody>
      </table>

      <nav>
        <ul className="pagination justify-content-center">
          {boardData.current_page > 1 && (
            <li className="page-item">
              <button
                className="page-link"
                onClick={() => handlePageChange(boardData.current_page - 1)}
              >
                이전
              </button>
            </li>
          )}
          {[...Array(boardData.last_page)].map((_, index) => (
            <li
              key={index + 1}
              className={`page-item ${boardData.current_page === index + 1 ? 'active' : ''}`}
            >
              <button
                className="page-link"
                onClick={() => handlePageChange(index + 1)}
              >
                {index + 1}
              </button>
            </li>
          ))}
          {boardData.current_page < boardData.last_page && (
            <li className="page-item">
              <button
                className="page-link"
                onClick={() => handlePageChange(boardData.current_page + 1)}
              >
                다음
              </button>
            </li>
          )}
        </ul>
      </nav>
    </div>
  );
};

export default BoardList; 