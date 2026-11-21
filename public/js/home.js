(function () {
    const $ = id => document.getElementById(id);
    const grid = $('grid');
    const content = document.querySelector('.content');
    const cards = [...grid.querySelectorAll('.card')];
    const filterDrawer = document.querySelector('.filter-drawer');
    const compactLayout = window.matchMedia('(max-width: 900px)');
    const cartKey = `oversmoke-cart-user-${document.body.dataset.userId}`;

    document.querySelectorAll('[data-featured-carousel]').forEach(carousel => {
        const slides = [...carousel.querySelectorAll('[data-featured-slide]')];
        const dots = [...carousel.querySelectorAll('[data-featured-dot]')];
        if (slides.length < 2) return;

        let activeIndex = 0;
        let autoplayTimer = null;
        let pointerOver = false;
        let hasFocus = false;
        let manuallyPaused = false;
        const autoplayToggle = carousel.querySelector('[data-featured-toggle]');

        const showSlide = (index, direction = index > activeIndex ? 'next' : 'previous') => {
            const previousIndex = activeIndex;
            activeIndex = (index + slides.length) % slides.length;
            if (activeIndex === previousIndex) return;

            slides.forEach((slide, slideIndex) => {
                slide.hidden = slideIndex !== activeIndex;
                slide.classList.remove('is-active', 'slide-next', 'slide-previous');
                if (slideIndex === activeIndex) {
                    slide.classList.add('is-active', `slide-${direction}`);
                }
            });
            dots.forEach((dot, dotIndex) => {
                if (dotIndex === activeIndex) {
                    dot.setAttribute('aria-current', 'true');
                    dot.tabIndex = 0;
                } else {
                    dot.removeAttribute('aria-current');
                    dot.tabIndex = -1;
                }
            });
        };

        const stopAutoplay = () => {
            window.clearInterval(autoplayTimer);
            autoplayTimer = null;
        };
        const startAutoplay = () => {
            stopAutoplay();
            if (manuallyPaused || document.hidden || pointerOver || hasFocus) return;

            autoplayTimer = window.setInterval(() => showSlide(activeIndex + 1, 'next'), 6000);
        };
        const showManualSlide = (index, direction) => {
            showSlide(index, direction);
            startAutoplay();
        };

        carousel.querySelector('[data-featured-previous]').addEventListener('click', () => showManualSlide(activeIndex - 1, 'previous'));
        carousel.querySelector('[data-featured-next]').addEventListener('click', () => showManualSlide(activeIndex + 1, 'next'));
        autoplayToggle?.addEventListener('click', () => {
            manuallyPaused = !manuallyPaused;
            autoplayToggle.textContent = manuallyPaused ? 'Play' : 'Pause';
            autoplayToggle.setAttribute('aria-label', `${manuallyPaused ? 'Play' : 'Pause'} automatic featured products`);
            autoplayToggle.setAttribute('aria-pressed', String(manuallyPaused));
            if (manuallyPaused) stopAutoplay();
            else {
                hasFocus = false;
                startAutoplay();
            }
        });
        dots.forEach(dot => {
            dot.addEventListener('click', () => showManualSlide(Number(dot.dataset.featuredDot)));
        });
        carousel.addEventListener('keydown', event => {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                showManualSlide(activeIndex - 1, 'previous');
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                showManualSlide(activeIndex + 1, 'next');
            }
        });
        carousel.addEventListener('pointerenter', event => {
            if (event.pointerType !== 'mouse') return;
            pointerOver = true;
            stopAutoplay();
        });
        carousel.addEventListener('pointerleave', event => {
            if (event.pointerType !== 'mouse') return;
            pointerOver = false;
            startAutoplay();
        });
        carousel.addEventListener('focusin', () => {
            hasFocus = true;
            stopAutoplay();
        });
        carousel.addEventListener('focusout', event => {
            if (carousel.contains(event.relatedTarget)) return;
            hasFocus = false;
            startAutoplay();
        });
        document.addEventListener('visibilitychange', startAutoplay);
        startAutoplay();
    });

    function syncFilterDrawer() {
        filterDrawer.open = !compactLayout.matches;
    }

    syncFilterDrawer();
    compactLayout.addEventListener('change', syncFilterDrawer);

    function syncCartCount() {
        const items = JSON.parse(localStorage.getItem(cartKey) || '[]');
        $('cartCount').textContent = items.length;
    }

    syncCartCount();
    window.addEventListener('pageshow', syncCartCount);
    window.addEventListener('storage', event => {
        if (event.key === cartKey) syncCartCount();
    });

    if ('IntersectionObserver' in window) {
        const cardObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                cardObserver.unobserve(entry.target);
            });
        }, { threshold: 0.12 });

        cards.forEach(card => {
            card.classList.add('scroll-reveal');
            cardObserver.observe(card);
        });
    } else {
        cards.forEach(card => card.classList.add('is-visible'));
    }

    /* ---------- Filters / search / sort ---------- */
    function applyFilters() {
        const cats   = [...document.querySelectorAll('.f-cat:checked')].map(i => i.value);
        const brands = [...document.querySelectorAll('.f-brand:checked')].map(i => i.value);
        const minValue = $('minPrice').value;
        const maxValue = $('maxPrice').value;
        const min = minValue === '' ? 0 : Math.max(0, Number(minValue));
        const max = maxValue === '' ? Infinity : Math.max(0, Number(maxValue));
        const q = $('search').value.trim().toLowerCase();

        const visible = cards.filter(c => {
            const price = parseFloat(c.dataset.price);
            const okCat   = !cats.length   || cats.includes(c.dataset.category);
            const okBrand = !brands.length || brands.includes(c.dataset.brand);
            const okPrice = price >= min && price <= max;
            const okQ = !q || (c.dataset.name + ' ' + c.dataset.brand + ' ' + c.dataset.category).toLowerCase().includes(q);
            return okCat && okBrand && okPrice && okQ;
        });

        const sort = $('sort').value;
        visible.sort((a, b) => {
            if (sort === 'low')  return a.dataset.price - b.dataset.price;
            if (sort === 'high') return b.dataset.price - a.dataset.price;
            if (sort === 'az')   return a.dataset.name.localeCompare(b.dataset.name);
            return a.dataset.index - b.dataset.index;
        });

        cards.forEach(c => c.style.display = 'none');
        visible.forEach(c => { c.style.display = ''; grid.appendChild(c); });
        $('empty').style.display = visible.length ? 'none' : 'block';
    }

    document.querySelectorAll('.f-cat, .f-brand, #minPrice, #maxPrice, #sort').forEach(el => el.addEventListener('input', applyFilters));
    $('search').addEventListener('input', applyFilters);

    $('clearFilters').addEventListener('click', () => {
        document.querySelectorAll('.f-cat, .f-brand').forEach(i => i.checked = false);
        $('minPrice').value = 0; $('maxPrice').value = $('maxPrice').dataset.default || 5000;
        $('search').value = ''; $('brandSearch').value = ''; $('sort').value = 'pop';
        $('showMore').dataset.open = '';
        $('showMore').textContent = 'Show More ⌄';
        $('showMore').setAttribute('aria-expanded', 'false');
        document.querySelectorAll('.extra-brand').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.brand-item').forEach(el => el.style.display = '');
        applyFilters();
    });

    /* ---------- Brand search + show more ---------- */
    $('brandSearch').addEventListener('input', e => {
        const q = e.target.value.toLowerCase();
        document.querySelectorAll('.brand-item').forEach(el => {
            const match = el.textContent.toLowerCase().includes(q);
            el.style.display = match && (!el.classList.contains('extra-brand') || $('showMore').dataset.open || q) ? 'flex' : 'none';
        });
    });
    $('showMore').addEventListener('click', e => {
        const button = e.currentTarget;
        const open = button.dataset.open !== '1';
        button.dataset.open = open ? '1' : '';
        button.setAttribute('aria-expanded', String(open));
        document.querySelectorAll('.extra-brand').forEach(el => {
            const matchesSearch = el.textContent.toLowerCase().includes($('brandSearch').value.trim().toLowerCase());
            el.style.display = open && matchesSearch ? 'flex' : 'none';
        });
        button.textContent = open ? 'Show Less ⌃' : 'Show More ⌄';
    });

    /* ---------- Grid / list ---------- */
    $('gridBtn').addEventListener('click', () => { grid.classList.remove('list'); $('gridBtn').classList.add('active'); $('listBtn').classList.remove('active'); });
    $('listBtn').addEventListener('click', () => { grid.classList.add('list'); $('listBtn').classList.add('active'); $('gridBtn').classList.remove('active'); });

    /* ---------- Shop Now: scroll to the product catalog ---------- */
    document.querySelector('.banner .shop').addEventListener('click', e => {
        e.preventDefault();
        window.scrollTo({
            top: content.getBoundingClientRect().top + window.scrollY,
            behavior: 'smooth'
        });
    });

    /* ---------- 3D tilt ---------- */
    cards.forEach(card => {
        card.addEventListener('mousemove', e => {
            if (grid.classList.contains('list')) return;
            const r = card.getBoundingClientRect();
            const x = (e.clientX - r.left) / r.width - .5;
            const y = (e.clientY - r.top) / r.height - .5;
            card.style.setProperty('--ry', (x * 12) + 'deg');
            card.style.setProperty('--rx', (-y * 12) + 'deg');
        });
        card.addEventListener('mouseleave', () => {
            card.style.setProperty('--rx', '0deg');
            card.style.setProperty('--ry', '0deg');
        });
    });

    /* ---------- Lumilipad na sparks ---------- */
    (function () {
        const c = document.createElement('canvas');
        c.id = 'fx';
        document.body.insertBefore(c, document.querySelector('.layout'));
        const ctx = c.getContext('2d');
        let W, H;
        const resize = () => { W = c.width = innerWidth; H = c.height = innerHeight; };
        resize();
        addEventListener('resize', resize);

        function spawn(init) {
            return {
                x: Math.random() * W,
                y: init ? Math.random() * H : H + 20,
                r: Math.random() * 2.2 + .6,
                vy: Math.random() * .5 + .15,
                vx: (Math.random() - .5) * .3,
                a: Math.random() * .5 + .2,
                hue: Math.random() < .5 ? 260 : 215
            };
        }
        const P = Array.from({ length: 70 }, () => spawn(true));

        (function loop() {
            ctx.clearRect(0, 0, W, H);
            for (const p of P) {
                p.y -= p.vy;
                p.x += p.vx + Math.sin(p.y / 60) * .2;
                if (p.y < -20) Object.assign(p, spawn(false));
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.shadowBlur = 12;
                ctx.shadowColor = `hsl(${p.hue},90%,60%)`;
                ctx.fillStyle = `hsla(${p.hue},90%,70%,${p.a})`;
                ctx.fill();
            }
            requestAnimationFrame(loop);
        })();
    })();

    /* ---------- Feature cards: lalabas habang nag-i-scroll ---------- */
    const io = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('show'); io.unobserve(e.target); } });
    }, { root: content, threshold: .2 });

    document.querySelectorAll('.feature').forEach((f, i) => {
        f.style.transitionDelay = (i * 150) + 'ms';
        io.observe(f);
        f.addEventListener('mousemove', e => {
            const r = f.getBoundingClientRect();
            f.style.setProperty('--mx', (e.clientX - r.left) + 'px');
            f.style.setProperty('--my', (e.clientY - r.top) + 'px');
        });
    });

    /* ---------- Start ---------- */
})();