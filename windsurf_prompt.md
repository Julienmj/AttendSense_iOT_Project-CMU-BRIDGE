# Indoor Room Classifier — Full Frontend Project Prompt for Windsurf

## Project Overview
Build a full production-grade web dashboard called **"RoomSense"** — an Indoor BLE Room Classifier dashboard. The app simulates and visualizes an Arduino Nano 33 BLE scanning Bluetooth signals, collecting labeled room data, and classifying rooms in real time using a trained ML model. Built with **Vue 3 + Vite + Tailwind CSS**.

---

## Tech Stack
- **Framework**: Vue 3 (Composition API with `<script setup>`)
- **Build Tool**: Vite
- **Styling**: Tailwind CSS v3
- **Charts**: Chart.js with vue-chartjs
- **Icons**: Lucide Vue Next
- **Routing**: Vue Router 4
- **State**: Pinia
- **Fonts**: Google Fonts — "Space Mono" for headings, "DM Sans" for body
- **Color Theme**: Dark theme — deep navy `#0a0f1e` background, cyan `#00f5d4` accent, soft white text

---

## Project Structure

```
roomsense/
├── index.html
├── vite.config.js
├── tailwind.config.js
├── postcss.config.js
├── package.json
└── src/
    ├── main.js
    ├── App.vue
    ├── router/
    │   └── index.js
    ├── stores/
    │   └── ble.js
    ├── views/
    │   ├── Dashboard.vue
    │   ├── Collect.vue
    │   ├── Train.vue
    │   └── Predict.vue
    └── components/
        ├── Navbar.vue
        ├── SignalCard.vue
        ├── RoomBadge.vue
        ├── DeviceList.vue
        ├── RSSIChart.vue
        └── RoomFingerprint.vue
```

---

## package.json

```json
{
  "name": "roomsense",
  "version": "1.0.0",
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview"
  },
  "dependencies": {
    "vue": "^3.4.0",
    "vue-router": "^4.3.0",
    "pinia": "^2.1.7",
    "chart.js": "^4.4.0",
    "vue-chartjs": "^5.3.0",
    "lucide-vue-next": "^0.378.0"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.0.0",
    "vite": "^5.0.0",
    "tailwindcss": "^3.4.0",
    "autoprefixer": "^10.4.0",
    "postcss": "^8.4.0"
  }
}
```

---

## vite.config.js

```js
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()]
})
```

---

## tailwind.config.js

```js
/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js}'],
  theme: {
    extend: {
      colors: {
        navy: '#0a0f1e',
        'navy-light': '#111827',
        cyan: '#00f5d4',
        'cyan-dim': '#00c4aa',
        signal: '#f72585',
      },
      fontFamily: {
        mono: ['"Space Mono"', 'monospace'],
        sans: ['"DM Sans"', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
```

---

## postcss.config.js

```js
export default {
  plugins: {
    tailwindcss: {},
    autoprefixer: {},
  },
}
```

---

## index.html

```html
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>RoomSense — BLE Room Classifier</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
  </head>
  <body class="bg-navy text-white font-sans">
    <div id="app"></div>
    <script type="module" src="/src/main.js"></script>
  </body>
</html>
```

---

## src/main.js

```js
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import './style.css'

createApp(App).use(createPinia()).use(router).mount('#app')
```

---

## src/style.css

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

* {
  box-sizing: border-box;
}

body {
  background-color: #0a0f1e;
  color: #f0f4ff;
  font-family: 'DM Sans', sans-serif;
}

::-webkit-scrollbar {
  width: 6px;
}
::-webkit-scrollbar-track {
  background: #0a0f1e;
}
::-webkit-scrollbar-thumb {
  background: #00f5d4;
  border-radius: 3px;
}

.glow {
  box-shadow: 0 0 20px rgba(0, 245, 212, 0.3);
}

.pulse-dot {
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.4; transform: scale(0.85); }
}

