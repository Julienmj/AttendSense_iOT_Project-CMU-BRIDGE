<?php
/**
 * Frontend Integration Helper
 * Update your Vue.js Pinia store to use the PHP MySQL backend
 */

// src/services/api.js
const API_BASE_URL = 'http://localhost/attendsense/api';

// Helper function for API requests
const apiRequest = async (endpoint, options = {}) => {
  const url = `${API_BASE_URL}${endpoint}`;
  const config = {
    headers: {
      'Content-Type': 'application/json',
      ...options.headers,
    },
    ...options,
  };

  try {
    const response = await fetch(url, config);
    const data = await response.json();
    
    if (!data.success) {
      throw new Error(data.error || 'API request failed');
    }
    
    return data.data;
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
};

// Classes API
export const classesAPI = {
  getAll: () => apiRequest('/classes'),
  getById: (id) => apiRequest(`/classes/${id}`),
  create: (data) => apiRequest('/classes', {
    method: 'POST',
    body: JSON.stringify(data),
  }),
  update: (id, data) => apiRequest(`/classes/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  }),
  delete: (id) => apiRequest(`/classes/${id}`, {
    method: 'DELETE',
  }),
  getStudents: (id) => apiRequest(`/classes/${id}/students`),
  addStudent: (id, data) => apiRequest(`/classes/${id}/students`, {
    method: 'POST',
    body: JSON.stringify(data),
  }),
  updateStudent: (classId, studentId, data) => apiRequest(`/classes/${classId}/students/${studentId}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  }),
  removeStudent: (classId, studentId) => apiRequest(`/classes/${classId}/students/${studentId}`, {
    method: 'DELETE',
  }),
};

// Sessions API
export const sessionsAPI = {
  getAll: () => apiRequest('/sessions'),
  getById: (id) => apiRequest(`/sessions/${id}`),
  create: (data) => apiRequest('/sessions', {
    method: 'POST',
    body: JSON.stringify(data),
  }),
  update: (id, data) => apiRequest(`/sessions/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  }),
  start: (id) => apiRequest(`/sessions/${id}/start`, {
    method: 'PUT',
  }),
  end: (id) => apiRequest(`/sessions/${id}/end`, {
    method: 'PUT',
  }),
  getAttendance: (id) => apiRequest(`/sessions/${id}/attendance`),
  markAttendance: (id, data) => apiRequest(`/sessions/${id}/attendance`, {
    method: 'POST',
    body: JSON.stringify(data),
  }),
  processDetectedDevice: (id, data) => apiRequest(`/sessions/${id}/detect`, {
    method: 'POST',
    body: JSON.stringify(data),
  }),
  getActive: () => apiRequest('/sessions/active'),
  getToday: () => apiRequest('/sessions/today'),
};

// Reports API
export const reportsAPI = {
  getAttendance: (params = {}) => {
    const queryString = new URLSearchParams(params).toString();
    return apiRequest(`/reports/attendance?${queryString}`);
  },
  getClassPerformance: () => apiRequest('/reports/class-performance'),
  getStudentAttendance: (studentId) => apiRequest(`/reports/student-attendance/${studentId}`),
  getSummary: () => apiRequest('/reports/summary'),
  exportSession: (sessionId, format = 'csv') => 
    apiRequest(`/reports/export/sessions/${sessionId}?format=${format}`),
  exportClass: (classId, params = {}) => {
    const queryString = new URLSearchParams({ format: 'csv', ...params }).toString();
    return apiRequest(`/reports/export/class/${classId}?${queryString}`);
  },
  getDashboardStats: () => apiRequest('/reports/dashboard-stats'),
};

// WebSocket for real-time updates (simplified)
export const websocketService = {
  connect: (url = 'ws://localhost:8080') => {
    const ws = new WebSocket(url);
    
    ws.onopen = () => {
      console.log('WebSocket connected');
    };
    
    ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      console.log('WebSocket message:', data);
      
      // Dispatch custom event for Vue components to listen to
      window.dispatchEvent(new CustomEvent('attendance-update', { detail: data }));
    };
    
    ws.onerror = (error) => {
      console.error('WebSocket error:', error);
    };
    
    ws.onclose = () => {
      console.log('WebSocket disconnected');
      // Attempt to reconnect after 5 seconds
      setTimeout(() => websocketService.connect(url), 5000);
    };
    
    return ws;
  },
};

// Arduino communication service
export const arduinoService = {
  // Simulate Arduino device detection for testing
  simulateDetection: (sessionId) => {
    const mockDevices = [
      'A4:C1:38:2B:5E:9F',
      'B5:D2:49:3C:6F:0A',
      'C6:E3:5A:4D:7G:1B',
      'D7:F4:6B:5E:8H:2C'
    ];
    
    const randomDevice = mockDevices[Math.floor(Math.random() * mockDevices.length)];
    const rssi = Math.floor(Math.random() * (-30 - -80) + -80);
    
    return sessionsAPI.processDetectedDevice(sessionId, {
      mac_address: randomDevice,
      rssi: rssi
    });
  },
  
  // Start real Arduino scanning (requires backend service running)
  startScanning: () => {
    // This would trigger the Arduino service via WebSocket or API
    console.log('Starting Arduino scanning...');
  },
  
  stopScanning: () => {
    console.log('Stopping Arduino scanning...');
  },
};

export default {
  classesAPI,
  sessionsAPI,
  reportsAPI,
  websocketService,
  arduinoService,
};
?>
