import authService from './auth';

const api = authService.api;

// 게시글 목록 조회
const getBoards = async (boardType = 'bbs1', params = {}) => {
  try {
    const queryParams = new URLSearchParams();
    if (params.page) queryParams.append('page', params.page);
    if (params.keyword) queryParams.append('keyword', params.keyword);
    if (params.searchType) queryParams.append('search_type', params.searchType);

    const response = await api.get(`/board/${boardType}?${queryParams.toString()}`);
    return response.data;
  } catch (error) {
    console.error('Error fetching boards:', error);
    throw error.response?.data || { message: '게시글 목록을 불러오는 중 오류가 발생했습니다.' };
  }
};

// 게시글 상세 조회
const getBoard = async (boardType, id) => {
  try {
    const response = await api.get(`/board/${boardType}/${id}`);
    return response.data;
  } catch (error) {
    console.error('Error fetching board:', error);
    throw error.response?.data || { message: '게시글을 불러오는 중 오류가 발생했습니다.' };
  }
};

// 게시글 작성
const createBoard = async (boardType, formData) => {
  try {
    const response = await api.post(`/board/${boardType}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  } catch (error) {
    console.error('Error creating board:', error);
    throw error.response?.data || { message: '게시글 작성 중 오류가 발생했습니다.' };
  }
};

// 게시글 수정
const updateBoard = async (boardType, id, formData) => {
  try {
    formData.append('_method', 'PUT');
    const response = await api.post(`/board/${boardType}/${id}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  } catch (error) {
    console.error('Error updating board:', error);
    throw error.response?.data || { message: '게시글 수정 중 오류가 발생했습니다.' };
  }
};

// 게시글 삭제
const deleteBoard = async (boardType, id) => {
  try {
    const response = await api.delete(`/board/${boardType}/${id}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting board:', error);
    throw error.response?.data || { message: '게시글 삭제 중 오류가 발생했습니다.' };
  }
};

const getRelatedThreads = async (boardType, id) => {
  try {
    const response = await api.get(`/board/${boardType}/threads/${id}`);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: '연관 글을 불러오는데 실패했습니다.' };
  }
};

const boardService = {
  getBoards,
  getBoard,
  createBoard,
  updateBoard,
  deleteBoard,
  getRelatedThreads,
};

export default boardService; 