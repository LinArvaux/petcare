<?php
require_once __DIR__ . '/../db/app.php';
$page_title = 'Cost Estimator & Decision Support';
include __DIR__ . '/../design/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<div class="form-panel" style="max-width: 800px;">
    <h2>Veterinary Cost & Decision Support</h2>
    <p style="color: var(--bark-700);">Enter the clinical parameters to generate a cost estimate and actionable recommendation.</p>
    
    <!-- 1. Expanded Input Form for the New Features -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
        <div class="input-group">
            <label>Pet Species:</label>
            <select id="speciesSelect" class="form-control">
                <option value="dog">Dog</option>
                <option value="cat">Cat</option>
            </select>
        </div>
        <div class="input-group">
            <label>Select Disease:</label>
            <select id="diseaseSelect" class="form-control">
                <option value="Dermatitis">Dermatitis</option>
                <option value="Fungal_infections">Fungal Infections</option>
                <option value="demodicosis">Demodicosis</option>
                <option value="ringworm">Ringworm</option>
            </select>
        </div>
        <div class="input-group">
            <label>Severity Level:</label>
            <select id="severitySelect" class="form-control">
                <option value="Mild">Mild (Day 1)</option>
                <option value="Moderate">Moderate (Week 2)</option>
                <option value="Severe">Severe (Week 4)</option>
            </select>
        </div>
        <div class="input-group">
            <label>Pet Weight (kg):</label>
            <input type="number" id="weightInput" value="5.0" step="0.1" class="form-control">
        </div>
        <div class="input-group">
            <label>Treatment Duration (Days):</label>
            <input type="number" id="durationInput" value="7" class="form-control">
        </div>
        <div class="input-group">
            <label>Target Clinic Tier:</label>
            <select id="tierSelect" class="form-control">
                <option value="Economy">Economy</option>
                <option value="Standard">Standard</option>
                <option value="Premium">Premium</option>
            </select>
        </div>
    </div>
    
    <button onclick="runDecisionSupport()" class="btn btn-primary" style="width: 100%;">Generate Clinical Action Plan</button>
    
    <!-- 2. The Decision Support Output Panel -->
    <div id="costOutput" style="display:none; margin-top: 25px; padding: 20px; background: #f9f9f9; border-radius: 8px;">
        <h3 style="color: var(--moss-700); border-bottom:2px solid var(--moss-200); padding-bottom: 10px;">Clinical Recommendation</h3>
        <p id="decisionText" style="font-size: 1.1em; line-height: 1.6; color: #333; font-weight: bold;"></p>
        
        <h4 style="margin-top: 20px;">Estimated Cost: <span id="finalEstimateText" style="color: #b34a3a;"></span></h4>
        
        <div style="height: 250px; margin-top: 20px;"><canvas id="delayChart"></canvas></div>
        
        <button onclick="exportToPDF()" class="btn btn-secondary" style="margin-top: 25px; width: 100%;">
            📄 Download / Export as PDF
        </button>
    </div>
</div>

<!-- 3. Hidden PDF Template (Fixed positioned to prevent scroll/crop bugs) -->
<div id="pdfReportContainer" style="display: none; position: fixed; top: 0; left: 0; width: 800px; z-index: -9999; background-color: #ffffff;">
    <div id="pdfContent" style="padding: 40px; font-family: 'Nunito', sans-serif; color: #333; background: #fff; width: 100%; box-sizing: border-box;">
        <h1 style="color: #4a5c48; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px;">PetCare AI: Preliminary Assessment</h1>
        <p><strong>Date:</strong> <span id="pdfDate"></span></p>
        
        <h3 style="margin-top: 20px;">1. AI Skin Classification</h3>
        <ul>
            <li><strong>Suspected Condition:</strong> <span id="pdfDisease" style="text-transform: capitalize;"></span></li>
            <li><strong>AI Confidence Match:</strong> <span id="pdfConfidence"></span>%</li>
        </ul>

        <h3>2. Estimated Treatment Cost</h3>
        <ul>
            <li><strong>Baseline Estimate:</strong> <span id="pdfCost" style="color: #b34a3a; font-weight: bold;"></span></li>
            <li><strong>Clinic Tier:</strong> <span id="pdfTier"></span></li>
            <li><strong>Patient Weight:</strong> <span id="pdfWeight"></span> kg</li>
        </ul>

        <h3>3. Clinical Recommendation</h3>
        <p id="pdfRecommendation" style="background: #f9f9f9; padding: 15px; border-left: 4px solid #4a5c48;"></p>

        <div style="margin-top: 40px; padding: 15px; border: 1px solid #ffcc00; background: #fffde7; font-size: 0.85em;">
            <strong>DISCLAIMER:</strong> This document is generated by an AI tool for informational purposes and cost estimation only. It does not constitute a verified medical diagnosis. Please present this report to a licensed veterinarian for professional evaluation.
        </div>
    </div>
</div>