.fade-in {
  animation: fadeIn 0.5s ease forwards;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(12px); }
  to { opacity: 1; transform: translateY(0); }
}
```

---

## src/router/index.js

```js
import { createRouter, createWebHistory } from 'vue-router'
import Dashboard from '../views/Dashboard.vue'
import Collect from '../views/Collect.vue'
import Train from '../views/Train.vue'
import Predict from '../views/Predict.vue'

export default createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: Dashboard },
    { path: '/collect', component: Collect },
    { path: '/train', component: Train },
    { path: '/predict', component: Predict },
  ],
})
```

---

## src/stores/ble.js

```js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useBleStore = defineStore('ble', () => {
  const devices = ref([])
  const collectedData = ref([])
  const currentRoom = ref('')
  const isScanning = ref(false)
  const modelTrained = ref(false)
  const predictedRoom = ref(null)
  const rooms = ref(['Living Room', 'Bedroom', 'Kitchen', 'Bathroom', 'Office'])

  // Simulate BLE scan — replace with real serial/WebBluetooth data
  function simulateScan() {
    const mockAddresses = [
      '63:bf:10:e7:1b:6f',
      'c4:c8:6b:c3:a6:a9',
      'fa:e3:e7:90:9e:8a',
      '70:5c:0a:56:a1:05',
      'ef:12:da:da:1c:b8',
      '57:96:45:f0:d9:0d',
    ]
    devices.value = mockAddresses.map((addr) => ({
      address: addr,
      rssi: Math.floor(Math.random() * (-40 - -90) + -40),
      lastSeen: new Date().toLocaleTimeString(),
    }))
  }

  function startScanning() {
    isScanning.value = true
    simulateScan()
    const interval = setInterval(() => {
      if (!isScanning.value) { clearInterval(interval); return }
      simulateScan()
    }, 2000)
  }

  function stopScanning() {
    isScanning.value = false
  }

  function collectSample(roomLabel) {
    if (!devices.value.length) return
    const sample = {
      room: roomLabel,
      timestamp: new Date().toISOString(),
      signals: devices.value.map(d => ({ address: d.address, rssi: d.rssi })),
    }
    collectedData.value.push(sample)
  }

  function trainModel() {
    // Placeholder — in real project send data to ML backend or Edge Impulse
    modelTrained.value = true
  }

  function predict() {
    // Placeholder — simulate prediction from trained model
    if (!modelTrained.value) return
    const idx = Math.floor(Math.random() * rooms.value.length)
    predictedRoom.value = rooms.value[idx]
  }

  const samplesByRoom = computed(() => {
    return rooms.value.map(room => ({
      room,
      count: collectedData.value.filter(d => d.room === room).length,
    }))
  })

  return {
    devices, collectedData, currentRoom, isScanning,
    modelTrained, predictedRoom, rooms, samplesByRoom,
    startScanning, stopScanning, collectSample, trainModel, predict,
  }
})
```

---

## src/App.vue

```vue
<template>
  <div class="min-h-screen bg-navy font-sans">
    <Navbar />
    <main class="max-w-6xl mx-auto px-6 py-10">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import Navbar from './components/Navbar.vue'
</script>
```

---

## src/components/Navbar.vue

```vue
<template>
  <nav class="border-b border-white/10 bg-navy/90 backdrop-blur sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-2.5 h-2.5 rounded-full bg-cyan pulse-dot"></div>
        <span class="font-mono text-cyan text-lg font-bold tracking-widest">ROOMSENSE</span>
      </div>
      <div class="flex gap-1">
        <RouterLink
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 text-white/60 hover:text-white hover:bg-white/5"
          active-class="text-cyan bg-cyan/10 border border-cyan/30"
        >
          {{ link.label }}
        </RouterLink>
      </div>
    </div>
  </nav>
</template>

