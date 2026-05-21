import AsyncStorage from '@react-native-async-storage/async-storage';

const API_URL = (process.env.EXPO_PUBLIC_API_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const TOKEN_KEY = 'furnistyle_jwt';

export function getApiBaseUrl() {
  return API_URL;
}

export async function getToken() {
  return AsyncStorage.getItem(TOKEN_KEY);
}

export async function setToken(token) {
  if (token) {
    await AsyncStorage.setItem(TOKEN_KEY, token);
  } else {
    await AsyncStorage.removeItem(TOKEN_KEY);
  }
}

export async function apiRequest(path, { method = 'GET', body, auth = false } = {}) {
  const headers = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  };

  if (auth) {
    const token = await getToken();
    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }
  }

  const response = await fetch(`${API_URL}${path}`, {
    method,
    headers,
    body: body ? JSON.stringify(body) : undefined,
  });

  let data = null;
  const text = await response.text();
  if (text) {
    try {
      data = JSON.parse(text);
    } catch {
      data = { message: text };
    }
  }

  if (!response.ok) {
    const error = new Error(data?.message || `Request failed (${response.status})`);
    error.status = response.status;
    error.data = data;
    throw error;
  }

  return data;
}

export async function login(email, password) {
  const data = await apiRequest('/api/customer/login', {
    method: 'POST',
    body: { email, password },
  });
  if (data?.token) {
    await setToken(data.token);
  }
  return data;
}

export async function register(payload) {
  return apiRequest('/api/customer/register', {
    method: 'POST',
    body: payload,
  });
}

export async function fetchProducts(params = {}) {
  const query = new URLSearchParams();
  if (params.category) query.set('category', String(params.category));
  if (params.q) query.set('q', params.q);
  const qs = query.toString();
  return apiRequest(`/api/customer/products${qs ? `?${qs}` : ''}`);
}

export async function fetchProduct(id) {
  return apiRequest(`/api/customer/products/${id}`);
}

export async function fetchCategories() {
  return apiRequest('/api/customer/categories');
}

export async function fetchMe() {
  return apiRequest('/api/customer/me', { auth: true });
}

export async function updateProfile(payload) {
  return apiRequest('/api/customer/me', {
    method: 'PATCH',
    auth: true,
    body: payload,
  });
}

export async function changePassword(payload) {
  return apiRequest('/api/customer/change-password', {
    method: 'POST',
    auth: true,
    body: payload,
  });
}

export async function sendContact(payload) {
  return apiRequest('/api/customer/contact', {
    method: 'POST',
    body: payload,
  });
}

export async function logout() {
  await setToken(null);
}
