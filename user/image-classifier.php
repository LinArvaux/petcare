<?php
require_once __DIR__ . '/../db/app.php';
$page_title = 'Skin Check AI';
include __DIR__ . '/../design/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* CSS for the Drag and Drop Zone */
.drop-zone {
    display: block; 
    border: 2px dashed #ccc;
    border-radius: 8px;
    padding: 40px 20px;
    text-align: center;
    background-color: #fafafa;
    transition: all 0.3s ease;
    cursor: pointer;
    width: 100%;
    box-sizing: border-box;
}

.drop-zone.dragover {
    background-color: #e9ecef;
    border-color: #0d6efd;
    color: #0d6efd;
}

.drop-text {
    margin-right: 10px;
    color: #6c757d;
}

.drop-zone .btn {
    display: inline-block;
    background-color: #0d6efd;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.drop-zone .btn:hover {
    background-color: #0b5ed7;
}

/* Safely hides the file input without breaking browser functionality */
.visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}
</style>

<div class="form-panel" style="max-width: 800px; margin: 0 auto;">
    <h2>Skin Disease Analyzer</h2>
    <p style="color: var(--bark-700); margin-bottom: 20px;">Upload a clear photo of your pet's skin condition for an AI-powered preliminary assessment.</p>
    
    <div class="input-group">
        <!-- Using a label automatically links clicks to the input -->
        <label id="drop-zone" class="drop-zone" for="skinImage">
            <span class="drop-text">Drag and drop your image here or</span>
            <span class="btn">Choose File</span>
            
            <!-- Visually hidden input instead of display:none -->
            <input type="file" id="skinImage" accept=".jpeg, .jpg, .png, image/jpeg, image/png" class="visually-hidden">
        </label>
        
        <!-- Feedback messages -->
        <div id="file-info" class="text-success mt-2" style="width: 100%; font-weight: bold;"></div>
        <div id="error-msg" class="text-danger mt-2" style="width: 100%; font-weight: bold;"></div>
    </div>
    
    <button onclick="runClassification()" class="btn btn-primary" style="margin-top: 15px;">Analyze Image</button>
    
    <div id="classifierOutput" style="margin-top: 25px;"></div>
</div>

<script>
// --- Drag, Drop, and Validation Logic ---
document.addEventListener("DOMContentLoaded", () => {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('skinImage');
    const fileInfo = document.getElementById('file-info');
    const errorMsg = document.getElementById('error-msg');

    const MAX_SIZE = 50 * 1024 * 1024; // 50MB in bytes
    const ALLOWED_TYPES = ['image/jpeg', 'image/png']; 

    // Prevent default browser behavior for ALL drag events
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Add visual feedback when dragging over the zone
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.remove('dragover');
        }, false);
    });

    // Handle the Drop event
    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;

        if (files.length > 0) {
            fileInput.files = files; // Assign the dropped file to the HTML input
            validateAndProcessFile(files[0]);
        }
    });

    // Handle standard file selection (when clicking the label/button)
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            validateAndProcessFile(this.files[0]);
        }
    });

    // Validation Logic
    function validateAndProcessFile(file) {
        errorMsg.textContent = '';
        fileInfo.textContent = '';

        // Check file type
        if (!ALLOWED_TYPES.includes(file.type)) {
            showError('Invalid file type. Only JPEG, JPG, and PNG are allowed.');
            return;
        }

        // Check file size
        if (file.size > MAX_SIZE) {
            showError('File is too large. Maximum size allowed is 50MB.');
            return;
        }

        // Success: File is valid
        const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
        fileInfo.textContent = `Ready to upload: ${file.name} (${fileSizeMB} MB)`;
    }

    function showError(message) {
        errorMsg.textContent = message;
        fileInput.value = ''; // Clear the input so the user can try again
    }
});

// --- Existing AI Classification Logic ---
let probChartInstance = null;

async function runClassification() {
    const fileInput = document.getElementById('skinImage');
    const output = document.getElementById('classifierOutput');
    const errorMsg = document.getElementById('error-msg');
    
    if (fileInput.files.length === 0) {
        alert("Please upload a valid image first.");
        return;
    }
    
    const file = fileInput.files[0];
    const formData = new FormData();
    formData.append("image", file);
    
    output.innerHTML = "<p><strong>Analyzing image... Please wait.</strong></p>";
    
    try {
        const res = await fetch("http://127.0.0.1:5000/predict-image", { method: "POST", body: formData });
        const json = await res.json();
        
        if (json.error) {
            output.innerHTML = `<div class="alert alert-error">${json.error}</div>`;
            return;
        }

        const possibleDiseasesHtml = json.possible_diseases.map(d => `<li>${d.disease}: ${d.probability}% match</li>`).join('');
        const isHealthy = json.primary_classification.toLowerCase() === 'healthy';
        
        sessionStorage.setItem('petScanConfidence', json.possible_diseases[0].probability);
        sessionStorage.setItem('petScanDisease', json.primary_classification);
        
        output.innerHTML = `
            <div class="alert alert-error" style="font-size: 0.85em;">DISCLAIMER: This AI analysis is not 100% accurate and serves only as a preliminary tool. Please consult an actual veterinarian for a formal diagnosis.</div>
            <div class="result-panel result-${isHealthy ? 'healthy' : 'diseased'}">
                <h3>Primary Classification: <span style="text-transform:uppercase;">${json.primary_classification}</span></h3>
            </div>
            
            <div style="display:flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
                <div style="flex:1; min-width: 250px;">
                    <h4>Top 3 Possibilities:</h4>
                    <ul>${possibleDiseasesHtml}</ul>
                    <div style="height: 200px;"><canvas id="probChart"></canvas></div>
                </div>
                <div style="flex:1; min-width: 250px;">
                    <h4>AI Reasoning (Heatmap):</h4>
                    <img src="http://127.0.0.1:5000${json.heatmap_url}" style="max-width:100%; border-radius: 8px;">
                </div>
            </div>
            
            <!-- Transition Button to Cost Estimator -->
            <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid var(--moss-200); text-align: center;">
                <button onclick="window.location.href='./cost-estimator.php?disease=' + encodeURIComponent('${json.primary_classification}')" class="btn btn-primary" style="padding: 10px 20px; font-size: 1.1em;">
                    Estimate Treatment Cost &rarr;
                </button>
            </div>
        `;

        const ctxProb = document.getElementById('probChart').getContext('2d');
        if (probChartInstance) probChartInstance.destroy();
        probChartInstance = new Chart(ctxProb, {
            type: 'doughnut',
            data: {
                labels: json.possible_diseases.map(d => d.disease),
                datasets: [{ data: json.possible_diseases.map(d => d.probability), backgroundColor: ['#b34a3a', '#9a7150', '#8fb48a'] }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // Send the exact AI prediction to the database and save the ID
        const logRes = await fetch('./ajax-log.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'log_image',
                label: json.primary_classification, 
                confidence: json.possible_diseases[0].probability,
                image_path: json.heatmap_url
            })
        });
        
        const logData = await logRes.json();
        if (logData.classification_id) {
            sessionStorage.setItem('petScanClassificationId', logData.classification_id);
        }

    } catch (err) {
        output.innerHTML = `<div class="alert alert-error">Error connecting to AI service. Ensure Python server is running on port 5000.</div>`;
    }
}
</script>

<?php include __DIR__ . '/../design/footer.php'; ?>