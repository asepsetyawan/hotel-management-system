const root=document.documentElement;
let savedTheme;
try { savedTheme=localStorage.getItem('stayos-theme'); } catch {}
if(savedTheme==='dark'&&document.body.classList.contains('admin-shell')) root.dataset.theme='dark';
function updateThemeButtons(){
 document.querySelectorAll('[data-theme-toggle]').forEach(button=>{
  const dark=root.dataset.theme==='dark';
  button.setAttribute('aria-pressed',String(dark));
  button.setAttribute('aria-label',`Switch to ${dark?'light':'dark'} theme`);
 });
}
updateThemeButtons();
let drawerTrigger;
const sidebar=document.getElementById('sidebar');
const backdrop=document.querySelector('.sidebar-backdrop');
const mobileQuery=window.matchMedia('(max-width:767px)');
function setDrawer(open){
 if(!sidebar)return;
 sidebar.classList.toggle('open',open);
 sidebar.inert=mobileQuery.matches&&!open;
 if(backdrop)backdrop.hidden=!open;
 document.querySelectorAll('[data-sidebar]').forEach(button=>button.setAttribute('aria-expanded',String(open)));
 document.body.style.overflow=open?'hidden':'';
 const main=document.querySelector('.main-wrap');
 if(main)main.inert=open;
 if(open){drawerTrigger=document.activeElement;sidebar.querySelector('[data-sidebar-close]')?.focus();}
 else if(drawerTrigger){drawerTrigger.focus();drawerTrigger=null;}
}
if(sidebar){sidebar.inert=mobileQuery.matches;mobileQuery.addEventListener('change',()=>setDrawer(false));}
function activateTab(tab){
 const tablist=tab.closest('[role=tablist]');
 tablist?.querySelectorAll('[data-tab]').forEach(item=>{
  const active=item===tab;
  item.classList.toggle('active',active);
  item.setAttribute('aria-selected',String(active));
  item.tabIndex=active?0:-1;
  const panel=document.getElementById(item.getAttribute('aria-controls'));
  if(panel)panel.hidden=!active;
 });
}
const dialogTriggers=new WeakMap();
document.addEventListener('click',event=>{
 const open=event.target.closest('[data-open]');
 if(open){const dialog=document.getElementById(open.dataset.open);if(dialog){dialogTriggers.set(dialog,open);dialog.showModal();dialog.querySelector('input:not([type=hidden]),select,button')?.focus();}}
 if(event.target.closest('[data-close]'))event.target.closest('dialog')?.close();
 if(event.target.closest('[data-sidebar]'))setDrawer(!sidebar?.classList.contains('open'));
 if(event.target.closest('[data-sidebar-close]'))setDrawer(false);
 const theme=event.target.closest('[data-theme-toggle]');
 if(theme){root.dataset.theme=root.dataset.theme==='dark'?'light':'dark';try{localStorage.setItem('stayos-theme',root.dataset.theme);}catch{}updateThemeButtons();}
 const publicMenu=event.target.closest('[data-public-menu]');
 if(publicMenu){const nav=document.getElementById('public-navigation');const open=nav?.classList.toggle('open');publicMenu.setAttribute('aria-expanded',String(!!open));}
 if(event.target.closest('.public-nav-links a')){document.getElementById('public-navigation')?.classList.remove('open');document.querySelector('[data-public-menu]')?.setAttribute('aria-expanded','false');}
 const copy=event.target.closest('[data-copy]');
 if(copy){const input=document.getElementById(copy.dataset.copy);if(input){const original=copy.textContent;const fallback=()=>{input.select();input.focus();};if(navigator.clipboard){navigator.clipboard.writeText(input.value).then(()=>{copy.textContent='Copied';setTimeout(()=>copy.textContent=original,1800);}).catch(fallback);}else fallback();}}
 const tab=event.target.closest('[data-tab]');
 if(tab)activateTab(tab);
 if(event.target.closest('[data-print]'))window.print();
 const cadence=event.target.closest('[data-pricing-period]');
 if(cadence){const yearly=cadence.dataset.pricingPeriod==='year';document.querySelectorAll('[data-pricing-period]').forEach(button=>button.setAttribute('aria-pressed',String(button===cadence)));document.querySelectorAll('[data-plan-price]').forEach(price=>price.textContent=yearly?price.dataset.year:price.dataset.month);document.querySelectorAll('[data-price-unit]').forEach(unit=>unit.textContent=yearly?'/year':'/month');}
});
document.addEventListener('keydown',event=>{
 if(event.key==='Escape'){
  if(sidebar?.classList.contains('open'))setDrawer(false);
  const nav=document.getElementById('public-navigation');
  if(nav?.classList.contains('open')){nav.classList.remove('open');const toggle=document.querySelector('[data-public-menu]');toggle?.setAttribute('aria-expanded','false');toggle?.focus();}
 }
 if(sidebar?.classList.contains('open')&&event.key==='Tab'){
  const focusable=[...sidebar.querySelectorAll('a[href],button,select')].filter(el=>!el.disabled&&el.getClientRects().length);
  const first=focusable[0],last=focusable.at(-1);
  if(event.shiftKey&&document.activeElement===first){event.preventDefault();last?.focus();}
  else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first?.focus();}
 }
 const tab=event.target.closest('[data-tab]');
 if(tab&&['ArrowLeft','ArrowRight','Home','End'].includes(event.key)){
  event.preventDefault();const tabs=[...tab.closest('[role=tablist]').querySelectorAll('[data-tab]')];
  const index=event.key==='Home'?0:event.key==='End'?tabs.length-1:(tabs.indexOf(tab)+(event.key==='ArrowRight'?1:-1)+tabs.length)%tabs.length;
  activateTab(tabs[index]);tabs[index].focus();
 }
});
document.querySelectorAll('dialog').forEach(dialog=>{
 const heading=dialog.querySelector('h2');
 if(heading){heading.id ||= `${dialog.id}-title`;dialog.setAttribute('aria-labelledby',heading.id);}
 dialog.addEventListener('close',()=>dialogTriggers.get(dialog)?.focus());
 dialog.addEventListener('click',event=>{if(event.target===dialog){const rect=dialog.getBoundingClientRect();if(event.clientX<rect.left||event.clientX>rect.right||event.clientY<rect.top||event.clientY>rect.bottom)dialog.close();}});
});
document.querySelectorAll('form').forEach(form=>form.addEventListener('submit',event=>{
 if(form.dataset.submitting==='true'){event.preventDefault();return;}
 form.dataset.submitting='true';form.setAttribute('aria-busy','true');
 const button=event.submitter||form.querySelector('button[type=submit],button:not([type])');
 if(button){button.disabled=true;button.dataset.originalLabel=button.textContent;button.textContent=form.method.toLowerCase()==='get'?'Loading…':'Saving…';}
 const status=document.createElement('p');status.className='form-status';status.setAttribute('role','status');status.textContent=form.method.toLowerCase()==='get'?'Loading results…':'Saving your changes…';form.append(status);
}));
window.addEventListener('pageshow',()=>{
 document.querySelectorAll('form[data-submitting=true]').forEach(form=>{delete form.dataset.submitting;form.removeAttribute('aria-busy');form.querySelector('.form-status')?.remove();form.querySelectorAll('button[data-original-label]').forEach(button=>{button.disabled=false;button.textContent=button.dataset.originalLabel;delete button.dataset.originalLabel;});});
});
document.querySelectorAll('input[name=check_in]').forEach(input=>input.addEventListener('change',()=>{
 const out=input.form?.querySelector('input[name=check_out]');if(!out||!input.value)return;
 const date=new Date(input.value+'T12:00:00');date.setDate(date.getDate()+1);
 const min=[date.getFullYear(),String(date.getMonth()+1).padStart(2,'0'),String(date.getDate()).padStart(2,'0')].join('-');out.min=min;if(!out.value||out.value<=input.value)out.value=min;
}));

