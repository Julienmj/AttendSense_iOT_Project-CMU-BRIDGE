import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { classesAPI, sessionsAPI, reportsAPI, arduinoService } from '../services/api.js'

export const useAttendanceStore = defineStore('attendance', () => {
  const classes = ref([])
  const sessions = ref([])
  const attendanceRecords = ref([])
  const currentSession = ref(null)
  const detectedDevices = ref([])
  const isDetecting = ref(false)

  // Class Management
  async function loadClasses() {
    try {
      classes.value = await classesAPI.getAll()
    } catch (error) {
      console.error('Failed to load classes:', error)
    }
  }
  
  async function addClass(classData) {
    try {
      const newClass = await classesAPI.create(classData)
      classes.value.push(newClass)
      return newClass
    } catch (error) {
      console.error('Failed to add class:', error)
      throw error
    }
  }

  async function updateClass(id, updates) {
    try {
      const updatedClass = await classesAPI.update(id, updates)
      const index = classes.value.findIndex(c => c.id === id)
      if (index !== -1) {
        classes.value[index] = updatedClass
      }
    } catch (error) {
      console.error('Failed to update class:', error)
      throw error
    }
  }

  async function deleteClass(id) {
    try {
      await classesAPI.delete(id)
      classes.value = classes.value.filter(c => c.id !== id)
    } catch (error) {
      console.error('Failed to delete class:', error)
      throw error
    }
  }

  // Student Management (within classes)
  async function loadClassStudents(classId) {
    try {
      const students = await classesAPI.getStudents(classId)
      const classItem = classes.value.find(c => c.id === classId)
      if (classItem) {
        classItem.students = students
      }
      return students
    } catch (error) {
      console.error('Failed to load class students:', error)
      return []
    }
  }
  
  async function addStudentToClass(classId, studentData) {
    try {
      const newStudent = await classesAPI.addStudent(classId, studentData)
      const classItem = classes.value.find(c => c.id === classId)
      if (classItem) {
        if (!classItem.students) classItem.students = []
        classItem.students.push(newStudent)
      }
      return newStudent
    } catch (error) {
      console.error('Failed to add student:', error)
      throw error
    }
  }

  async function updateStudentInClass(classId, studentId, updates) {
    try {
      const updatedStudent = await classesAPI.updateStudent(classId, studentId, updates)
      const classItem = classes.value.find(c => c.id === classId)
      if (classItem) {
        const studentIndex = classItem.students.findIndex(s => s.id === studentId)
        if (studentIndex !== -1) {
          classItem.students[studentIndex] = updatedStudent
        }
      }
    } catch (error) {
      console.error('Failed to update student:', error)
      throw error
    }
  }

  async function removeStudentFromClass(classId, studentId) {
    try {
      await classesAPI.removeStudent(classId, studentId)
      const classItem = classes.value.find(c => c.id === classId)
      if (classItem) {
        classItem.students = classItem.students.filter(s => s.id !== studentId)
      }
    } catch (error) {
      console.error('Failed to remove student:', error)
      throw error
    }
  }

  function getStudentByMacAddress(macAddress) {
    for (const classItem of classes.value) {
      const student = classItem.students.find(s => s.macAddress.toLowerCase() === macAddress.toLowerCase())
      if (student) return student
    }
    return null
  }

  function getClassStudents(classId) {
    const classItem = classes.value.find(c => c.id === classId)
    return classItem ? classItem.students : []
  }

  // Session Management
  async function loadSessions() {
    try {
      sessions.value = await sessionsAPI.getAll()
    } catch (error) {
      console.error('Failed to load sessions:', error)
    }
  }
  
  async function createSession(sessionData) {
    try {
      const session = await sessionsAPI.create(sessionData)
      sessions.value.push(session)
      return session
    } catch (error) {
      console.error('Failed to create session:', error)
      throw error
    }
  }

  async function startSession(sessionId) {
    try {
      await sessionsAPI.start(sessionId)
      const session = sessions.value.find(s => s.id === sessionId)
      if (session) {
        session.status = 'active'
        session.startTime = new Date().toISOString()
        currentSession.value = session
        startDetection()
      }
    } catch (error) {
      console.error('Failed to start session:', error)
      throw error
    }
  }

  async function endSession(sessionId) {
    try {
      await sessionsAPI.end(sessionId)
      const session = sessions.value.find(s => s.id === sessionId)
      if (session) {
        session.status = 'completed'
        session.endTime = new Date().toISOString()
        
        // Load attendance records to update counts
        await loadSessionAttendance(sessionId)
        
        if (currentSession.value?.id === sessionId) {
          currentSession.value = null
          stopDetection()
        }
      }
    } catch (error) {
      console.error('Failed to end session:', error)
      throw error
    }
  }

  // Bluetooth Detection
  function startDetection() {
    isDetecting.value = true
    // Simulate Bluetooth detection - replace with actual Arduino serial communication
    const detectionInterval = setInterval(() => {
      if (!isDetecting.value) {
        clearInterval(detectionInterval)
        return
      }
      simulateBluetoothDetection()
    }, 3000)
  }

  function stopDetection() {
    isDetecting.value = false
    detectedDevices.value = []
  }

  function simulateBluetoothDetection() {
    if (!currentSession.value) return
    
    // Use Arduino service for simulation
    arduinoService.simulateDetection(currentSession.value.id)
      .then(() => {
        // Refresh attendance data
        loadSessionAttendance(currentSession.value.id)
      })
      .catch(error => {
        console.error('Simulation failed:', error)
      })
  }

  async function processDetectedDevice(macAddress, rssi) {
    if (!currentSession.value || !isDetecting.value) return
    
    try {
      await sessionsAPI.processDetectedDevice(currentSession.value.id, {
        mac_address: macAddress,
        rssi: rssi
      })
      
      // Refresh attendance data
      await loadSessionAttendance(currentSession.value.id)
    } catch (error) {
      console.error('Failed to process detected device:', error)
    }
  }

  // Attendance Management
  async function loadSessionAttendance(sessionId) {
    try {
      const records = await sessionsAPI.getAttendance(sessionId)
      
      // Update attendance records for this session
      attendanceRecords.value = attendanceRecords.value.filter(r => r.sessionId !== sessionId)
      attendanceRecords.value.push(...records)
      
      // Update session present count
      const session = sessions.value.find(s => s.id === sessionId)
      if (session) {
        session.presentCount = records.filter(r => r.status === 'present').length
      }
      
      return records
    } catch (error) {
      console.error('Failed to load attendance:', error)
      return []
    }
  }
  
  async function markAttendance(sessionId, studentId, status) {
    try {
      await sessionsAPI.markAttendance(sessionId, { studentId, status })
      await loadSessionAttendance(sessionId)
    } catch (error) {
      console.error('Failed to mark attendance:', error)
      throw error
    }
  }

  // Computed Properties
  const activeSessions = computed(() => 
    sessions.value.filter(s => s.status === 'active')
  )

  const todaySessions = computed(() => {
    const today = new Date().toDateString()
    return sessions.value.filter(s => 
      new Date(s.date).toDateString() === today
    )
  })

  const totalStudents = computed(() => 
    classes.value.reduce((sum, cls) => sum + (cls.students?.length || 0), 0)
  )

  const getAttendanceBySession = computed(() => {
    return (sessionId) => {
      return attendanceRecords.value.filter(r => r.sessionId === sessionId)
        .map(record => {
          const student = getStudentByMacAddress(record.studentId)
          return {
            ...record,
            studentName: student?.name || 'Unknown',
            studentId: student?.studentId || 'Unknown',
            macAddress: student?.macAddress || 'Unknown'
          }
        })
    }
  })

  const getStudentAttendanceHistory = computed(() => {
    return (studentId) => {
      return attendanceRecords.value
        .filter(r => r.studentId === studentId)
        .map(record => {
          const session = sessions.value.find(s => s.id === record.sessionId)
          const classItem = classes.value.find(c => c.id === session?.classId)
          return {
            ...record,
            className: classItem?.name || 'Unknown',
            sessionDate: session?.date || 'Unknown'
          }
        })
        .sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp))
    }
  })

  const getClassPerformance = computed(() => {
    return classes.value.map(cls => {
      const classSessions = sessions.value.filter(s => s.classId === cls.id)
      const totalSessions = classSessions.length
      const totalPresent = classSessions.reduce((sum, s) => sum + s.presentCount, 0)
      const totalPossible = cls.students.length * totalSessions
      const rate = totalPossible > 0 ? Math.round((totalPresent / totalPossible) * 100) : 0
      
      return {
        ...cls,
        totalSessions,
        totalPresent,
        totalPossible,
        rate
      }
    })
  })

  return {
    // State
    classes,
    sessions,
    attendanceRecords,
    currentSession,
    detectedDevices,
    isDetecting,
    
    // Class Actions
    loadClasses,
    addClass,
    updateClass,
    deleteClass,
    
    // Student Actions
    loadClassStudents,
    addStudentToClass,
    updateStudentInClass,
    removeStudentFromClass,
    getStudentByMacAddress,
    getClassStudents,
    
    // Session Actions
    loadSessions,
    createSession,
    startSession,
    endSession,
    
    // Detection Actions
    startDetection,
    stopDetection,
    processDetectedDevice,
    
    // Attendance Actions
    markAttendance,
    
    // Computed
    activeSessions,
    todaySessions,
    totalStudents,
    getAttendanceBySession,
    getStudentAttendanceHistory,
    getClassPerformance
  }
})
