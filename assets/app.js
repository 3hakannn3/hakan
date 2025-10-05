const API = '/api';
function api(path, method='GET', data=null){
  const opts = {method, headers:{'Accept':'application/json'}};
  if (data){ opts.headers['Content-Type']='application/json'; opts.body = JSON.stringify(data); }
  return fetch(API+path, opts).then(async r=>{ if(!r.ok) throw await r.json(); return r.json(); });
}
function showToast(text){ const t=document.getElementById('toast'); t.textContent=text; t.style.display='block'; setTimeout(()=>t.style.display='none',2200); }
function openModal(html){ const m=document.getElementById('modal'); m.querySelector('.modal-box').innerHTML=html; m.style.display='flex'; }
function closeModal(){ document.getElementById('modal').style.display='none'; }

function renderSidebarEvents(){
  document.querySelectorAll('.nav a[data-key]').forEach(a=> a.addEventListener('click', e=>{ e.preventDefault(); loadPage(a.dataset.key); }));
}

async function loadPage(key){
  document.getElementById('page-title').textContent = ({dashboard:'Gösterge Paneli', kisiler:'Kişiler', tarlalar:'Tarlalar', durumlar:'Durumlar', ayarlar:'Ayarlar'}[key]||key);
  const area = document.getElementById('content-area');
  if (key==='dashboard'){
    area.innerHTML = '<div class="cardgrid" id="stats">Yükleniyor...</div>';
    try{ const s = await api('/stats.php'); document.getElementById('stats').innerHTML = `
      <div class="card">Toplam Kişi<br><strong>${s.kisiler}</strong></div>
      <div class="card">Toplam Tarla<br><strong>${s.tarlalar}</strong></div>
      <div class="card">Ekili Alan (dönüm)<br><strong>${s.ekili_alan}</strong></div>
      <div class="card">Boş Alan (dönüm)<br><strong>${s.bos_alan}</strong></div>`; }catch(e){ console.error(e); }
  } else if (key==='kisiler'){
    area.innerHTML = `<div style="margin-bottom:12px"><button class="btn" id="yeniKisi">Yeni Kişi</button></div>
      <table class="table" id="kisilerTable"><thead><tr><th>Ad</th><th>Telefon</th><th>Tarla Sayısı</th><th>Toplam Dönüm</th><th>İşlemler</th></tr></thead><tbody></tbody></table>`;
    document.getElementById('yeniKisi').addEventListener('click', ()=> openPersonModal());
    refreshKisiler();
  } else if (key==='tarlalar'){
    area.innerHTML = `<div style="margin-bottom:12px"><button class="btn" id="yeniTarla">Yeni Tarla</button></div><div id="tarlalarArea"></div>`;
    document.getElementById('yeniTarla').addEventListener('click', ()=> openTarlaModal());
    refreshTarlalar();
  } else if (key==='durumlar'){
    area.innerHTML = `<div><label>Durum seç: <select id="durumSecim"><option value="">-- Seç --</option></select></label></div><div id="durumList"></div>`;
    loadDurumlarSelect();
  } else if (key.startsWith('alanlar')){
    // alan tanımları sayfaları
    area.innerHTML = `<div style="margin-bottom:12px"><button class="btn" id="yeniAlan">Yeni Alan</button></div><div id="alanlarList"></div>`;
    document.getElementById('yeniAlan').addEventListener('click', ()=> openAlanModal(key));
    refreshAlanlar(key);
  } else {
    area.innerHTML = '<div>Henüz içerik yok</div>';
  }
}

async function refreshKisiler(){
  const tbody = document.querySelector('#kisilerTable tbody'); tbody.innerHTML='<tr><td colspan=5>Yükleniyor...</td></tr>';
  try{ const list = await api('/kisiler.php'); tbody.innerHTML=''; list.forEach(p=>{
    const tr=document.createElement('tr'); tr.innerHTML = `<td>${p.isim}</td><td>${p.telefon||''}</td><td>${p.tarla_sayisi||0}</td><td>${p.toplam_alan||0}</td>
      <td><button class="btn" data-id="${p.id}" data-act="detay">Detay</button> <button class="btn" data-id="${p.id}" data-act="duzenle">Düzenle</button> <button class="btn" data-id="${p.id}" data-act="sil">Sil</button></td>`;
    tbody.appendChild(tr);
  }); tbody.querySelectorAll('button').forEach(b=> b.addEventListener('click', onKisiAction)); }catch(e){ console.error(e); tbody.innerHTML='<tr><td colspan=5>Hata</td></tr>'; }
}

