console.log('Calzado Oxlaj main.js v20250916');
// Version badge helper
(()=>{
  const vEl = document.getElementById('buildVersion');
  if (vEl) vEl.textContent = 'v20250916';
  else console.warn('[CalzadoOxlaj] buildVersion element no encontrado (HTML antiguo en caché)');
})();
// ---- Roles (declarar temprano para evitar ReferenceError) ----
const ROLE_KEY = 'oxlaj_role'; // 'cliente' | 'admin'
const ROLE_PASSWORDS = { cliente: 'cliente123', admin: 'admin123' };
function setRole(role){ try{ localStorage.setItem(ROLE_KEY, role);}catch(e){} }
function getRole(){ try{ return localStorage.getItem(ROLE_KEY);}catch(e){ return null; } }
function clearRole(){ try{ localStorage.removeItem(ROLE_KEY);}catch(e){} }
// Utilidades
const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

// Menu responsive
const navToggle = $('.nav__toggle');
const navMenu = $('#navMenu');
navToggle?.addEventListener('click', () => {
  const isOpen = navMenu.classList.toggle('open');
  navToggle.setAttribute('aria-expanded', String(isOpen));
});
$$('.nav__link').forEach(l => l.addEventListener('click', () => {
  if (navMenu.classList.contains('open')) {
    navMenu.classList.remove('open');
    navToggle.setAttribute('aria-expanded', 'false');
  }
}));

// Render de productos
const productsGrid = $('#productsGrid');
let productsOverride = null; // arreglo opcional para override de PRODUCTS en localStorage
function formatPrice(num) { return new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'GTQ' }).format(num); }
function createProductCard(p) {
  const el = document.createElement('article');
  el.className = 'product';
  el.setAttribute('data-id', String(p.id));
  const isAdmin = getRole && getRole() === 'admin';
  el.innerHTML = `
    <img class="product__img" src="${p.img}" alt="${p.title}" loading="lazy"/>
    <div class="product__body">
      <h3 class="product__title">${p.title}</h3>
      <div class="product__price">${formatPrice(p.price)}</div>
      <div class="product__tags">${(p.tags||[]).map(t => `<span class='tag'>${t}</span>`).join('')}</div>
    </div>
    <div class="product__actions" ${isAdmin? 'style="display:none"':''}>
      <button class="btn btn--outline" data-action="wish" data-id="${p.id}" aria-label="Agregar a favoritos">❤ Favorito</button>
      <button class="btn btn--primary" data-action="buy" data-id="${p.id}" aria-label="Agregar al carrito">🛒 Agregar</button>
    </div>`;
  if (isAdmin) {
    const adminBar = document.createElement('div');
    adminBar.className = 'product__adminBar';
    adminBar.innerHTML = `
      <button class="btn btn--xs" data-admin="edit" data-id="${p.id}">✏️ Editar</button>
      <button class="btn btn--xs btn--danger" data-admin="del" data-id="${p.id}">🗑 Eliminar</button>`;
    el.appendChild(adminBar);
  }
  return el;
}
function renderProducts() {
  if (!productsGrid) return;
  productsGrid.innerHTML = '';
  const hasRealImage = (p) => {
    const src = String(p.img || '').trim();
    if (!src) return false;
    if (src.includes('placehold.co')) return false;
    return true;
  };
  const base = Array.isArray(productsOverride) ? productsOverride : PRODUCTS;
  let list = base.filter(hasRealImage);
  const isAdmin = getRole && getRole()==='admin';
  if(isAdmin && window.__adminSearchTerm){
    const term = window.__adminSearchTerm.toLowerCase();
    list = list.filter(p=> String(p.title||'').toLowerCase().includes(term));
  }
  {
    // Orden original estable por índice + prioridad imágenes locales
    list = list.map((p,i)=>({p,i})).sort((a,b)=>{
      const aLocal = String(a.p.img || '').startsWith('assets/img/catalogo/');
      const bLocal = String(b.p.img || '').startsWith('assets/img/catalogo/');
      if (aLocal !== bLocal) return aLocal ? -1 : 1;
      return a.i - b.i;
    }).map(x=>x.p);
  }
  list.forEach(p => productsGrid.appendChild(createProductCard(p)));
  updateFavButtons();
}

// Slider de testimonios simple
const slider = $('#testimonialSlider');
function renderTestimonials() {
  if (!slider) return;
  slider.innerHTML = '';
  TESTIMONIALS.forEach(t => {
    const card = document.createElement('article');
    card.className = 'testimonial';
    card.innerHTML = `<p>“${t.text}”</p><div class="testimonial__name">${t.name}</div>`;
    slider.appendChild(card);
  });
}

// Back to top
const backTop = $('#backToTop');
window.addEventListener('scroll', () => {
  if (window.scrollY > 400) backTop.classList.add('show');
  else backTop.classList.remove('show');
});
backTop?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