<script setup>
const links = [
  { to: '/', label: 'Dashboard' },
  { to: '/collect', label: 'Collect Data' },
  { to: '/train', label: 'Train Model' },
  { to: '/predict', label: 'Predict' },
]
</script>
```

---

## src/components/SignalCard.vue

```vue
<template>
  <div class="bg-navy-light border border-white/10 rounded-2xl p-5 hover:border-cyan/40 transition-all duration-300 fade-in">
    <div class="flex items-center justify-between mb-3">
      <span class="font-mono text-xs text-white/40 tracking-wider">{{ device.address }}</span>
      <span class="text-xs px-2 py-0.5 rounded-full font-mono"
        :class="rssiColor">
        {{ device.rssi }} dBm
      </span>
    </div>
    <div class="w-full bg-white/5 rounded-full h-1.5 mt-2">
      <div
        class="h-1.5 rounded-full transition-all duration-700"
        :class="barColor"
        :style="{ width: barWidth }"
      ></div>
    </div>
    <div class="mt-2 flex justify-between text-xs text-white/30">
      <span>{{ strengthLabel }}</span>
      <span>{{ device.lastSeen }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ device: Object })

const rssiColor = computed(() => {
  const r = props.device.rssi
  if (r >= -55) return 'bg-cyan/20 text-cyan'
  if (r >= -70) return 'bg-yellow-400/20 text-yellow-400'
  return 'bg-signal/20 text-signal'
})

const barColor = computed(() => {
  const r = props.device.rssi
  if (r >= -55) return 'bg-cyan'
  if (r >= -70) return 'bg-yellow-400'
  return 'bg-signal'
})

const barWidth = computed(() => {
  const r = props.device.rssi
  const percent = Math.max(0, Math.min(100, ((r + 100) / 60) * 100))
  return percent + '%'
})

const strengthLabel = computed(() => {
  const r = props.device.rssi
  if (r >= -55) return 'Strong — very close'
  if (r >= -70) return 'Medium — nearby'
  return 'Weak — far away'
})
</script>
```

---

## src/components/RSSIChart.vue

```vue
<template>
  <div class="bg-navy-light border border-white/10 rounded-2xl p-5">
    <h3 class="font-mono text-cyan text-xs tracking-widest mb-4">SIGNAL STRENGTH CHART</h3>
    <Bar :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, BarElement, CategoryScale, LinearScale, Tooltip } from 'chart.js'
ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip)

const props = defineProps({ devices: Array })

const chartData = computed(() => ({
  labels: props.devices.map(d => d.address.slice(-8)),
  datasets: [{
    label: 'RSSI (dBm)',
    data: props.devices.map(d => d.rssi),
    backgroundColor: props.devices.map(d =>
      d.rssi >= -55 ? 'rgba(0,245,212,0.7)' :
      d.rssi >= -70 ? 'rgba(250,204,21,0.7)' :
      'rgba(247,37,133,0.7)'
    ),
    borderRadius: 6,
  }]
}))

const chartOptions = {
  responsive: true,
  plugins: { legend: { display: false } },
  scales: {
    x: { ticks: { color: '#ffffff60', font: { family: 'Space Mono', size: 10 } }, grid: { color: '#ffffff10' } },
    y: { ticks: { color: '#ffffff60' }, grid: { color: '#ffffff10' }, min: -100, max: -30 }
  }
}
</script>
```

---

## src/components/DeviceList.vue

```vue
<template>
  <div>
    <div v-if="devices.length === 0" class="text-center text-white/30 py-10 font-mono text-sm">
      No devices detected yet...
    </div>
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <SignalCard v-for="device in devices" :key="device.address" :device="device" />
    </div>
  </div>
</template>

