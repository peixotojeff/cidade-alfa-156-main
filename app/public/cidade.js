(() => {
  const lat = document.querySelector('#latitude');
  const lon = document.querySelector('#longitude');
  const info = document.querySelector('#mapa-status');
  const locate = document.querySelector('#localizar');
  const service = document.querySelector('#servico');
  const sla = document.querySelector('#prazo-previsto');
  const showSla = () => {
    const dias = service.selectedOptions[0]?.dataset.dias;
    sla.textContent = dias ? `Prazo previsto: ${dias} dias corridos.` : 'O prazo depende do serviço escolhido.';
  };
  service.addEventListener('change', showSla);
  showSla();
  let map, pin, revision = 0;
  const mark = (a, b, message, recenter = false) => {
    if (map) {
      if (pin) pin.setLatLng([a, b]);
      else {
        pin = L.marker([a, b], {draggable: true, title: 'Local do problema'}).addTo(map);
        pin.on('dragend', () => {
          revision++;
          const point = pin.getLatLng().wrap();
          mark(point.lat, point.lng, 'Pin ajustado. Confira antes de registrar.');
        });
      }
      if (recenter) map.setView([a, b], 16);
    }
    lat.value = Number(a).toFixed(6);
    lon.value = Number(b).toFixed(6);
    info.textContent = message;
  };
  if (window.L) {
    map = L.map('mapa', {worldCopyJump: true, scrollWheelZoom: false}).setView([-23.5505, -46.6333], 13);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).on('tileerror', () => {
      if (!info.textContent) info.textContent = 'Mapa-base indisponível. Você também pode digitar as coordenadas.';
    }).addTo(map);
    map.on('click', ({latlng}) => {
      revision++;
      const point = latlng.wrap();
      mark(point.lat, point.lng, 'Pin marcado. Confira antes de registrar.');
    });
  }
  const sync = () => {
    revision++;
    if (lat.value && lon.value && lat.validity.valid && lon.validity.valid) {
      mark(Number(lat.value), Number(lon.value), 'Coordenadas informadas. Confira antes de registrar.', true);
    } else if (map && pin) {
      pin.remove();
      pin = undefined;
      info.textContent = '';
    }
  };
  [lat, lon].forEach(field => { field.addEventListener('input', () => revision++); field.addEventListener('change', sync); });
  if (lat.value && lon.value) sync();
  locate.hidden = false;
  locate.addEventListener('click', () => {
    if (!window.isSecureContext || !navigator.geolocation) {
      info.textContent = 'GPS indisponível. Marque no mapa ou digite as coordenadas.';
      return;
    }
    const started = ++revision;
    locate.disabled = true;
    info.textContent = 'Buscando localização…';
    navigator.geolocation.getCurrentPosition(({coords}) => {
      locate.disabled = false;
      if (revision === started) mark(coords.latitude, coords.longitude, `Localização estimada (precisão: ${Math.ceil(coords.accuracy)} m). Ajuste o pin se necessário.`, true);
    }, () => {
      locate.disabled = false;
      if (revision === started) info.textContent = 'Não foi possível localizar. Marque no mapa ou digite as coordenadas.';
    }, {enableHighAccuracy: true, timeout: 10000, maximumAge: 0});
  });
})();