// Año dinámico
$('#year').textContent = new Date().getFullYear();

// Validación básica del formulario
const form = $('#contactForm');
function showError(input, msg) {
  const field = input.closest('.form__field');
  const small = field?.querySelector('.error');
  if (small) small.textContent = msg || '';
}
function validateEmail(email) {
  return /\S+@\S+\.\S+/.test(email);
}
form?.addEventListener('submit', (e) => {
  e.preventDefault();
  const name = $('#name');
  const email = $('#email');
  const message = $('#message');
  let ok = true;

  if (!name.value.trim()) { showError(name, 'Ingresa tu nombre'); ok = false; } else showError(name, '');
  if (!validateEmail(email.value)) { showError(email, 'Correo inválido'); ok = false; } else showError(email, '');
  if (!message.value.trim()) { showError(message, 'Escribe un mensaje'); ok = false; } else showError(message, '');

  if (ok) {
    const msg = `Hola, soy ${name.value.trim()} (${email.value.trim()}).\nQuiero información:\n${message.value.trim()}`;
    const link = buildWaLink({ text: msg });
    window.open(link, '_blank');
  }
});

// WhatsApp floating button and form submission
// Número destino para contacto y pedidos (solo dígitos con código de país, sin +)
const WA_NUMBER = '50254728021';
const waBtn = document.getElementById('waButton');
function buildWaLink(params) {
  const base = 'https://wa.me/';
  const text = encodeURIComponent(params.text || 'Hola, quiero más información.');
  return `${base}${WA_NUMBER.replace(/[^\d]/g,'')}?text=${text}`;
}
waBtn?.addEventListener('click', () => {
  const link = buildWaLink({ text: 'Hola Calzado Oxlaj, me gustaría consultar disponibilidad.' });
  window.open(link, '_blank');
});

// ----- Estado: carrito y favoritos -----
const CART_KEY = 'oxlaj_cart_v1';
const FAV_KEY = 'oxlaj_fav_v1';
let cart = [];
let favs = new Set();

function loadState() {
  try { cart = JSON.parse(localStorage.getItem(CART_KEY)) || []; } catch { cart = []; }
  try { favs = new Set(JSON.parse(localStorage.getItem(FAV_KEY)) || []); } catch { favs = new Set(); }
  updateCartBadge();
}
function saveCart() { localStorage.setItem(CART_KEY, JSON.stringify(cart)); }
function saveFavs() { localStorage.setItem(FAV_KEY, JSON.stringify([...favs])); }

// ----- Toast -----
const toastEl = document.getElementById('toast');
let toastTimer;
function showToast(msg) {
  if (!toastEl) return;
  toastEl.textContent = msg;
  toastEl.hidden = false;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => { toastEl.hidden = true; }, 2000);
}

// ----- Utilidades carrito -----
const cartBtn = document.getElementById('cartBtn');
const cartCountEl = document.getElementById('cartCount');
const drawer = document.getElementById('cartDrawer');
const drawerOverlay = document.getElementById('drawerOverlay');
const cartClose = document.getElementById('cartClose');
const cartList = document.getElementById('cartList');
const cartEmpty = document.getElementById('cartEmpty');
const cartTotalEl = document.getElementById('cartTotal');
const cartCheckout = document.getElementById('cartCheckout');

function updateCartBadge() {
  const count = cart.reduce((sum, it) => sum + (it.qty || 0), 0);
  if (cartCountEl) cartCountEl.textContent = String(count);
}
function openDrawer() {
  if (!drawer) return;
  drawer.classList.add('open');
  if (drawerOverlay) drawerOverlay.hidden = false;
  drawer.setAttribute('aria-hidden', 'false');
}
function closeDrawer() {
  if (!drawer) return;
  drawer.classList.remove('open');
  if (drawerOverlay) drawerOverlay.hidden = true;
  drawer.setAttribute('aria-hidden', 'true');
}
cartBtn?.addEventListener('click', openDrawer);
cartClose?.addEventListener('click', closeDrawer);
drawerOverlay?.addEventListener('click', closeDrawer);