<script setup>
import SignalCard from './SignalCard.vue'
defineProps({ devices: Array })
</script>
```

---

## src/views/Dashboard.vue

```vue
<template>
  <div class="fade-in">
    <!-- Header -->
    <div class="mb-10">
      <p class="font-mono text-cyan text-xs tracking-widest mb-2">ARDUINO NANO 33 BLE</p>
      <h1 class="text-4xl font-bold text-white leading-tight">Indoor Room <br/><span class="text-cyan">Classifier</span></h1>
      <p class="text-white/50 mt-3 max-w-lg text-sm leading-relaxed">
        Scan nearby Bluetooth devices, collect room fingerprints, train a machine learning model, and predict your room in real time.
      </p>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      <div v-for="stat in stats" :key="stat.label"
        class="bg-navy-light border border-white/10 rounded-2xl p-5 hover:border-cyan/30 transition-all">
        <p class="font-mono text-xs text-white/40 tracking-wider mb-1">{{ stat.label }}</p>
        <p class="text-3xl font-bold" :class="stat.color">{{ stat.value }}</p>
      </div>
    </div>

    <!-- Scanning Section -->
    <div class="bg-navy-light border border-white/10 rounded-2xl p-6 mb-6">
      <div class="flex items-center justify-between mb-5">
        <div class="flex items-center gap-3">
          <div class="w-2 h-2 rounded-full" :class="store.isScanning ? 'bg-cyan pulse-dot' : 'bg-white/20'"></div>
          <h2 class="font-mono text-sm tracking-wider text-white">
            {{ store.isScanning ? 'SCANNING...' : 'SCANNER IDLE' }}
          </h2>
        </div>
        <button
          @click="toggleScan"
          class="px-5 py-2 rounded-xl text-sm font-medium transition-all duration-200 font-mono"
          :class="store.isScanning
            ? 'bg-signal/20 text-signal border border-signal/40 hover:bg-signal/30'
            : 'bg-cyan/20 text-cyan border border-cyan/40 hover:bg-cyan/30 glow'"
        >
          {{ store.isScanning ? 'STOP SCAN' : 'START SCAN' }}
        </button>
      </div>
      <DeviceList :devices="store.devices" />
    </div>

    <!-- Chart -->
    <RSSIChart v-if="store.devices.length" :devices="store.devices" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useBleStore } from '../stores/ble'
import DeviceList from '../components/DeviceList.vue'
import RSSIChart from '../components/RSSIChart.vue'

const store = useBleStore()

function toggleScan() {
  store.isScanning ? store.stopScanning() : store.startScanning()
}

const stats = computed(() => [
  { label: 'DEVICES FOUND', value: store.devices.length, color: 'text-cyan' },
  { label: 'SAMPLES COLLECTED', value: store.collectedData.length, color: 'text-white' },
  { label: 'ROOMS DEFINED', value: store.rooms.length, color: 'text-white' },
  { label: 'MODEL STATUS', value: store.modelTrained ? 'READY' : 'UNTRAINED', color: store.modelTrained ? 'text-cyan' : 'text-signal' },
])
</script>
```

---

## src/views/Collect.vue

```vue
<template>
  <div class="fade-in">
    <div class="mb-8">
      <p class="font-mono text-cyan text-xs tracking-widest mb-2">STEP 1</p>
      <h1 class="text-3xl font-bold text-white">Collect Room Data</h1>
      <p class="text-white/50 mt-2 text-sm">
        Go to each room, start scanning, then click "Collect Sample" to label that room's BLE fingerprint.
      </p>
    </div>

    <!-- Room Selector -->
    <div class="bg-navy-light border border-white/10 rounded-2xl p-6 mb-6">
      <h2 class="font-mono text-xs text-white/40 tracking-wider mb-4">SELECT CURRENT ROOM</h2>
      <div class="flex flex-wrap gap-3">
        <button
          v-for="room in store.rooms"
          :key="room"
          @click="selectedRoom = room"
          class="px-4 py-2 rounded-xl text-sm font-medium transition-all border"
          :class="selectedRoom === room
            ? 'bg-cyan/20 text-cyan border-cyan/50 glow'
            : 'bg-white/5 text-white/60 border-white/10 hover:border-white/30'"
        >
          {{ room }}
        </button>
      </div>
    </div>

    <!-- Collect Button -->
    <div class="flex gap-4 mb-6">
      <button
        @click="store.isScanning ? store.stopScanning() : store.startScanning()"
        class="px-5 py-3 rounded-xl text-sm font-mono border transition-all"
        :class="store.isScanning ? 'bg-signal/20 text-signal border-signal/40' : 'bg-cyan/20 text-cyan border-cyan/40'"
      >
        {{ store.isScanning ? 'STOP SCAN' : 'START SCAN' }}
      </button>
      <button
        @click="collect"
        :disabled="!selectedRoom || !store.isScanning"
        class="px-5 py-3 rounded-xl text-sm font-mono border transition-all disabled:opacity-30 disabled:cursor-not-allowed bg-white/10 text-white border-white/20 hover:bg-white/20"
      >
        COLLECT SAMPLE
      </button>
    </div>

    <!-- Live devices -->
    <DeviceList :devices="store.devices" />

    <!-- Samples Table -->
    <div class="mt-8 bg-navy-light border border-white/10 rounded-2xl p-6" v-if="store.collectedData.length">
      <h2 class="font-mono text-xs text-white/40 tracking-wider mb-4">COLLECTED SAMPLES</h2>
      <div class="overflow-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-white/10 text-white/40 font-mono text-xs">
              <th class="text-left py-2 pr-4">#</th>
              <th class="text-left py-2 pr-4">ROOM</th>
              <th class="text-left py-2 pr-4">DEVICES</th>
              <th class="text-left py-2">TIME</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(sample, i) in store.collectedData"
              :key="i"
              class="border-b border-white/5 hover:bg-white/5 transition-colors"
            >
              <td class="py-2 pr-4 text-white/30 font-mono text-xs">{{ i + 1 }}</td>
              <td class="py-2 pr-4">
                <span class="px-2 py-0.5 rounded-full bg-cyan/20 text-cyan text-xs font-mono">{{ sample.room }}</span>
              </td>
              <td class="py-2 pr-4 text-white/60">{{ sample.signals.length }} signals</td>
              <td class="py-2 text-white/30 text-xs font-mono">{{ new Date(sample.timestamp).toLocaleTimeString() }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useBleStore } from '../stores/ble'
