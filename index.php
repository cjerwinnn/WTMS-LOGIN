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
      width: 380px;
      background: #ffffff;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      padding: 20px;
      display: flex;
      flex-direction: column;
      height: 480px;
      font-size: 14px;
      color: #444;
    }

    #history-container h3 {
      margin-top: 0;
      margin-bottom: 15px;
      font-weight: 700;
      color: #2c3e50;
      border-bottom: 2px solid #3498db;
      padding-bottom: 10px;
      text-align: center;
    }

    #history-log {
      flex: 1;
      overflow-y: auto;
      padding-right: 10px;
    }

    /* --- MODIFIED LOG ENTRY STYLES --- */
    .log-entry {
      display: flex;
      flex-direction: column;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 10px;
      transition: background-color 0.3s;
    }

    .log-success {
      background-color: #e8f5e9;
      border-left: 5px solid #4CAF50;
    }

    .log-error {
      background-color: #ffebee;
      border-left: 5px solid #f44336;
    }

    .log-time {
      font-size: 11px;
      color: #7f8c8d;
      margin-bottom: 6px;
      /* Increased space */
      font-weight: 500;
    }

    .log-name {
      /* For the [ID] Fullname line */
      font-size: 14px;
      font-weight: 600;
      color: #34495e;
      margin-bottom: 4px;
      /* Space between name and status */
    }

    .log-status {
      /* For the punch state line */
      font-size: 13px;
      font-weight: bold;
    }

    .log-status-success {
      color: #388e3c;
      /* Darker green for success */
    }

    .log-status-error {
      color: #d32f2f;
      /* Darker red for error */
    }

    /* --- END OF LOG STYLES --- */

    #result {
      margin-top: 15px;
      font-family: monospace;
      white-space: pre-wrap;
      background: #eee;
      padding: 15px;
      border-radius: 6px;
      border: 1px solid #ccc;
      max-width: 640px;
      width: 100%;
      color: #222;
      min-height: 60px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
    }

    #result-name {
      font-size: 18px;
      font-weight: bold;
      color: #333;
    }

    #result-status {
      font-size: 14px;
      color: #555;
      margin-top: 5px;
    }

    #result.loading {
      color: #007bff;
      font-weight: 700;
      font-size: 16px;
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
      <h3>Log History</h3>
      <div id="history-log"></div>
    </div>
  </div>

  <pre id="result"></pre>

  <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>

  <script>
    let voices = [];

    function populateVoiceList() {
      voices = window.speechSynthesis.getVoices();
    }

    populateVoiceList();
    if (window.speechSynthesis.onvoiceschanged !== undefined) {
      window.speechSynthesis.onvoiceschanged = populateVoiceList;
    }

    function playFlashAnimation() {
      const flash = document.getElementById('flash-overlay');
      flash.style.opacity = '1';
      setTimeout(() => {
        flash.style.opacity = '0';
      }, 100);
    }

    // --- HEAVILY MODIFIED FUNCTION ---
    function addHistoryEntry(data) {
      const historyLog = document.getElementById('history-log');
      const entry = document.createElement('div');

      const isSuccess = data.status === 'success';
      entry.className = `log-entry ${isSuccess ? 'log-success' : 'log-error'}`;

      // 1. DATE TIME
      const timeDiv = document.createElement('div');
      timeDiv.className = 'log-time';
      timeDiv.textContent = new Date().toLocaleString();
      entry.appendChild(timeDiv);

      if (isSuccess) {
        // 2. [EMPLOYEEID] FULLNAME
        const nameDiv = document.createElement('div');
        nameDiv.className = 'log-name';
        nameDiv.textContent = `[${data.employeeid}] ${data.lastname}, ${data.firstname}`;
        entry.appendChild(nameDiv);

        // 3. PUNCH STATE
        const statusDiv = document.createElement('div');
        statusDiv.className = 'log-status log-status-success';
        statusDiv.textContent = data.punch_status;
        entry.appendChild(statusDiv);
      } else {
        // For errors, just show the message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'log-status log-status-error';
        errorDiv.textContent = data.message;
        entry.appendChild(errorDiv);
      }

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

      const selectedVoice = voices.find(voice => voice.name === 'Google UK English Female');

      if (selectedVoice) {
        utter.voice = selectedVoice;
      }

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

    async function captureAndRecognize(punchAction) {
      playFlashAnimation();
      freezeFrame();
      await countdown(0);

      resultEl.innerHTML = '';
      resultEl.classList.add('loading');
      resultEl.textContent = 'Please wait... Searching in database...';

      const dataURL = captureCanvas.toDataURL('image/jpeg');

      const response = await fetch('recognize.php', {
        method: 'POST',
        body: new URLSearchParams({
          webcam_image: dataURL,
          action: punchAction
        })
      });

      const data = await response.json();

      resultEl.classList.remove('loading');
      resultEl.textContent = '';

      // Pass the entire data object to the history function
      addHistoryEntry(data);

      if (data.status === 'success') {
        const {
          firstname,
          lastname,
          employeeid,
          punch_status
        } = data;

        const nameDiv = document.createElement('div');
        nameDiv.id = 'result-name';
        nameDiv.textContent = `[${employeeid}] ${lastname}, ${firstname}`;

        const statusSmall = document.createElement('small');
        statusSmall.id = 'result-status';
        statusSmall.textContent = punch_status;

        resultEl.appendChild(nameDiv);
        resultEl.appendChild(statusSmall);

        let title = data.gender.toLowerCase() === 'male' ? 'Mister' : 'Miss';

        const hour = new Date().getHours();
        let greeting = 'Hello';
        if (hour >= 5 && hour < 12) greeting = 'Good morning';
        else if (hour >= 12 && hour < 17) greeting = 'Good afternoon';
        else greeting = 'Good evening';

        speak(`${greeting}, ${title} ${lastname}. ${punch_status}.`);

      } else {
        const errorDiv = document.createElement('div');
        errorDiv.id = 'result-name';
        errorDiv.style.color = '#d32f2f';
        errorDiv.textContent = data.message;
        resultEl.appendChild(errorDiv);
      }

      unfreezeFrame();
    }


    window.addEventListener('keydown', e => {
      if (detectionRunning) {
        if (e.key === ' ') {
          e.preventDefault();
          captureAndRecognize('IN');
        } else if (e.key === 'Enter') {
          captureAndRecognize('OUT');
        }
      }
    });

    setup();
  </script>

</body>

</html>