async function addToCart(prod) {
  const idx = cart.findIndex(i => i.id === prod.id);
  if (idx >= 0) cart[idx].qty += 1; else cart.push({ id: prod.id, title: prod.title, price: prod.price, img: prod.img, qty: 1 });
  saveCart(); updateCartBadge(); renderCart(); showToast('Producto agregado al carrito');
  serverAdd(prod.id); // no await para no bloquear UI
}
async function removeFromCart(id) {
  cart = cart.filter(i => i.id !== id); saveCart(); updateCartBadge(); renderCart(); serverRemove(id);
}
async function setQty(id, qty) {
  const it = cart.find(i => i.id === id); if (!it) return; it.qty = Math.max(1, qty); saveCart(); updateCartBadge(); renderCart(); serverUpdate(id, it.qty);
}
function renderCart() {
  if (!cartList || !cartEmpty || !cartTotalEl) return;
  cartList.innerHTML = '';
  if (cart.length === 0) {
    cartEmpty.style.display = 'block';
    cartTotalEl.textContent = formatPrice(0);
    return;
  }
  cartEmpty.style.display = 'none';
  let total = 0;
  cart.forEach(it => {
    const li = document.createElement('li');
    li.className = 'cart__item';
    const sub = it.price * it.qty;
    total += sub;
    li.innerHTML = `
      <img class="cart__img" src="${it.img}" alt="${it.title}">
      <div>
        <div class="cart__title">${it.title}</div>
        <div class="cart__price">${formatPrice(it.price)} · Subtotal: ${formatPrice(sub)}</div>
        <button class="cart__remove" data-role="remove" data-id="${it.id}">Quitar</button>
      </div>
      <div class="cart__controls">
        <button class="cart__btn" data-role="dec" data-id="${it.id}">−</button>
        <span class="cart__qty">${it.qty}</span>
        <button class="cart__btn" data-role="inc" data-id="${it.id}">+</button>
      </div>`;
    cartList.appendChild(li);
  });
  cartTotalEl.textContent = formatPrice(total);
}
cartList?.addEventListener('click', (e) => {
  const btn = e.target.closest('button');
  if (!btn) return;
  const id = Number(btn.getAttribute('data-id'));
  const role = btn.getAttribute('data-role');
  if (role === 'inc') {
    const it = cart.find(i => i.id === id); if (!it) return; setQty(id, it.qty + 1);
  } else if (role === 'dec') {
    const it = cart.find(i => i.id === id); if (!it) return; setQty(id, Math.max(1, it.qty - 1));
  } else if (role === 'remove') {
    removeFromCart(id);
  }
});

cartCheckout?.addEventListener('click', () => {
  if (cart.length === 0) { showToast('Tu carrito está vacío'); return; }
  const lines = cart.map(it => `${it.qty} × ${it.title} (${formatPrice(it.price)} c/u)`);
  const total = cart.reduce((s, it) => s + it.price * it.qty, 0);
  const msg = `Hola, confirmo mi pedido:%0A- ${lines.join('%0A- ')}%0A%0ATotal: ${formatPrice(total)}`;
  const link = buildWaLink({ text: decodeURIComponent(msg) });
  window.open(link, '_blank');
});

// ----- Favoritos -----
function isFav(id) { return favs.has(id); }
function toggleFav(id) {
  if (favs.has(id)) favs.delete(id); else favs.add(id);
  saveFavs();
  updateFavButtons();
  showToast(favs.has(id) ? 'Agregado a favoritos' : 'Quitado de favoritos');
}
function updateFavButtons() {
  if (!productsGrid) return;
  $$("[data-action='wish'][data-id]").forEach(btn => {
    const id = Number(btn.getAttribute('data-id'));
    const active = isFav(id);
    btn.classList.toggle('active', active);
    btn.textContent = active ? '❤ En favoritos' : '❤ Favorito';
  });
}

// ----- Integración con la grilla de productos -----
productsGrid?.addEventListener('click', (e) => {
  const btn = e.target.closest('button');
  if (!btn) return;
  const article = e.target.closest('.product');
  const idAttr = btn.getAttribute('data-id') || article?.getAttribute('data-id');
  const id = Number(idAttr);
  if (!id) return;
  const action = btn.getAttribute('data-action');
  const base = Array.isArray(productsOverride) ? productsOverride : PRODUCTS;
  const prod = base.find(p => p.id === id);
  if (!prod) return;
  if (action === 'buy') {
    addToCart(prod);
  } else if (action === 'wish') {
    toggleFav(id);
  }
});

const USE_SERVER = false; // Deshabilita backend/BD por ahora
const API_BASE = 'api'; // base relativa para XAMPP/Apache local (funciona en subcarpetas)
async function api(path, options={}) {
  if (!USE_SERVER) return null;
  try {
    const res = await fetch(`${API_BASE}${path}`, { headers: { 'Content-Type':'application/json' }, ...options });
    return await res.json();
  } catch(e) { console.warn('API error', e); return null; }
}
async function syncCartFromServer() {
  if (!USE_SERVER) return;
  const data = await api('/cart.php');
  if (data && Array.isArray(data.cart)) {
    cart = data.cart.map(it => ({ id: it.id, title: it.title, price: Number(it.price), img: it.img, qty: it.qty }));
    saveCart();
    updateCartBadge();
    renderCart();
  }
}
async function serverAdd(id) { if (!USE_SERVER) return; await api('/cart.php', { method:'POST', body: JSON.stringify({ id }) }); }
async function serverUpdate(id, qty) { if (!USE_SERVER) return; await api('/cart.php', { method:'PUT', body: JSON.stringify({ id, qty }) }); }
async function serverRemove(id) { if (!USE_SERVER) return; await api(`/cart.php?id=${id}`, { method:'DELETE' }); }

