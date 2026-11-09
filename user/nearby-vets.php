<?php
require_once __DIR__ . '/../db/app.php';
$page_title = 'Nearby Vet Clinics';
include __DIR__ . '/../design/header.php';
?>

<!-- Load Leaflet.js CSS and JS for the free map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="form-panel" style="max-width: 1000px; margin: 0 auto;">
    <h2>Find a Veterinarian Near You</h2>
    <p style="color: var(--bark-700); margin-bottom: 20px;">Allow location access to find veterinary clinics in your immediate area, or search manually by city.</p>
    
    <!-- Controls -->
    <div style="display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap;">
        <button onclick="geolocateUser()" class="btn btn-primary" style="flex: 1; min-width: 200px;">
            Use My Location
        </button>
        <div style="flex: 2; display: flex; gap: 10px; min-width: 300px;">
            <input type="text" id="manualLocation" placeholder="e.g., Manila, Metro Manila" class="form-control" style="flex: 1;" onkeypress="if(event.key === 'Enter') manualSearch()">
            <button onclick="manualSearch()" class="btn btn-secondary">Search Area</button>
        </div>
    </div>

    <!-- Map and Results Split -->
    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <!-- Leaflet Map Container -->
        <div id="map" style="flex: 1.5; min-width: 300px; height: 500px; border-radius: 8px; background: #e9e9e9; border: 1px solid #ddd; z-index: 1;"></div>
        
        <!-- Results List -->
        <div id="results-container" style="flex: 1; min-width: 300px; max-height: 500px; overflow-y: auto; padding-right: 10px;">
            <div id="status-msg" style="padding: 20px; background: #f9f9f9; border-radius: 8px; text-align: center; color: var(--bark-700);">
                Click "Use My Location" or enter a city to begin.
            </div>
            <div id="clinics-list" style="display: flex; flex-direction: column; gap: 15px;"></div>
        </div>
    </div>
</div>

<script>
let map;
let markersLayer = new L.FeatureGroup();
// Default to Manila based on system context
let userLat = 14.5995; 
let userLng = 120.9842;

document.addEventListener('DOMContentLoaded', () => {
    // Initialize the Leaflet Map
    map = L.map('map').setView([userLat, userLng], 12);
    
    // Add free OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    map.addLayer(markersLayer);
});

// 1. Auto-Locate User via HTML5
function geolocateUser() {
    const statusMsg = document.getElementById('status-msg');
    statusMsg.style.display = 'block';
    statusMsg.innerText = 'Locating you...';
    document.getElementById('clinics-list').innerHTML = '';

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLat = position.coords.latitude;
                userLng = position.coords.longitude;
                map.setView([userLat, userLng], 14);
                
                // Add a marker for the user
                L.marker([userLat, userLng]).addTo(markersLayer)
                    .bindPopup("<b>You are here</b>").openPopup();
                    
                searchVets(userLat, userLng);
            },
            () => {
                statusMsg.innerText = 'Location access denied. Please use the manual search.';
            }
        );
    } else {
        statusMsg.innerText = 'Geolocation is not supported by this browser.';
    }
}

