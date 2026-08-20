
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