// Inicialización extendida
loadState();
// cargar override de productos desde localStorage
const PROD_KEY = 'oxlaj_products_override';
try { productsOverride = JSON.parse(localStorage.getItem(PROD_KEY) || 'null'); } catch { productsOverride = null; }
renderProducts();
renderTestimonials();
syncCartFromServer().then(()=>renderCart());

// Escuchar cambios de productos en otras pestañas (sin backend) y refrescar
window.addEventListener('storage', (e)=>{
  if (e.key === 'oxlaj_products_override') {
    try { productsOverride = JSON.parse(e.newValue||'null'); } catch { productsOverride = null; }
    renderProducts();
  }
});

const navBarEl = document.querySelector('.nav');
// Roles estáticos (sin BD)
const navLogin = document.getElementById('navLogin');
const roleOverlay = document.getElementById('roleOverlay');
const pageHeader = document.querySelector('header');
const pageMain = document.querySelector('main');
const pageFooter = document.querySelector('footer');
const loginForm = document.getElementById('loginForm');
const logoutBtn = document.getElementById('logoutBtn');
const rolePasswordInput = document.getElementById('rolePassword');
const pwError = document.getElementById('pwError');
const pwToggle = document.getElementById('pwToggle');


function showSite(show){
  [pageHeader,pageMain,pageFooter].forEach(el=>{ if(!el) return; el.style.display = show ? '' : ''; }); // siempre visibles ahora
}
function showLogin(){
  if (roleOverlay) roleOverlay.hidden = false;
  if (roleOverlay) {
    roleOverlay.style.display='flex';
    roleOverlay.style.opacity='1';
    roleOverlay.style.pointerEvents='auto';
    roleOverlay.removeAttribute('aria-hidden');
    document.body.classList.add('role-overlay-open');
  }
  const hasRole = !!getRole();
  if (logoutBtn) logoutBtn.style.display = hasRole ? 'inline-flex' : 'none';
  if (navLogin) navLogin.textContent='Ingresar';
  const current = getRole();
  const radios = $$('input[name="rol"]', roleOverlay||document);
  if (current && radios.length){ radios.forEach(r=>{ r.checked = (r.value === current); }); }
}
function afterLogin(role){
  if (roleOverlay) {
    roleOverlay.setAttribute('aria-hidden','true');
    roleOverlay.style.opacity='0';
    roleOverlay.style.pointerEvents='none';
    setTimeout(()=>{ if(roleOverlay){ roleOverlay.hidden=true; roleOverlay.style.display='none'; roleOverlay.style.opacity=''; document.body.classList.remove('role-overlay-open'); } },250);
  }
  navLogin && (navLogin.textContent = role==='admin'?'Admin':'Cliente');
  logoutBtn && (logoutBtn.style.display='inline-flex');
  // (enlace admin removido de la navegación)
  // Enfocar el contenido principal (productos) tras login
  const foco = document.getElementById('productos') || document.getElementById('inicio');
  if (foco) {
    setTimeout(()=> foco.scrollIntoView({behavior:'smooth', block:'start'}), 300);
  }
  document.body.classList.toggle('is-admin', role==='admin');
  // Re-render para aplicar modo admin (oculta acciones, agrega barra admin)
  renderProducts();
  setupRoleUI();
}

document.addEventListener('DOMContentLoaded', ()=>{
  const role = getRole();
  if (!role) { showLogin(); }
  else { afterLogin(role); }

  // Asegurar selección manual por si algún estilo bloquea el label
  $$('.role-card').forEach(card=>{
    card.addEventListener('click', ()=>{
      const inp = card.querySelector('input[name="rol"]');
      if (inp) { inp.checked = true; }
        const dbg = document.getElementById('roleDebug');
        if(dbg){
          const sel = document.querySelector("input[name='rol']:checked");
          dbg.textContent = 'rol seleccionado: ' + (sel? sel.value : 'ninguno');
        }
        // Toggle clase visual rápida sin depender de :has (compatibilidad y rendimiento)
        $$('.role-card').forEach(c=>c.classList.remove('selected'));
        card.classList.add('selected');
    });
  });
  // nada adicional específico al cargar si es admin (CRUD inline se inyecta al renderizar)
});

navLogin?.addEventListener('click', (e)=>{ e.preventDefault(); showLogin(); });