import DeviceList from '../components/DeviceList.vue'

const store = useBleStore()
const selectedRoom = ref('')

function collect() {
  if (selectedRoom.value) {
    store.collectSample(selectedRoom.value)
  }
}
</script>
```

---

## src/views/Train.vue

```vue
<template>
  <div class="fade-in">
    <div class="mb-8">
      <p class="font-mono text-cyan text-xs tracking-widest mb-2">STEP 2</p>
      <h1 class="text-3xl font-bold text-white">Train the Model</h1>
      <p class="text-white/50 mt-2 text-sm">
        Review your collected data per room, then train the classifier. You need at least 5 samples per room.
      </p>
    </div>

    <!-- Samples per room -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
      <div
        v-for="item in store.samplesByRoom"
        :key="item.room"
        class="bg-navy-light border rounded-2xl p-5 transition-all"
        :class="item.count >= 5 ? 'border-cyan/30' : 'border-white/10'"
      >
        <p class="font-mono text-xs tracking-wider mb-2" :class="item.count >= 5 ? 'text-cyan' : 'text-white/40'">
          {{ item.room.toUpperCase() }}
        </p>
        <p class="text-4xl font-bold" :class="item.count >= 5 ? 'text-cyan' : 'text-white/30'">
          {{ item.count }}
        </p>
        <p class="text-xs text-white/30 mt-1">{{ item.count >= 5 ? '✓ Ready' : `Need ${5 - item.count} more` }}</p>
      </div>
    </div>

    <!-- Train Button -->
    <div class="bg-navy-light border border-white/10 rounded-2xl p-8 text-center">
      <p class="text-white/50 text-sm mb-6">
        Total samples collected: <span class="text-white font-bold">{{ store.collectedData.length }}</span>
      </p>
      <button
        @click="train"
        :disabled="store.collectedData.length < 5"
        class="px-10 py-4 rounded-2xl font-mono text-sm border transition-all duration-300 disabled:opacity-30 disabled:cursor-not-allowed"
        :class="store.modelTrained
          ? 'bg-cyan/20 text-cyan border-cyan/50 glow'
          : 'bg-white/10 text-white border-white/20 hover:bg-white/20'"
      >
        {{ store.modelTrained ? '✓ MODEL TRAINED' : 'TRAIN MODEL' }}
      </button>
      <p v-if="store.modelTrained" class="text-cyan/60 text-xs font-mono mt-4">
        Model is ready — go to Predict tab
      </p>
    </div>
  </div>