// 2. Manual Text Search using Nominatim (Free Geocoding)
async function manualSearch() {
    const query = document.getElementById('manualLocation').value;
    if (!query) return;

    const statusMsg = document.getElementById('status-msg');
    statusMsg.style.display = 'block';
    statusMsg.innerText = 'Searching location...';
    document.getElementById('clinics-list').innerHTML = '';

    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`);
        const data = await response.json();

        if (data && data.length > 0) {
            userLat = parseFloat(data[0].lat);
            userLng = parseFloat(data[0].lon);
            map.setView([userLat, userLng], 14);
            searchVets(userLat, userLng);
        } else {
            statusMsg.innerText = 'Could not find that location. Try being more specific (e.g., "Quezon City, Metro Manila").';
        }
    } catch (error) {
        statusMsg.innerText = 'Error connecting to search service.';
    }
}

// 3. Search for nearby Vets using Overpass API with Fallback Mirrors
async function searchVets(lat, lng) {
    markersLayer.clearLayers();
    const statusMsg = document.getElementById('status-msg');
    statusMsg.innerText = 'Scanning for nearby veterinary clinics...';
    
    // [timeout:15] prevents the server from hanging
    const overpassQuery = `
        [out:json][timeout:15];
        (
          node["amenity"="veterinary"](around:5000,${lat},${lng});
          way["amenity"="veterinary"](around:5000,${lat},${lng});
        );
        out center;
    `;
    
    // Array of free public mirror servers to prevent 504 Timeout errors
    const endpoints = [
        'https://overpass-api.de/api/interpreter',
        'https://lz4.overpass-api.de/api/interpreter',
        'https://z.overpass-api.de/api/interpreter'
    ];

    let data = null;

    // Try servers one by one until one responds
    for (let i = 0; i < endpoints.length; i++) {
        try {
            const url = `${endpoints[i]}?data=${encodeURIComponent(overpassQuery)}`;
            const response = await fetch(url);
            
            if (!response.ok) continue; 
            
            data = await response.json();
            break; // Success! Exit loop.
            
        } catch (error) {
            console.warn(`Server ${endpoints[i]} failed.`);
        }
    }

    if (!data) {
        statusMsg.style.display = 'block';
        statusMsg.innerText = 'The public map servers are currently busy. Please try again in a few minutes.';
        return;
    }
    
    statusMsg.style.display = 'none';
    
    if (!data.elements || data.elements.length === 0) {
        statusMsg.style.display = 'block';
        statusMsg.innerText = 'No veterinary clinics found within 5km of this location in the public database.';
        return;
    }

    data.elements.forEach((element) => {
        const clinicLat = element.lat || element.center.lat;
        const clinicLon = element.lon || element.center.lon;
        const tags = element.tags || {};
        
        createMarker(clinicLat, clinicLon, tags);
        appendClinicCard(clinicLat, clinicLon, tags);
    });
}

// Helper: Create Map Pins
function createMarker(lat, lng, tags) {
    const name = tags.name || 'Unnamed Vet Clinic';
    const marker = L.marker([lat, lng]).addTo(markersLayer);
    marker.bindPopup(`<strong>${name}</strong>`);
}

// Helper: Build the HTML Card
function appendClinicCard(lat, lng, tags) {
    const list = document.getElementById('clinics-list');
    
    const name = tags.name || 'Veterinary Clinic';
    
    let addressParts = [];
    if (tags['addr:street']) addressParts.push(tags['addr:street']);
    if (tags['addr:city']) addressParts.push(tags['addr:city']);
    const address = addressParts.length > 0 ? addressParts.join(', ') : '';
    
    const phone = tags.phone || tags['contact:phone'] ? `📞 ${tags.phone || tags['contact:phone']}` : '';
    
    // THE MAGIC FIX: We pass the NAME to Google Maps, not the coordinates.
    // Google will look up the real building address in its own database.
    const searchQuery = `${name} veterinary ${address}`;
    const dirUrl = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(searchQuery)}`;

    const cardHTML = `
        <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #eee;">
            <h4 style="margin: 0 0 5px 0; color: var(--moss-900);">${name}</h4>
            <p style="margin: 0 0 5px 0; font-size: 0.9em; color: #555;">📍 ${address || 'Address details not publicly listed'}</p>
            ${phone ? `<p style="margin: 0 0 10px 0; font-size: 0.85em; color: #555;">${phone}</p>` : '<div style="margin-bottom: 10px;"></div>'}
            <a href="${dirUrl}" target="_blank" class="btn btn-secondary btn-sm" style="display: block; text-align: center; font-size: 0.85em; padding: 5px;">
                Open Directions &rarr;
            </a>
        </div>
    `;
    list.insertAdjacentHTML('beforeend', cardHTML);
}
</script>

<?php include __DIR__ . '/../design/footer.php'; ?>