const staySearch=document.querySelector('[data-stay-search]');
if(staySearch){
 const panels=[...staySearch.querySelectorAll('[data-search-panel]')];
 const arrival=staySearch.elements.check_in,departure=staySearch.elements.check_out;
 const adults=staySearch.elements.adults,children=staySearch.elements.children,rooms=staySearch.elements.rooms;
 const day=value=>new Date(value+'T12:00:00');
 const dateFormat=new Intl.DateTimeFormat('en-GB',{weekday:'short',day:'2-digit',month:'short'});
 const plural=(count,one,many=one+'s')=>`${count} ${count===1?one:many}`;
 function updateSearch(){
  const start=day(arrival.value),end=day(departure.value);
  const nights=Math.round((Date.UTC(end.getFullYear(),end.getMonth(),end.getDate())-Date.UTC(start.getFullYear(),start.getMonth(),start.getDate()))/86400000);
  departure.setCustomValidity(nights>90?'Choose a stay of no more than 90 nights.':'');
  if(Number.isFinite(nights)&&nights>0)staySearch.querySelector('[data-dates-summary]').textContent=`${dateFormat.format(start)} – ${dateFormat.format(end)} (${plural(nights,'night')})`;
  const adultCount=Number(adults.value),childCount=Number(children.value),roomCount=Number(rooms.value);
  adults.setCustomValidity(adultCount<roomCount?'Choose at least one adult per room.':'');
  staySearch.querySelector('[data-guests-summary]').textContent=`${plural(adultCount,'adult')}, ${plural(childCount,'child','children')}, ${plural(roomCount,'room')}`;
  staySearch.querySelectorAll('[data-step]').forEach(button=>{
   const input=document.getElementById(button.dataset.for);
   button.disabled=Number(button.dataset.step)<0?Number(input.value)<=Number(input.min):Number(input.value)>=Number(input.max);
  });
 }
 function closePanel(panel,focus=false){panel.open=false;if(focus)panel.querySelector('summary').focus();}
 panels.forEach(panel=>panel.addEventListener('toggle',()=>{if(panel.open)panels.filter(other=>other!==panel).forEach(other=>closePanel(other));}));
 staySearch.addEventListener('click',event=>{
  const step=event.target.closest('[data-step]');
  if(step){const input=document.getElementById(step.dataset.for);input.value=Math.min(Number(input.max),Math.max(Number(input.min),Number(input.value)+Number(step.dataset.step)));updateSearch();}
  const done=event.target.closest('[data-search-done]');
  if(done){const panel=done.closest('details');const invalid=[...panel.querySelectorAll('input')].find(input=>!input.checkValidity());if(invalid)invalid.reportValidity();else closePanel(panel,true);}
 });
 staySearch.addEventListener('input',updateSearch);
 staySearch.addEventListener('change',updateSearch);
 staySearch.addEventListener('invalid',event=>{const panel=event.target.closest('details');if(panel)panel.open=true;},true);
 staySearch.addEventListener('keydown',event=>{if(event.key==='Escape')panels.filter(panel=>panel.open).forEach(panel=>closePanel(panel,true));});
 document.addEventListener('click',event=>panels.filter(panel=>panel.open&&!panel.contains(event.target)).forEach(panel=>closePanel(panel)));
 updateSearch();
}
