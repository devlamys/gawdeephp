/* GAWDEE animated hero — 3D product carousel (GSAP).
   Dynamic slides arrive via #gxHeroData JSON from includes/hero-animated.php.
   Without GSAP (CDN blocked) the hero degrades to a static first-slide view. */
(function () {
  var GX_READY_TRIES = 0;

  function init() {
    var stage = document.getElementById("gxStage");
    if (!stage || stage.dataset.gxInit === "1") return;
    if (!window.gsap) {
      // GSAP loads with `defer` from CDN — retry briefly, then static fallback.
      if (GX_READY_TRIES < 50) {
        GX_READY_TRIES += 1;
        window.setTimeout(init, 100);
      } else {
        staticFallback(stage);
      }
      return;
    }
    stage.dataset.gxInit = "1";

    var shadowEl = document.getElementById("gxShadow");
    var dataEl = document.getElementById("gxHeroData");
    var PRODUCTS = [];
    try {
      PRODUCTS = JSON.parse(dataEl ? dataEl.textContent : "[]");
    } catch (e) {
      PRODUCTS = [];
    }
    if (!PRODUCTS.length) return;

    var AUTOPLAY_MS = 3200,
      DUR = 1.15,
      EASE = "power3.inOut";
    var current = 0,
      isAnimating = false,
      timer = null,
      floatTween = null;
    var N = PRODUCTS.length,
      slides = [];
    var reduceMotion =
      window.matchMedia &&
      window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    PRODUCTS.forEach(function (p, idx) {
      var d = null;
      var img = null;
      if (idx === 0) {
        // SEO/LCP: first slide is server-rendered — adopt it instead of cloning.
        d = stage.querySelector("[data-gx-static]");
        if (d) {
          d.removeAttribute("data-gx-static");
          img = d.querySelector("img");
        }
      }
      if (!d) {
        d = document.createElement("div");
        d.className = "gx-slide";
        stage.appendChild(d);
      }
      if (!img) {
        img = document.createElement("img");
        d.appendChild(img);
      }
      img.src = p.img;
      img.alt = p.alt || "";
      img.draggable = false;
      img.onerror = function () {
        this.style.display = "none";
      };
      if (idx === 0) img.fetchPriority = "high";
      else img.loading = "lazy";
      slides.push(d);
    });
    if (shadowEl) stage.appendChild(shadowEl);

    var dotsWrap = document.getElementById("gxDots");
    var singleSlide = N === 1;
    if (dotsWrap && !singleSlide) {
      PRODUCTS.forEach(function (_, i) {
        var b = document.createElement("button");
        b.type = "button";
        b.className = "gx-dot";
        b.setAttribute("role", "tab");
        b.setAttribute("aria-selected", i === 0 ? "true" : "false");
        b.setAttribute("aria-label", "Go to slide " + (i + 1));
        b.addEventListener("click", function () {
          goTo(i);
        });
        dotsWrap.appendChild(b);
      });
    } else if (dotsWrap) {
      dotsWrap.style.display = "none";
    }
    var prevBtn = document.getElementById("gxPrev");
    var nextBtn = document.getElementById("gxNext");
    if (singleSlide && prevBtn) prevBtn.style.display = "none";
    if (singleSlide && nextBtn) nextBtn.style.display = "none";

    function positions() {
      var W = stage.clientWidth || 600;
      return {
        previewX: Math.min(W * 0.36, 340),
        offRightX: W * 0.75,
        offLeftX: -W * 0.85,
      };
    }

    function setText(id, value) {
      var el = document.getElementById(id);
      if (el) el.textContent = value;
    }

    function paintText(p) {
      setText("gxCat", p.cat);
      var titleEl = document.getElementById("gxTitle");
      if (titleEl) titleEl.innerHTML = p.title; // sanitised server-side (br/span only)
      setText("gxSub", p.sub);
      setText("gxPrice", p.priceLabel);
      setText("gxMrp", p.mrpLabel);
      setText("gxOff", p.off);
      setText("gxReviews", p.reviews);
      var mini = document.querySelector("#gxCartBtn .gx-mini");
      if (mini) mini.textContent = p.priceLabel;
      var shop = document.getElementById("gxShopBtn");
      if (shop && p.url) shop.setAttribute("href", p.url);
      var cart = document.getElementById("gxCartBtn");
      if (cart) {
        cart.setAttribute("data-id", p.cartId);
        cart.setAttribute("data-name", p.cartName);
        cart.setAttribute("data-price", String(p.cartPrice));
        cart.setAttribute("data-image", p.cartImage);
      }
    }

    function syncMeta() {
      if (dotsWrap) {
        Array.prototype.forEach.call(dotsWrap.children, function (d, i) {
          d.classList.toggle("active", i === current);
          d.setAttribute("aria-selected", i === current ? "true" : "false");
        });
      }
      setText(
        "gxCount",
        String(current + 1).padStart(2, "0") +
          " — " +
          String(N).padStart(2, "0")
      );
      setText("gxIndex", String(current + 1).padStart(2, "0"));
      var np = PRODUCTS[(current + 1) % N];
      setText(
        "gxNextName",
        np.word.charAt(0) + np.word.slice(1).toLowerCase()
      );
      if (window.gsap && !singleSlide) {
        gsap.fromTo(
          "#gxProgressBar",
          { scaleX: 0 },
          {
            scaleX: 1,
            duration: AUTOPLAY_MS / 1000,
            ease: "none",
            overwrite: true,
          }
        );
      }
    }

    function layoutInstant() {
      var pos = positions();
      slides.forEach(function (el, i) {
        if (i === current)
          gsap.set(el, {
            x: 0,
            scale: 1.06,
            opacity: 1,
            zIndex: 30,
            rotationY: 0,
            rotation: 0,
          });
        else if (i === (current + 1) % N)
          gsap.set(el, {
            x: pos.previewX,
            scale: 0.64,
            opacity: 0.38,
            zIndex: 10,
            rotationY: -18,
            rotation: 4,
          });
        else if (i === (current - 1 + N) % N)
          gsap.set(el, { x: pos.offLeftX, scale: 0.8, opacity: 0, zIndex: 5 });
        else
          gsap.set(el, {
            x: pos.offRightX,
            scale: 0.55,
            opacity: 0,
            zIndex: 1,
          });
      });
      paintText(PRODUCTS[current]);
      syncMeta();
    }

    function startFloat() {
      if (reduceMotion) return;
      if (floatTween) floatTween.kill();
      floatTween = gsap.to(slides[current], {
        y: -18,
        duration: 2.1,
        yoyo: true,
        repeat: -1,
        ease: "sine.inOut",
      });
      if (shadowEl) {
        gsap.to(shadowEl, {
          scale: 0.82,
          opacity: 0.7,
          duration: 2.1,
          yoyo: true,
          repeat: -1,
          ease: "sine.inOut",
        });
      }
    }

    function animateTextOutIn(inIdx, tl) {
      var p = PRODUCTS[inIdx];
      tl.to(
        "#gxText",
        { y: -28, opacity: 0, duration: 0.45, ease: "power3.in" },
        0
      );
      tl.add(
        function () {
          paintText(p);
          setText(
            "gxCount",
            String(inIdx + 1).padStart(2, "0") +
              " — " +
              String(N).padStart(2, "0")
          );
          setText("gxIndex", String(inIdx + 1).padStart(2, "0"));
          if (dotsWrap) {
            Array.prototype.forEach.call(dotsWrap.children, function (d, i) {
              d.classList.toggle("active", i === inIdx);
              d.setAttribute("aria-selected", i === inIdx ? "true" : "false");
            });
          }
          gsap.fromTo(
            "#gxProgressBar",
            { scaleX: 0 },
            {
              scaleX: 1,
              duration: AUTOPLAY_MS / 1000,
              ease: "none",
              overwrite: true,
            }
          );
          var np = PRODUCTS[(inIdx + 1) % N];
          setText(
            "gxNextName",
            np.word.charAt(0) + np.word.slice(1).toLowerCase()
          );
        },
        DUR * 0.45
      );
      tl.fromTo(
        "#gxText",
        { y: 44, opacity: 0 },
        { y: 0, opacity: 1, duration: 0.75, ease: "expo.out" },
        DUR * 0.5
      );
    }

    function next() {
      if (isAnimating || singleSlide) return;
      isAnimating = true;
      if (floatTween) floatTween.kill();
      if (shadowEl) gsap.killTweensOf(shadowEl);
      var pos = positions();
      var outIdx = current,
        inIdx = (current + 1) % N,
        upIdx = (current + 2) % N;
      var outEl = slides[outIdx],
        inEl = slides[inIdx],
        upEl = slides[upIdx];
      gsap.set(upEl, {
        x: pos.offRightX,
        scale: 0.55,
        opacity: 0,
        zIndex: 1,
        y: 0,
      });
      var tl = gsap.timeline({
        defaults: { duration: DUR, ease: EASE },
        onComplete: function () {
          current = inIdx;
          isAnimating = false;
          layoutInstant();
          startFloat();
          restartAuto();
        },
      });
      tl.to(
        outEl,
        {
          x: pos.offLeftX,
          opacity: 0,
          scale: 0.8,
          rotation: -10,
          rotationY: 18,
          y: 0,
          zIndex: 5,
        },
        0
      );
      tl.to(
        inEl,
        {
          x: 0,
          opacity: 1,
          scale: 1.06,
          rotation: 0,
          rotationY: 0,
          y: 0,
          zIndex: 30,
        },
        0
      );
      tl.to(
        upEl,
        {
          x: pos.previewX,
          opacity: 0.38,
          scale: 0.64,
          rotation: 4,
          rotationY: -18,
          zIndex: 10,
        },
        0.08
      );
      if (shadowEl) {
        tl.to(
          shadowEl,
          {
            scale: 0.7,
            opacity: 0.4,
            duration: DUR / 2,
            ease: "power2.in",
            yoyo: true,
            repeat: 1,
          },
          0
        );
      }
      animateTextOutIn(inIdx, tl);
    }

    function prev(explicit) {
      if (isAnimating || singleSlide) return;
      isAnimating = true;
      if (floatTween) floatTween.kill();
      if (shadowEl) gsap.killTweensOf(shadowEl);
      var pos = positions();
      var inIdx =
        explicit !== null && explicit !== undefined
          ? explicit
          : (current - 1 + N) % N;
      var outEl = slides[current],
        inEl = slides[inIdx];
      gsap.set(inEl, {
        x: pos.offLeftX,
        scale: 0.8,
        opacity: 0,
        zIndex: 30,
        y: 0,
        rotation: -10,
      });
      var oldNext = slides[(current + 1) % N];
      var tl = gsap.timeline({
        defaults: { duration: DUR, ease: EASE },
        onComplete: function () {
          current = inIdx;
          isAnimating = false;
          layoutInstant();
          startFloat();
          restartAuto();
        },
      });
      tl.to(
        outEl,
        {
          x: pos.previewX,
          opacity: 0.38,
          scale: 0.64,
          rotation: 4,
          rotationY: -18,
          zIndex: 10,
        },
        0
      );
      tl.to(
        inEl,
        {
          x: 0,
          opacity: 1,
          scale: 1.06,
          rotation: 0,
          rotationY: 0,
          zIndex: 30,
        },
        0
      );
      tl.to(
        oldNext,
        { x: pos.offRightX, opacity: 0, scale: 0.55, zIndex: 1 },
        0
      );
      if (shadowEl) {
        tl.to(
          shadowEl,
          {
            scale: 0.7,
            opacity: 0.4,
            duration: DUR / 2,
            ease: "power2.in",
            yoyo: true,
            repeat: 1,
          },
          0
        );
      }
      animateTextOutIn(inIdx, tl);
    }

    function goTo(target) {
      if (isAnimating || target === current || singleSlide) {
        restartAuto();
        return;
      }
      var fwd = (target - current + N) % N;
      if (fwd <= N / 2) {
        if ((current + 1) % N === target) next();
        else {
          current = (target - 1 + N) % N;
          layoutInstant();
          next();
        }
      } else {
        prev(target);
      }
    }

    function restartAuto() {
      clearInterval(timer);
      if (singleSlide || reduceMotion) return;
      timer = setInterval(function () {
        if (!document.hidden) next();
      }, AUTOPLAY_MS);
    }

    if (nextBtn) nextBtn.addEventListener("click", next);
    if (prevBtn)
      prevBtn.addEventListener("click", function () {
        prev();
      });
    document.addEventListener("keydown", function (e) {
      if (e.key === "ArrowRight") next();
      if (e.key === "ArrowLeft") prev();
    });
    stage.addEventListener("mouseenter", function () {
      clearInterval(timer);
      if (window.gsap) gsap.killTweensOf("#gxProgressBar");
    });
    stage.addEventListener("mouseleave", restartAuto);
    var sx = 0;
    stage.addEventListener(
      "touchstart",
      function (e) {
        sx = e.touches[0].clientX;
      },
      { passive: true }
    );
    stage.addEventListener(
      "touchend",
      function (e) {
        var dx = e.changedTouches[0].clientX - sx;
        if (Math.abs(dx) > 40) {
          if (dx < 0) next();
          else prev();
        }
      },
      { passive: true }
    );

    // Fly-dot micro-interaction on Add (real cart handled by app.js via data-add-to-cart)
    var cartBtn = document.getElementById("gxCartBtn");
    if (cartBtn) {
      cartBtn.addEventListener("click", function () {
        if (!window.gsap || reduceMotion) return;
        var dot = document.createElement("div");
        dot.style.cssText =
          "position:fixed;width:16px;height:16px;border-radius:999px;background:#005c4e;z-index:100;pointer-events:none;left:50%;top:60%";
        document.body.appendChild(dot);
        gsap.to(dot, {
          x: window.innerWidth / 2 - 120,
          y: -window.innerHeight / 2 + 80,
          scale: 0.3,
          duration: 0.8,
          ease: "power3.in",
          onComplete: function () {
            dot.remove();
          },
        });
      });
    }

    layoutInstant();
    startFloat();
    restartAuto();
    window.addEventListener("resize", function () {
      if (!isAnimating) layoutInstant();
    });
    if (!reduceMotion) {
      gsap.from(".gx-hero .gx-intro", {
        y: 34,
        opacity: 0,
        duration: 1,
        stagger: 0.12,
        ease: "expo.out",
        delay: 0.2,
      });
      gsap.from("#gxText", {
        y: 50,
        opacity: 0,
        duration: 1.1,
        ease: "expo.out",
        delay: 0.35,
      });
    }
  }

  function staticFallback(stage) {
    // No GSAP: the first slide is already server-rendered, so only build one
    // if the markup was cached without it. Content is never blank.
    try {
      if (stage.querySelector(".gx-slide img")) return;
      var dataEl = document.getElementById("gxHeroData");
      var items = JSON.parse(dataEl ? dataEl.textContent : "[]");
      if (!items.length) return;
      var wrap = document.createElement("div");
      wrap.className = "gx-slide";
      wrap.style.cssText =
        "position:relative;top:auto;left:auto;margin:0;width:min(420px,80vw);height:min(480px,52vh);";
      var img = document.createElement("img");
      img.src = items[0].img;
      img.alt = items[0].alt || "";
      img.style.cssText = "width:100%;height:100%;object-fit:contain;";
      wrap.appendChild(img);
      stage.appendChild(wrap);
      var dots = document.getElementById("gxDots");
      if (dots) dots.style.display = "none";
    } catch (e) {
      /* leave server-rendered copy visible */
    }
  }

  if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", init);
  else init();
})();