<script>
// Auto-populate disease from URL parameters
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const diseaseParam = params.get('disease');
    
    if (diseaseParam) {
        const diseaseSelect = document.getElementById('diseaseSelect');
        const normalizedDisease = diseaseParam.toLowerCase().replace(/ /g, '_');
        
        for(let option of diseaseSelect.options) {
            if (option.value.toLowerCase() === normalizedDisease || option.text.toLowerCase() === diseaseParam.toLowerCase()) {
                option.selected = true;
                break;
            }
        }
    }
});

let delayChartInstance = null;

async function runDecisionSupport() {
    const payload = {
        species: document.getElementById('speciesSelect').value,
        disease_name: document.getElementById('diseaseSelect').value,
        severity_level: document.getElementById('severitySelect').value,
        pet_weight_kg: parseFloat(document.getElementById('weightInput').value),
        treatment_duration_days: parseInt(document.getElementById('durationInput').value, 10),
        clinic_tier: document.getElementById('tierSelect').value
    };
    
    const outputDiv = document.getElementById('costOutput');
    const decisionText = document.getElementById('decisionText');
    const estimateText = document.getElementById('finalEstimateText');
    
    try {
        const res = await fetch("http://127.0.0.1:5000/predict-cost", {
            method: "POST", headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        
        if (json.error) { alert(json.error); return; }
        
        const estCost = json.estimated_cost;
        outputDiv.style.display = "block";
        estimateText.innerHTML = `₱${estCost.toFixed(2)}`;
        
        let actionPlan = `Based on the parameters, the system has identified a ${payload.severity_level.toLowerCase()} case of ${payload.disease_name.replace('_', ' ')}. `;
        
        if (payload.severity_level === 'Severe') {
            actionPlan += `Immediate veterinary intervention is strongly recommended at a ${payload.clinic_tier} tier clinic. `;
        } else {
            actionPlan += `Early veterinary consultation is advised to prevent progression. `;
        }
        
        if (payload.pet_weight_kg > 15) {
            actionPlan += `Note: Expected medication costs are elevated due to the patient's weight (${payload.pet_weight_kg}kg). `;
        }
        
        actionPlan += `Delaying treatment could significantly increase the baseline cost of ₱${estCost.toFixed(2)}.`;
        
        decisionText.innerHTML = actionPlan;

        if (delayChartInstance) delayChartInstance.destroy();
        delayChartInstance = new Chart(document.getElementById('delayChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Mild (Day 1)', 'Moderate (Week 2)', 'Severe (Week 4)', 'Critical (Month 2+)'],
                datasets: [
                    { label: 'Projected Cost Escalation (₱)', data: [estCost, estCost * 1.5, estCost * 2.5, estCost * 4.0], borderColor: '#b34a3a', fill: true, backgroundColor: 'rgba(179, 74, 58, 0.1)' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { title: { display: true, text: 'Cost of Delay Progression' } } }
        });

const classId = sessionStorage.getItem('petScanClassificationId') || null;
        
        await fetch('./ajax-log.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'log_cost', 
                disease: payload.disease_name, 
                cost: estCost,
                classification_id: classId,
                species: payload.species,
                severity_level: payload.severity_level,
                weight: payload.pet_weight_kg,
                duration: payload.treatment_duration_days,
                tier: payload.clinic_tier
            })
        });

    } catch (err) { alert("Error connecting to Python backend. Ensure your Flask API is updated to accept the new variables."); }
}

function exportToPDF() {
    // 1. Populate data into the hidden element
    document.getElementById('pdfDate').innerText = new Date().toLocaleString();
    document.getElementById('pdfDisease').innerText = sessionStorage.getItem('petScanDisease') || document.getElementById('diseaseSelect').value;
    
    let confidence = sessionStorage.getItem('petScanConfidence');
    document.getElementById('pdfConfidence').innerText = confidence ? parseFloat(confidence).toFixed(2) : 'N/A';
    
    document.getElementById('pdfCost').innerText = document.getElementById('finalEstimateText').innerText;
    document.getElementById('pdfTier').innerText = document.getElementById('tierSelect').options[document.getElementById('tierSelect').selectedIndex].text;
    document.getElementById('pdfWeight').innerText = document.getElementById('weightInput').value;
    document.getElementById('pdfRecommendation').innerText = document.getElementById('decisionText').innerText;

    // 2. Target the live DOM elements
    const container = document.getElementById('pdfReportContainer');
    const element = document.getElementById('pdfContent');
    
    // 3. Unhide it. Because it is position: fixed and z-index: -9999, the user will not see it flash on screen.
    container.style.display = 'block';
    
    // 4. Render
    html2pdf().set({
        margin: 15,
        filename: 'PetCare_AI_Report.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { 
            scale: 2, 
            scrollY: 0, // Enforces top-alignment
            useCORS: true 
        },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    }).from(element).save().then(() => {
        // 5. Cleanup
        container.style.display = 'none';
    });
}
</script>
<?php include __DIR__ . '/../design/footer.php'; ?>