function onKisiAction(e){
  const id=e.target.dataset.id, act=e.target.dataset.act;
  if(act==='detay') openKisiDetay(id);
  if(act==='duzenle') openPersonModal(id);
  if(act==='sil') { if(confirm('Silinsin mi?')) deleteKisi(id); }
}

async function deleteKisi(id){ try{ await api('/kisiler.php?id='+id, 'DELETE'); showToast('Silindi'); refreshKisiler(); }catch(e){ console.error(e); showToast('Hata'); } }

function openPersonModal(id=null){
  const html = `<h3>${id? 'Kişi Düzenle':'Yeni Kişi'}</h3>
    <div class="form-row"><label>Ad <input id="isim"></label></div>
    <div class="form-row"><label>Telefon <input id="telefon"></label></div>
    <div style="text-align:right"><button class="btn" id="kaydetKisi">Kaydet</button> <button class="btn small" id="kapatModal">Kapat</button></div>`;
  openModal(html);
  if(id) { api('/kisiler.php?id='+id).then(r=>{ document.getElementById('isim').value=r.isim; document.getElementById('telefon').value=r.telefon; }).catch(()=>{}); }
  document.getElementById('kapatModal').addEventListener('click', closeModal);
  document.getElementById('kaydetKisi').addEventListener('click', async ()=>{
    const data={isim:document.getElementById('isim').value, telefon:document.getElementById('telefon').value};
    try{ if(id) await api('/kisiler.php?id='+id,'PUT',data); else await api('/kisiler.php','POST',data); showToast('Kaydedildi'); closeModal(); refreshKisiler(); }catch(e){ console.error(e); showToast('Hata'); }
  });
}

async function openKisiDetay(id){
  try{ const p = await api('/kisiler.php?id='+id); const farms = await api('/tarlalar.php?kisi_id='+id);
    let html = `<h3>${p.isim} - Tarlaları</h3><table class="table"><thead><tr><th>Başlık</th><th>Alan</th><th>Durum</th></tr></thead><tbody>`;
    farms.forEach(f=> html+=`<tr><td>${f.baslik}</td><td>${f.alan_decimal}</td><td><small class="muted">${f.lokasyon||''}</small></td></tr>`);
    html += '</tbody></table><div style="text-align:right"><button class="btn small" id="kapat">Kapat</button></div>';
    openModal(html); document.getElementById('kapat').addEventListener('click', closeModal);
  }catch(e){ console.error(e); }
}

async function refreshTarlalar(){
  const area=document.getElementById('tarlalarArea'); area.innerHTML='Yükleniyor...';
  try{ const list = await api('/tarlalar.php'); let html=`<table class="table"><thead><tr><th>Başlık</th><th>Sahip</th><th>Alan</th><th>İşlemler</th></tr></thead><tbody>`;
    list.forEach(t=> html+=`<tr><td>${t.baslik}</td><td>${t.kisi_adi||''}</td><td>${t.alan_decimal}</td><td><button class="btn" data-id="${t.id}" data-act="duzenle">Düzenle</button> <button class="btn" data-id="${t.id}" data-act="sil">Sil</button></td></tr>`);
    html+='</tbody></table>'; area.innerHTML=html; area.querySelectorAll('button').forEach(b=> b.addEventListener('click', async (e)=>{ const act=e.target.dataset.act; const id=e.target.dataset.id; if(act==='duzenle') openTarlaModal(id); if(act==='sil'){ if(confirm('Silinsin mi?')){ await api('/tarlalar.php?id='+id,'DELETE'); showToast('Silindi'); refreshTarlalar(); } } }));
  }catch(e){ console.error(e); area.innerHTML='Hata'; }
}

function openTarlaModal(id=null){
  // fetch kisiler for select
  api('/kisiler.php').then(kisiler=>{
    let opts = kisiler.map(k=>`<option value="${k.id}">${k.isim}</option>`).join('');
    const html = `<h3>${id?'Tarla Düzenle':'Yeni Tarla'}</h3>
      <div class="form-row"><label>Başlık <input id="t_baslik"></label></div>
      <div class="form-row"><label>Sahip <select id="t_kisi">${opts}</select></label></div>
      <div class="form-row"><label>Alan (dönüm) <input id="t_alan" type="number" step="0.01"></label></div>
      <div style="text-align:right"><button class="btn" id="kaydetTarla">Kaydet</button> <button class="btn small" id="kapat">Kapat</button></div>`;
    openModal(html);
    if(id){ api('/tarlalar.php?id='+id).then(r=>{ document.getElementById('t_baslik').value=r.baslik; document.getElementById('t_kisi').value=r.kisi_id; document.getElementById('t_alan').value=r.alan_decimal; }).catch(()=>{}); }
    document.getElementById('kapat').addEventListener('click', closeModal);
    document.getElementById('kaydetTarla').addEventListener('click', async ()=>{
      const data={baslik:document.getElementById('t_baslik').value,kisi_id:document.getElementById('t_kisi').value,alan_decimal:document.getElementById('t_alan').value};
      try{ if(id) await api('/tarlalar.php?id='+id,'PUT',data); else await api('/tarlalar.php','POST',data); showToast('Kaydedildi'); closeModal(); refreshTarlalar(); }catch(e){ console.error(e); showToast('Hata'); }
    });
  });
}

