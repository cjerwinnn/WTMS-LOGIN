<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>WTMS Face Recognition</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f5f5;
      margin: 0;
      padding: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 100vh;
      box-sizing: border-box;
    }

    h2 {
      margin-bottom: 20px;
      text-align: center;
      color: #333;
    }

    #main-container {
      display: flex;
      gap: 30px;
      width: 100%;
      max-width: 1024px;
    }

    #preview-container {
      position: relative;
      flex: 1;
      max-width: 640px;
      aspect-ratio: 4 / 3;
      background: #000;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
      overflow: hidden;
    }

    #video,
    #overlay,
    #captureCanvas {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      user-select: none;
      -webkit-user-select: none;
    }

    #countdown {
      position: absolute;
      top: 15px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 72px;
      font-weight: 900;
      color: rgba(255, 0, 0, 0.8);
      pointer-events: none;
      user-select: none;
      z-index: 10;
      text-shadow: 0 0 5px #fff;
    }

    #history-container {
      width: 320px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      padding: 15px;
      display: flex;
      flex-direction: column;
      height: 480px;
      overflow-y: auto;
      font-size: 14px;
      color: #444;
    }

    #history-container h3 {
      margin-top: 0;
      margin-bottom: 12px;
      font-weight: 700;
      color: #222;
      border-bottom: 2px solid #007bff;
      padding-bottom: 6px;
    }

    #history-log {
      flex: 1;
      overflow-y: auto;
    }

    .log-entry {
      margin-bottom: 10px;
      padding-bottom: 6px;
      border-bottom: 1px solid #eee;
    }

    .log-time {
      font-size: 11px;
      color: #999;
      margin-bottom: 3px;
    }

    .log-text {
      font-weight: 600;
    }

    #result {
      margin-top: 15px;
      font-family: monospace;
      white-space: pre-wrap;
      background: #eee;
      padding: 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
      max-width: 640px;
      width: 100%;
      color: #222;
      min-height: 40px;
    }

    #result.loading {
      color: #007bff;
      font-weight: 700;
    }

    #flash-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: white;
      opacity: 0;
      pointer-events: none;
      z-index: 999;
      transition: opacity 0.3s ease;
    }
  </style>
</head>

<body>

  <h2>WTMS Face Recognition</h2>

  <div id="main-container">
    <div id="preview-container">
      <div id="flash-overlay"></div>
      <video id="video" autoplay muted></video>
      <canvas id="overlay"></canvas>
      <canvas id="captureCanvas" style="display:none;"></canvas>
      <div id="countdown"></div>
    </div>

    <div id="history-container">
      <h3>Detection History</h3>
      <div id="history-log"></div>
    </div>
  </div>

  <pre id="result"></pre>

  <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>

  <script>
    function playFlashAnimation() {
      const flash = document.getElementById('flash-overlay');
      flash.style.opacity = '1';
      setTimeout(() => {
        flash.style.opacity = '0';
      }, 100);
    }

    function addHistoryEntry(text) {
      const historyLog = document.getElementById('history-log');
      const entry = document.createElement('div');
      entry.className = 'log-entry';

      const time = document.createElement('div');
      time.className = 'log-time';
      time.textContent = new Date().toLocaleTimeString();

      const content = document.createElement('div');
      content.className = 'log-text';
      content.textContent = text;

      entry.appendChild(time);
      entry.appendChild(content);
      historyLog.prepend(entry);

      if (historyLog.childElementCount > 50) {
        historyLog.removeChild(historyLog.lastChild);
      }
    }

    function speak(text) {
      const synth = window.speechSynthesis;
      if (!synth) return;

      const utter = new SpeechSynthesisUtterance(text);
      utter.lang = 'en-US';
      utter.rate = 1;
      utter.pitch = 1;
      synth.speak(utter);
    }

    const video = document.getElementById('video');
    const overlay = document.getElementById('overlay');
    const ctx = overlay.getContext('2d');
    const captureCanvas = document.getElementById('captureCanvas');
    const captureCtx = captureCanvas.getContext('2d');
    const countdownEl = document.getElementById('countdown');
    const resultEl = document.getElementById('result');

    let detectionRunning = true;

    async function setup() {
      await faceapi.nets.tinyFaceDetector.loadFromUri('https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/');
      const stream = await navigator.mediaDevices.getUserMedia({
        video: {}
      });
      video.srcObject = stream;

      video.onloadedmetadata = () => {
        overlay.width = video.videoWidth;
        overlay.height = video.videoHeight;
        captureCanvas.width = video.videoWidth;
        captureCanvas.height = video.videoHeight;
        detectFaces();
      };
    }

    async function detectFaces() {
      if (!detectionRunning) return;

      const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions());
      ctx.clearRect(0, 0, overlay.width, overlay.height);

      detections.forEach(det => {
        const {
          x,
          y,
          width,
          height
        } = det.box;
        ctx.strokeStyle = '#00FF00';
        ctx.lineWidth = 3;
        ctx.strokeRect(x, y, width, height);
      });

      requestAnimationFrame(detectFaces);
    }

    function freezeFrame() {
      captureCtx.drawImage(video, 0, 0, captureCanvas.width, captureCanvas.height);
      video.style.display = 'none';
      overlay.style.display = 'none';
      captureCanvas.style.display = 'block';
      detectionRunning = false;
    }

    function unfreezeFrame() {
      captureCanvas.style.display = 'none';
      video.style.display = 'block';
      overlay.style.display = 'block';
      detectionRunning = true;
      detectFaces();
    }

    function countdown(seconds) {
      return new Promise(resolve => {
        let count = seconds;
        countdownEl.textContent = count;
        const interval = setInterval(() => {
          count--;
          if (count > 0) {
            countdownEl.textContent = count;
          } else {
            clearInterval(interval);
            countdownEl.textContent = '';
            resolve();
          }
        }, 1000);
      });
    }

    async function captureAndRecognize() {
      playFlashAnimation();
      freezeFrame();
      await countdown(0);

      resultEl.classList.add('loading');
      resultEl.textContent = 'Please wait... Searching in database...';

      const dataURL = captureCanvas.toDataURL('image/jpeg');

      const response = await fetch('recognize.php', {
        method: 'POST',
        body: new URLSearchParams({
          webcam_image: dataURL
        })
      });

      const text = await response.text();
      const cleanedText = text.replace(/<br\s*\/?>/gi, '');

      resultEl.classList.remove('loading');
      resultEl.textContent = cleanedText;
      addHistoryEntry(cleanedText);

      // Extract name and greet
      const nameMatch = cleanedText.match(/Name:\s*([A-Za-z\s]+)/i);
      if (nameMatch && nameMatch[1]) {
        const name = nameMatch[1].trim();
        const hour = new Date().getHours();
        let greeting = 'Hello';
        if (hour >= 5 && hour < 12) greeting = 'Good morning';
        else if (hour >= 12 && hour < 17) greeting = 'Good afternoon';
        else greeting = 'Good evening';
        speak(`${greeting}, ${name}`);
      }

      unfreezeFrame();
    }

    window.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        if (detectionRunning) {
          captureAndRecognize();
        }
      }
    });

    setup();
  </script>

</body>

</html>