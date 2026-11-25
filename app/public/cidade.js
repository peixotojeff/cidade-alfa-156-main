(() => {
  const lat = document.querySelector('#latitude');
  const lon = document.querySelector('#longitude');
  const info = document.querySelector('#mapa-status');
  const locate = document.querySelector('#localizar');
  const manual = document.querySelector('.coordenadas');
  const openManual = () => { if (manual) manual.open = true; };
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
      openManual();
    }).addTo(map);
    map.on('click', ({latlng}) => {
      revision++;
      const point = latlng.wrap();
      mark(point.lat, point.lng, 'Pin marcado. Confira antes de registrar.');
    });
  }
  if (!map) openManual();
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
      openManual();
      return;
    }
    const started = ++revision;
    locate.disabled = true;
    info.textContent = 'Buscando localização…';
    const success = ({coords}) => {
      locate.disabled = false;
      if (revision === started) mark(coords.latitude, coords.longitude, `Localização estimada (precisão: ${Math.ceil(coords.accuracy)} m). Ajuste o pin se necessário.`, true);
    };
    const fail = (error) => {
      locate.disabled = false;
      if (revision !== started) return;
      const reasons = {
        1: 'Sem permissão para usar sua localização. Libere o acesso no ícone de cadeado da barra de endereço ou marque no mapa.',
        2: 'Seu aparelho não informou a localização. No computador, confira se a localização está ligada nas configurações do sistema, ou marque no mapa.',
        3: 'A localização demorou demais para responder. Tente de novo ou marque no mapa.',
      };
      info.textContent = reasons[error?.code] || 'Não foi possível localizar. Marque no mapa ou digite as coordenadas.';
      openManual();
    };
    // 1ª tentativa: alta precisão (GPS). Se o aparelho não tiver GPS ou demorar,
    // tenta de novo com precisão normal (Wi-Fi/rede), que funciona na maioria dos computadores.
    navigator.geolocation.getCurrentPosition(success, (error) => {
      if (revision !== started) return;
      if (error.code === error.PERMISSION_DENIED) return fail(error);
      info.textContent = 'Ainda buscando, com precisão menor…';
      navigator.geolocation.getCurrentPosition(success, fail, {enableHighAccuracy: false, timeout: 15000, maximumAge: 300000});
    }, {enableHighAccuracy: true, timeout: 8000, maximumAge: 0});
  });
})();