async function loadDurumlarSelect(){
  try{ const d = await api('/alanlar.php?scope=durum'); const sel=document.getElementById('durumSecim'); d.forEach(x=> sel.innerHTML += `<option value="${x.id}">${x.label}</option>`);
    sel.addEventListener('change', ()=> loadDurumList(sel.value));
  }catch(e){ console.error(e); }
}

async function loadDurumList(durumId){
  const area = document.getElementById('durumList'); if(!durumId){ area.innerHTML=''; return; }
  area.innerHTML='Yükleniyor...';
  try{ const list = await api('/durumlar.php?alan_id='+durumId); let html=`<form id="durumForm"><table class="table"><thead><tr><th>Tarla</th><th>Durum</th></tr></thead><tbody>`;
    list.forEach(r=> html+=`<tr><td>${r.tarla_baslik}</td><td><input type="checkbox" name="d_${r.tarla_id}" ${r.deger?'checked':''}></td></tr>`);
    html += '</tbody></table><div style="text-align:right"><button class="btn" id="kaydetDurum">Kaydet</button></div></form>';
    area.innerHTML=html;
    document.getElementById('kaydetDurum').addEventListener('click', async (e)=>{ e.preventDefault();
      const form = document.getElementById('durumForm'); const data = []; new FormData(form).forEach((v,k)=> data.push({k:v})); // simplified
      // We'll send simple payload: checked tarla ids
      const checked = Array.from(form.querySelectorAll('input[type=checkbox]:checked')).map(ch=> ch.name.replace('d_',''));
      await api('/durumlar.php','POST',{alan_id:durumId,checked:checked}); showToast('Kaydedildi');
    });
  }catch(e){ console.error(e); area.innerHTML='Hata'; }
}

async function refreshAlanlar(key){
  const scope = key.includes('kisi')? 'kisi' : key.includes('tarla')? 'tarla' : 'durum';
  try{ const list = await api('/alanlar.php?scope='+scope); let html = '<table class="table"><thead><tr><th>Label</th><th>Tip</th><th>İşlemler</th></tr></thead><tbody>';
    list.forEach(a=> html += `<tr><td>${a.label}</td><td>${a.tip}</td><td><button class="btn" data-id="${a.id}" data-act="sil">Sil</button></td></tr>`);
    html += '</tbody></table>'; document.getElementById('alanlarList').innerHTML=html;
    document.querySelectorAll('#alanlarList button').forEach(b=> b.addEventListener('click', async e=>{ if(confirm('Silinsin mi?')){ await api('/alanlar.php?id='+e.target.dataset.id,'DELETE'); showToast('Silindi'); refreshAlanlar(key); } }));
  }catch(e){ console.error(e); }
}

function openAlanModal(key){
  const scope = key.includes('kisi')? 'kisi' : key.includes('tarla')? 'tarla' : 'durum';
  const html = `<h3>Yeni Alan (${scope})</h3>
    <div class="form-row"><label>Label <input id="a_label"></label></div>
    <div class="form-row"><label>Tip <select id="a_tip"><option value="text">Text</option><option value="select">Select</option><option value="number">Number</option></select></label></div>
    <div class="form-row"><label>Options (select ise, virgülle ayır) <input id="a_options"></label></div>
    <div style="text-align:right"><button class="btn" id="kaydetAlan">Kaydet</button> <button class="btn small" id="kapat">Kapat</button></div>`;
  openModal(html);
  document.getElementById('kapat').addEventListener('click', closeModal);
  document.getElementById('kaydetAlan').addEventListener('click', async ()=>{
    const data = {kapsam:scope,label:document.getElementById('a_label').value,tip:document.getElementById('a_tip').value,options:document.getElementById('a_options').value};
    try{ await api('/alanlar.php','POST',data); showToast('Kaydedildi'); closeModal(); refreshAlanlar(key); }catch(e){ console.error(e); showToast('Hata'); }
  });
}

// başlangıç
renderInit();
function renderInit(){ renderSidebarEvents(); loadPage('dashboard'); document.getElementById('modal').addEventListener('click', e=>{ if(e.target.id==='modal') closeModal(); }); }