</template>

<script setup>
import { useBleStore } from '../stores/ble'
const store = useBleStore()
function train() { store.trainModel() }
</script>
```

---

## src/views/Predict.vue

```vue
<template>
  <div class="fade-in">
    <div class="mb-8">
      <p class="font-mono text-cyan text-xs tracking-widest mb-2">STEP 3</p>
      <h1 class="text-3xl font-bold text-white">Real-Time Prediction</h1>
      <p class="text-white/50 mt-2 text-sm">
        Scan BLE signals in any room and the trained model will predict which room you are in.
      </p>
    </div>

    <div v-if="!store.modelTrained" class="bg-signal/10 border border-signal/30 rounded-2xl p-6 text-center">
      <p class="text-signal font-mono text-sm">Model not trained yet — go to Train tab first</p>
    </div>

    <div v-else>
      <!-- Prediction Box -->
      <div class="bg-navy-light border border-cyan/30 rounded-2xl p-10 text-center mb-6 glow">
        <p class="font-mono text-xs text-white/40 tracking-widest mb-4">PREDICTED ROOM</p>
        <div v-if="store.predictedRoom" class="fade-in">
          <p class="text-6xl font-bold text-cyan mb-2">{{ store.predictedRoom }}</p>
          <p class="text-white/30 text-sm font-mono">Confidence: {{ confidence }}%</p>
        </div>
        <div v-else class="text-white/20 font-mono text-lg">
          Press Predict to classify
        </div>
      </div>

      <!-- Controls -->
      <div class="flex gap-4 justify-center">
        <button
          @click="store.isScanning ? store.stopScanning() : store.startScanning()"
          class="px-6 py-3 rounded-xl font-mono text-sm border transition-all"
          :class="store.isScanning ? 'bg-signal/20 text-signal border-signal/40' : 'bg-white/10 text-white border-white/20'"
        >
          {{ store.isScanning ? 'STOP SCAN' : 'START SCAN' }}
        </button>
        <button
          @click="predict"
          :disabled="!store.isScanning"
          class="px-6 py-3 rounded-xl font-mono text-sm border bg-cyan/20 text-cyan border-cyan/40 hover:bg-cyan/30 transition-all disabled:opacity-30 disabled:cursor-not-allowed glow"
        >
          PREDICT ROOM
        </button>
      </div>

      <!-- Live signals -->
      <div class="mt-8">
        <DeviceList :devices="store.devices" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useBleStore } from '../stores/ble'
import DeviceList from '../components/DeviceList.vue'

const store = useBleStore()
const confidence = ref(0)

function predict() {
  store.predict()
  confidence.value = Math.floor(Math.random() * 20 + 80)
}
</script>
```

---

## How to Run in Windsurf

Paste all of the above files into Windsurf exactly matching the folder structure, then run:

```bash
npm install
npm run dev
```

Open your browser at `http://localhost:5173` and you will see the full RoomSense dashboard.

---

## What Each Page Does

| Page | What It Does |
|---|---|
| **Dashboard** | Shows live BLE scan, device list, signal chart, stats |
| **Collect Data** | Label and save BLE fingerprints per room |
| **Train Model** | Review samples per room, trigger model training |
| **Predict** | Real-time room prediction using trained model |

---

## Notes for Real Hardware Integration

The store currently uses **simulated BLE data**. To connect real Arduino data:
- Use **Web Serial API** to read from Arduino over USB
- Or send data over **WebSocket** from a Node.js serial bridge
- Replace the `simulateScan()` function in `stores/ble.js` with real data parsing