loginForm?.addEventListener('submit', (e)=>{
  e.preventDefault();

  //ACA ESTA EL HANDLER DEL LOGIN
  // Leer directamente el radio seleccionado (evita problemas si FormData falla en algunos navegadores por reflow)
  const checked = document.querySelector("input[name='rol']:checked");
  let role = checked ? checked.value : 'cliente';
  if(!ROLE_PASSWORDS[role]) { console.warn('[login] rol desconocido capturado:', role, 'forzando cliente'); role='cliente'; }
  const entered = rolePasswordInput ? rolePasswordInput.value.trim() : '';
  const expected = ROLE_PASSWORDS[role];
  console.log('[login] intento v2', { roleSeleccionado: role, enteredLen: entered.length, expectedDefined: !!expected, radioDetectado: !!checked });
  if (!entered) { pwError && (pwError.textContent='Ingresa la contraseña'); return; }
  if (entered === 'demo') { console.warn('[login] usando bypass demo'); }
  else if (entered !== expected) { pwError && (pwError.textContent='Contraseña incorrecta'); return; }
  pwError && (pwError.textContent='');
  setRole(role); afterLogin(role);
  if (rolePasswordInput) rolePasswordInput.value='';
});

logoutBtn?.addEventListener('click', ()=>{ clearRole(); showLogin(); });

pwToggle?.addEventListener('click', ()=>{
  if (!rolePasswordInput) return;
  const t = rolePasswordInput.getAttribute('type')==='password' ? 'text' : 'password';
  rolePasswordInput.setAttribute('type', t);
  pwToggle.textContent = t==='password' ? '👁' : '🙈';
});

// (no existe enlace directo a un panel separado: CRUD es inline)

// ---------- MODO ADMIN: CRUD INLINE ----------
// (usar PROD_KEY ya definido arriba)
function currentProducts(){ return Array.isArray(productsOverride) ? productsOverride : PRODUCTS; }
function ensureOverride(){ if(!Array.isArray(productsOverride)) { productsOverride = PRODUCTS.map(p=>({...p})); } }
function persistProducts(){ localStorage.setItem(PROD_KEY, JSON.stringify(productsOverride)); }

function setupRoleUI(){
  if (!productsGrid) return;
  const isAdmin = getRole() === 'admin';
  // Insertar barra admin si hace falta
  let bar = document.getElementById('adminCatalogBar');
  if (isAdmin){
    if (!bar){
      bar = document.createElement('div');
      bar.id = 'adminCatalogBar';
      bar.innerHTML = `
        <div style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;width:100%">
          <strong style="flex:0 0 auto">Modo administrador</strong>
          <input id="adminSearch" type="text" placeholder="Buscar nombre" style="flex:1 1 220px;padding:.5rem .65rem;border:1px solid #ccd5dd;border-radius:6px;font-size:.75rem" />
          <button type="button" class="btn btn--outline" data-admin="add">➕ Añadir</button>
        </div>
      `;
      productsGrid.parentElement?.insertBefore(bar, productsGrid);
      const search = bar.querySelector('#adminSearch');
      search.addEventListener('input', e=>{
        window.__adminSearchTerm = e.target.value.trim();
        renderProducts();
      });
      // Restaurar termino si existe
      if(window.__adminSearchTerm){
        search.value = window.__adminSearchTerm;
      }
    }
  } else {
    if (bar) bar.remove();
  }
  // Ocultar / mostrar carrito
  const cartRelated = [cartBtn, drawer, drawerOverlay];
  cartRelated.forEach(el=>{ if(!el) return; el.style.display = isAdmin ? 'none':'', el.hidden = isAdmin? true:false; });
}

