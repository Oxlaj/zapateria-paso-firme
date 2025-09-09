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
function formatPrice(num) { return new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'GTQ' }).format(num); }
function createProductCard(p) {
  const el = document.createElement('article');
  el.className = 'product';
  el.setAttribute('data-id', String(p.id));
  el.innerHTML = `
    <img class="product__img" src="${p.img}" alt="${p.title}" loading="lazy"/>
    <div class="product__body">
      <h3 class="product__title">${p.title}</h3>
      <div class="product__price">${formatPrice(p.price)}</div>
      <div class="product__tags">${p.tags.map(t => `<span class='tag'>${t}</span>`).join('')}</div>
    </div>
    <div class="product__actions">
      <button class="btn btn--outline" data-action="wish" data-id="${p.id}" aria-label="Agregar a favoritos">❤ Favorito</button>
      <button class="btn btn--primary" data-action="buy" data-id="${p.id}" aria-label="Agregar al carrito">🛒 Agregar</button>
    </div>`;
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
  const sorted = PRODUCTS.filter(hasRealImage).map((p, i) => ({ p, i }))
    .sort((a, b) => {
      const aLocal = String(a.p.img || '').startsWith('assets/img/catalogo/');
      const bLocal = String(b.p.img || '').startsWith('assets/img/catalogo/');
      if (aLocal !== bLocal) return aLocal ? -1 : 1;
      return a.i - b.i; // orden estable para el resto
    })
    .map(x => x.p);
  sorted.forEach(p => productsGrid.appendChild(createProductCard(p)));
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
  const prod = PRODUCTS.find(p => p.id === id);
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
renderProducts();
renderTestimonials();
syncCartFromServer().then(()=>renderCart());

const navBarEl = document.querySelector('.nav');
// Sitio público sin BD ni autenticación
document.addEventListener('DOMContentLoaded', ()=>{
  // sin cambios
});

