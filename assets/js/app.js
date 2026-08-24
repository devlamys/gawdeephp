(() => {
    'use strict';

    const qs = (selector, scope = document) => scope.querySelector(selector);
    const qsa = (selector, scope = document) => [...scope.querySelectorAll(selector)];
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const header = qs('[data-header]');
    const menuToggles = qsa('[data-menu-toggle]');
    const mobileMenu = qs('[data-mobile-menu]');
    const searchToggle = qs('[data-search-toggle]');
    const searchClose = qs('[data-search-close]');
    const searchPanel = qs('[data-search-panel]');
    const searchInput = qs('[data-site-search]');

    const setHeaderState = () => header?.classList.toggle('is-scrolled', window.scrollY > 12);
    setHeaderState();
    window.addEventListener('scroll', setHeaderState, { passive: true });

    menuToggles.forEach(menuToggle => menuToggle.addEventListener('click', () => {
        const isOpen = mobileMenu?.classList.toggle('is-open') ?? false;
        menuToggles.forEach(toggle => toggle.setAttribute('aria-expanded', String(isOpen)));
        menuToggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
        qs('i', menuToggle)?.classList.toggle('ph-x', isOpen);
        qs('i', menuToggle)?.classList.toggle('ph-list', !isOpen);
    }));

    qsa('[data-mobile-menu] a').forEach(link => link.addEventListener('click', () => {
        mobileMenu?.classList.remove('is-open');
        menuToggles.forEach(toggle => toggle.setAttribute('aria-expanded', 'false'));
    }));

    const openSearch = () => {
        searchPanel?.classList.add('is-open');
        window.setTimeout(() => searchInput?.focus(), 100);
    };

    const closeSearch = () => searchPanel?.classList.remove('is-open');
    searchToggle?.addEventListener('click', openSearch);
    searchClose?.addEventListener('click', closeSearch);

    qsa('[data-password-toggle]').forEach(button => {
        button.addEventListener('click', () => {
            const input = button.closest('div')?.querySelector('input');
            if (!input) return;
            const reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            button.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
            const icon = qs('i', button);
            icon?.classList.toggle('ph-eye', !reveal);
            icon?.classList.toggle('ph-eye-slash', reveal);
        });
    });

    const productGrid = qs('[data-product-grid]');
    const productCards = qsa('[data-category]', productGrid || document);
    const emptyState = qs('[data-product-empty]');
    const catalogCount = qs('[data-catalog-count]');
    const catalogSearch = qs('[data-catalog-search]');
    let activeFilter = productGrid?.dataset.initialCategory || 'all';
    let activeSearch = '';

    const filterProducts = () => {
        if (!productGrid) return;
        let visibleCount = 0;

        productCards.forEach(card => {
            const matchesCategory = activeFilter === 'all' || card.dataset.category === activeFilter;
            const matchesSearch = !activeSearch || (card.dataset.searchName || '').includes(activeSearch);
            const visible = matchesCategory && matchesSearch;
            card.hidden = !visible;
            if (visible) visibleCount += 1;
        });

        if (emptyState) emptyState.hidden = visibleCount !== 0;
        if (catalogCount) catalogCount.textContent = String(visibleCount);
    };

    qsa('[data-filter]').forEach(button => {
        button.addEventListener('click', () => {
            activeFilter = button.dataset.filter || 'all';
            qsa('[data-filter]').forEach(item => item.classList.toggle('is-active', item === button));
            filterProducts();
        });
    });

    qsa('[data-nav-filter], [data-category-link]').forEach(link => {
        link.addEventListener('click', event => {
            if (!productGrid) return;
            const destination = new URL(link.href, window.location.href);
            if (destination.pathname !== window.location.pathname) return;
            event.preventDefault();
            activeFilter = link.dataset.navFilter || link.dataset.categoryLink || 'all';
            activeSearch = '';
            if (searchInput) searchInput.value = '';
            filterProducts();
            document.getElementById('shop')?.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth' });
        });
    });

    searchInput?.addEventListener('input', event => {
        activeSearch = event.target.value.trim().toLowerCase();
        if (productGrid) {
            filterProducts();
            document.getElementById('shop')?.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth' });
        }
    });

    catalogSearch?.addEventListener('input', event => {
        activeSearch = event.target.value.trim().toLowerCase();
        filterProducts();
    });

    searchInput?.addEventListener('keydown', event => {
        if (event.key !== 'Enter') return;
        if (!productGrid) {
            window.location.href = `index.php?search=${encodeURIComponent(searchInput.value.trim())}#shop`;
        } else {
            closeSearch();
        }
    });

    const initialSearch = new URLSearchParams(window.location.search).get('search');
    if (initialSearch && productGrid) {
        activeSearch = initialSearch.toLowerCase();
        if (searchInput) searchInput.value = initialSearch;
        filterProducts();
    }
    if (productGrid) filterProducts();

    // Video hero experience & 8-phrase headline rotation loop changing every 2 seconds
    const scrubSection = qs('[data-hero-scrub-section]');
    const scrubVideo = qs('[data-hero-scrub-video]', scrubSection || document);
    const scrubPoster = qs('[data-hero-scrub-poster]', scrubSection || document);
    const scrubTitle = qs('[data-scrub-title]', scrubSection || document);
    const scrubSteps = qsa('[data-scrub-step]', scrubSection || document);
    const scrubIndicator = qs('[data-scrub-indicator]', scrubSection || document);

    // 8-Phrase rotating headline loop changing every 1 second without starting delay
    if (scrubTitle) {
        const headlines = [
            "PURE FOOD. BETTER EVERYDAY.",
            "ORGANIC GOODNESS. PURE HARVEST.",
            "SOULFUL WELLNESS. NATURALLY CRAFTED.",
            "HANDPICKED INGREDIENTS. HONEST TASTE.",
            "TRADITIONAL RECIPES. MODERN LIVING.",
            "NO CHEMICALS. 100% AUTHENTIC.",
            "SUSTAINABLE LIVING. BETTER HEALTH.",
            "FROM NATURE TO YOUR TABLE."
        ];
        let headlineIndex = 0;

        const rotateHeadline = () => {
            scrubTitle.style.opacity = '0';
            scrubTitle.style.transform = 'translateY(10px)';
            setTimeout(() => {
                headlineIndex = (headlineIndex + 1) % headlines.length;
                scrubTitle.textContent = headlines[headlineIndex];
                scrubTitle.style.opacity = '1';
                scrubTitle.style.transform = 'translateY(0)';
            }, 150);
        };

        setInterval(rotateHeadline, 1000);
    }

    if (scrubSection && scrubVideo) {
        let isPlayingCompleted = false;
        let isPlaybackStarted = false;

        scrubVideo.pause();
        scrubVideo.currentTime = 0;

        const unlockPageScroll = () => {
            isPlayingCompleted = true;
            document.body.classList.remove('hero-video-locked');
            if (scrubIndicator) scrubIndicator.style.opacity = '0';
        };

        const startNonstopPlayback = () => {
            if (isPlaybackStarted) return;
            isPlaybackStarted = true;

            document.body.classList.add('hero-video-locked');

            if (scrubPoster) {
                scrubPoster.style.opacity = '0';
            }
            if (scrubIndicator) {
                scrubIndicator.style.opacity = '0';
            }

            scrubVideo.currentTime = 0;
            scrubVideo.playbackRate = 0.85;
            scrubVideo.play().catch(() => {
                unlockPageScroll();
            });
        };

        scrubVideo.addEventListener('ended', unlockPageScroll);
        scrubVideo.addEventListener('timeupdate', () => {
            if (scrubVideo.duration && scrubVideo.currentTime >= scrubVideo.duration - 0.15) {
                unlockPageScroll();
            }
        });

        const triggerOnFirstScroll = () => {
            if (isPlayingCompleted || isPlaybackStarted) return;
            const rect = scrubSection.getBoundingClientRect();
            if (rect.top <= window.innerHeight * 0.85 && rect.bottom >= 0) {
                startNonstopPlayback();
            }
        };

        if (reduceMotion) {
            scrubSteps.forEach(step => step.classList.add('is-active'));
            if (scrubPoster) scrubPoster.style.opacity = '1';
            unlockPageScroll();
        } else {
            window.addEventListener('scroll', triggerOnFirstScroll, { passive: true });
            window.addEventListener('wheel', (e) => {
                if (!isPlayingCompleted && !isPlaybackStarted) {
                    const rect = scrubSection.getBoundingClientRect();
                    if (rect.top <= 100) {
                        startNonstopPlayback();
                    }
                }
            }, { passive: true });
            window.addEventListener('touchstart', triggerOnFirstScroll, { passive: true });
            triggerOnFirstScroll();
        }
    }

    // Full-image hero carousel with autoplay, keyboard controls and touch swiping.
    const heroSlider = qs('[data-hero-slider]');
    const heroTrack = qs('[data-hero-track]', heroSlider || document);
    const heroSlides = qsa('[data-hero-slide]', heroSlider || document);
    const heroDots = qsa('[data-hero-dot]', heroSlider || document);
    const heroProgress = qs('[data-hero-progress]', heroSlider || document);

    if (heroSlider && heroTrack && heroSlides.length > 1) {
        let heroIndex = 0;
        let heroTimer;
        let pointerStartX = null;

        const restartHeroProgress = () => {
            if (!heroProgress || reduceMotion) return;
            heroProgress.classList.remove('is-running');
            void heroProgress.offsetWidth;
            heroProgress.classList.add('is-running');
        };

        const showHeroSlide = index => {
            heroIndex = (index + heroSlides.length) % heroSlides.length;
            heroTrack.style.transform = `translate3d(-${heroIndex * 100}%, 0, 0)`;

            heroSlides.forEach((slide, slideIndex) => {
                const isActive = slideIndex === heroIndex;
                slide.classList.toggle('is-active', isActive);
                slide.setAttribute('aria-hidden', String(!isActive));
                slide.tabIndex = isActive ? 0 : -1;
            });

            heroDots.forEach((dot, dotIndex) => {
                const isActive = dotIndex === heroIndex;
                dot.classList.toggle('is-active', isActive);
                dot.setAttribute('aria-selected', String(isActive));
            });
        };

        const pauseHero = () => {
            window.clearTimeout(heroTimer);
            heroProgress?.classList.remove('is-running');
        };
        const scheduleHero = () => {
            pauseHero();
            if (reduceMotion || document.hidden || heroSlider.matches(':hover') || heroSlider.contains(document.activeElement)) return;
            restartHeroProgress();
            heroTimer = window.setTimeout(() => {
                showHeroSlide(heroIndex + 1);
                scheduleHero();
            }, 5500);
        };

        const selectHeroSlide = index => {
            showHeroSlide(index);
            scheduleHero();
        };

        qs('[data-hero-prev]', heroSlider)?.addEventListener('click', () => selectHeroSlide(heroIndex - 1));
        qs('[data-hero-next]', heroSlider)?.addEventListener('click', () => selectHeroSlide(heroIndex + 1));
        heroDots.forEach(dot => dot.addEventListener('click', () => selectHeroSlide(Number(dot.dataset.heroDot || 0))));

        heroSlider.addEventListener('keydown', event => {
            if (event.key === 'ArrowLeft') selectHeroSlide(heroIndex - 1);
            if (event.key === 'ArrowRight') selectHeroSlide(heroIndex + 1);
        });
        heroSlider.addEventListener('mouseenter', pauseHero);
        heroSlider.addEventListener('mouseleave', scheduleHero);
        heroSlider.addEventListener('focusin', pauseHero);
        heroSlider.addEventListener('focusout', () => window.setTimeout(() => {
            if (!heroSlider.contains(document.activeElement)) scheduleHero();
        }, 0));

        heroSlider.addEventListener('pointerdown', event => {
            if (event.pointerType === 'touch') pointerStartX = event.clientX;
        }, { passive: true });
        heroSlider.addEventListener('pointerup', event => {
            if (pointerStartX === null) return;
            const movement = event.clientX - pointerStartX;
            pointerStartX = null;
            if (Math.abs(movement) < 45) return;
            event.preventDefault();
            selectHeroSlide(heroIndex + (movement < 0 ? 1 : -1));
        });

        document.addEventListener('visibilitychange', () => document.hidden ? pauseHero() : scheduleHero());
        showHeroSlide(0);
        scheduleHero();
    }

    // Scroll reveal with a no-motion fallback.
    qsa('.reveal').forEach(element => {
        const delay = Number(element.dataset.delay || 0);
        element.style.setProperty('--delay', `${delay}ms`);
    });

    if (reduceMotion || !('IntersectionObserver' in window)) {
        qsa('.reveal').forEach(element => element.classList.add('is-visible'));
    } else {
        const revealObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                revealObserver.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -3% 0px', threshold: 0.04 });

        qsa('.reveal').forEach(element => revealObserver.observe(element));
    }

    // Multi-layer hero depth, kept on the compositor and disabled for reduced motion.
    const heroShowcase = qs('[data-hero-showcase]');
    const parallaxItems = qsa('[data-parallax-item]');
    if (heroShowcase && !reduceMotion && window.matchMedia('(min-width: 821px)').matches) {
        heroShowcase.addEventListener('pointermove', event => {
            const rect = heroShowcase.getBoundingClientRect();
            const x = (event.clientX - rect.left) / rect.width - .5;
            const y = (event.clientY - rect.top) / rect.height - .5;
            heroShowcase.style.setProperty('--hero-rx', `${(-y * 2.4).toFixed(2)}deg`);
            heroShowcase.style.setProperty('--hero-ry', `${(x * 2.8).toFixed(2)}deg`);
        });
        heroShowcase.addEventListener('pointerleave', () => {
            heroShowcase.style.setProperty('--hero-rx', '0deg');
            heroShowcase.style.setProperty('--hero-ry', '0deg');
        });

        let ticking = false;
        window.addEventListener('scroll', () => {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(() => {
                const heroProgress = Math.min(window.scrollY, window.innerHeight * 1.25);
                parallaxItems.forEach(item => {
                    const speed = Number(item.dataset.parallaxItem || .05);
                    item.style.transform = `translate3d(0, ${(heroProgress * speed).toFixed(2)}px, 0)`;
                });
                ticking = false;
            });
        }, { passive: true });
    }

    // Local cart demonstration.
    const storageKey = 'gawdee-modern-cart';
    const cartDrawer = qs('[data-cart-drawer]');
    const backdrop = qs('[data-drawer-backdrop]');
    const cartItems = qs('[data-cart-items]');
    const cartEmpty = qs('[data-cart-empty]');
    const cartSummary = qs('[data-cart-summary]');
    const cartTotal = qs('[data-cart-total]');
    const cartCount = qs('[data-cart-count]');
    const toast = qs('[data-toast]');
    let toastTimer;
    let cart = [];

    try {
        const savedCart = JSON.parse(localStorage.getItem(storageKey) || '[]');
        cart = Array.isArray(savedCart) ? savedCart : [];
    } catch {
        cart = [];
    }

    const formatMoney = amount => new Intl.NumberFormat('en-IN', {
        style: 'currency', currency: 'INR', maximumFractionDigits: 0
    }).format(amount);

    const saveCart = () => localStorage.setItem(storageKey, JSON.stringify(cart));

    const showToast = message => {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('is-visible');
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => toast.classList.remove('is-visible'), 2200);
    };

    qsa('[data-newsletter-form]').forEach(form => {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const email = qs('input[type="email"]', form)?.value.trim();
            if (!email) return;
            try {
                const response = await fetch('api/subscribe.php', {
                    method: 'POST', headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({email, csrf_token: qs('meta[name="gawdee-csrf"]')?.content || ''})
                });
                const result = await response.json();
                if (!response.ok || !result.ok) throw new Error(result.message || 'Unable to subscribe.');
                form.reset();
                showToast(result.message);
            } catch (error) {
                showToast(error.message || 'Unable to subscribe right now.');
            }
        });
    });

    qsa('[data-copy-offer]').forEach(button => {
        button.addEventListener('click', async () => {
            const code = button.dataset.copyOffer || '';
            if (!code) return;

            try {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(code);
                } else {
                    const temporaryInput = document.createElement('textarea');
                    temporaryInput.value = code;
                    temporaryInput.setAttribute('readonly', '');
                    temporaryInput.style.position = 'fixed';
                    temporaryInput.style.opacity = '0';
                    document.body.appendChild(temporaryInput);
                    temporaryInput.select();
                    document.execCommand('copy');
                    temporaryInput.remove();
                }

                button.classList.add('is-copied');
                const label = qs('span', button);
                if (label) label.innerHTML = '<i class="ph ph-check"></i> Copied';
                showToast(`Offer code ${code} copied`);
                window.setTimeout(() => {
                    button.classList.remove('is-copied');
                    if (label) label.innerHTML = '<i class="ph ph-copy"></i> Copy code';
                }, 2200);
            } catch {
                showToast(`Use code ${code} at checkout`);
            }
        });
    });

    // Homepage Independence Day offer: accessible, dismissible and shown once per session.
    const offerPopup = qs('[data-offer-popup]');
    if (offerPopup) {
        const popupKey = `gawdee-offer-popup:${offerPopup.dataset.popupKey || 'current'}`;
        const popupDialog = qs('.offer-popup__dialog', offerPopup);
        const popupClose = qs('[data-offer-popup-close]', offerPopup);
        const popupDelay = Math.max(0, Math.min(10000, Number(offerPopup.dataset.popupDelay || 850)));
        let returnFocus = null;

        const sessionRead = () => {
            try { return window.sessionStorage.getItem(popupKey); } catch { return null; }
        };
        const sessionDismiss = () => {
            try { window.sessionStorage.setItem(popupKey, 'dismissed'); } catch { /* Storage can be unavailable in private contexts. */ }
        };
        const closeOfferPopup = () => {
            if (offerPopup.hidden) return;
            sessionDismiss();
            offerPopup.classList.remove('is-visible');
            document.body.classList.remove('has-offer-popup');
            window.setTimeout(() => {
                offerPopup.hidden = true;
                returnFocus?.focus?.();
            }, reduceMotion ? 0 : 280);
        };
        const openOfferPopup = () => {
            if (sessionRead()) return;
            returnFocus = document.activeElement;
            offerPopup.hidden = false;
            document.body.classList.add('has-offer-popup');
            window.requestAnimationFrame(() => {
                offerPopup.classList.add('is-visible');
                window.setTimeout(() => popupClose?.focus(), reduceMotion ? 0 : 220);
            });
        };

        window.setTimeout(openOfferPopup, popupDelay);
        qsa('[data-offer-popup-close], [data-offer-popup-shop]', offerPopup).forEach(control => control.addEventListener('click', closeOfferPopup));
        offerPopup.addEventListener('click', event => {
            if (event.target === offerPopup) closeOfferPopup();
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !offerPopup.hidden) closeOfferPopup();
            if (event.key !== 'Tab' || offerPopup.hidden || !popupDialog) return;
            const focusable = qsa('a[href], button:not([disabled])', popupDialog);
            if (!focusable.length) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });
    }

    qsa('[data-scroll-rail]').forEach(button => {
        button.addEventListener('click', () => {
            const rail = qs(button.dataset.scrollRail || '');
            if (!rail) return;
            const direction = Number(button.dataset.scrollDirection || 1);
            rail.scrollBy({
                left: direction * Math.min(rail.clientWidth * .86, 460),
                behavior: reduceMotion ? 'auto' : 'smooth'
            });
            rail.dispatchEvent(new CustomEvent('railinteraction'));
        });
    });

    qsa('[data-sliding-rail]').forEach(rail => {
        let pointerId = null;
        let startX = 0;
        let startScroll = 0;
        let hasDragged = false;

        rail.addEventListener('pointerdown', event => {
            if (event.pointerType === 'mouse' && event.button !== 0) return;
            pointerId = event.pointerId;
            startX = event.clientX;
            startScroll = rail.scrollLeft;
            hasDragged = false;
            rail.classList.add('is-grabbing');
            rail.setPointerCapture?.(pointerId);
        });

        rail.addEventListener('pointermove', event => {
            if (pointerId !== event.pointerId) return;
            const movement = event.clientX - startX;
            if (Math.abs(movement) > 5) hasDragged = true;
            if (!hasDragged) return;
            event.preventDefault();
            rail.scrollLeft = startScroll - movement;
        });

        const releaseRail = event => {
            if (pointerId !== event.pointerId) return;
            rail.releasePointerCapture?.(pointerId);
            pointerId = null;
            rail.classList.remove('is-grabbing');
        };

        rail.addEventListener('pointerup', releaseRail);
        rail.addEventListener('pointercancel', releaseRail);
        rail.addEventListener('click', event => {
            if (!hasDragged) return;
            event.preventDefault();
            event.stopPropagation();
            hasDragged = false;
        }, true);

        rail.addEventListener('keydown', event => {
            if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
            event.preventDefault();
            rail.scrollBy({
                left: (event.key === 'ArrowLeft' ? -1 : 1) * Math.min(rail.clientWidth * .86, 460),
                behavior: reduceMotion ? 'auto' : 'smooth'
            });
            rail.dispatchEvent(new CustomEvent('railinteraction'));
        });
    });

    qsa('[data-auto-slide]').forEach(rail => {
        const interval = Math.max(2800, Number(rail.dataset.autoSlide || 4200));
        let autoplayTimer = null;

        const stopAutoplay = () => {
            window.clearInterval(autoplayTimer);
            autoplayTimer = null;
        };

        const advanceRail = () => {
            if (document.hidden || rail.matches(':hover') || rail.contains(document.activeElement) || rail.classList.contains('is-grabbing')) return;
            if (rail.scrollWidth <= rail.clientWidth + 4) return;

            const maximumScroll = rail.scrollWidth - rail.clientWidth;
            const isAtEnd = rail.scrollLeft >= maximumScroll - 12;
            rail.scrollTo({
                left: isAtEnd ? 0 : Math.min(rail.scrollLeft + Math.min(rail.clientWidth * .86, 460), maximumScroll),
                behavior: 'smooth'
            });
        };

        const startAutoplay = () => {
            stopAutoplay();
            if (reduceMotion) return;
            autoplayTimer = window.setInterval(advanceRail, interval);
        };

        rail.addEventListener('mouseenter', stopAutoplay);
        rail.addEventListener('mouseleave', startAutoplay);
        rail.addEventListener('focusin', stopAutoplay);
        rail.addEventListener('focusout', startAutoplay);
        rail.addEventListener('pointerdown', stopAutoplay);
        rail.addEventListener('pointerup', startAutoplay);
        rail.addEventListener('pointercancel', startAutoplay);
        rail.addEventListener('railinteraction', startAutoplay);
        document.addEventListener('visibilitychange', () => document.hidden ? stopAutoplay() : startAutoplay());
        startAutoplay();
    });

    qsa('[data-wishlist]').forEach(button => {
        button.addEventListener('click', () => {
            const isSaved = button.classList.toggle('is-saved');
            button.setAttribute('aria-pressed', String(isSaved));
            const icon = qs('i', button);
            icon?.classList.toggle('ph-fill', isSaved);
            showToast(isSaved ? 'Saved to your wishlist' : 'Removed from your wishlist');
        });
    });

    const openCart = () => {
        cartDrawer?.classList.add('is-open');
        backdrop?.classList.add('is-open');
        cartDrawer?.setAttribute('aria-hidden', 'false');
        document.body.classList.add('is-locked');
        qs('[data-cart-close]', cartDrawer || document)?.focus();
    };

    const closeCart = () => {
        cartDrawer?.classList.remove('is-open');
        backdrop?.classList.remove('is-open');
        cartDrawer?.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('is-locked');
    };

    const renderCart = () => {
        const count = cart.reduce((sum, item) => sum + item.quantity, 0);
        const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
        if (cartCount) cartCount.textContent = String(count);
        if (cartTotal) cartTotal.textContent = formatMoney(total);
        if (cartEmpty) cartEmpty.hidden = cart.length > 0;
        if (cartSummary) cartSummary.hidden = cart.length === 0;

        if (!cartItems) return;
        cartItems.innerHTML = cart.map(item => `
            <article class="cart-item">
                <img src="${item.image}" alt="">
                <div>
                    <h3>${item.name}</h3>
                    <p>${formatMoney(item.price)}</p>
                    <div class="cart-item__qty" aria-label="Quantity for ${item.name}">
                        <button type="button" data-cart-decrease="${item.id}" aria-label="Decrease quantity">−</button>
                        <span>${item.quantity}</span>
                        <button type="button" data-cart-increase="${item.id}" aria-label="Increase quantity">+</button>
                    </div>
                </div>
                <button class="cart-item__remove" type="button" data-cart-remove="${item.id}" aria-label="Remove ${item.name}"><i class="ph ph-trash"></i></button>
            </article>
        `).join('');
    };

    const addToCart = (button, quantity = 1) => {
        const item = {
            id: button.dataset.id,
            name: button.dataset.name,
            price: Number(button.dataset.price),
            image: button.dataset.image,
            quantity
        };
        const existing = cart.find(product => product.id === item.id);
        if (existing) existing.quantity += quantity;
        else cart.push(item);
        saveCart();
        renderCart();
        button.classList.add('is-added');
        window.setTimeout(() => button.classList.remove('is-added'), 650);
        showToast(`${item.name} added to your bag`);
    };

    qsa('[data-cart-toggle]').forEach(button => button.addEventListener('click', openCart));
    qsa('[data-cart-close]').forEach(button => button.addEventListener('click', closeCart));
    backdrop?.addEventListener('click', closeCart);
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeCart();
            closeSearch();
        }
    });

    qsa('[data-add-to-cart]').forEach(button => {
        button.addEventListener('click', () => {
            const quantity = button.classList.contains('product-add')
                ? Number(qs('[data-product-qty]')?.textContent || 1)
                : 1;
            addToCart(button, quantity);
        });
    });

    cartItems?.addEventListener('click', event => {
        const increase = event.target.closest('[data-cart-increase]');
        const decrease = event.target.closest('[data-cart-decrease]');
        const remove = event.target.closest('[data-cart-remove]');
        const id = increase?.dataset.cartIncrease || decrease?.dataset.cartDecrease || remove?.dataset.cartRemove;
        if (!id) return;
        const item = cart.find(product => product.id === id);
        if (!item) return;

        if (increase) item.quantity += 1;
        if (decrease) item.quantity -= 1;
        if (remove || item.quantity <= 0) cart = cart.filter(product => product.id !== id);
        saveCart();
        renderCart();
    });

    const qtyDisplay = qs('[data-product-qty]');
    let productQuantity = 1;
    qs('[data-product-qty-minus]')?.addEventListener('click', () => {
        productQuantity = Math.max(1, productQuantity - 1);
        if (qtyDisplay) qtyDisplay.textContent = String(productQuantity);
    });
    qs('[data-product-qty-plus]')?.addEventListener('click', () => {
        productQuantity = Math.min(10, productQuantity + 1);
        if (qtyDisplay) qtyDisplay.textContent = String(productQuantity);
    });

    // Product gallery and purchase actions.
    const productMainImage = qs('[data-product-main-image]');
    const productExpandLink = qs('.ref-gallery__expand');
    qsa('[data-gallery-thumb]').forEach(thumbnail => {
        thumbnail.addEventListener('click', () => {
            if (!productMainImage || !thumbnail.dataset.image) return;
            qsa('[data-gallery-thumb]').forEach(item => {
                const active = item === thumbnail;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-pressed', String(active));
            });
            productMainImage.classList.add('is-changing');
            window.setTimeout(() => {
                productMainImage.src = thumbnail.dataset.image;
                productMainImage.alt = thumbnail.dataset.alt || productMainImage.alt;
                if (productExpandLink) productExpandLink.href = thumbnail.dataset.image;
                productMainImage.classList.remove('is-changing');
            }, reduceMotion ? 0 : 150);
        });
    });

    qsa('[data-ref-product-tab]').forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.refProductTab;
            qsa('[data-ref-product-tab]').forEach(item => {
                const active = item === tab;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', String(active));
            });
            qsa('[data-ref-product-panel]').forEach(panel => {
                panel.hidden = panel.dataset.refProductPanel !== target;
            });
        });
    });

    qsa('[data-buy-now]').forEach(button => {
        button.addEventListener('click', () => {
            if (button.disabled) return;
            const quantity = Number(qtyDisplay?.textContent || 1);
            addToCart(button, quantity);
            window.setTimeout(() => { window.location.href = 'checkout.php'; }, reduceMotion ? 0 : 220);
        });
    });

    qsa('[data-delivery-check]').forEach(form => {
        form.addEventListener('submit', event => {
            event.preventDefault();
            const input = qs('input[name="pincode"]', form);
            const result = qs('[data-delivery-result]', form);
            const pincode = input?.value.trim() || '';
            if (!/^[1-9][0-9]{5}$/.test(pincode)) {
                if (result) result.textContent = 'Enter a valid 6-digit Indian pincode.';
                input?.focus();
                return;
            }
            if (result) result.textContent = `Pincode ${pincode} saved. Final serviceability and delivery estimate are confirmed at checkout.`;
        });
    });

    const appendPublishedReview = review => {
        const reviewList = qs('[data-review-list]');
        if (!reviewList) return;
        const card = document.createElement('article');
        card.className = 'review-card';
        const avatar = document.createElement('div');
        avatar.className = 'review-card__avatar';
        avatar.textContent = (review.name || 'G').slice(0, 1).toUpperCase();
        const content = document.createElement('div');
        const top = document.createElement('div');
        top.className = 'review-card__top';
        const identity = document.createElement('div');
        const name = document.createElement('strong');
        name.textContent = review.name;
        const meta = document.createElement('span');
        meta.textContent = `Customer review · ${review.date}`;
        identity.append(name, meta);
        const stars = document.createElement('div');
        stars.className = 'review-stars';
        stars.setAttribute('aria-label', `${review.rating} out of 5 stars`);
        stars.textContent = '★'.repeat(review.rating) + '☆'.repeat(5 - review.rating);
        const copy = document.createElement('p');
        copy.textContent = `“${review.review}”`;
        top.append(identity, stars);
        content.append(top, copy);
        card.append(avatar, content);
        reviewList.prepend(card);
    };

    qsa('[data-review-form]').forEach(form => {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const status = qs('[data-review-status]', form);
            const submit = qs('button[type="submit"]', form);
            const formData = new FormData(form);
            const payload = {
                product_id: form.dataset.productId,
                name: String(formData.get('name') || '').trim(),
                email: String(formData.get('email') || '').trim(),
                review: String(formData.get('review') || '').trim(),
                rating: Number(formData.get('rating') || 0),
                csrf_token: qs('meta[name="gawdee-csrf"]')?.content || ''
            };
            if (submit) submit.disabled = true;
            if (status) status.textContent = 'Publishing your review…';
            try {
                const response = await fetch('api/product-review.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (!response.ok || !result.ok) throw new Error(result.message || 'Unable to publish your review.');
                appendPublishedReview(result.review);
                form.reset();
                if (status) status.textContent = result.message;
                showToast(result.message);
            } catch (error) {
                if (status) status.textContent = error.message || 'Unable to publish your review right now.';
            } finally {
                if (submit) submit.disabled = false;
            }
        });
    });

    // Floating AI shopping assistant.
    const aiChat = qs('[data-ai-chat]');
    const aiToggle = qs('[data-ai-toggle]');
    const aiForm = qs('[data-ai-form]');
    const aiInput = qs('#ai-question');
    const aiMessages = qs('[data-ai-messages]');
    const csrfToken = qs('meta[name="gawdee-csrf"]')?.content || '';
    const addAiMessage = (message, role = 'assistant') => {
        if (!aiMessages) return;
        const node = document.createElement('div');
        node.className = `ai-message ai-message--${role}`;
        node.textContent = message;
        aiMessages.appendChild(node);
        aiMessages.scrollTop = aiMessages.scrollHeight;
        return node;
    };
    const openAi = () => {
        aiChat?.classList.add('is-open');
        aiToggle?.classList.add('is-active');
        aiChat?.setAttribute('aria-hidden', 'false');
        aiToggle?.setAttribute('aria-expanded', 'true');
        window.setTimeout(() => aiInput?.focus(), 120);
    };
    const closeAi = () => {
        aiChat?.classList.remove('is-open');
        aiToggle?.classList.remove('is-active');
        aiChat?.setAttribute('aria-hidden', 'true');
        aiToggle?.setAttribute('aria-expanded', 'false');
    };
    const askAi = async message => {
        if (!message || !aiForm) return;
        addAiMessage(message, 'user');
        const waiting = addAiMessage('Thinking with care…', 'assistant');
        aiForm.classList.add('is-loading');
        try {
            const response = await fetch('api/ai-chat.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({message, csrf_token: csrfToken})
            });
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.message || 'The assistant is unavailable.');
            if (waiting) waiting.textContent = result.reply;
        } catch (error) {
            if (waiting) waiting.textContent = error.message || 'The assistant is unavailable right now.';
        } finally {
            aiForm.classList.remove('is-loading');
            aiInput?.focus();
            if (aiMessages) aiMessages.scrollTop = aiMessages.scrollHeight;
        }
    };
    aiToggle?.addEventListener('click', () => aiChat?.classList.contains('is-open') ? closeAi() : openAi());
    qs('[data-ai-close]')?.addEventListener('click', closeAi);
    aiForm?.addEventListener('submit', event => {
        event.preventDefault();
        const message = aiInput?.value.trim() || '';
        if (!message) return;
        if (aiInput) aiInput.value = '';
        askAi(message);
    });
    qsa('[data-ai-suggestion]').forEach(button => button.addEventListener('click', () => {
        openAi();
        askAi(button.dataset.aiSuggestion || button.textContent.trim());
    }));

    renderCart();
})();
