document.addEventListener('DOMContentLoaded',()=>{
  // Sidebar mobile
  const sb=document.getElementById('sidebar'),ov=document.getElementById('sb-overlay'),hb=document.getElementById('hamburger');
  hb?.addEventListener('click',()=>{sb.classList.toggle('open');ov.classList.toggle('open')});
  ov?.addEventListener('click',()=>{sb.classList.remove('open');ov.classList.remove('open')});

  // Toast
  window.toast=(msg,type='s')=>{
    const w=document.getElementById('toast-wrap')||Object.assign(document.body.appendChild(document.createElement('div')),{id:'toast-wrap',className:'toast-wrap'});
    const t=document.createElement('div');
    t.className=`toast ${type}`;
    t.innerHTML=`<span>${type==='s'?'✓':'✕'}</span>${msg}`;
    w.appendChild(t);
    setTimeout(()=>{t.style.opacity='0';t.style.transition='opacity .3s';setTimeout(()=>t.remove(),300)},3000);
  };

  // Modal
  document.querySelectorAll('[data-modal]').forEach(b=>b.addEventListener('click',()=>document.getElementById(b.dataset.modal)?.classList.add('open')));
  document.querySelectorAll('.modal-overlay').forEach(o=>o.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open')}));
  document.querySelectorAll('.modal').forEach(m=>m.addEventListener('click',e=>e.stopPropagation()));
  document.querySelectorAll('.modal-close').forEach(b=>b.addEventListener('click',()=>b.closest('.modal-overlay')?.classList.remove('open')));

  // AJAX Like
  document.querySelectorAll('.like-btn').forEach(btn=>{
    btn.addEventListener('click',async function(){
      const res=await fetch('api/like.php',{method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({post_id:this.dataset.id,csrf:document.querySelector('meta[name=csrf]')?.content})});
      const d=await res.json();
      if(d.success){
        this.classList.toggle('liked',d.liked);
        this.querySelector('.lcount').textContent=d.count;
        this.querySelector('svg').setAttribute('fill',d.liked?'currentColor':'none');
      }
    });
  });

  // Repeater
  window.addRep=(type)=>{
    const c=document.getElementById(type+'-rep'),isE=type==='exp';
    c.insertAdjacentHTML('beforeend',`<div class="rep-item">
      <button type="button" class="rep-remove" onclick="this.parentElement.remove()">×</button>
      <div class="form-row">
        <div class="form-group"><label class="form-label">${isE?'Job Title':'Degree'}</label><input type="text" name="${isE?'exp_title':'edu_degree'}[]" class="form-control" placeholder="${isE?'Frontend Developer':'BSc Computer Science'}" required></div>
        <div class="form-group"><label class="form-label">${isE?'Company':'Institution'}</label><input type="text" name="${isE?'exp_company':'edu_institution'}[]" class="form-control" placeholder="${isE?'Tech Corp':'University name'}" required></div>
      </div>
      <div class="form-group"><label class="form-label">Period</label><input type="text" name="${isE?'exp_period':'edu_period'}[]" class="form-control" placeholder="2023 – Present"></div>
      <div class="form-group"><label class="form-label">Description</label><textarea name="${isE?'exp_desc':'edu_desc'}[]" class="form-control" rows="2" placeholder="Brief description..."></textarea></div>
    </div>`);
  };

  // Auto dismiss alerts
  document.querySelectorAll('.alert[data-auto]').forEach(a=>setTimeout(()=>{a.style.opacity='0';a.style.transition='opacity .5s';setTimeout(()=>a.remove(),500)},4000));

  // Char counter
  document.querySelectorAll('[data-max]').forEach(el=>{
    const s=document.createElement('span');s.style.cssText='font-size:11px;color:var(--text3);float:right';
    el.parentNode.insertBefore(s,el);
    const u=()=>{const r=+el.dataset.max-el.value.length;s.textContent=r+' left';s.style.color=r<20?'var(--danger)':'var(--text3)'};
    el.addEventListener('input',u);u();
  });
});