function htmlEscape(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function enterEdit(id){
  const card = productsGrid.querySelector(`.product[data-id='${id}']`);
  if (!card) return;
  const prod = currentProducts().find(p=>p.id===id);
  if (!prod) return;
  card.classList.add('editing');
  card.innerHTML = `
    <form class="product-edit" data-id="${id}" onsubmit="return false;">
      <label class="pe-field">Título<br><input name="title" value="${htmlEscape(prod.title)}" required></label>
      <label class="pe-field">Precio<br><input name="price" type="number" step="0.01" value="${prod.price}" required></label>
      <div class="pe-field">Imagen
        <div class="img-drop" data-drop>
          <input type="file" accept="image/*" hidden>
          <div class="img-drop__inner" tabindex="0">
            <div class="img-drop__placeholder" data-ph>
              <p style="margin:0;font-size:.65rem;line-height:1.2;color:#334">Arrastra una imagen o <button type="button" class="btn btn--outline btn--pick" data-pick>Selecciona</button></p>
            </div>
            <img alt="Vista previa" data-preview hidden>
          </div>
        </div>
      </div>
      <label class="pe-field">Etiquetas (coma)<br><input name="tags" value="${htmlEscape((prod.tags||[]).join(', '))}"></label>
      <div class="edit__actions">
        <button class="btn btn--primary" type="button" data-admin="save" data-id="${id}">Guardar</button>
        <button class="btn btn--outline" type="button" data-admin="cancel" data-id="${id}">Cancelar</button>
      </div>
    </form>`;
  const form = card.querySelector('form.product-edit');
  if(form){
    form.dataset.originalImg = prod.img || '';
    initImageDrop(form, prod.img);
  }
}

function cancelEdit(){ renderProducts(); }

function saveEdit(id){
  const form = productsGrid.querySelector(`form.product-edit[data-id='${id}']`);
  if(!form) return;
  const fd = new FormData(form);
  const title = String(fd.get('title')||'').trim();
  const price = parseFloat(String(fd.get('price')||'0'));
  const tagsStr = String(fd.get('tags')||'').trim();
  const newImg = form.dataset.imgData || '';
  const original = form.dataset.originalImg || '';
  const finalImg = newImg || original;
  if(!title || !(price>0) || !finalImg){ showToast('Completa los campos (faltan datos o imagen)'); return; }
  const tags = tagsStr? tagsStr.split(',').map(t=>t.trim()).filter(Boolean):[];
  ensureOverride();
  const idx = productsOverride.findIndex(p=>p.id===id);
  if(idx<0) return;
  productsOverride[idx] = { ...productsOverride[idx], title, price, img: finalImg, tags };
  persistProducts();
  showToast('Producto actualizado');
  renderProducts();
  setupRoleUI();
}

function deleteProduct(id){
  const prod = currentProducts().find(p=>p.id===id);
  customConfirm(`¿Eliminar el producto "${prod?prod.title:''}"?`).then(ok=>{
    if(!ok) return;
    ensureOverride();
    productsOverride = productsOverride.filter(p=>p.id!==id);
    persistProducts();
    showToast('Producto eliminado');
    renderProducts();
    setupRoleUI();
  });
}

function toggleAddForm(){
  let form = document.getElementById('adminAddForm');
  if(form){ form.remove(); return; }
  form = document.createElement('form');
  form.id='adminAddForm';
  form.className='admin-add-form';
  form.innerHTML = `
    <label>Título <input name="title" required></label>
    <label>Precio <input name="price" type="number" step="0.01" required></label>
    <div class="pe-field">Imagen
      <div class="img-drop" data-drop>
        <input type="file" accept="image/*" hidden>
        <div class="img-drop__inner" tabindex="0">
          <div class="img-drop__placeholder" data-ph>
            <p style="margin:0;font-size:.65rem;line-height:1.2;color:#334">Arrastra una imagen o <button type="button" class="btn btn--outline btn--pick" data-pick>Selecciona</button></p>
          </div>
          <img alt="Vista previa" data-preview hidden>
        </div>
      </div>
    </div>
    <label>Etiquetas (coma) <input name="tags"></label>
    <div>
      <button type="button" class="btn btn--primary" data-admin="add-save">Guardar nuevo</button>
      <button type="button" class="btn btn--outline" data-admin="add-cancel">Cancelar</button>
    </div>`;
  const bar = document.getElementById('adminCatalogBar');
  bar?.insertAdjacentElement('afterend', form);
  initImageDrop(form);
}

function saveNewProduct(){
  const form = document.getElementById('adminAddForm');
  if(!form) return;
  const fd = new FormData(form);
  const title = String(fd.get('title')||'').trim();
  const price = parseFloat(String(fd.get('price')||'0'));
  const tagsStr = String(fd.get('tags')||'').trim();
  const imgData = form.dataset.imgData || '';
  if(!title || !(price>0) || !imgData){ showToast('Completa los campos (falta imagen)'); return; }
  const tags = tagsStr? tagsStr.split(',').map(t=>t.trim()).filter(Boolean):[];
  ensureOverride();
  const nextId = currentProducts().reduce((m,p)=> Math.max(m,p.id),0)+1;
  productsOverride.push({ id: nextId, title, price, img: imgData, tags });
  persistProducts();
  form.remove();
  showToast('Producto creado');
  renderProducts();
  setupRoleUI();
}

// Delegación global para acciones admin
document.addEventListener('click', (e)=>{
  const btn = e.target.closest('[data-admin]');
  if(!btn) return;
  if(getRole() !== 'admin') return; // seguridad extra
  const action = btn.getAttribute('data-admin');
  const id = Number(btn.getAttribute('data-id')) || null;
  switch(action){
    case 'edit': if(id) enterEdit(id); break;
    case 'del': if(id) deleteProduct(id); break;
    case 'save': if(id) saveEdit(id); break;
    case 'cancel': cancelEdit(); break;
    case 'add': toggleAddForm(); break;
    case 'add-save': saveNewProduct(); break;
    case 'add-cancel': toggleAddForm(); break;
  }
});

// Inject minimal styles for admin inline CRUD (solo una vez)
(function addAdminStyles(){
  if(document.getElementById('adminInlineStyles')) return;
  const css = `body.is-admin .product__actions{display:none}#adminCatalogBar{background:#0D1B2A;color:#fff;padding:.75rem 1rem;margin:2rem auto 1rem;border-radius:.5rem;display:flex;flex-wrap:wrap;gap:1rem;align-items:center}#adminCatalogBar .btn{background:#fff;color:#0D1B2A}#adminCatalogBar .btn:hover{background:#f1f5f9}.product__adminBar{margin-top:.5rem;display:flex;gap:.5rem}.btn--xs{padding:.25rem .5rem;font-size:.7rem;line-height:1;border-radius:.35rem}.btn--danger{background:#b42318;color:#fff}.btn--danger:hover{background:#932015}.product-edit{display:grid;gap:.5rem;font-size:.8rem}.product-edit input{width:100%;padding:.35rem .5rem;border:1px solid #ccc;border-radius:4px;font-size:.8rem}.edit__actions{display:flex;gap:.5rem;margin-top:.25rem}.admin-add-form{background:#f8fafc;padding:1rem;border:1px solid #dce3ec;border-radius:.75rem;margin:0 auto 1.5rem;display:grid;gap:.75rem;max-width:900px}.admin-add-form label{font-size:.75rem;display:grid;gap:.25rem;font-weight:600}.admin-add-form input{padding:.4rem .6rem;font-size:.85rem;border:1px solid #c2ccd6;border-radius:4px;} .img-drop{border:2px dashed #8697a8;border-radius:10px;padding:.75rem;display:block;position:relative;background:#fff;cursor:pointer;transition:.25s}.img-drop:hover{border-color:#0D1B2A}.img-drop.dragover{background:#eef5ff;border-color:#0D1B2A}.img-drop__inner{display:grid;place-items:center;min-height:160px;outline:none}.img-drop__inner:focus-visible{box-shadow:0 0 0 3px rgba(13,27,42,.35);border-radius:6px}.img-drop img{max-width:100%;max-height:180px;object-fit:contain;display:block}.btn--pick{font-size:.6rem;padding:.35rem .5rem;margin-left:.25rem}.modal-overlay{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.45);z-index:4000;padding:1rem}.modal{background:#fff;max-width:420px;width:100%;border-radius:12px;box-shadow:0 10px 40px -5px rgba(0,0,0,.25);padding:1.25rem;display:grid;gap:1rem;font-size:.95rem}.modal__title{font-size:1.05rem;font-weight:600}.modal__actions{display:flex;justify-content:flex-end;gap:.75rem}.modal button{cursor:pointer}.ck-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);display:none;align-items:flex-start;justify-content:center;overflow:auto;padding:2rem 1rem;z-index:4100}.ck-overlay.open{display:flex}.ck-modal{background:#fff;padding:1.5rem 1.25rem;border-radius:14px;max-width:560px;width:100%;display:grid;gap:1rem;box-shadow:0 10px 40px -8px rgba(0,0,0,.3)}.ck-modal h2{margin:0;font-size:1.15rem}.ck-form{display:grid;gap:.85rem}.ck-form label{display:grid;gap:.3rem;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}.ck-form input,.ck-form textarea{padding:.55rem .65rem;border:1px solid #c5ced8;border-radius:6px;font-size:.85rem;font-family:inherit;resize:vertical}.ck-actions{display:flex;justify-content:flex-end;gap:.75rem;margin-top:.25rem}.btn[disabled]{opacity:.6;cursor:not-allowed}`;
  const style = document.createElement('style');
  style.id='adminInlineStyles';
  style.textContent = css;
  document.head.appendChild(style);
})();

// ----- Confirmación custom modal -----
function customConfirm(message){
  return new Promise(resolve=>{
    let overlay = document.getElementById('modalConfirmOverlay');
    if(overlay) overlay.remove();
    overlay = document.createElement('div');
    overlay.id='modalConfirmOverlay';
    overlay.className='modal-overlay';
    overlay.innerHTML = `
      <div class="modal" role="dialog" aria-modal="true">
        <div class="modal__title">Confirmar acción</div>
        <div>${message}</div>
        <div class="modal__actions">
          <button type="button" class="btn btn--outline" data-act="no">Cancelar</button>
          <button type="button" class="btn btn--primary" data-act="yes">Sí</button>
        </div>
      </div>`;
    document.body.appendChild(overlay);
    function done(val){ overlay.remove(); resolve(val); }
    overlay.addEventListener('click', e=>{ if(e.target===overlay) done(false); });
    overlay.querySelector('[data-act="no"]').addEventListener('click',()=>done(false));
    overlay.querySelector('[data-act="yes"]').addEventListener('click',()=>done(true));
  });
}

// ----- Inicializador drag & drop de imagen -----
function initImageDrop(form, existing){
  if(!form) return;
  const dz = form.querySelector('.img-drop');
  if(!dz) return;
  const fileInput = dz.querySelector('input[type="file"]');
  const pickBtn = dz.querySelector('[data-pick]');
  const img = dz.querySelector('img[data-preview]');
  const placeholder = dz.querySelector('[data-ph]');
  function setImage(src){
    if(!img) return;
    img.src = src;
    img.hidden = !src;
    if(src){ placeholder?.setAttribute('hidden',''); form.dataset.imgData = src; }
    else { placeholder?.removeAttribute('hidden'); delete form.dataset.imgData; }
  }
  function handleFiles(files){
    if(!files || !files.length) return;
    const file = files[0];
    if(!file.type.startsWith('image/')){ showToast('Archivo no es una imagen'); return; }
    if(file.size > 1.5 * 1024 * 1024){ showToast('Imagen muy grande (max 1.5MB)'); }
    const reader = new FileReader();
    reader.onload = e=>{ setImage(e.target.result); };
    reader.readAsDataURL(file);
  }
  pickBtn?.addEventListener('click', ()=> fileInput?.click());
  fileInput?.addEventListener('change', e=> handleFiles(e.target.files));
  dz.addEventListener('dragover', e=>{ e.preventDefault(); dz.classList.add('dragover'); });
  dz.addEventListener('dragleave', e=>{ if(e.relatedTarget && dz.contains(e.relatedTarget)) return; dz.classList.remove('dragover'); });
  dz.addEventListener('drop', e=>{ e.preventDefault(); dz.classList.remove('dragover'); handleFiles(e.dataTransfer.files); });
  if(existing){ setImage(existing); }
}

// ----- Checkout overlay con datos del cliente -----
const oldCheckout = cartCheckout; // ya capturado arriba
if(oldCheckout){
  oldCheckout.replaceWith(oldCheckout.cloneNode(true));
}
const newCheckoutBtn = document.getElementById('cartCheckout');
newCheckoutBtn?.addEventListener('click', ()=>{
  if(getRole && getRole()==='admin'){ showToast('Modo administrador: ventas deshabilitadas'); return; }
  if(cart.length===0){ showToast('Tu carrito está vacío'); return; }
  openCheckout();
});

function ensureCheckout(){
  if(document.getElementById('checkoutOverlay')) return;
  const ov = document.createElement('div');
  ov.id='checkoutOverlay';
  ov.className='ck-overlay';
  ov.innerHTML = `
    <div class="ck-modal" role="dialog" aria-modal="true">
      <h2>Finalizar compra</h2>
      <form class="ck-form" id="checkoutForm">
        <label>Nombre completo<input name="nombre" required></label>
        <label>Teléfono (WhatsApp)<input name="telefono" required></label>
        <label>Dirección / Municipio<textarea name="direccion" rows="2" required></textarea></label>
        <label>Notas adicionales<textarea name="notas" rows="3" placeholder="Talla, color u otra indicación"></textarea></label>
        <div class="ck-actions">
          <button type="button" class="btn btn--outline" data-ck="cancel">Cancelar</button>
          <button type="submit" class="btn btn--primary">Enviar pedido</button>
        </div>
      </form>
    </div>`;
  document.body.appendChild(ov);
  ov.addEventListener('click', e=>{ if(e.target===ov) closeCheckout(); });
  ov.querySelector('[data-ck="cancel"]').addEventListener('click', closeCheckout);
  ov.querySelector('#checkoutForm').addEventListener('submit', submitCheckout);
}
function openCheckout(){ ensureCheckout(); const ov = document.getElementById('checkoutOverlay'); ov.classList.add('open'); }
function closeCheckout(){ const ov = document.getElementById('checkoutOverlay'); if(ov) ov.classList.remove('open'); }
function submitCheckout(e){
  e.preventDefault();
  if(cart.length===0){ showToast('Carrito vacío'); return; }
  const fd = new FormData(e.target);
  const nombre = String(fd.get('nombre')||'').trim();
  const telefono = String(fd.get('telefono')||'').trim();
  const direccion = String(fd.get('direccion')||'').trim();
  const notas = String(fd.get('notas')||'').trim();
  if(!nombre || !telefono || !direccion){ showToast('Completa los campos'); return; }
  const lines = cart.map(it => `${it.qty} × ${it.title} (${formatPrice(it.price)} c/u)`);
  const total = cart.reduce((s, it) => s + it.price * it.qty, 0);
  const msg = `Pedido de ${nombre}\nTel: ${telefono}\nDirección: ${direccion}\n\nProductos:\n- ${lines.join('\n- ')}\n\nTotal: ${formatPrice(total)}${notas?`\nNotas: ${notas}`:''}`;
  const link = buildWaLink({ text: msg });
  window.open(link, '_blank');
  cart = []; saveCart(); updateCartBadge(); renderCart();
  closeCheckout();
  showToast('Pedido enviado');
}


