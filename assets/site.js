
function calc(){
  const l=parseFloat(document.getElementById('len')?.value||0);
  const w=parseFloat(document.getElementById('wid')?.value||0);
  const r=document.getElementById('calcResult');
  if(!r)return;
  if(l>0&&w>0){
    const sqm=l*w,p=sqm/3.305785;
    r.textContent=`約 ${sqm.toFixed(2)} m²｜約 ${p.toFixed(2)} 坪`;
  } else r.textContent='請輸入正確長度與寬度。';
}
let rfqStep=1,rfqChoice='';
function pickChoice(el){
  el.parentElement.querySelectorAll('.choice').forEach(x=>x.classList.remove('selected'));
  el.classList.add('selected');rfqChoice=el.textContent.trim();
}
function renderRFQ(){
  document.querySelectorAll('.rfqStep').forEach(x=>x.classList.toggle('on',Number(x.dataset.step)===rfqStep));
  document.querySelectorAll('#rfqProgress span').forEach((x,i)=>x.classList.toggle('on',i<rfqStep));
}
function nextRFQ(){if(rfqStep<5){rfqStep++;renderRFQ();}}
function prevRFQ(){if(rfqStep>1){rfqStep--;renderRFQ();}}
function submitWizard(e){
  e.preventDefault();
  const r=document.getElementById('rfqResult');
  if(r){r.classList.remove('hidden');r.textContent='目前為前端展示版；正式 RFQ Backend 上線後才會保存資料。';}
}


(function(){
  const form=document.getElementById('rfqForm');
  if(!form)return;
  const steps=[...form.querySelectorAll('.rfqStep')];
  let current=0;
  const show=(i)=>{current=Math.max(0,Math.min(steps.length-1,i));steps.forEach((s,n)=>s.classList.toggle('on',n===current));document.querySelectorAll('#rfqProgress span').forEach((x,n)=>x.classList.toggle('on',n<=current));window.scrollTo({top:form.offsetTop-100,behavior:'smooth'});};
  form.querySelectorAll('[data-next]').forEach(btn=>btn.addEventListener('click',()=>{
    const required=[...steps[current].querySelectorAll('[required]')];
    for(const el of required){if(!el.checkValidity()){el.reportValidity();return;}}
    if(current===2){
      const files=document.getElementById('rfqPhotos').files;
      if(files.length>5){alert('最多上傳 5 張照片。');return;}
      for(const f of files){if(f.size>8*1024*1024){alert('單張照片請控制在 8 MB 以下。');return;}}
    }
    if(current===2||current===3) updateSummary();
    show(current+1);
  }));
  form.querySelectorAll('[data-prev]').forEach(btn=>btn.addEventListener('click',()=>show(current-1)));

  const filesEl=document.getElementById('rfqPhotos');
  filesEl?.addEventListener('change',()=>{
    const n=filesEl.files.length;
    document.getElementById('photoStatus').textContent=n?`已選擇 ${n} 張照片。照片預設為私人資料，不會公開成案例。`:'照片只用於本次詢問與施工評估，預設不公開成案例。';
  });

  function val(name){return form.elements[name]?.value?.trim()||''}
  function updateSummary(){
    const n=filesEl?.files?.length||0;
    document.getElementById('rfqSummary').innerHTML=
      `<b>姓名／稱呼：</b>${esc(val('customer_name'))}<br>`+
      `<b>聯絡方式：</b>${esc(val('contact_value'))}<br>`+
      `<b>地區：</b>${esc(val('city')+' '+val('district'))}<br>`+
      `<b>約略坪數：</b>${esc(val('area_ping')||'未填')}<br>`+
      `<b>需求：</b>${esc(val('request_type'))}<br>`+
      `<b>原地面：</b>${esc(val('existing_floor')||'未填')}<br>`+
      `<b>主要問題：</b>${esc(val('floor_issue')||'未填')}<br>`+
      `<b>照片：</b>${n} 張`;
  }
  function esc(s){return String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));}

  form.addEventListener('submit',async(e)=>{
    e.preventDefault();
    if(!form.checkValidity()){form.reportValidity();return;}
    const status=document.getElementById('submitStatus');
    const submit=document.getElementById('submitRFQ');
    submit.disabled=true;status.textContent='正在送出，請稍候…';

    const fd=new FormData(form);
    fd.set('landing_page',location.pathname);
    fd.set('source_page',document.referrer||location.pathname);
    fd.set('referrer_url',document.referrer||'');
    const qs=new URLSearchParams(location.search);
    for(const k of ['utm_source','utm_medium','utm_campaign','utm_content','utm_term']){
      if(qs.get(k))fd.set(k,qs.get(k));
    }

    try{
      const res=await fetch('/api/submit-rfq.php',{method:'POST',body:fd,credentials:'same-origin'});
      const data=await res.json().catch(()=>({ok:false}));
      if(!res.ok||!data.ok)throw new Error(data.error||'SUBMIT_FAILED');
      form.classList.add('hidden');
      document.getElementById('rfqNo').textContent=data.rfq_no;
      document.getElementById('rfqSuccess').classList.remove('hidden');
      window.scrollTo({top:document.getElementById('rfqSuccess').offsetTop-100,behavior:'smooth'});
    }catch(err){
      status.textContent='目前沒有成功送出，請不要重複送出太多次。請稍後再試。';
      submit.disabled=false;
    }
  });
})();

document.addEventListener('click', async (e) => {
  const btn = e.target.closest('[data-copy-line]');
  if (!btn) return;
  const id = btn.getAttribute('data-copy-line') || 'iceaureus';
  try {
    await navigator.clipboard.writeText(id);
    const old = btn.textContent;
    btn.textContent = '已複製：' + id;
    setTimeout(() => btn.textContent = old, 1800);
  } catch (err) {
    window.prompt('請複製 LINE ID：', id);
  }
});
