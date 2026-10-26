/* Cambridge Curriculum page interactions — scoped to .cb-page */
(function () {
    "use strict";

    function init() {
        var root = document.querySelector(".cb-page");
        if (!root) return;

        initPathwayTabs(root);
        initCampusCarousel(root);
        initReelVideos(root);
        initExternalLinks(root);
        initEnquiryPopup(root);
    }

    /* ---- Enquiry form: popup on mobile, anchor scroll on desktop ---- */
    function initEnquiryPopup(root) {
        var modal = root.querySelector("#cbEnquiryModal");
        if (!modal) return;
        var MOBILE = 900;

        function isMobile() { return window.innerWidth <= MOBILE; }
        function open() {
            modal.classList.add("cb-open");
            document.body.style.overflow = "hidden";
        }
        function close() {
            modal.classList.remove("cb-open");
            document.body.style.overflow = "";
        }

        root.querySelectorAll('a[href$="#AdmissionForm"]').forEach(function (link) {
            link.addEventListener("click", function (e) {
                if (!isMobile()) return;
                e.preventDefault();
                open();
            });
        });

        modal.addEventListener("click", function (e) {
            if (e.target === modal) close();
        });
        modal.querySelectorAll("[data-cb-enquiry-close]").forEach(function (btn) {
            btn.addEventListener("click", close);
        });
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") close();
        });
        window.addEventListener("resize", function () {
            if (!isMobile()) close();
        });
    }

    /* ---- Brochure/PDF links open in a new tab ---- */
    function initExternalLinks(root) {
        root.querySelectorAll('a[href]').forEach(function (link) {
            var href = link.getAttribute("href") || "";
            if (/\.pdf(\?|#|$)/i.test(href) || /^https?:\/\//i.test(href)) {
                link.setAttribute("target", "_blank");
                link.setAttribute("rel", "noopener");
            }
        });
    }

    /* ---- Cambridge pathway stage tabs ---- */
    function initPathwayTabs(root) {
        var tabs = root.querySelectorAll("[data-stage-tab]");
        var panels = root.querySelectorAll("[data-stage-panel]");
        if (!tabs.length || !panels.length) return;

        tabs.forEach(function (tab) {
            tab.addEventListener("click", function () {
                var stage = tab.getAttribute("data-stage-tab");
                tabs.forEach(function (item) {
                    var active = item === tab;
                    item.classList.toggle("active", active);
                    item.setAttribute("aria-selected", String(active));
                });
                panels.forEach(function (panel) {
                    var active = panel.getAttribute("data-stage-panel") === stage;
                    panel.hidden = !active;
                    panel.classList.toggle("active", active);
                });
            });
        });
    }

    /* ---- Campus image carousel ---- */
    function initCampusCarousel(root) {
        var wrap = root.querySelector(".campus-carousel");
        if (!wrap) return;
        var track = wrap.querySelector(".campus-track");
        var dotsWrap = wrap.querySelector(".carousel-dots");
        var prev = wrap.querySelector(".carousel-arrow.prev");
        var next = wrap.querySelector(".carousel-arrow.next");
        var slides = Array.prototype.slice.call(wrap.querySelectorAll(".campus-slide"));
        if (!track || !slides.length) return;

        var index = Math.min(2, slides.length - 1);
        var dots = [];

        if (dotsWrap) {
            slides.forEach(function (_, i) {
                var dot = document.createElement("button");
                dot.type = "button";
                dot.setAttribute("aria-label", "Show image " + (i + 1));
                dot.addEventListener("click", function () { setSlide(i); });
                dotsWrap.appendChild(dot);
                dots.push(dot);
            });
        }

        function setSlide(i) {
            index = (i + slides.length) % slides.length;
            slides.forEach(function (slide, n) {
                slide.classList.toggle("is-active", n === index);
            });
            dots.forEach(function (dot, n) {
                dot.classList.toggle("active", n === index);
            });
            if (window.innerWidth <= 900) {
                var slide = slides[index];
                track.scrollTo({
                    left: slide.offsetLeft - track.clientWidth / 2 + slide.offsetWidth / 2,
                    behavior: "smooth"
                });
            }
        }

        if (prev) prev.addEventListener("click", function () { setSlide(index - 1); });
        if (next) next.addEventListener("click", function () { setSlide(index + 1); });
        setSlide(index);
    }

    /* ---- YouTube reel facades (click play -> nocookie embed) ---- */
    function initReelVideos(root) {
        root.querySelectorAll(".reel-media[data-cb-yt]").forEach(function (media) {
            var button = media.querySelector(".reel-play");
            if (!button) return;
            button.addEventListener("click", function () {
                var id = media.getAttribute("data-cb-yt");
                if (!id) return;
                var iframe = document.createElement("iframe");
                iframe.src = "https://www.youtube-nocookie.com/embed/" + encodeURIComponent(id) + "?autoplay=1&rel=0";
                iframe.setAttribute("allow", "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share");
                iframe.setAttribute("allowfullscreen", "");
                iframe.setAttribute("referrerpolicy", "strict-origin-when-cross-origin");
                iframe.setAttribute("title", "Video");
                media.innerHTML = "";
                media.appendChild(iframe);
            });
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init, { once: true });
    } else {
        init();
    }
})();
