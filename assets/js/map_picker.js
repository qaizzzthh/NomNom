// Leaflet Map Picker and Nominatim Reverse Geocoding Integration
document.addEventListener('DOMContentLoaded', () => {
  const mapEl = document.getElementById('map-picker');
  const latInput = document.getElementById('lat-input');
  const lonInput = document.getElementById('lon-input');
  const addrInput = document.getElementById('address-input');

  if (!mapEl || !latInput || !lonInput) return;

  // Default coordinate (Jakarta)
  let defaultLat = -6.17539240;
  let defaultLon = 106.82715280;

  // Check if we already have coordinates
  const valLat = parseFloat(latInput.value);
  const valLon = parseFloat(lonInput.value);
  if (!isNaN(valLat) && !isNaN(valLon) && valLat !== 0 && valLon !== 0) {
    defaultLat = valLat;
    defaultLon = valLon;
  }

  // Initialize Map
  const map = L.map('map-picker').setView([defaultLat, defaultLon], 15);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors'
  }).addTo(map);

  // Initialize Marker
  let marker = L.marker([defaultLat, defaultLon], { draggable: true }).addTo(map);

  // Reverse Geocoding via Nominatim
  function reverseGeocode(lat, lon) {
    fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lon}`, {
      headers: {
        'Accept-Language': 'id'
      }
    })
    .then(r => r.json())
    .then(data => {
      if (data && data.display_name && addrInput) {
        addrInput.value = data.display_name;
      }
    })
    .catch(err => console.error("Reverse geocoding error:", err));
  }

  // Update fields on drag end
  marker.on('dragend', () => {
    const pos = marker.getLatLng();
    latInput.value = pos.lat.toFixed(8);
    lonInput.value = pos.lng.toFixed(8);
    reverseGeocode(pos.lat, pos.lng);
  });

  // Update on map click
  map.on('click', (e) => {
    marker.setLatLng(e.latlng);
    latInput.value = e.latlng.lat.toFixed(8);
    lonInput.value = e.latlng.lng.toFixed(8);
    reverseGeocode(e.latlng.lat, e.latlng.lng);
  });

  // Handle modal trigger (if map is inside #addAddressModal)
  const modal = document.getElementById('addAddressModal');
  if (modal) {
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.attributeName === 'class' && modal.classList.contains('open')) {
          setTimeout(() => {
            map.invalidateSize();
            map.setView(marker.getLatLng(), 15);
          }, 200);
        }
      });
    });
    observer.observe(modal, { attributes: true });
  }
});
